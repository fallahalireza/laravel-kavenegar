<?php

declare(strict_types=1);

namespace FallahAlireza\Kavenegar\Data;

use FallahAlireza\Kavenegar\Enums\SmsStatus;

/**
 * نتیجه‌ی یک پیامک در پاسخ کاوه‌نگار (به‌جای stdClass بدون نوع).
 */
final readonly class SmsResult
{
    public function __construct(
        public int|string|null $messageId = null,
        public ?string $message = null,
        public ?SmsStatus $status = null,
        public ?string $statusText = null,
        public ?string $sender = null,
        public ?string $receptor = null,
        public ?int $date = null,
        public ?int $cost = null,
        public ?string $localId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $entry  یک عنصر از آرایه‌ی entries در پاسخ API
     */
    public static function fromArray(array $entry): self
    {
        $string = static fn (string $key): ?string => isset($entry[$key]) ? (string) $entry[$key] : null;
        $int = static fn (string $key): ?int => isset($entry[$key]) ? (int) $entry[$key] : null;

        return new self(
            messageId: $entry['messageid'] ?? null,
            message: $string('message'),
            status: isset($entry['status']) ? SmsStatus::tryFrom((int) $entry['status']) : null,
            statusText: $string('statustext'),
            sender: $string('sender'),
            receptor: $string('receptor'),
            date: $int('date'),
            cost: $int('cost'),
            localId: $string('localid'),
        );
    }

    /**
     * برچسب فارسی وضعیت.
     *
     * برای کدهای ناشناخته (که ممکن است کاوه‌نگار بعداً اضافه کند) به متن
     * `statustext` وب‌سرویس برمی‌گردد؛ اگر آن هم نباشد `null` است.
     */
    public function statusLabel(): ?string
    {
        return $this->status?->label() ?? $this->statusText;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'messageid' => $this->messageId,
            'message' => $this->message,
            'status' => $this->status?->value,
            'statustext' => $this->statusText,
            'sender' => $this->sender,
            'receptor' => $this->receptor,
            'date' => $this->date,
            'cost' => $this->cost,
            'localid' => $this->localId,
        ];
    }
}
