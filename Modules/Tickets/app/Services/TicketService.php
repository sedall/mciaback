<?php

namespace Modules\Tickets\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Tickets\Models\Ticket;
use Modules\Tickets\Models\TicketAttachment;
use Modules\Tickets\Models\TicketMessage;

// اگر SettingService در ماژول Settings با namespace دیگر است، این use را اصلاح کن:
use Modules\Settings\Services\SettingService;

class TicketService
{
    public function __construct(private readonly SettingService $settingService)
    {
    }

    public function createTicket(int $userId, string $entryPoint, string $subject, string $body, array $attachments = []): Ticket
    {
        return DB::transaction(function () use ($userId, $entryPoint, $subject, $body, $attachments) {
            $ticket = Ticket::query()->create([
                'creator_id' => $userId,
                'entry_point' => $entryPoint,
                'subject' => $subject,
                'status' => 'open',
                'last_message_at' => now(),
            ]);

            $message = TicketMessage::query()->create([
                'ticket_id' => $ticket->id,
                'sender_id' => $userId,
                'sender_type' => $entryPoint,
                'body' => $body,
            ]);

            $this->storeAttachments($message, $attachments);

            return $ticket->fresh(['messages.attachments']);
        });
    }

    public function addMessage(Ticket $ticket, int $userId, string $senderType, string $body, array $attachments = []): TicketMessage
    {
        if ($ticket->status === 'closed') {
            throw ValidationException::withMessages(['ticket' => 'تیکت بسته است و امکان ارسال پیام ندارد.']);
        }

        return DB::transaction(function () use ($ticket, $userId, $senderType, $body, $attachments) {
            $message = TicketMessage::query()->create([
                'ticket_id' => $ticket->id,
                'sender_id' => $userId,
                'sender_type' => $senderType,
                'body' => $body,
            ]);

            $this->storeAttachments($message, $attachments);

            $ticket->update([
                'status' => $senderType === 'admin' ? 'answered' : 'open',
                'last_message_at' => now(),
            ]);

            return $message->fresh(['attachments']);
        });
    }

    public function changeStatus(Ticket $ticket, string $status, ?int $adminId = null): Ticket
    {
        $data = ['status' => $status];

        if ($status === 'closed') {
            $data['closed_at'] = now();
            $data['closed_by'] = $adminId;
        } else {
            $data['closed_at'] = null;
            $data['closed_by'] = null;
        }

        $ticket->update($data);

        return $ticket->fresh();
    }

    private function storeAttachments(TicketMessage $message, array $attachments): void
    {
        if (empty($attachments)) return;

        $enabled = filter_var($this->settingService->get('ticket_attachments_enabled', true), FILTER_VALIDATE_BOOL);
        if (!$enabled) {
            throw ValidationException::withMessages(['attachments' => 'آپلود فایل در تیکت غیرفعال است.']);
        }

        $maxMb = (int) $this->settingService->get('ticket_max_file_size_mb', 5);
        $allowedRaw = (string) $this->settingService->get('ticket_allowed_extensions', 'jpg,jpeg,png,pdf');
        $allowed = collect(explode(',', strtolower($allowedRaw)))
            ->map(fn($x) => trim($x))
            ->filter()
            ->values()
            ->all();

        foreach ($attachments as $file) {
            /** @var UploadedFile $file */
            $ext = strtolower($file->getClientOriginalExtension());
            $size = (int) $file->getSize();

            if (!in_array($ext, $allowed, true)) {
                throw ValidationException::withMessages([
                    'attachments' => "پسوند فایل .{$ext} مجاز نیست.",
                ]);
            }

            if ($size > ($maxMb * 1024 * 1024)) {
                throw ValidationException::withMessages([
                    'attachments' => "حجم فایل از {$maxMb}MB بیشتر است.",
                ]);
            }

            $path = $file->store('tickets/' . $message->ticket_id . '/messages/' . $message->id, 'tickets_attachments');

            TicketAttachment::query()->create([
                'ticket_message_id' => $message->id,
                'disk' => 'tickets_attachments',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'extension' => $ext,
                'size_bytes' => $size,
                'mime_type' => $file->getMimeType(),
            ]);
        }
    }
}
