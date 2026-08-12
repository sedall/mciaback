<?php

namespace Modules\Tickets\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tickets\Http\Requests\StoreTicketMessageRequest;
use Modules\Tickets\Http\Requests\StoreTicketRequest;
use Modules\Tickets\Http\Resources\TicketMessageResource;
use Modules\Tickets\Http\Resources\TicketResource;
use Modules\Tickets\Models\Ticket;
use Modules\Tickets\Services\TicketService;

class TicketsController extends Controller
{
    public function __construct(private readonly TicketService $ticketService)
    {
    }

    public function index(Request $request)
    {
        $entry = $request->route('entry_point') ?? 'customer';

        $items = Ticket::query()
            ->where('creator_id', $request->user()->id)
            ->where('entry_point', $entry)
            ->withCount('messages')
            ->latest('last_message_at')
            ->paginate(20);

        return TicketResource::collection($items);
    }

    public function store(StoreTicketRequest $request)
    {
        $entry = $request->route('entry_point') ?? 'customer';

        $ticket = $this->ticketService->createTicket(
            userId: $request->user()->id,
            entryPoint: $entry,
            subject: $request->string('subject')->toString(),
            body: $request->string('body')->toString(),
            attachments: $request->file('attachments', [])
        );

        return (new TicketResource($ticket))->response()->setStatusCode(201);
    }

    public function show(Request $request, Ticket $ticket)
    {
        abort_unless($ticket->creator_id === $request->user()->id, 403);

        $ticket->load(['messages.attachments']);
        return new TicketResource($ticket);
    }

    public function storeMessage(StoreTicketMessageRequest $request, Ticket $ticket)
    {
        abort_unless($ticket->creator_id === $request->user()->id, 403);

        $senderType = $request->route('entry_point') ?? 'customer';

        $message = $this->ticketService->addMessage(
            ticket: $ticket,
            userId: $request->user()->id,
            senderType: $senderType,
            body: $request->string('body')->toString(),
            attachments: $request->file('attachments', [])
        );

        return (new TicketMessageResource($message))->response()->setStatusCode(201);
    }
}
