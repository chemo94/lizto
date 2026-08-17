<?php

namespace App\Http\Controllers\Api\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\DeliveryOrder;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Traits\SupportTicketManager;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TicketController extends Controller
{
    use SupportTicketManager;

    public function __construct()
    {
        $this->userType   = 'user';
        $this->column     = 'user_id';
        $this->user       = auth()->user();
        $this->apiRequest = true;
    }

    public function storeOrderTicket(Request $request, $id)
    {
        $user  = auth()->user();
        $order = DeliveryOrder::where('user_id', $user->id)->find($id);

        if (!$order) {
            return apiResponse('order_not_found', 'error', ['Pedido no encontrado']);
        }

        $validator = Validator::make($request->all(), [
            'subject'     => 'required|string|max:255',
            'description' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $ticket                = new SupportTicket();
        $ticket->user_id       = $user->id;
        $ticket->ticket        = rand(100000, 999999);
        $ticket->name          = $user->fullname;
        $ticket->email         = $user->email;
        $ticket->subject       = '[Pedido #' . $order->order_no . '] ' . $request->subject;
        $ticket->last_reply    = Carbon::now();
        $ticket->status        = Status::TICKET_OPEN;
        $ticket->priority      = Status::PRIORITY_MEDIUM;
        $ticket->order_id      = $order->id;
        $ticket->save();

        $message                     = new SupportMessage();
        $message->support_ticket_id  = $ticket->id;
        $message->message            = 'Pedido #' . $order->order_no . ":\n" . $request->description;
        $message->save();

        $adminNotification            = new AdminNotification();
        $adminNotification->user_id   = $user->id;
        $adminNotification->title     = 'Nuevo reporte de pedido #' . $order->order_no;
        $adminNotification->click_url = urlPath('admin.ticket.view', $ticket->id);
        $adminNotification->save();

        return apiResponse('order_ticket_created', 'success', ['Reporte enviado'], [
            'ticket' => $ticket,
        ]);
    }
}
