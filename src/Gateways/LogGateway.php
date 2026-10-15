<?php

declare(strict_types=1);

namespace FallahAlireza\Kavenegar\Gateways;

use FallahAlireza\Kavenegar\Contracts\SmsGateway;
use FallahAlireza\Kavenegar\Data\LookupMessage;
use FallahAlireza\Kavenegar\Data\SmsMessage;
use FallahAlireza\Kavenegar\Data\SmsResult;
use Psr\Log\LoggerInterface;

/**
 * درایور توسعه/استیجینگ: به‌جای ارسال واقعی، پیامک را در لاگ می‌نویسد (بدون هزینه).
 */
final class LogGateway implements SmsGateway
{
    public function __construct(private readonly LoggerInterface $logger) {}

    public function send(SmsMessage $message): array
    {
        $this->logger->info('SMS (log driver)', [
            'sender' => $message->sender,
            'receptors' => $message->receptors,
            'text' => $message->text,
        ]);

        return array_map(
            static fn (string $receptor): SmsResult => new SmsResult(
                message: $message->text,
                statusText: 'logged',
                sender: $message->sender,
                receptor: $receptor,
            ),
            $message->receptors,
        );
    }

    public function lookup(LookupMessage $message): array
    {
        $this->logger->info('SMS lookup (log driver)', [
            'receptor' => $message->receptor,
            'template' => $message->template,
            'tokens' => $message->tokens,
        ]);

        return [new SmsResult(statusText: 'logged', receptor: $message->receptor)];
    }

    public function status(array|int|string $messageIds): array
    {
        return [];
    }

    public function cancel(array|int|string $messageIds): array
    {
        return [];
    }
}
