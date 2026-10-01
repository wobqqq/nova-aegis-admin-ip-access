<?php

declare(strict_types=1);

namespace Wobqqq\AegisAdminIpAccess\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Wobqqq\AegisAdminIpAccess\Support\Ip;

final class IpOrSubnet implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (Ip::normalize($value) === null) {
            $fail((string)__('aegis-admin-ip-access::admin-ip-access.validation.ip', [
                'value' => mb_substr(is_scalar($value) ? (string)$value : '', 0, 100),
            ]));
        }
    }
}
