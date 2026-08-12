<?php

namespace Modules\Tickets\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tickets\Http\Requests\AdminUpdateTicketStatusRequest;
use Modules\Tickets\Http\Requests\StoreTicketMessageRequest;
use Modules\Tickets\Http\Resources\TicketMessageResource;
use Modules\Tickets\Http\Resources\TicketResource;
use Modules\Tickets\Models\Ticket;
use Modules\Tickets\Services\TicketService;

class AdminTicketsController extends Controller
{
    public function __construct(private readonly TicketService $ticketService)
    {
    }

    public function index(Request $request)
    {
        $q = Ticket::query()->withCount('messages')->latest('last_message_at');

        if ($request->filled('status')) {
            $q->where('status', $request->string('status')->toString());
        }

        return TicketResource::collection($q->paginate(30));
    }

    public function show(Ticket $ticket)
    {
        $ticket->load(['messages.attachments']);
        return new TicketResource($ticket);
    }

    public function storeMessage(StoreTicketMessageRequest $request, Ticket $ticket)
    {
        $message = $this->ticketService->addMessage(
            ticket: $ticket,
            userId: $request->user()->id,
            senderType: 'admin',
            body: $request->string('body')->toString(),
            attachments: $request->file('attachments', [])
        );

        return (new TicketMessageResource($message))->response()->setStatusCode(201);
    }

    public function updateStatus(AdminUpdateTicketStatusRequest $request, Ticket $ticket)
    {
        $ticket = $this->ticketService->changeStatus(
            ticket: $ticket,
            status: $request->string('status')->toString(),
            adminId: $request->user()->id
        );

        return new TicketResource($ticket);
    }
}
