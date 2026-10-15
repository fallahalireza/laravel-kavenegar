<?php

declare(strict_types=1);

namespace FallahAlireza\Kavenegar\Support;

use FallahAlireza\Kavenegar\Exceptions\InvalidMessageException;

/**
 * نرمال‌سازی شماره‌ها: تبدیل ارقام فارسی/عربی به انگلیسی و حذف فاصله، خط تیره، پرانتز و «+».
 * فرمت نهایی (مثلاً 0912... یا 98912...) را دست‌نخورده می‌گذارد، چون کاوه‌نگار هر دو را می‌پذیرد.
 */
final class PhoneNumber
{
    private const DIGITS = [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ];

    public static function normalize(string $value): string
    {
        $number = preg_replace('/[\s\-().+]/u', '', strtr(trim($value), self::DIGITS)) ?? '';

        if ($number === '' || ! ctype_digit($number)) {
            throw InvalidMessageException::invalidReceptor($value);
        }

        return $number;
    }

    /**
     * @param  array<int, scalar>|string  $values
     * @return list<string> شماره‌های نرمال‌شده و بدون تکرار
     */
    public static function normalizeMany(array|string $values): array
    {
        $numbers = array_map(static fn ($v): string => self::normalize((string) $v), (array) $values);

        return array_values(array_unique($numbers));
    }
}
