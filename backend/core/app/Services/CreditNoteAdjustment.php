<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;

/** Calculates adjustments from issued XML, with amounts expressed in the original currency. */
class CreditNoteAdjustment
{
    public function lines(string $xml): array
    {
        if (!$xml || preg_match('/<!DOCTYPE|<!ENTITY/i', $xml)) return [];
        $doc = new DOMDocument();
        if (!@$doc->loadXML($xml, LIBXML_NONET)) return [];
        $xp = $this->xpath($doc);
        $rows = [];
        foreach ($xp->query('/*/cac:InvoiceLine | /*/cac:CreditNoteLine') as $line) {
            $v = fn($p) => trim($xp->evaluate('string('.$p.')', $line));
            $rows[$v('cbc:ID')] = ['id'=>$v('cbc:ID'), 'description'=>$v('cac:Item/cbc:Description'),
                'quantity'=>(float)$v('cbc:InvoicedQuantity | cbc:CreditedQuantity'),
                'net'=>(float)$v('cbc:LineExtensionAmount'), 'tax'=>(float)$v('cac:TaxTotal/cbc:TaxAmount')];
        }
        return $rows;
    }

    public function adjust(string $xml, string $reason, array $input): array
    {
        $doc = new DOMDocument(); $doc->loadXML($xml, LIBXML_NONET);
        $xp = $this->xpath($doc); $root = $doc->documentElement;
        $currency = $xp->evaluate('string(/*/cbc:DocumentCurrencyCode)');
        $originalLines = $this->lines($xml);
        $metadata = ['reason'=>$reason, 'lines'=>[]];
        if (in_array($reason, ['01','06'], true)) return ['xml'=>$xml, 'metadata'=>$metadata];
        if ($reason === '02') {
            $ruc = trim((string)($input['correct_ruc'] ?? ''));
            $originalRuc = $xp->evaluate('string(/*/cac:AccountingCustomerParty/cac:Party/cac:PartyIdentification/cbc:ID)');
            if ($xp->evaluate('string(/*/cac:BillingReference/cac:InvoiceDocumentReference/cbc:DocumentTypeCode)') !== '01'
                || !preg_match('/^(10|15|16|17|20)\d{9}$/D', $ruc) || $ruc === $originalRuc) {
                throw new InvalidArgumentException('El motivo 02 requiere una factura y un RUC correcto distinto del original.');
            }
            $sum = 0;
            foreach ([5,4,3,2,7,6,5,4,3,2] as $i=>$weight) $sum += (int)$ruc[$i]*$weight;
            if ((11-($sum%11))%10 !== (int)$ruc[10]) throw new InvalidArgumentException('El dígito verificador del RUC no es válido.');
            $metadata['correct_ruc'] = $ruc;
            // The NC remains addressed to the original recipient; a replacement invoice is separate.
            return ['xml'=>$xml, 'metadata'=>$metadata];
        }
        $nonMonetary = in_array($reason, ['03','13'], true);
        if (!$nonMonetary && $xp->query('/*/cac:AllowanceCharge')->length) {
            throw new InvalidArgumentException('La factura contiene cargos o descuentos globales previos. Requiere conciliar su distribución antes de un ajuste parcial.');
        }
        $selected = $input['lines'] ?? [];
        if (!is_array($selected)) throw new InvalidArgumentException('Detalle del ajuste inválido.');
        foreach ($selected as $id=>$row) {
            if (!isset($originalLines[$id]) || !is_array($row)) throw new InvalidArgumentException('El ítem seleccionado no pertenece al XML original.');
        }
        $allocations = [];
        if ($reason === '04') {
            $total = array_sum(array_column($originalLines, 'net'));
            $amount = $this->number($input['global_amount'] ?? 0);
            if ($amount <= 0 || $amount > $total) throw new InvalidArgumentException('El descuento global sin impuestos debe ser mayor a cero y no superar el valor de venta.');
            $remaining = (int)round($amount*100); $ids = array_keys($originalLines);
            foreach ($ids as $index=>$id) {
                $cents = $index === count($ids)-1 ? $remaining : min($remaining, (int)floor($amount*100*$originalLines[$id]['net']/$total));
                $allocations[$id] = $cents/100; $remaining -= $cents;
            }
        }
        $taxes = []; $netTotal = 0; $taxTotal = 0; $changed = 0;
        foreach (iterator_to_array($xp->query('/*/cac:CreditNoteLine')) as $line) {
            $id = $xp->evaluate('string(cbc:ID)', $line); $old = $originalLines[$id]; $row = $selected[$id] ?? [];
            $quantity = $old['quantity']; $net = 0;
            if ($reason === '03') {
                $description = trim((string)($row['description'] ?? ''));
                if ($description !== '' && $description !== $old['description']) {
                    if (mb_strlen($description) > 500) throw new InvalidArgumentException('La descripción no puede superar 500 caracteres.');
                    $xp->query('cac:Item/cbc:Description', $line)->item(0)->nodeValue = $description;
                    $metadata['lines'][$id] = ['description'=>$description]; $changed++;
                }
            } elseif ($reason !== '13') {
                if ($reason === '07') {
                    $quantity = $this->number($row['quantity'] ?? 0, 6);
                    $availableQty=max(0,$old['quantity']-(float)($input['used_lines'][$id]['quantity'] ?? 0));
                    $availableNet=max(0,$old['net']-(float)($input['used_lines'][$id]['net'] ?? 0));
                    if ($quantity > $availableQty) throw new InvalidArgumentException('La devolución supera la cantidad original del ítem '.$id.'.');
                    $net = $availableQty ? round($availableNet*$quantity/$availableQty, 2) : 0;
                } else {
                    $net = $reason === '04' ? ($allocations[$id] ?? 0) : $this->number($row['amount'] ?? 0);
                }
                if ($net <= 0) { $root->removeChild($line); continue; }
                if ($net > $old['net']) throw new InvalidArgumentException('El ajuste supera el valor original del ítem '.$id.'.');
                if ($reason === '11' && !$xp->query('cac:TaxTotal/cac:TaxSubtotal/cac:TaxCategory/cac:TaxScheme[cbc:ID="9995"]', $line)->length) {
                    throw new InvalidArgumentException('El motivo 11 solo ajusta ítems de exportación.');
                }
                if ($reason === '12' && !$xp->query('cac:TaxTotal/cac:TaxSubtotal/cac:TaxCategory/cac:TaxScheme[cbc:ID="1016"]', $line)->length) {
                    throw new InvalidArgumentException('El motivo 12 solo ajusta ítems afectos al IVAP.');
                }
                $metadata['lines'][$id] = ['net'=>$net, 'quantity'=>$reason === '07' ? $quantity : 0]; $changed++;
            }
            $ratio = $old['net'] > 0 ? $net/$old['net'] : 0;
            $lineTax = 0;
            foreach ($xp->query('cac:TaxTotal/cac:TaxSubtotal', $line) as $subtotal) {
                $scheme = $xp->evaluate('string(cac:TaxCategory/cac:TaxScheme/cbc:ID)', $subtotal);
                $key = $scheme.'|'.$xp->evaluate('string(cac:TaxCategory/cbc:Percent)', $subtotal);
                foreach ($xp->query('cbc:TaxableAmount | cbc:TaxAmount', $subtotal) as $amountNode) {
                    $amountNode->nodeValue = number_format(round((float)$amountNode->textContent*$ratio, 2), 2, '.', '');
                }
                $value = (float)$xp->evaluate('string(cbc:TaxAmount)', $subtotal);
                $lineTax += $value;
                if (!isset($taxes[$key])) $taxes[$key] = ['node'=>$subtotal->cloneNode(true), 'base'=>0, 'tax'=>0];
                $taxes[$key]['base'] += (float)$xp->evaluate('string(cbc:TaxableAmount)', $subtotal);
                $taxes[$key]['tax'] += $value;
            }
            if (!$nonMonetary && !$xp->query('cac:TaxTotal/cac:TaxSubtotal', $line)->length) throw new InvalidArgumentException('El XML no contiene el desglose tributario del ítem '.$id.'.');
            foreach ($xp->query('cac:TaxTotal/cbc:TaxAmount', $line) as $n) $n->nodeValue = number_format($lineTax, 2, '.', '');
            $xp->query('cbc:LineExtensionAmount', $line)->item(0)->nodeValue = number_format($net, 2, '.', '');
            $xp->query('cbc:CreditedQuantity', $line)->item(0)->nodeValue = (string)$quantity;
            foreach ($xp->query('cac:Price/cbc:PriceAmount', $line) as $n) $n->nodeValue = number_format($quantity ? $net/$quantity : 0, 10, '.', '');
            foreach ($xp->query('cac:PricingReference/cac:AlternativeConditionPrice/cbc:PriceAmount', $line) as $n) $n->nodeValue = number_format($quantity ? ($net+$lineTax)/$quantity : 0, 10, '.', '');
            foreach (iterator_to_array($xp->query('cac:AllowanceCharge', $line)) as $n) $line->removeChild($n);
            $netTotal += $net; $taxTotal += $lineTax;
        }
        if ($reason !== '13' && !$changed) throw new InvalidArgumentException('Selecciona al menos un ítem y un ajuste o descripción diferente.');
        foreach (iterator_to_array($xp->query('/*/cac:TaxTotal | /*/cac:AllowanceCharge | /*/cbc:Note[@languageLocaleID="1000"]')) as $n) $root->removeChild($n);
        $totalNode = $xp->query('/*/cac:LegalMonetaryTotal')->item(0);
        $taxNode = $doc->createElementNS(CreditNoteXml::CAC, 'cac:TaxTotal');
        $this->amount($doc, $taxNode, 'TaxAmount', $taxTotal, $currency);
        foreach ($taxes as $tax) {
            $node = $tax['node'];
            foreach ($xp->query('cbc:TaxableAmount', $node) as $n) $n->nodeValue = number_format($tax['base'], 2, '.', '');
            foreach ($xp->query('cbc:TaxAmount', $node) as $n) $n->nodeValue = number_format($tax['tax'], 2, '.', '');
            $taxNode->appendChild($node);
        }
        $root->insertBefore($taxNode, $totalNode);
        while ($totalNode->firstChild) $totalNode->removeChild($totalNode->firstChild);
        $this->amount($doc, $totalNode, 'LineExtensionAmount', $netTotal, $currency);
        $this->amount($doc, $totalNode, 'TaxInclusiveAmount', $netTotal+$taxTotal, $currency);
        $this->amount($doc, $totalNode, 'PayableAmount', $netTotal+$taxTotal, $currency);
        if ($reason === '13') {
            if (empty($input['original_credit'])) throw new InvalidArgumentException('El motivo 13 requiere una factura emitida al crédito.');
            $pending = $this->number($input['pending_amount'] ?? 0);
            $installments = $input['installments'] ?? [];
            if (is_array($installments)) $installments=array_values($installments);
            if ($pending <= 0 || !is_array($installments) || !count($installments) || count($installments)>99) throw new InvalidArgumentException('Indica el saldo pendiente y entre 1 y 99 cuotas.');
            $sum = 0; $metadata['installments'] = [];
            foreach ($installments as $row) {
                $amount = $this->number($row['amount'] ?? 0); $date = (string)($row['date'] ?? '');
                $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
                if ($amount <= 0 || !$parsed || $parsed->format('Y-m-d') !== $date || $date < now('America/Lima')->format('Y-m-d')) throw new InvalidArgumentException('Cada cuota requiere un importe positivo y un vencimiento válido desde hoy.');
                $sum += $amount; $metadata['installments'][] = ['amount'=>$amount,'date'=>$date];
            }
            if (abs($sum-$pending)>0.001) throw new InvalidArgumentException('La suma de cuotas debe coincidir con el monto pendiente.');
            $metadata['pending_amount'] = $pending;
            $rows = array_merge([['id'=>'Credito','amount'=>$pending]], array_map(fn($row,$i)=>array_merge($row,['id'=>'Cuota'.str_pad($i+1,3,'0',STR_PAD_LEFT)]), $metadata['installments'], array_keys($metadata['installments'])));
            foreach ($rows as $row) {
                $term = $doc->createElementNS(CreditNoteXml::CAC, 'cac:PaymentTerms');
                foreach (['ID'=>'FormaPago','PaymentMeansID'=>$row['id']] as $key=>$value) $term->appendChild($doc->createElementNS(CreditNoteXml::CBC,'cbc:'.$key,$value));
                $this->amount($doc,$term,'Amount',$row['amount'],$currency);
                if (isset($row['date'])) $term->appendChild($doc->createElementNS(CreditNoteXml::CBC,'cbc:PaymentDueDate',$row['date']));
                $root->insertBefore($term,$taxNode);
            }
        }
        return ['xml'=>$doc->saveXML(), 'metadata'=>$metadata];
    }

    private function xpath(DOMDocument $doc): DOMXPath
    {
        $xp = new DOMXPath($doc); $xp->registerNamespace('cbc', CreditNoteXml::CBC); $xp->registerNamespace('cac', CreditNoteXml::CAC); return $xp;
    }
    private function number($value, int $precision=2): float
    {
        if (!is_scalar($value) || !preg_match('/^\d+(?:\.\d{1,'.$precision.'})?$/D', (string)$value)) throw new InvalidArgumentException('Importe o cantidad inválida: usa valores positivos con hasta '.$precision.' decimales.');
        return round((float)$value, $precision);
    }
    private function amount(DOMDocument $doc, DOMElement $parent, string $name, float $value, string $currency): void
    {
        $node=$doc->createElementNS(CreditNoteXml::CBC, 'cbc:'.$name, number_format($value,2,'.','')); $node->setAttribute('currencyID',$currency); $parent->appendChild($node);
    }
}
