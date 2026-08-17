<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ComplaintController extends Controller
{
    public function showForm()
    {
        $pageTitle = __('Libro de Reclamaciones');
        return view('Template::complaints.form', compact('pageTitle'));
    }

    public function submitForm(Request $request)
    {
        $request->validate([
            'claim_type'               => 'required|in:1,2',
            'full_name'                => 'required|string|max:150',
            'document_type'            => 'required|string|max:30',
            'document_number'          => 'required|string|max:30',
            'phone'                    => 'required|string|max:30',
            'email'                    => 'required|email|max:100',
            'address'                  => 'required|string|max:255',
            'is_minor'                 => 'nullable|string',
            'guardian_name'            => 'required_if:is_minor,on|nullable|string|max:150',
            'guardian_document_type'   => 'required_if:is_minor,on|nullable|string|max:30',
            'guardian_document_number' => 'required_if:is_minor,on|nullable|string|max:30',
            'item_type'                => 'required|in:1,2',
            'amount_claimed'           => 'required|numeric|min:0',
            'item_description'         => 'required|string|max:1000',
            'detail'                   => 'required|string|max:3000',
            'request'                  => 'required|string|max:3000',
        ]);

        // Generate Ticket Number: RECL-YYYY-XXXXX
        $year = date('Y');
        $lastComplaint = Complaint::whereYear('created_at', $year)->orderBy('id', 'desc')->first();
        $nextNum = $lastComplaint ? (int) substr($lastComplaint->ticket_number, -5) + 1 : 1;
        $ticketNumber = 'RECL-' . $year . '-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);

        $complaint = new Complaint();
        $complaint->ticket_number = $ticketNumber;
        $complaint->claim_type = $request->claim_type;
        $complaint->status = Complaint::STATUS_PENDING;
        $complaint->full_name = $request->full_name;
        $complaint->document_type = $request->document_type;
        $complaint->document_number = $request->document_number;
        $complaint->phone = $request->phone;
        $complaint->email = $request->email;
        $complaint->address = $request->address;
        $complaint->is_minor = $request->is_minor ? true : false;
        $complaint->guardian_name = $request->guardian_name;
        $complaint->guardian_document_type = $request->guardian_document_type;
        $complaint->guardian_document_number = $request->guardian_document_number;
        $complaint->item_type = $request->item_type;
        $complaint->amount_claimed = $request->amount_claimed;
        $complaint->item_description = $request->item_description;
        $complaint->detail = $request->detail;
        $complaint->request = $request->request;
        $complaint->save();

        $notify[] = ['success', 'Su reclamo/queja ha sido registrado con éxito. Ticket: ' . $ticketNumber];
        return redirect()->route('complaints.success', $ticketNumber)->withNotify($notify);
    }

    public function success($ticketNumber)
    {
        $complaint = Complaint::where('ticket_number', $ticketNumber)->firstOrFail();
        $pageTitle = __('Reclamación Registrada');
        return view('Template::complaints.success', compact('pageTitle', 'complaint'));
    }

    public function downloadPdf($ticketNumber)
    {
        $complaint = Complaint::where('ticket_number', $ticketNumber)->firstOrFail();
        $pdf = Pdf::loadView('Template::complaints.pdf_receipt', compact('complaint'));
        return $pdf->download($ticketNumber . '.pdf');
    }
}
