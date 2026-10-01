<?php

declare(strict_types=1);

namespace Wobqqq\AegisAdminIpAccess\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Override;
use Wobqqq\AegisAdminIpAccess\Support\Ip;
use Wobqqq\AegisAdminIpAccess\Support\Message;

final class IpOrSubnet implements ValidationRule
{
    #[Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (Ip::normalize($value) === null) {
            $fail(Message::get('aegis-admin-ip-access::admin-ip-access.validation.ip', [
                'value' => mb_substr(is_scalar($value) ? (string)$value : '', 0, 100),
            ]));
        }
    }
}
