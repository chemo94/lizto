<?php

namespace App\Services;

use App\Models\SunatInvoice;
use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;

/** Full cancellation: preserve the issued UBL amounts and parties, never today's product data. */
class CreditNoteXml
{
    const CBC = 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';
    const CAC = 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';
    const EXT = 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2';

    public function build(SunatInvoice $original, string $series, int $number, string $reason, string $description, \DateTimeInterface $date, string $ruc): string
    {
        $prefix = match ($original->tipo_doc) { '03' => 'BC', '01' => 'FC', default => throw new InvalidArgumentException('Solo se admiten boletas y facturas.') };
        if (!preg_match('/^'.$prefix.'[A-Z0-9]{2}$/D', $series) || $number < 1 || $number > 99999999) {
            throw new InvalidArgumentException('Serie o correlativo de nota de crédito inválido.');
        }
        if (!array_key_exists($reason, CreditNoteReasons::LABELS) || trim($description) === '' || mb_strlen($description) > 250) {
            throw new InvalidArgumentException('Indica un motivo SUNAT válido y un sustento de hasta 250 caracteres.');
        }
        $xml = (string) $original->xml_content;
        if (!$xml || preg_match('/<!DOCTYPE|<!ENTITY/i', $xml)) throw new InvalidArgumentException('Se necesita el XML original válido para emitir la nota.');
        $source = new DOMDocument();
        $old = libxml_use_internal_errors(true);
        try { $loaded = $source->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS); }
        finally { libxml_clear_errors(); libxml_use_internal_errors($old); }
        if (!$loaded || ($source->documentElement->localName !== 'Invoice' || $source->documentElement->namespaceURI !== 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2')) throw new InvalidArgumentException('El XML original no es una factura/boleta UBL.');
        $xp = new DOMXPath($source);
        $xp->registerNamespace('cbc', self::CBC); $xp->registerNamespace('cac', self::CAC);
        $text = fn ($path) => trim($xp->evaluate('string(/*/'.$path.')'));
        $reference = $text('cbc:ID');
        $parts = explode('-', $reference);
        if (count($parts) !== 2 || $parts[0] !== $original->serie || (int)$parts[1] !== (int)$original->correlativo || $text('cbc:InvoiceTypeCode') !== $original->tipo_doc) {
            throw new InvalidArgumentException('El XML no corresponde al comprobante seleccionado.');
        }
        if ($text('cbc:UBLVersionID') !== '2.1' || $text('cac:AccountingSupplierParty/cac:Party/cac:PartyIdentification/cbc:ID') !== $ruc) {
            throw new InvalidArgumentException('La versión UBL o el RUC emisor del XML no corresponde a la empresa.');
        }
        if ($date->format('Y-m-d') < $text('cbc:IssueDate')) throw new InvalidArgumentException('La nota no puede ser anterior al comprobante.');
        foreach (['cbc:DocumentCurrencyCode','cac:AccountingCustomerParty','cac:AccountingSupplierParty','cac:TaxTotal','cac:LegalMonetaryTotal','cac:InvoiceLine','cac:Signature'] as $required) {
            if (!$xp->query('/*/'.$required)->length) throw new InvalidArgumentException('El XML original está incompleto: '.$required);
        }
        // Do not silently reinterpret advance payments or special retention flows.
        if ($xp->query('/*/cac:PrepaidPayment | /*/cac:WithholdingTaxTotal')->length || (float)$text('cac:LegalMonetaryTotal/cbc:PrepaidAmount') !== 0.0) {
            throw new InvalidArgumentException('Este comprobante tiene anticipos o retenciones y requiere un flujo específico de nota de crédito.');
        }
        if (abs((float)$text('cac:LegalMonetaryTotal/cbc:PayableAmount') - (float)$original->total) > 0.01) throw new InvalidArgumentException('El total registrado no coincide con el XML original.');
        if ($text('cbc:DocumentCurrencyCode') !== $original->moneda) throw new InvalidArgumentException('La moneda registrada no coincide con el XML original.');
        $out = new DOMDocument('1.0', 'UTF-8');
        $root = $out->createElementNS('urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2','CreditNote'); $out->appendChild($root);
        foreach (['cbc'=>self::CBC,'cac'=>self::CAC,'ext'=>self::EXT] as $p=>$ns) $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:'.$p, $ns);
        $add = function (DOMElement $parent, string $name, ?string $value = null) use ($out) {
            $ns = str_starts_with($name,'cbc:') ? self::CBC : (str_starts_with($name,'ext:') ? self::EXT : self::CAC);
            $node=$out->createElementNS($ns,$name); if($value !== null)$node->appendChild($out->createTextNode($value)); $parent->appendChild($node); return $node;
        };
        $copy = function (string $path) use ($xp,$out,$root) { foreach ($xp->query('/*/'.$path) as $node) $root->appendChild($out->importNode($node,true)); };
        $add($add($add($root,'ext:UBLExtensions'),'ext:UBLExtension'),'ext:ExtensionContent');
        $add($root,'cbc:UBLVersionID','2.1'); $add($root,'cbc:CustomizationID','2.0');
        $add($root,'cbc:ID',$series.'-'.$number); $add($root,'cbc:IssueDate',$date->format('Y-m-d')); $add($root,'cbc:IssueTime',$date->format('H:i:s'));
        $copy('cbc:Note'); $copy('cbc:DocumentCurrencyCode');
        $dis=$add($root,'cac:DiscrepancyResponse'); $add($dis,'cbc:ReferenceID',$reference); $add($dis,'cbc:ResponseCode',$reason); $add($dis,'cbc:Description',trim($description));
        $copy('cac:OrderReference');
        $ref=$add($add($root,'cac:BillingReference'),'cac:InvoiceDocumentReference'); $add($ref,'cbc:ID',$reference); $add($ref,'cbc:DocumentTypeCode',$original->tipo_doc);
        foreach (['cac:DespatchDocumentReference','cac:AdditionalDocumentReference','cac:Signature','cac:AccountingSupplierParty','cac:AccountingCustomerParty','cac:AllowanceCharge','cac:TaxTotal','cac:LegalMonetaryTotal'] as $path) $copy($path);
        foreach ($xp->query('/*/cac:InvoiceLine') as $line) {
            $newLine=$add($root,'cac:CreditNoteLine');
            foreach($line->childNodes as $child) {
                if($child instanceof DOMElement && $child->localName === 'InvoicedQuantity') {
                    $q=$add($newLine,'cbc:CreditedQuantity',$child->textContent);
                    foreach($child->attributes as $attribute)$q->setAttribute($attribute->name,$attribute->value);
                } else $newLine->appendChild($out->importNode($child,true));
            }
        }
        return $out->saveXML();
    }
}
