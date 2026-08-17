<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Models\Deposit;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Gateway\PaymentController;
use Illuminate\Http\Request;

class DepositController extends Controller
{
    public function pending()
    {
        $pageTitle = __('Pending Deposits');
        $baseQuery = $this->baseQuery('pending');

        if (request()->export) {
            return $this->callExportData($baseQuery);
        }
        $deposits    = $baseQuery->with(['driver', 'gateway'])->paginate(getPaginate());
        return view('admin.deposit.log', compact('pageTitle', 'deposits'));
    }
    public function approved()
    {
        $pageTitle = __('Approved Deposits');
        $baseQuery = $this->baseQuery('approved');
        if (request()->export) {
            return $this->callExportData($baseQuery);
        }

        $deposits    = $baseQuery->with(['driver', 'gateway'])->paginate(getPaginate());
        return view('admin.deposit.log', compact('pageTitle', 'deposits'));
    }

    public function successful()
    {
        $pageTitle = __('Successful Deposits');
        $baseQuery = $this->baseQuery('successful');

        if (request()->export) {
            return $this->callExportData($baseQuery);
        }
        $deposits    = $baseQuery->with(['driver', 'gateway'])->paginate(getPaginate());
        return view('admin.deposit.log', compact('pageTitle', 'deposits'));
    }

    public function rejected()
    {
        $pageTitle = __('Rejected Deposits');
        $baseQuery = $this->baseQuery('rejected');

        if (request()->export) {
            return $this->callExportData($baseQuery);
        }
        $deposits    = $baseQuery->with(['driver', 'gateway'])->paginate(getPaginate());
        return view('admin.deposit.log', compact('pageTitle', 'deposits'));
    }

    public function initiated()
    {
        $pageTitle = __('Initiated Deposits');
        $baseQuery = $this->baseQuery('initiated');

        if (request()->export) {
            return $this->callExportData($baseQuery);
        }
        $deposits    = $baseQuery->with(['driver', 'gateway'])->paginate(getPaginate());
        return view('admin.deposit.log', compact('pageTitle', 'deposits'));
    }

    public function deposit()
    {
        $pageTitle = __('Deposit History');
        $baseQuery = $this->baseQuery();

        if (request()->export) {
            return $this->callExportData($baseQuery);
        }

        $depositData = $this->summery($baseQuery);
        $deposits    = $baseQuery->with(['driver', 'gateway'])->paginate(getPaginate());
        $widget      = $depositData['summary'];

        return view('admin.deposit.log', compact('pageTitle', 'deposits', 'widget'));
    }

    protected function summery($baseQuery)
    {

        $successfulSummary = (clone $baseQuery)->where('status', Status::PAYMENT_SUCCESS)->sum('amount');
        $pendingSummary    = (clone $baseQuery)->where('status', Status::PAYMENT_PENDING)->sum('amount');
        $rejectedSummary   = (clone $baseQuery)->where('status', Status::PAYMENT_REJECT)->sum('amount');
        $initiatedSummary  = (clone $baseQuery)->where('status', Status::PAYMENT_INITIATE)->sum('amount');

        return [
            'summary' => [
                'successful' => $successfulSummary,
                'pending'    => $pendingSummary,
                'rejected'   => $rejectedSummary,
                'initiated'  => $initiatedSummary,
            ]
        ];
    }

    public function details($id)
    {
        $deposit   = Deposit::where('id', $id)->with(['driver', 'gateway'])->firstOrFail();
        $pageTitle = $deposit->driver->username . ' requested ' . showAmount($deposit->amount);
        $details   = ($deposit->detail != null) ? json_encode($deposit->detail) : null;
        return view('admin.deposit.detail', compact('pageTitle', 'deposit', 'details'));
    }

    public function approve($id)
    {
        $deposit = Deposit::where('id', $id)->where('status', Status::PAYMENT_PENDING)->firstOrFail();

        PaymentController::userDataUpdate($deposit, true);

        $notify[] = ['success', __('Deposit request approved successfully')];
        return to_route('admin.deposit.pending')->withNotify($notify);
    }

    public function reject(Request $request)
    {
        $request->validate([
            'id'      => 'required|integer',
            'message' => __('required|string|max:255')
        ]);

        $deposit = Deposit::where('id', $request->id)->where('status', Status::PAYMENT_PENDING)->firstOrFail();

        $deposit->admin_feedback = $request->message;
        $deposit->status         = Status::PAYMENT_REJECT;
        $deposit->save();

        notify($deposit->driver, 'DEPOSIT_REJECT', [
            'method_name'       => $deposit->methodName(),
            'method_currency'   => $deposit->method_currency,
            'method_amount'     => showAmount($deposit->final_amount, currencyFormat: false),
            'amount'            => showAmount($deposit->amount, currencyFormat: false),
            'charge'            => showAmount($deposit->charge, currencyFormat: false),
            'rate'              => showAmount($deposit->rate, currencyFormat: false),
            'trx'               => $deposit->trx,
            'rejection_message' => $request->message
        ]);

        $notify[] = ['success', __('Deposit request rejected successfully')];
        return  to_route('admin.deposit.pending')->withNotify($notify);
    }

    private function baseQuery($scope = 'query')
    {
        $baseQuery = Deposit::$scope()->searchable(['trx', 'driver:username'])->filter(['driver_id'])->where('user_id', 0)->dateFilter();
        $request   = request();

        if ($request->method) {
            $baseQuery = $baseQuery->where('method_code', $request->method);
        }

        return $baseQuery->orderBy('id', getOrderBy());
    }

    private function callExportData($baseQuery)
    {
        return exportData($baseQuery, request()->export, "deposit", "A4 landscape");
    }
}
