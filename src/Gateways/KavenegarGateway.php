<?php

declare(strict_types=1);

namespace FallahAlireza\Kavenegar\Gateways;

use FallahAlireza\Kavenegar\Contracts\SmsGateway;
use FallahAlireza\Kavenegar\Data\LookupMessage;
use FallahAlireza\Kavenegar\Data\SmsMessage;
use FallahAlireza\Kavenegar\Data\SmsResult;
use FallahAlireza\Kavenegar\Exceptions\ApiException;
use FallahAlireza\Kavenegar\Exceptions\ConfigurationException;
use FallahAlireza\Kavenegar\Exceptions\TransportException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;

/**
 * درایور اصلی؛ مستقیم با REST API کاوه‌نگار صحبت می‌کند.
 *
 * تفاوت با SDK قدیمی: از HTTP Client لاراول استفاده می‌کند (پس با Http::fake() تست‌پذیر است)،
 * گواهی SSL را بررسی می‌کند، timeout دارد، به‌جای die() استثنا پرتاب می‌کند
 * و کلید API را در پیام خطاها نشان نمی‌دهد.
 */
final class KavenegarGateway implements SmsGateway
{
    private const DEFAULT_BASE_URL = 'https://api.kavenegar.com/v1';

    /**
     * @param  array<string, mixed>  $options  base_url, timeout, connect_timeout, retries, retry_delay
     */
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $apiKey,
        private readonly ?string $defaultSender = null,
        private readonly array $options = [],
    ) {
        if (trim($apiKey) === '') {
            throw ConfigurationException::missingApiKey();
        }
    }

    public function send(SmsMessage $message): array
    {
        $sender = $message->sender ?? $this->defaultSender;

        if ($sender === null || $sender === '') {
            throw ConfigurationException::missingSender();
        }

        return $this->call('sms', 'send', [
            'receptor' => implode(',', $message->receptors),
            'sender' => $sender,
            'message' => $message->text,
            'date' => $message->sendAt,
            'type' => $message->type,
            'localid' => $message->localId,
        ]);
    }

    public function lookup(LookupMessage $message): array
    {
        return $this->call('verify', 'lookup', [
            'receptor' => $message->receptor,
            'template' => $message->template,
            'type' => $message->type,
        ] + $message->tokenParameters());
    }

    public function status(array|int|string $messageIds): array
    {
        return $this->call('sms', 'status', ['messageid' => $this->implodeIds($messageIds)]);
    }

    public function cancel(array|int|string $messageIds): array
    {
        return $this->call('sms', 'cancel', ['messageid' => $this->implodeIds($messageIds)]);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return list<SmsResult>
     */
    private function call(string $group, string $method, array $params): array
    {
        $url = sprintf(
            '%s/%s/%s/%s.json',
            rtrim((string) ($this->options['base_url'] ?? self::DEFAULT_BASE_URL), '/'),
            $this->apiKey,
            $group,
            $method,
        );

        $retries = (int) ($this->options['retries'] ?? 0);

        try {
            $response = $this->http
                ->asForm()
                ->acceptJson()
                ->connectTimeout((int) ($this->options['connect_timeout'] ?? 5))
                ->timeout((int) ($this->options['timeout'] ?? 10))
                ->when($retries > 0, fn ($request) => $request->retry(
                    $retries,
                    (int) ($this->options['retry_delay'] ?? 200),
                    // فقط خطای اتصال تکرار می‌شود؛ پاسخ خطای API هرگز تکرار نمی‌شود تا پیامک دوبار نرود.
                    fn ($exception) => $exception instanceof ConnectionException,
                    throw: false,
                ))
                ->post($url, array_filter($params, static fn ($value): bool => $value !== null));
        } catch (ConnectionException $e) {
            throw new TransportException($this->maskKey($e->getMessage()), 0, $e);
        }

        $json = $response->json();

        if (! is_array($json) || ! isset($json['return']['status'])) {
            throw TransportException::invalidResponse($response->status());
        }

        $status = (int) $json['return']['status'];

        if ($status !== 200) {
            throw ApiException::fromStatus($status, (string) ($json['return']['message'] ?? ''));
        }

        return array_map(SmsResult::fromArray(...), $this->normalizeEntries($json['entries'] ?? []));
    }

    /**
     * بعضی متدها entries را به‌صورت یک شیء تکی برمی‌گردانند و بعضی آرایه؛ همه را به لیست تبدیل می‌کنیم.
     *
     * @return list<array<string, mixed>>
     */
    private function normalizeEntries(mixed $entries): array
    {
        if (! is_array($entries) || $entries === []) {
            return [];
        }

        return array_is_list($entries) ? $entries : [$entries];
    }

    /**
     * @param  array<int, int|string>|int|string  $ids
     */
    private function implodeIds(array|int|string $ids): string
    {
        return implode(',', (array) $ids);
    }

    private function maskKey(string $text): string
    {
        return str_replace($this->apiKey, '***', $text);
    }
}
