<?php

namespace App\Console\Commands;

use App\Models\PosInvoiceSeries;
use App\Models\SunatInvoice;
use App\Models\PosCustomerProfile;
use App\Services\SunatService;
use Illuminate\Console\Command;

class ProcessSunatInvoices extends Command
{
    protected $signature   = 'sunat:process-invoices';
    protected $description = 'Process pending SUNAT invoices: generate XML, send to SUNAT, query CDR status';

    public function handle()
    {
        $this->info('Starting SUNAT invoice processing...');

        $this->processNewInvoices();
        $this->processPendingCdr();

        $this->info('Finished.');
        return 0;
    }

    private function processNewInvoices()
    {
        $this->info('--- Phase 1: Sending new invoices to SUNAT ---');

        $newInvoices = SunatInvoice::where('cdr_status', 'pending')
            ->whereNull('xml_content')
            ->with('order.items')
            ->get();

        if ($newInvoices->isEmpty()) {
            $this->info('No new invoices to send.');
            return;
        }

        $sent = 0;
        foreach ($newInvoices as $invoice) {
            try {
                $company = $this->validateInvoice($invoice);
                if (!$company) continue;

                $series = $this->findSeries($invoice, $company);
                if (!$series) continue;

                $sunatService = new SunatService($company);

                $clientData = [
                    'tipo_doc' => $invoice->cliente_tipo_doc ?? '1',
                    'num_doc'  => $invoice->cliente_num_doc ?? '0',
                    'nombre'   => $invoice->cliente_nombre ?? 'CLIENTE VARIOS',
                    'direccion'=> $invoice->cliente_direccion ?: (string) PosCustomerProfile::where('seller_id', $invoice->seller_id)
                        ->where('document_number', preg_replace('/\D+/', '', (string) ($invoice->cliente_num_doc ?? '')))
                        ->value('address'),
                ];

                if (in_array($invoice->tipo_doc, ['07', '08'])) {
                    $descripcion = $invoice->note_description
                        ?? trim(str_replace('Anulación SUNAT: ', '', (string) ($invoice->order->cancel_reason ?? '')))
                        ?: 'Anulación de comprobante';
                    $updatedInvoice = $sunatService->sendNote(
                        $invoice->order,
                        $series,
                        $invoice->note_motivo ?? '01',
                        $descripcion,
                        $invoice
                    );
                } else {
                    $updatedInvoice = $sunatService->sendInvoice(
                        $invoice->order,
                        $series,
                        $clientData,
                        (int) $invoice->correlativo,
                        false,
                        $invoice
                    );
                }

                $sent++;
                $label = $invoice->serie . '-' . str_pad($invoice->correlativo, 8, '0', STR_PAD_LEFT);

                if ($updatedInvoice->cdr_status === SunatInvoice::STATUS_ACCEPTED) {
                    $this->info("  OK: $label accepted by SUNAT.");
                    if (!in_array($invoice->tipo_doc, ['07', '08'])) {
                        $this->updateOrderFromInvoice($invoice, $series, $clientData);
                    }
                } elseif ($updatedInvoice->cdr_status === SunatInvoice::STATUS_REJECTED) {
                    $this->warn("  REJECTED: $label - " . ($updatedInvoice->cdr_response['desc'] ?? 'Unknown'));
                } else {
                    $this->warn("  PENDING: $label - cdr_status: {$updatedInvoice->cdr_status}");
                }
            } catch (\Exception $e) {
                $this->handleRetry($invoice, $e);
            }
        }

        $this->info("Phase 1 done. Sent: $sent");
    }

    private function processPendingCdr()
    {
        $this->info('--- Phase 2: Consulting CDR for sent invoices ---');

        $pendingCdr = SunatInvoice::where('cdr_status', 'pending')
            ->whereNotNull('xml_content')
            ->with('order')
            ->get();

        if ($pendingCdr->isEmpty()) {
            $this->info('No invoices pending CDR consultation.');
            return;
        }

        $consulted = 0;
        $accepted  = 0;
        $rejected  = 0;

        foreach ($pendingCdr as $invoice) {
            try {
                $company = $this->validateInvoice($invoice);
                if (!$company) continue;

                $sunatService = new SunatService($company);
                $result = $sunatService->getCdrResult(
                    $invoice->tipo_doc,
                    $invoice->serie,
                    (int) $invoice->correlativo
                );

                $consulted++;
                $label = $invoice->serie . '-' . str_pad($invoice->correlativo, 8, '0', STR_PAD_LEFT);

                if ($result['status'] === 'not_found') {
                    $this->line("  WAITING: $label - CDR aún no disponible en SUNAT.");
                    $invoice->increment('retries');
                    continue;
                }

                if ($result['status'] === 'error') {
                    $this->warn("  ERROR CDR: $label - " . ($result['message'] ?? 'Unknown'));
                    $this->handleRetry($invoice, new \Exception($result['message'] ?? 'CDR error'));
                    continue;
                }

                $invoice->update([
                    'cdr_status'   => $result['status'],
                    'cdr_response' => json_encode([
                        'code' => $result['code'],
                        'desc' => $result['description']
                    ]),
                    'retries'      => 0,
                ]);

                if ($result['status'] === SunatInvoice::STATUS_ACCEPTED) {
                    $this->info("  CDR OK: $label accepted by SUNAT.");
                    $accepted++;
                } else {
                    $this->warn("  CDR REJECTED: $label - " . ($result['description'] ?? ''));
                    $rejected++;
                }
            } catch (\Exception $e) {
                $this->handleRetry($invoice, $e);
            }
        }

        $this->info("Phase 2 done. Consulted: $consulted | Accepted: $accepted | Rejected: $rejected");
    }

    private function validateInvoice(SunatInvoice $invoice): ?\App\Models\SellerCompany
    {
        if (!$invoice->order) {
            $invoice->update(['cdr_status' => 'error', 'errors' => json_encode(['Order not found'])]);
            $this->warn("  Invoice #{$invoice->id}: Order not found.");
            return null;
        }

        if (!$invoice->seller_company_id) {
            $invoice->update(['cdr_status' => 'error', 'errors' => json_encode(['No company assigned'])]);
            $this->warn("  Invoice #{$invoice->id}: No company assigned.");
            return null;
        }

        $company = \App\Models\SellerCompany::find($invoice->seller_company_id);
        if (!$company || !$company->sunat_cert_path) {
            $invoice->update(['cdr_status' => 'error', 'errors' => json_encode(['Company not configured for SUNAT'])]);
            $this->warn("  Invoice #{$invoice->id}: Company not configured.");
            return null;
        }

        return $company;
    }

    private function findSeries(SunatInvoice $invoice, \App\Models\SellerCompany $company): ?PosInvoiceSeries
    {
        $series = PosInvoiceSeries::where('seller_company_id', $company->id)
            ->where('series', $invoice->serie)
            ->where('active', true)
            ->with('invoiceType')
            ->first();

        if (!$series) {
            $invoice->update(['cdr_status' => 'error', 'errors' => json_encode(['Series not found or inactive'])]);
            $this->warn("  Invoice #{$invoice->id}: Series {$invoice->serie} not found.");
        }

        return $series;
    }

    private function updateOrderFromInvoice(SunatInvoice $invoice, PosInvoiceSeries $series, array $clientData): void
    {
        if ($invoice->order) {
            $invoice->order->update([
                'invoice_type_id'   => $series->invoiceType->id,
                'invoice_series'    => $invoice->serie,
                'invoice_number'    => str_pad($invoice->correlativo, 8, '0', STR_PAD_LEFT),
                'invoice_type_code' => $invoice->tipo_doc,
                'customer_doc'      => $clientData['num_doc'],
                'customer_doc_type' => $clientData['tipo_doc'],
            ]);
        }
    }

    private function handleRetry(SunatInvoice $invoice, \Exception $e): void
    {
        $retries = (int) ($invoice->retries ?? 0) + 1;
        $maxRetries = 10;

        if ($retries >= $maxRetries) {
            $invoice->update([
                'cdr_status' => 'error',
                'retries'    => $retries,
                'errors'     => json_encode(['message' => $e->getMessage()])
            ]);
            $this->error("  Invoice #{$invoice->id}: exceeded max retries, marked as error: " . $e->getMessage());
        } else {
            $invoice->update([
                'retries' => $retries,
                'errors'  => json_encode(['message' => $e->getMessage()])
            ]);
            $this->warn("  Invoice #{$invoice->id}: attempt $retries failed, will retry: " . $e->getMessage());
        }
    }
}
