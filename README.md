# Laravel Kavenegar (نسخه‌ی بازطراحی‌شده)

پکیج ارسال پیامک کاوه‌نگار فقط برای **لاراول ۱۳** و PHP 8.3 به بالا؛ انعطاف‌پذیر، توسعه‌پذیر و کاملاً تست‌پذیر.

توسعه‌دهنده: [Alireza Fallah](https://www.alirezafallah.ir/) — توسعه‌دهنده‌ی Full-stack لاراول · وب‌سایت: [alirezafallah.ir](https://www.alirezafallah.ir/)

> **اعلام غیررسمی بودن:** این پکیج یک پروژه‌ی متن‌باز و مستقل است و **رسمی نیست**؛ هیچ وابستگی یا پشتیبانی از طرف شرکت کاوه‌نگار ندارد. «کاوه‌نگار» نام تجاری متعلق به صاحبان آن است و در اینجا فقط برای توصیف سرویس سازگار به کار رفته است.

## چه چیزی نسبت به `kavenegar/laravel` عوض شده؟

| موضوع | پکیج قدیمی | این پکیج |
|---|---|---|
| ارتباط با API | SDK مبتنی بر cURL با `die()` و `CURLOPT_SSL_VERIFYPEER=false` | HTTP Client لاراول، SSL فعال، timeout، استثناهای تایپ‌دار |
| تست | غیرممکن (Facade ثابت در کانال) | `Sms::fake()` و `Http::fake()` |
| پیام‌ها | آرایه/`stdClass` و propertyهای public | DTOهای نامتغیر و اعتبارسنجی‌شده (`SmsMessage`, `LookupMessage`, `SmsResult`) |
| توسعه | ثابت | Manager + `Sms::extend()` برای درایور دلخواه |
| ServiceProvider | سه Provider بر اساس نسخه‌ی لاراول | یک Provider + Package Auto-Discovery |
| رویدادها | ندارد | `SmsSent` و `SmsFailed` |
| امنیت | کلید API در پیام خطا | کلید API در خطاها ماسک می‌شود |

## نصب

```bash
composer require fallahalireza/laravel-kavenegar
php artisan vendor:publish --tag=kavenegar-config
```

```env
KAVENEGAR_API_KEY=xxxxxxxx
KAVENEGAR_SENDER=10004346
# KAVENEGAR_DRIVER=log   # در توسعه: فقط در لاگ بنویس
```

## استفاده

```php
use FallahAlireza\Kavenegar\Data\{SmsMessage, LookupMessage};
use FallahAlireza\Kavenegar\Facades\Sms;

// پیامک ساده
Sms::send(new SmsMessage('سفارش شما ثبت شد', ['09121234567', '۰۹۳۵۱۲۳۴۵۶۷']));

// قالب (کد تأیید)
Sms::lookup(new LookupMessage('09121234567', 'verify', ['12345']));

// پیگیری و لغو
Sms::status([123, 456]);
Sms::cancel(123);
```

### وضعیت پیامک

`Sms::status([...])` لیستی از `SmsResult` برمی‌گرداند و فیلد `status` یک enum تایپ‌دار با برچسب فارسی است:

```php
use FallahAlireza\Kavenegar\Enums\SmsStatus;

foreach (Sms::status([123, 456]) as $result) {
    if ($result->status === SmsStatus::Delivered) {
        // پیامک رسیده است
    }

    echo $result->statusLabel(); // «رسیده به گیرنده»
}
```

| کد | case | برچسب |
|---|---|---|
| `1` | `SmsStatus::Queued` | در صف ارسال قرار دارد |
| `2` | `SmsStatus::Scheduled` | زمان‌بندی شده |
| `4` | `SmsStatus::SentToTelecom` | ارسال شده به مخابرات |
| `5` | `SmsStatus::SentToTelecomSame` | ارسال شده به مخابرات |
| `6` | `SmsStatus::Failed` | خطا در ارسال |
| `10` | `SmsStatus::Delivered` | رسیده به گیرنده |
| `11` | `SmsStatus::Undelivered` | نرسیده به گیرنده |
| `13` | `SmsStatus::Cancelled` | لغو شده |
| `14` | `SmsStatus::Blocked` | مسدود شده توسط گیرنده |
| `100` | `SmsStatus::InvalidMessageId` | شناسه پیامک نامعتبر است |

کد ۵ طبق مستندات کاوه‌نگار همانند کد ۴ است. اگر کاوه‌نگار کد جدیدی اضافه کند که در enum نباشد، `status` مقدار `null` می‌گیرد و `statusLabel()` متن `statustext` خود وب‌سرویس را برمی‌گرداند.

بهتر است در سرویس‌ها اینترفیس را تزریق کنید تا به Facade وابسته نباشید:

```php
public function __construct(private SmsGateway $sms) {}
```

### Notification

```php
class InvoicePaid extends Notification
{
    public function via($notifiable): array { return ['kavenegar']; }

    public function toKavenegar($notifiable): KavenegarMessage
    {
        return (new KavenegarMessage("فاکتور {$this->invoice->id} پرداخت شد"))->from('10004346');
        // یا: ->verifyLookup('verify', ['12345'])
    }
}

// در مدل User
public function routeNotificationForKavenegar(): ?string { return $this->mobile; }
```

اگر کاربر شماره نداشته باشد، مثل بقیه‌ی کانال‌های لاراول بی‌صدا رد می‌شود. خطاهای وب‌سرویس پرتاب می‌شوند تا Queue بتواند دوباره تلاش کند.

## تست

```php
$fake = Sms::fake();

$user->notify(new InvoicePaid($invoice));

$fake->assertSentTo('09121234567', fn ($m) => str_contains($m->text, 'پرداخت'));
$fake->assertNothingSent();
$fake->failWith(ApiException::fromStatus(418, 'اعتبار کافی نیست')); // شبیه‌سازی خطا
```

برای تست خود پکیج: `composer test` (Pest + Testbench).

## افزودن درایور دلخواه

```php
// AppServiceProvider::boot()
Sms::extend('ghasedak', fn ($app) => new GhasedakGateway(config('services.ghasedak.key')));
```

سپس `KAVENEGAR_DRIVER=ghasedak`. کافی است کلاس شما `FallahAlireza\Kavenegar\Contracts\SmsGateway` را پیاده کند.

## مدیریت خطا

همه‌ی استثناها از `KavenegarException` ارث می‌برند:

- `InvalidMessageException`: ورودی نامعتبر (قبل از هر تماس شبکه‌ای)
- `ConfigurationException`: کلید API یا شماره‌ی ارسال‌کننده تنظیم نشده
- `ApiException`: کاوه‌نگار خطا برگردانده؛ کد آن در `getCode()`
- `TransportException`: قطعی شبکه، timeout یا پاسخ نامعتبر

## نکته‌ی مهم درباره‌ی retry

تنظیم `retries` فقط خطای اتصال را تکرار می‌کند و پیش‌فرض آن ۰ است؛ چون اگر درخواست به سرور رسیده ولی پاسخ برنگشته باشد، تکرار آن می‌تواند پیامک تکراری بفرستد. در صورت نیاز از `localid` برای رهگیری استفاده کنید.

## نویسنده و مجوز

نوشته‌ی [Alireza Fallah](https://www.alirezafallah.ir/)، توسعه‌دهنده‌ی Full-stack لاراول. برای آشنایی با نویسنده و پروژه‌های دیگر او به [alirezafallah.ir](https://www.alirezafallah.ir/) سر بزنید. گزارش خطا و پیشنهاد را از طریق [Issues](https://github.com/fallahalireza/laravel-kavenegar/issues) بفرستید.

منتشرشده با مجوز [MIT](LICENSE).
