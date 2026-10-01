<?php

declare(strict_types=1);

namespace Wobqqq\AegisAdminIpAccess\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Override;
use Symfony\Component\HttpFoundation\IpUtils;
use Wobqqq\Aegis\Support\Values;
use Wobqqq\AegisAdminIpAccess\AccessList;
use Wobqqq\AegisAdminIpAccess\Support\Message;

/**
 * The administrator who saves an enabled whitelist must still be on it.
 */
final class CoversCurrentIp implements DataAwareRule, ValidationRule
{
    /** @var array<mixed> */
    private array $data = [];

    public function __construct(private readonly ?string $currentIp)
    {
    }

    /**
     * @param array<mixed> $data
     */
    #[Override]
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    #[Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->currentIp === null || !Values::bool(['enabled' => $this->data['enabled'] ?? null], 'enabled')) {
            return;
        }

        $entries = array_column(AccessList::rows($value), 'ip');

        if ($entries !== [] && !IpUtils::checkIp($this->currentIp, $entries)) {
            $fail(Message::get('aegis-admin-ip-access::admin-ip-access.validation.current_ip', ['ip' => $this->currentIp]));
        }
    }
}
