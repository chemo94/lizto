<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\RidePayment;
use App\Models\Transaction;
use App\Models\UserLogin;
use Illuminate\Http\Request;

class ReportController extends Controller {
    public function transaction(Request $request) {
        $pageTitle = __('Transaction History');
        $baseQuery = Transaction::searchable(['trx', 'user:username'])->filter(['trx_type', 'remark', 'driver_id'])->dateFilter()->orderBy('id', getOrderBy());

        if (request()->export) {
            return exportData($baseQuery, request()->export, "Transaction");
        }
        $transactions = $baseQuery->with('driver')->where('driver_id', '!=', 0)->paginate(getPaginate());
        return view('admin.reports.transactions', compact('pageTitle', 'transactions'));
    }

    public function commission(Request $request) {
        $pageTitle = __('Commission History');
        $commissions = Transaction::searchable(['trx', 'user:username'])
            ->filter(['driver_id'])
            ->dateFilter()
            ->orderBy('id', getOrderBy())
            ->with('driver')
            ->where('driver_id', '!=', 0)
            ->where('remark', 'ride_commission')
            ->paginate(getPaginate());
        return view('admin.reports.commission', compact('pageTitle', 'commissions'));
    }

    public function riderPayment() {
        $pageTitle = __('Rider Payment Logs');
        $baseQuery = RidePayment::searchable(['driver:username', 'rider:username', 'ride:uid'])->filter(['driver_id', 'rider_id', 'payment_type'])->orderBy('id', getOrderBy());

        if (request()->export) {
            return exportData($baseQuery, request()->export, "RidePayment");
        }
        $payments = $baseQuery->with('rider', 'driver', 'ride')->paginate(getPaginate());
        return view('admin.reports.rider_payment', compact('pageTitle', 'payments'));
    }

    public function riderPaymentPerToday() {
        $pageTitle = 'Today\'s Earning Logs';
        $baseQuery = RidePayment::whereDate('created_at', now())->searchable(['driver:username', 'rider:username', 'ride:uid'])->filter(['driver_id', 'rider_id', 'payment_type'])->orderBy('id', getOrderBy());

        if (request()->export) {
            return exportData($baseQuery, request()->export, "RidePayment");
        }
        $payments = $baseQuery->with('rider', 'driver', 'ride')->paginate(getPaginate());
        return view('admin.reports.rider_payment', compact('pageTitle', 'payments'));
    }

    public function riderPaymentPerWeek() {
        $pageTitle = __('Weekly Earning Logs');
        $baseQuery = RidePayment::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->searchable(['driver:username', 'rider:username', 'ride:uid'])->filter(['driver_id', 'rider_id', 'payment_type'])->orderBy('id', getOrderBy());

        if (request()->export) {
            return exportData($baseQuery, request()->export, "RidePayment");
        }
        $payments = $baseQuery->with('rider', 'driver', 'ride')->paginate(getPaginate());
        return view('admin.reports.rider_payment', compact('pageTitle', 'payments'));
    }

    public function riderPaymentPerMonth() {
        $pageTitle = __('Monthly Earning Logs');
        $baseQuery = RidePayment::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->searchable(['driver:username', 'rider:username', 'ride:uid'])->filter(['driver_id', 'rider_id', 'payment_type'])->orderBy('id', getOrderBy());

        if (request()->export) {
            return exportData($baseQuery, request()->export, "RidePayment");
        }
        $payments = $baseQuery->with('rider', 'driver', 'ride')->paginate(getPaginate());
        return view('admin.reports.rider_payment', compact('pageTitle', 'payments'));
    }

    public function riderPaymentPerYear() {
        $pageTitle = __('Yearly Earning Logs');
        $baseQuery = RidePayment::whereYear('created_at', now()->year)->searchable(['driver:username', 'rider:username', 'ride:uid'])->filter(['driver_id', 'rider_id', 'payment_type'])->orderBy('id', getOrderBy());

        if (request()->export) {
            return exportData($baseQuery, request()->export, "RidePayment");
        }
        $payments = $baseQuery->with('rider', 'driver', 'ride')->paginate(getPaginate());
        return view('admin.reports.rider_payment', compact('pageTitle', 'payments'));
    }

    public function loginHistory(Request $request) {
        $pageTitle = __('Rider Login History');
        $baseQuery = UserLogin::orderBy('id', getOrderBy())->searchable(['user:username'])->filter(['user_ip'])->where('driver_id', 0)->dateFilter();

        if (request()->export) {
            return exportData($baseQuery, request()->export, "UserLogin");
        }

        $loginLogs = $baseQuery->with('user')->paginate(getPaginate());
        $userType  = "rider";
        return view('admin.reports.logins', compact('pageTitle', 'loginLogs', 'userType'));
    }

    public function loginHistoryDriver(Request $request) {
        $pageTitle = __('Driver Login History');
        $baseQuery = UserLogin::orderBy('id', getOrderBy())->searchable(['driver:username'])->filter(['driver_id'])->where('driver_id', '!=', 0)->dateFilter();

        if (request()->export) {
            return exportData($baseQuery, request()->export, "UserLogin");
        }
        $loginLogs = $baseQuery->with('driver')->paginate(getPaginate());
        $userType  = "driver";
        return view('admin.reports.logins', compact('pageTitle', 'loginLogs', 'userType'));
    }

    public function loginIpHistory($ip) {
        $pageTitle = 'Login by - ' . $ip;
        $baseQuery = UserLogin::where('user_ip', $ip)->orderBy('id', 'desc');

        if (request()->export) {
            return exportData($baseQuery, request()->export, "UserLogin");
        }

        $loginLogs = $baseQuery->with('user')->paginate(getPaginate());
        $userType  = "driver";
        return view('admin.reports.logins', compact('pageTitle', 'loginLogs', 'ip', 'userType'));
    }

    public function notificationHistory(Request $request) {
        $pageTitle = __('Rider Notification History');
        $baseQuery = NotificationLog::orderBy('id', 'desc')->searchable(['user:username'])->filter(['user_id'])->where('user_id', '!=', 0)->dateFilter();

        if (request()->export) {
            return exportData($baseQuery, request()->export, "NotificationLog");
        }

        $logs     = $baseQuery->with('user')->paginate(getPaginate());
        $userType = "rider";
        return view('admin.reports.notification_history', compact('pageTitle', 'logs', 'userType'));
    }

    public function notificationHistoryDriver(Request $request) {
        $pageTitle = __('Driver Notification History');
        $baseQuery = NotificationLog::orderBy('id', 'desc')->searchable(['user:username'])->filter(['user_id'])->where('user_id', 0)->dateFilter();

        if (request()->export) {
            return exportData($baseQuery, request()->export, "NotificationLog");
        }

        $logs     = $baseQuery->with('driver')->paginate(getPaginate());
        $userType = "driver";

        return view('admin.reports.notification_history', compact('pageTitle', 'logs', 'userType'));
    }

    public function emailDetails($id) {
        $pageTitle = __('Email Details');
        $email     = NotificationLog::findOrFail($id);
        return view('admin.reports.email_details', compact('pageTitle', 'email'));
    }
}
