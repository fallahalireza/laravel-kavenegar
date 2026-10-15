<?php

declare(strict_types=1);

namespace FallahAlireza\Kavenegar\Data;

use FallahAlireza\Kavenegar\Exceptions\InvalidMessageException;
use FallahAlireza\Kavenegar\Support\PhoneNumber;

/**
 * ارسال بر پایه‌ی قالب (VerifyLookup) مثل کد تأیید.
 * نکته: طبق مستندات کاوه‌نگار توکن‌ها نباید فاصله داشته باشند (به‌جای آن از _ استفاده کنید).
 */
final readonly class LookupMessage
{
    private const TOKEN_KEYS = ['token', 'token2', 'token3', 'token10', 'token20'];

    public string $receptor;

    /** @var list<string> */
    public array $tokens;

    /**
     * @param  array<int, string|int|float>|string|int|float  $tokens  به ترتیب: token, token2, token3, token10, token20
     */
    public function __construct(
        string $receptor,
        public string $template,
        array|string|int|float $tokens,
        public ?string $type = null,
    ) {
        if (trim($template) === '') {
            throw InvalidMessageException::emptyTemplate();
        }

        $list = array_values(array_map(static fn ($t): string => (string) $t, (array) $tokens));

        if ($list === [] || count($list) > count(self::TOKEN_KEYS)) {
            throw InvalidMessageException::invalidTokens(count($list));
        }

        $this->receptor = PhoneNumber::normalize($receptor);
        $this->tokens = $list;
    }

    /**
     * پارامترهای توکن آماده‌ی ارسال به API.
     *
     * @return array<string, string>
     */
    public function tokenParameters(): array
    {
        return array_combine(array_slice(self::TOKEN_KEYS, 0, count($this->tokens)), $this->tokens);
    }
}
