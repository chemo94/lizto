<?php

namespace App\Services;

use App\Models\PosInvoiceSeries;
use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\Seller;
use Greenter\See;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Company;
use Greenter\Model\Company\Address;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\Note;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\Document;
use Greenter\Model\Despatch\Despatch;
use Greenter\Model\Voided\Voided;
use Greenter\Model\Voided\VoidedDetail;
use Greenter\Ws\Services\SunatEndpoints;
use Illuminate\Support\Facades\Storage;

use App\Models\SellerCompany;
use App\Models\SunatInvoice;
class SunatService
{
    private $see;
    private $seller;
    private $companyModel;
    private $company;
    private $solUser;
    private $solPass;
    private $docNumber;
    private $envName;
    private $department;
    private $province;
    private $district;
    private $certPathRaw;
    private $certPassRaw;

    public function __construct($entity)
    {
        if ($entity instanceof SellerCompany) {
            $this->companyModel = $entity;
            $this->seller = $entity->seller;
            $this->docNumber = $entity->document_number;
            $businessName = $entity->business_name;
            $tradeName = $entity->trade_name;
            $ubigeo = $entity->ubigeo;
            $address = $entity->address;
            $this->solUser = $entity->sunat_sol_user;
            $this->solPass = $entity->sunat_sol_pass;
            $this->envName = $entity->sunat_env;
            $this->certPathRaw = $entity->sunat_cert_path;
            $this->certPassRaw = $entity->sunat_cert_pass;
            $this->department = $entity->department;
            $this->province = $entity->province;
            $this->district = $entity->district;
        } else {
            $this->seller = $entity;
            $this->docNumber = $entity->document_number;
            $businessName = $entity->business_name;
            $tradeName = $entity->trade_name;
            $ubigeo = $entity->ubigeo;
            $address = $entity->address;
            $this->solUser = $entity->sunat_sol_user;
            $this->solPass = $entity->sunat_sol_pass;
            $this->envName = $entity->sunat_env;
            $this->certPathRaw = $entity->sunat_cert_path;
            $this->certPassRaw = $entity->sunat_cert_pass;
            $this->department = $entity->department ?? null;
            $this->province = $entity->province ?? null;
            $this->district = $entity->district ?? null;
        }

        $this->company = (new Company())
            ->setRuc($this->docNumber)
            ->setRazonSocial($businessName ?? $this->seller->name)
            ->setNombreComercial($tradeName ?? $this->seller->name)
            ->setAddress((new Address())
                ->setUbigueo($ubigeo ?? '150101')
                ->setDepartamento($this->department ?? 'LIMA')
                ->setProvincia($this->province ?? 'LIMA')
                ->setDistrito($this->district ?? 'LIMA')
                ->setDireccion($address ?? ''));
    }

    private function initSee()
    {
        if ($this->see) {
            return;
        }

        $certPathRaw = $this->certPathRaw;
        $certPassRaw = $this->certPassRaw;

        if (!$certPathRaw || !$certPassRaw) {
            throw new \Exception('Certificado digital no configurado');
        }

        $certPath = Storage::path($certPathRaw);
        $env = $this->envName === 'production' ? SunatEndpoints::FE_PRODUCCION : SunatEndpoints::FE_BETA;

        if (!file_exists($certPath)) {
            throw new \Exception('Archivo de certificado no encontrado: ' . $certPath);
        }

        $certContent = file_get_contents($certPath);
        if ($certContent === false || strlen($certContent) === 0) {
            throw new \Exception('El archivo de certificado está vacío: ' . $certPath);
        }

        $certLower = strtolower($certPath);
        $isPfx = str_ends_with($certLower, '.pfx') || str_ends_with($certLower, '.p12');
        $pemContent = null;

        if (!$isPfx && !str_starts_with(trim($certContent), '-----BEGIN')) {
            $isPfx = true;
        }

        if ($isPfx) {
            $certs = [];
            $pfxRead = @openssl_pkcs12_read($certContent, $certs, $certPassRaw);

            if (!$pfxRead) {
                $tempPfx = tempnam(sys_get_temp_dir(), 'sunat_') . '.pfx';
                $tempPem = tempnam(sys_get_temp_dir(), 'sunat_') . '.pem';
                file_put_contents($tempPfx, $certContent);

                $passArg = escapeshellarg($certPassRaw);
                $inArg = escapeshellarg($tempPfx);
                $outArg = escapeshellarg($tempPem);

                $cmd = "openssl pkcs12 -in {$inArg} -out {$outArg} -nodes -passin pass:{$passArg} -provider legacy -provider default 2>&1";
                exec($cmd, $output, $returnCode);

                if ($returnCode !== 0) {
                    $cmd = "openssl pkcs12 -in {$inArg} -out {$outArg} -nodes -legacy -passin pass:{$passArg} 2>&1";
                    exec($cmd, $output, $returnCode);
                }
                $outputStr = implode("\n", $output);

                if ($returnCode === 0 && file_exists($tempPem) && filesize($tempPem) > 0) {
                    $pemContent = file_get_contents($tempPem);
                }

                @unlink($tempPfx);
                @unlink($tempPem);

                if (!$pemContent) {
                    throw new \Exception('No se pudo leer el certificado .pfx. OpenSSL: ' . $outputStr);
                }
            } else {
                $pemContent = $certs['pkey'] . "\n" . $certs['cert'];
                if (isset($certs['cacert'])) {
                    $pemContent .= "\n" . $certs['cacert'];
                }
            }
        } else {
            $pemContent = trim($certContent);
        }

        $hasPrivateKey = (bool) preg_match('/-----BEGIN.*PRIVATE KEY-----/', $pemContent);
        $hasCert = (bool) preg_match('/-----BEGIN CERTIFICATE-----/', $pemContent);

        if (!$hasPrivateKey) {
            throw new \Exception('El certificado no contiene una private key válida. Formato detectado: ' . substr($pemContent, 0, 100));
        }

        if (str_contains($pemContent, 'ENCRYPTED PRIVATE KEY')) {
            $keyRes = openssl_pkey_get_private($pemContent, $certPassRaw);
            if ($keyRes) {
                $unencKey = '';
                openssl_pkey_export($keyRes, $unencKey, $certPassRaw);
                openssl_pkey_free($keyRes);
                if ($unencKey) {
                    $certBody = '';
                    if (preg_match('/-----BEGIN CERTIFICATE-----.*-----END CERTIFICATE-----/s', $pemContent, $cm)) {
                        $certBody = $cm[0];
                    }
                    $pemContent = trim($unencKey) . ($certBody ? "\n" . $certBody : '');
                }
            }
        }

        $this->see = new See();
        $this->see->setCertificate($pemContent);
        
        $username = $this->solUser;
        if (!empty($username) && !empty($this->docNumber)) {
            $cleanedUser = strtoupper(trim($username));
            if (str_starts_with($cleanedUser, $this->docNumber)) {
                $cleanedUser = substr($cleanedUser, strlen($this->docNumber));
            }
            $username = $this->docNumber . $cleanedUser;
        }
        
        $this->see->setCredentials($username, $this->solPass);
        $this->see->setService($env);
    }

    public function sendInvoice(PosOrder $order, PosInvoiceSeries $series, array $clientData, ?int $correlativo = null, bool $incrementCorrelativo = true, ?SunatInvoice $existingInvoice = null)
    {
        $correlativo = $correlativo ?? (int) $series->current_number;
        $isElectronic = $series->invoiceType->is_electronic ?? false;
        $detailMode = $existingInvoice?->detail_mode ?? request('detail_mode') ?? 'detailed';
        $consumptionDescription = $existingInvoice?->consumption_description ?: (request('consumption_description') ?: 'Consumo');
        
        if (!$isElectronic || $this->getTipoDoc($series) === 'NV') {
            // Nota de Venta / Comprobante Interno: emitir localmente sin enviar a SUNAT
            $data = [
                'seller_id'       => $this->seller->id,
                'seller_company_id'=> $this->companyModel?->id,
                'pos_order_id'    => $order->id,
                'tipo_doc'        => 'NV',
                'serie'           => $series->series,
                'correlativo'     => $correlativo,
                'cliente_tipo_doc'=> $clientData['tipo_doc'] ?? '6',
                'cliente_num_doc' => $clientData['num_doc'] ?? '-',
                'cliente_nombre'  => $clientData['nombre'] ?? 'CLIENTE VARIOS',
                'detail_mode'     => $detailMode,
                'consumption_description' => $detailMode === 'consumption' ? $consumptionDescription : null,
                'total_gravada'   => round($order->total / 1.18, 2),
                'total_exonerada' => 0,
                'total_inafecta'  => 0,
                'total_igv'       => round($order->total - ($order->total / 1.18), 2),
                'total'           => $order->total,
                'hash'            => null,
                'cdr_status'      => SunatInvoice::STATUS_ACCEPTED,
                'cdr_response'    => json_encode(['code' => '0', 'desc' => 'Nota de Venta Emitida Localmente']),
                'xml_content'     => null,
                'sunat_response'  => 'OK',
                'errors'          => null,
                'fecha_emision'   => $existingInvoice ? $existingInvoice->fecha_emision : now(),
            ];

            if ($existingInvoice) {
                $existingInvoice->update($data);
                $sunatInvoice = $existingInvoice->fresh();
            } else {
                $sunatInvoice = SunatInvoice::create($data);
            }

            $order->update([
                'invoice_type_id'   => $series->invoiceType->id,
                'invoice_series'    => $series->series,
                'invoice_number'    => str_pad($correlativo, 8, '0', STR_PAD_LEFT),
                'invoice_type_code' => 'NV',
                'customer_doc'      => $clientData['num_doc'] ?? null,
                'customer_doc_type' => $clientData['tipo_doc'] ?? null,
            ]);

            if ($incrementCorrelativo) {
                $series->increment('current_number');
            }

            return $sunatInvoice;
        }

        $this->initSee();

        $tipoDocCliente = $clientData['tipo_doc'] ?? '6';
        $numDocCliente = $clientData['num_doc'] ?? $clientData['numeroDocumento'] ?? '-';
        if ($tipoDocCliente === '1') {
            $numDocCliente = preg_replace('/[^0-9]/', '', $numDocCliente);
            if (strlen($numDocCliente) !== 8 || $numDocCliente === '0') {
                $numDocCliente = '00000000';
            }
        }

        $client = (new Client())
            ->setTipoDoc($tipoDocCliente)
            ->setNumDoc($numDocCliente)
            ->setRznSocial($clientData['nombre'] ?? $clientData['razonSocial'] ?? 'CLIENTE VARIOS');

        $fechaEmision = now();
        if ($existingInvoice && $existingInvoice->fecha_emision) {
            $fechaEmision = $existingInvoice->fecha_emision instanceof \DateTime
                ? $existingInvoice->fecha_emision
                : new \DateTime($existingInvoice->fecha_emision->format('Y-m-d H:i:s'));
        }

        $invoice = (new Invoice())
            ->setUblVersion('2.1')
            ->setTipoOperacion('0101')
            ->setCompany($this->company)
            ->setClient($client)
            ->setTipoDoc($this->getTipoDoc($series))
            ->setSerie($series->series)
            ->setCorrelativo($correlativo)
            ->setFechaEmision($fechaEmision)
            ->setFormaPago(new FormaPagoContado())
            ->setTipoMoneda('PEN');

        // Agregar items
        $items = [];
        $hasExonerado = false;
        $totalGravada = 0;
        $totalExonerada = 0;
        $totalInafecta = 0;
        $totalIgv = 0;
        $totalValorVenta = 0;

        // SUNAT needs one line for each tax treatment. In consumption mode we
        // consolidate all products under a single line using the tax regime
        // configured in /seller/invoicing (default_tax_type), IGV by default.
        $sourceItems = $order->items;
        if ($detailMode === 'consumption') {
            $consumptionTaxType = $this->companyModel?->default_tax_type ?: 'gravado';
            $orderTotal = (float) ($order->total > 0 ? $order->total : $order->items->sum('total_price'));
            $sourceItems = collect([
                (object) [
                    'product' => null,
                    'tax_type' => $consumptionTaxType,
                    'unit_price' => $orderTotal,
                    'quantity' => 1,
                    'product_name' => $consumptionDescription,
                ],
            ]);
        }

        foreach ($sourceItems as $item) {
            $product = $item->product;
            $taxType = $item->tax_type ?? ($product ? $product->tax_type : 'gravado');
            
            $unitPrice = (float) $item->unit_price;
            $quantity = (float) $item->quantity;
            
            if ($taxType === 'exonerado') {
                $hasExonerado = true;
                $lineTotal = round($unitPrice * $quantity, 2);
                $mtoValorVenta = $lineTotal;
                $mtoBaseIgv = $mtoValorVenta;
                $porcentajeIgv = 0;
                $igv = 0;
                $mtoValorUnitario = round($unitPrice, 6);
                $mtoPrecioUnitario = round($unitPrice, 6);
                
                $totalExonerada += $mtoValorVenta;
            } elseif ($taxType === 'inafecto') {
                $lineTotal = round($unitPrice * $quantity, 2);
                $mtoValorVenta = $lineTotal;
                $mtoBaseIgv = $mtoValorVenta;
                $porcentajeIgv = 0;
                $igv = 0;
                $mtoValorUnitario = round($unitPrice, 6);
                $mtoPrecioUnitario = round($unitPrice, 6);
                
                $totalInafecta += $mtoValorVenta;
            } else { // gravado
                $lineTotal = round($unitPrice * $quantity, 2);
                $mtoValorVenta = round($lineTotal / 1.18, 2);
                $mtoBaseIgv = $mtoValorVenta;
                $porcentajeIgv = 18;
                $igv = round($mtoValorVenta * 0.18, 2);
                
                // Adjust for rounding discrepancies
                $lineSum = round($mtoValorVenta + $igv, 2);
                if ($lineSum !== $lineTotal) {
                    $diff = round($lineTotal - $lineSum, 2);
                    $igv = round($igv + $diff, 2);
                }
                
                $mtoValorUnitario = round($mtoValorVenta / $quantity, 6);
                $mtoPrecioUnitario = round($unitPrice, 6);
                
                $totalGravada += $mtoValorVenta;
                $totalIgv += $igv;
            }
            
            $totalValorVenta += $mtoValorVenta;

            $items[] = (new \Greenter\Model\Sale\SaleDetail())
                ->setUnidad($detailMode === 'consumption' ? 'NIU' : $this->resolveUnitCode($item))
                ->setCantidad($quantity)
                ->setDescripcion(substr($item->product_name, 0, 250))
                ->setMtoValorUnitario($mtoValorUnitario)
                ->setMtoValorVenta($mtoValorVenta)
                ->setMtoBaseIgv($mtoBaseIgv)
                ->setPorcentajeIgv($porcentajeIgv)
                ->setIgv($igv)
                ->setTotalImpuestos($igv)
                ->setTipAfeIgv($taxType === 'exonerado' ? '20' : ($taxType === 'inafecto' ? '30' : '10'))
                ->setMtoPrecioUnitario($mtoPrecioUnitario)
                ->setCodProdSunat($detailMode === 'consumption' ? null : $this->resolveSunatProductCode($item));
        }

        if ($totalGravada > 0) {
            $invoice->setMtoOperGravadas(round($totalGravada, 2));
        }
        if ($totalExonerada > 0) {
            $invoice->setMtoOperExoneradas(round($totalExonerada, 2));
        }
        if ($totalInafecta > 0) {
            $invoice->setMtoOperInafectas(round($totalInafecta, 2));
        }

        $invoice->setMtoIGV(round($totalIgv, 2))
            ->setTotalImpuestos(round($totalIgv, 2))
            ->setValorVenta(round($totalValorVenta, 2))
            ->setSubTotal($order->total)
            ->setMtoImpVenta($order->total)
            ->setDetails($items);

        // Leyenda
        $tipoDocNombre = $this->getTipoDoc($series) === '01' ? 'FACTURA' : 'BOLETA';
        $legends = [];
        $legends[] = (new Legend())->setCode('1000')->setValue(($order->total < 700 ? 'SON ' : '') . $this->numeroALetras($order->total) . ' SOLES');
        
        if ($hasExonerado) {
            $legends[] = (new Legend())->setCode('2001')->setValue('BIENES TRANSFERIDOS EN LA AMAZONIA REGION SELVA PARA SER CONSUMIDOS EN LA MISMA');
            $legends[] = (new Legend())->setCode('2002')->setValue('SERVICIOS PRESTADOS EN LA AMAZONIA REGION SELVA PARA SER CONSUMIDOS EN LA MISMA');
        }
        $invoice->setLegends($legends);

        $result = $this->see->send($invoice);

        $cdr = null;
        $status = SunatInvoice::STATUS_ERROR;
        $errors = [];

        if ($result->isSuccess()) {
            $cdr = $result->getCdrResponse();
            $status = $cdr->getCode() === '0' ? SunatInvoice::STATUS_ACCEPTED : SunatInvoice::STATUS_REJECTED;
        } else {
            $err = $result->getError();
            $errors[] = [
                'code' => $err->getCode(),
                'message' => $err->getMessage()
            ];
        }

        $xmlContent = $this->see->getFactory()->getLastXml();
        $hash = null;
        if (!empty($xmlContent)) {
            if (preg_match('/<ds:DigestValue>([^<]+)<\/ds:DigestValue>/i', $xmlContent, $matches)) {
                $hash = trim($matches[1]);
            } elseif (preg_match('/<DigestValue>([^<]+)<\/DigestValue>/i', $xmlContent, $matches)) {
                $hash = trim($matches[1]);
            }
        }
        if (empty($hash)) {
            $hash = $invoice->getName();
        }

        // Guardar o actualizar en BD
        $data = [
            'seller_id'       => $this->seller->id,
            'seller_company_id'=> $this->companyModel?->id,
            'pos_order_id'    => $order->id,
            'tipo_doc'        => $this->getTipoDoc($series),
            'serie'           => $series->series,
            'correlativo'     => $correlativo,
            'cliente_tipo_doc'=> $clientData['tipo_doc'] ?? '6',
            'cliente_num_doc' => $clientData['num_doc'] ?? '-',
            'cliente_nombre'  => $clientData['nombre'] ?? 'CLIENTE VARIOS',
            'detail_mode'     => $detailMode,
            'consumption_description' => $detailMode === 'consumption' ? $consumptionDescription : null,
            'total_gravada'   => round($totalGravada, 2),
            'total_exonerada' => round($totalExonerada, 2),
            'total_inafecta'  => round($totalInafecta, 2),
            'total_igv'       => round($totalIgv, 2),
            'total'           => $order->total,
            'hash'            => $hash ?? null,
            'cdr_status'      => $status,
            'cdr_response'    => $cdr ? json_encode([
                'code'        => $cdr->getCode(),
                'description' => $cdr->getDescription(),
                'notes'       => $cdr->getNotes() ?? [],
                'reference'   => $cdr->getReference()
            ], JSON_UNESCAPED_UNICODE) : null,
            'xml_content'     => $xmlContent,
            'sunat_response'  => $cdr ? json_encode(['code' => $cdr->getCode()]) : 'ERROR',
            'errors'          => !empty($errors) ? json_encode($errors, JSON_UNESCAPED_UNICODE) : null,
            'fecha_emision'   => $existingInvoice ? $existingInvoice->fecha_emision : now(),
        ];

        if ($existingInvoice) {
            $existingInvoice->update($data);
            $sunatInvoice = $existingInvoice->fresh();
        } else {
            $sunatInvoice = SunatInvoice::create($data);
        }

        // Guardar tipo de comprobante y datos de cliente en la orden
        $order->update([
            'invoice_type_code' => $this->getTipoDoc($series),
            'customer_doc'      => $clientData['num_doc'] ?? null,
            'customer_doc_type' => $clientData['tipo_doc'] ?? null,
        ]);

        // Incrementar correlativo (solo si no fue pre-asignado)
        if ($incrementCorrelativo) {
            $series->increment('current_number');
        }

        return $sunatInvoice;
    }

    public function sendNote(PosOrder $order, PosInvoiceSeries $series, string $tipoNota, string $descripcion, ?SunatInvoice $existingInvoice = null, ?string $affectedTipoDoc = null)
    {
        $this->initSee();

        $correlativo = $existingInvoice ? (int) $existingInvoice->correlativo : (int) $series->current_number;
        $tipoDoc = $existingInvoice?->tipo_doc ?: $series->invoiceType->code;
        $tipoNota = $existingInvoice?->note_motivo ?: $tipoNota;
        $descripcion = $existingInvoice?->note_description ?: $descripcion;

        // El tipo de documento afectado debe ser el del comprobante original
        // (01=Factura, 03=Boleta), nunca el de la propia nota (07/08).
        $affectedTipoDoc = $existingInvoice?->note_affected_type ?: $affectedTipoDoc;
        if (!$affectedTipoDoc && in_array($order->invoice_type_code, ['01', '03'])) {
            $affectedTipoDoc = $order->invoice_type_code;
        }
        if (!$affectedTipoDoc) {
            $query = \App\Models\SunatInvoice::where('pos_order_id', $order->id)
                ->whereIn('tipo_doc', ['01', '03']);
            if ($existingInvoice) {
                $query->where('id', '!=', $existingInvoice->id);
            }
            $affectedTipoDoc = $query->orderByDesc('id')->value('tipo_doc');
        }
        $affectedTipoDoc = $affectedTipoDoc ?: '03';
        if (in_array($order->invoice_type_code, ['07', '08']) && $order->invoice_type_code !== $affectedTipoDoc) {
            $order->update(['invoice_type_code' => $affectedTipoDoc]);
        }

        $tipoDocCliente = $order->customer_doc_type ?? '6';
        $numDocCliente = $order->customer_doc ?? '0';
        if ($tipoDocCliente === '1') {
            $numDocCliente = preg_replace('/[^0-9]/', '', $numDocCliente);
            if (strlen($numDocCliente) !== 8 || $numDocCliente === '0') {
                $numDocCliente = '00000000';
            }
        }

        $client = (new Client())
            ->setTipoDoc($tipoDocCliente)
            ->setNumDoc($numDocCliente)
            ->setRznSocial($order->customer_name ?? 'CLIENTE VARIOS');

        // Agregar items
        $items = [];
        $hasExonerado = false;
        $totalGravada = 0;
        $totalExonerada = 0;
        $totalInafecta = 0;
        $totalIgv = 0;
        $totalValorVenta = 0;

        foreach ($order->items as $item) {
            $product = $item->product;
            $taxType = $product ? $product->tax_type : 'gravado';
            
            $unitPrice = (float) $item->unit_price;
            $quantity = (float) $item->quantity;
            
            if ($taxType === 'exonerado') {
                $hasExonerado = true;
                $lineTotal = round($unitPrice * $quantity, 2);
                $mtoValorVenta = $lineTotal;
                $mtoBaseIgv = $mtoValorVenta;
                $porcentajeIgv = 0;
                $igv = 0;
                $mtoValorUnitario = round($unitPrice, 6);
                $mtoPrecioUnitario = round($unitPrice, 6);
                
                $totalExonerada += $mtoValorVenta;
            } elseif ($taxType === 'inafecto') {
                $lineTotal = round($unitPrice * $quantity, 2);
                $mtoValorVenta = $lineTotal;
                $mtoBaseIgv = $mtoValorVenta;
                $porcentajeIgv = 0;
                $igv = 0;
                $mtoValorUnitario = round($unitPrice, 6);
                $mtoPrecioUnitario = round($unitPrice, 6);
                
                $totalInafecta += $mtoValorVenta;
            } else { // gravado
                $lineTotal = round($unitPrice * $quantity, 2);
                $mtoValorVenta = round($lineTotal / 1.18, 2);
                $mtoBaseIgv = $mtoValorVenta;
                $porcentajeIgv = 18;
                $igv = round($mtoValorVenta * 0.18, 2);
                
                // Adjust for rounding discrepancies
                $lineSum = round($mtoValorVenta + $igv, 2);
                if ($lineSum !== $lineTotal) {
                    $diff = round($lineTotal - $lineSum, 2);
                    $igv = round($igv + $diff, 2);
                }
                
                $mtoValorUnitario = round($mtoValorVenta / $quantity, 6);
                $mtoPrecioUnitario = round($unitPrice, 6);
                
                $totalGravada += $mtoValorVenta;
                $totalIgv += $igv;
            }
            
            $totalValorVenta += $mtoValorVenta;

            $items[] = (new \Greenter\Model\Sale\SaleDetail())
                ->setUnidad($this->resolveUnitCode($item))
                ->setCantidad($quantity)
                ->setDescripcion(substr($item->product_name, 0, 250))
                ->setMtoValorUnitario($mtoValorUnitario)
                ->setMtoValorVenta($mtoValorVenta)
                ->setMtoBaseIgv($mtoBaseIgv)
                ->setPorcentajeIgv($porcentajeIgv)
                ->setIgv($igv)
                ->setTotalImpuestos($igv)
                ->setTipAfeIgv($taxType === 'exonerado' ? '20' : ($taxType === 'inafecto' ? '30' : '10'))
                ->setMtoPrecioUnitario($mtoPrecioUnitario)
                ->setCodProdSunat($this->resolveSunatProductCode($item));
        }

        $note = (new Note())
            ->setUblVersion('2.1')
            ->setCompany($this->company)
            ->setClient($client)
            ->setTipoDoc($tipoDoc) // 07=NC, 08=ND
            ->setSerie($series->series)
            ->setCorrelativo($correlativo)
            ->setFechaEmision(now())
            ->setFormaPago(new FormaPagoContado())
            ->setTipoMoneda('PEN')
            ->setCodMotivo($tipoNota) // 01=Anulacion, etc.
            ->setDesMotivo($descripcion)
            ->setDetails($items);

        if ($totalGravada > 0) {
            $note->setMtoOperGravadas(round($totalGravada, 2));
        }
        if ($totalExonerada > 0) {
            $note->setMtoOperExoneradas(round($totalExonerada, 2));
        }
        if ($totalInafecta > 0) {
            $note->setMtoOperInafectas(round($totalInafecta, 2));
        }

        $note->setMtoIGV(round($totalIgv, 2))
            ->setTotalImpuestos(round($totalIgv, 2))
            ->setMtoImpVenta($order->total);

        // Referencia al comprobante original
        $note->setTipDocAfectado($affectedTipoDoc);
        $note->setNumDocfectado($order->invoice_series . '-' . $order->invoice_number);

        $legends = [];
        $legends[] = (new Legend())->setCode('1000')->setValue(($order->total < 700 ? 'SON ' : '') . $this->numeroALetras($order->total) . ' SOLES');
        if ($hasExonerado) {
            $legends[] = (new Legend())->setCode('2001')->setValue('BIENES TRANSFERIDOS EN LA AMAZONIA REGION SELVA PARA SER CONSUMIDOS EN LA MISMA');
            $legends[] = (new Legend())->setCode('2002')->setValue('SERVICIOS PRESTADOS EN LA AMAZONIA REGION SELVA PARA SER CONSUMIDOS EN LA MISMA');
        }
        $note->setLegends($legends);

        $result = $this->see->send($note);

        $cdr = null;
        $status = SunatInvoice::STATUS_ERROR;
        $errors = [];

        if ($result->isSuccess()) {
            $cdr = $result->getCdrResponse();
            $status = $cdr->getCode() === '0' ? SunatInvoice::STATUS_ACCEPTED : SunatInvoice::STATUS_REJECTED;
        } else {
            $err = $result->getError();
            $errors[] = [
                'code' => $err->getCode(),
                'message' => $err->getMessage()
            ];
        }

        $xmlContent = $this->see->getFactory()->getLastXml();
        $hash = null;
        if (!empty($xmlContent)) {
            if (preg_match('/<ds:DigestValue>([^<]+)<\/ds:DigestValue>/i', $xmlContent, $matches)) {
                $hash = trim($matches[1]);
            } elseif (preg_match('/<DigestValue>([^<]+)<\/DigestValue>/i', $xmlContent, $matches)) {
                $hash = trim($matches[1]);
            }
        }
        if (empty($hash)) {
            $hash = $note->getName();
        }

        $data = [
            'seller_id'       => $this->seller->id,
            'seller_company_id'=> $this->companyModel?->id,
            'pos_order_id'    => $order->id,
            'tipo_doc'        => $tipoDoc,
            'serie'           => $series->series,
            'correlativo'     => $correlativo,
            'cliente_num_doc' => $order->customer_doc ?? '0',
            'cliente_nombre'  => $order->customer_name ?? 'CLIENTE VARIOS',
            'cliente_tipo_doc'=> $tipoDocCliente,
            'note_motivo'     => $tipoNota,
            'note_description'=> $descripcion,
            'note_affected_type' => $affectedTipoDoc,
            'total_gravada'   => round($totalGravada, 2),
            'total_exonerada' => round($totalExonerada, 2),
            'total_inafecta'  => round($totalInafecta, 2),
            'total_igv'       => round($totalIgv, 2),
            'total'           => $order->total,
            'moneda'          => 'PEN',
            'hash'            => $hash,
            'cdr_status'      => $status,
            'cdr_response'    => $cdr ? json_encode([
                'code'        => $cdr->getCode(),
                'description' => $cdr->getDescription(),
                'notes'       => $cdr->getNotes() ?? [],
                'reference'   => $cdr->getReference()
            ], JSON_UNESCAPED_UNICODE) : null,
            'xml_content'     => $xmlContent,
            'sunat_response'  => $cdr ? json_encode(['code' => $cdr->getCode()]) : 'ERROR',
            'errors'          => !empty($errors) ? json_encode($errors, JSON_UNESCAPED_UNICODE) : null,
            'fecha_emision'   => now(),
        ];

        if ($existingInvoice) {
            $existingInvoice->update($data);
            $sunatInvoice = $existingInvoice;
        } else {
            $sunatInvoice = \App\Models\SunatInvoice::create($data);
            $series->increment('current_number');
        }
        return $sunatInvoice;
    }

    /**
     * Comunicación de Baja (RA) para anular facturas y notas de crédito/débito.
     * SUNAT exige RA (no Nota de Crédito) para dar de baja documentos tipo 01, 07 y 08.
     */
    public function sendVoided(?PosOrder $order, string $tipoDoc, string $serie, int $correlativo, string $motivo, ?SunatInvoice $existingInvoice = null, ?string $docFechaEmision = null)
    {
        $this->initSee();

        // fecGeneracion = fecha de emisión del comprobante original (SUNAT exige coincidencia)
        $fecGeneracion = $docFechaEmision ? new \DateTime($docFechaEmision) : now();
        $fecComunicacion = now();
        $raSerie = 'RA-' . $fecComunicacion->format('Ymd');

        if ($existingInvoice) {
            $raCorrelativo = (int) $existingInvoice->correlativo;
        } else {
            $raCorrelativo = ((int) \App\Models\SunatInvoice::where('tipo_doc', 'RA')
                ->where('serie', $raSerie)
                ->max('correlativo')) + 1;
        }

        $detail = (new VoidedDetail())
            ->setTipoDoc($tipoDoc)
            ->setSerie($serie)
            ->setCorrelativo(str_pad((string) $correlativo, 8, '0', STR_PAD_LEFT))
            ->setDesMotivoBaja(substr($motivo, 0, 500));

        $voided = (new Voided())
            ->setCorrelativo(str_pad((string) $raCorrelativo, 6, '0', STR_PAD_LEFT))
            ->setFecGeneracion($fecGeneracion)
            ->setFecComunicacion($fecComunicacion)
            ->setCompany($this->company)
            ->setDetails([$detail]);

        $errors = [];
        $ticket = null;
        $result = $this->see->send($voided);
        if ($result->isSuccess()) {
            $ticket = $result->getTicket();
        } else {
            $err = $result->getError();
            $errors[] = ['code' => $err->getCode(), 'message' => $err->getMessage()];
        }

        $cdr = null;
        $status = SunatInvoice::STATUS_PENDING;
        if ($ticket) {
            for ($i = 0; $i < 5; $i++) {
                sleep(2);
                $res = $this->see->getStatus($ticket);
                if ($res->isSuccess()) {
                    $cdr = $res->getCdrResponse();
                    $status = $cdr->getCode() === '0' ? SunatInvoice::STATUS_ACCEPTED : SunatInvoice::STATUS_REJECTED;
                    break;
                }
            }
        }

        $xmlContent = $this->see->getFactory()->getLastXml();
        $hash = null;
        if (!empty($xmlContent)) {
            if (preg_match('/<ds:DigestValue>([^<]+)<\/ds:DigestValue>/i', $xmlContent, $matches)) {
                $hash = trim($matches[1]);
            } elseif (preg_match('/<DigestValue>([^<]+)<\/DigestValue>/i', $xmlContent, $matches)) {
                $hash = trim($matches[1]);
            }
        }

        $data = [
            'seller_id'        => $this->seller->id,
            'seller_company_id'=> $this->companyModel?->id,
            'pos_order_id'     => $order?->id,
            'tipo_doc'         => 'RA',
            'serie'            => $raSerie,
            'correlativo'      => $raCorrelativo,
            'cliente_num_doc'  => $order?->customer_doc ?? '0',
            'cliente_nombre'   => $order?->customer_name ?? 'CLIENTE VARIOS',
            'total'            => 0,
            'hash'             => $hash,
            'cdr_status'       => $status,
            'cdr_response'     => $cdr ? json_encode([
                'code'        => $cdr->getCode(),
                'description' => $cdr->getDescription(),
                'notes'       => $cdr->getNotes() ?? [],
                'reference'   => $cdr->getReference(),
            ], JSON_UNESCAPED_UNICODE) : null,
            'xml_content'      => $xmlContent,
            'sunat_response'   => $ticket ? json_encode(['ticket' => $ticket]) : 'ERROR',
            'ticket'           => $ticket,
            'errors'           => !empty($errors) ? json_encode($errors, JSON_UNESCAPED_UNICODE) : null,
            'fecha_emision'    => now(),
        ];

        if ($existingInvoice) {
            $existingInvoice->update($data);
            return $existingInvoice;
        }
        return \App\Models\SunatInvoice::create($data);
    }

    public function getStatus(string $ticket)
    {
        return $this->see->getStatus($ticket);
    }    public function getCdrResult(string $tipoDoc, string $serie, int $correlativo): ?array
    {
        $ruc = $this->docNumber;

        if (!$this->solUser || !$this->solPass) {
            throw new \Exception('Credenciales SOL no configuradas');
        }

        $wsdl = \Greenter\Ws\Services\SunatEndpoints::FE_CONSULTA_CDR . '?wsdl';
        $client = new \Greenter\Ws\Services\SoapClient($wsdl);
        $client->setCredentials($ruc . $this->solUser, $this->solPass);

        $service = new \Greenter\Ws\Services\ConsultCdrService();
        $service->setClient($client);

        $result = $service->getStatusCdr($ruc, $tipoDoc, $serie, $correlativo);
        $cdr = $result->getCdrResponse();

        if ($cdr === null) {
            return ['status' => 'not_found', 'message' => 'CDR aun no disponible en SUNAT'];
        }

        if ($result->isSuccess()) {
            return [
                'status'        => $cdr->getCode() === '0' ? SunatInvoice::STATUS_ACCEPTED : SunatInvoice::STATUS_REJECTED,
                'code'          => $cdr->getCode(),
                'description'   => $cdr->getDescription(),
                'cdr_zip'       => $result->getCdrZip(),
            ];
        }

        return ['status' => 'error', 'message' => $result->getError()->getMessage() ?? 'Error desconocido'];
    }

    private function getTipoDoc(PosInvoiceSeries $series): string
    {
        return $series->invoiceType->code;
    }

    private function resolveUnitCode($item): string
    {
        $product = $item->product;
        if (!$product) return 'NIU';

        // Packaged: get unit from linked InvItem via InvProductItem
        if ($product->stock_type === 'packaged') {
            $link = \App\Models\InvProductItem::where('product_id', $product->id)->first();
            if ($link && $link->item && $link->item->unit) {
                return strtoupper($link->item->unit);
            }
        }

        // Prepared: get unit from recipe's first ingredient or default to NIU
        if ($product->stock_type === 'prepared') {
            $recipe = \App\Models\InvRecipe::where('product_id', $product->id)->active()->first();
            if ($recipe && $recipe->unit_produced) {
                return strtoupper($recipe->unit_produced);
            }
        }

        return 'NIU';
    }

    private function resolveSunatProductCode($item): ?string
    {
        $product = $item->product;
        if (!$product) return null;

        // Try product's direct sunat_code first!
        if (!empty($product->sunat_code)) {
            return $product->sunat_code;
        }

        // Packaged: get sunat_code from linked InvItem
        if ($product->stock_type === 'packaged') {
            $link = \App\Models\InvProductItem::where('product_id', $product->id)->first();
            if ($link && $link->item && $link->item->sunat_code) {
                return $link->item->sunat_code;
            }
        }

        // Prepared: get from first ingredient's InvItem
        if ($product->stock_type === 'prepared') {
            $recipe = \App\Models\InvRecipe::where('product_id', $product->id)->active()->with('items.item')->first();
            if ($recipe && $recipe->items->first()?->item?->sunat_code) {
                return $recipe->items->first()->item->sunat_code;
            }
        }

        return null;
    }

    private function calcIgv($amount): float
    {
        return round($amount - $amount / 1.18, 2);
    }

    private function calcValorVenta($amount): float
    {
        return round($amount / 1.18, 2);
    }

    private function numeroALetras($num)
    {
        $num = round($num, 2);
        $entero = floor($num);
        $centimos = round(($num - $entero) * 100);

        $unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $centenas = ['', 'CIEN', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        if ($entero == 0) return 'CERO';
        if ($entero < 100) return $this->convertirMenor100($entero, $unidades, $decenas);
        return number_format($entero, 0) . ' CON ' . str_pad($centimos, 2, '0', STR_PAD_LEFT) . '/100';
    }

    private function convertirMenor100($num, $unidades, $decenas)
    {
        if ($num == 0) return '';
        if ($num < 10) return $unidades[$num];
        if ($num < 20) {
            $especiales = ['DIEZ','ONCE','DOCE','TRECE','CATORCE','QUINCE','DIECISÉIS','DIECISIETE','DIECIOCHO','DIECINUEVE'];
            return $especiales[$num - 10];
        }
        if ($num < 100) {
            $d = floor($num / 10);
            $u = $num % 10;
            return $decenas[$d] . ($u > 0 ? ' Y ' . $unidades[$u] : '');
        }
        return number_format($num);
    }
}
