<?php

declare(strict_types=1);

namespace Wobqqq\AegisAdminIpAccess;

use Symfony\Component\HttpFoundation\IpUtils;
use Wobqqq\Aegis\Support\Values;
use Wobqqq\AegisAdminIpAccess\Support\Ip;

/**
 * The whitelist as the middleware reads it on every Nova request.
 */
final readonly class AccessList
{
    /**
     * @param array<string, true> $addresses canonical addresses, keyed for the isset() fast path
     * @param list<string> $subnets canonical CIDR subnets
     */
    public function __construct(
        public bool $enabled,
        public string $view,
        public array $addresses = [],
        public array $subnets = [],
    ) {
    }

    /**
     * Reads the stored values again, whatever they are: an entry the rules never saw is skipped.
     *
     * @param array<string, mixed> $values
     */
    public static function fromArray(array $values): self
    {
        $addresses = [];
        $subnets = [];

        foreach (self::rows($values['ips'] ?? []) as $row) {
            if (Ip::isSubnet($row['ip'])) {
                $subnets[] = $row['ip'];
            } else {
                $addresses[$row['ip']] = true;
            }
        }

        return new self(
            Values::bool($values, 'enabled'),
            self::view(Values::string($values, 'view')),
            $addresses,
            array_values(array_unique($subnets)),
        );
    }

    /**
     * The valid rows of a stored list, in their canonical notation.
     *
     * @return list<array{ip: string, note: string}>
     */
    public static function rows(mixed $rows): array
    {
        $result = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            $ip = Ip::normalize(is_array($row) ? $row['ip'] ?? null : null);
            $note = is_array($row) && is_string($row['note'] ?? null) ? mb_substr(trim($row['note']), 0, 100) : '';

            if ($ip !== null) {
                $result[] = ['ip' => $ip, 'note' => $note];
            }
        }

        return $result;
    }

    public static function view(string $view): string
    {
        return $view !== '' && strlen($view) <= 100 && preg_match(AdminIpAccessModule::VIEW_PATTERN, $view) === 1
            ? $view
            : AdminIpAccessModule::DEFAULT_VIEW;
    }

    /**
     * Rebuilds the list from its cached form, or null when the entry is missing or has another shape.
     */
    public static function fromCache(mixed $cached): ?self
    {
        if (!is_array($cached)
            || !is_bool($cached['enabled'] ?? null)
            || !is_string($cached['view'] ?? null)
            || !is_array($cached['addresses'] ?? null)
            || !is_array($cached['subnets'] ?? null)) {
            return null;
        }

        $addresses = [];

        foreach ($cached['addresses'] as $address) {
            if (!is_string($address)) {
                return null;
            }

            $addresses[$address] = true;
        }

        $subnets = array_values(array_filter($cached['subnets'], is_string(...)));

        return count($subnets) === count($cached['subnets']) ? new self($cached['enabled'], self::view($cached['view']), $addresses, $subnets) : null;
    }

    /**
     * @return array{enabled: bool, view: string, addresses: list<string>, subnets: list<string>}
     */
    public function toCache(): array
    {
        return [
            'enabled' => $this->enabled,
            'view' => $this->view,
            'addresses' => array_map(strval(...), array_keys($this->addresses)),
            'subnets' => $this->subnets,
        ];
    }

    public function isEmpty(): bool
    {
        return $this->addresses === [] && $this->subnets === [];
    }

    public function count(): int
    {
        return count($this->addresses) + count($this->subnets);
    }

    /**
     * An enabled list with no entry lets everyone in: locking every administrator out would be worse.
     */
    public function allows(?string $ip): bool
    {
        if (!$this->enabled || $this->isEmpty()) {
            return true;
        }

        if ($ip === null || $ip === '') {
            return false;
        }

        if (isset($this->addresses[$ip])) {
            return true;
        }

        return IpUtils::checkIp($ip, [...array_map(strval(...), array_keys($this->addresses)), ...$this->subnets]);
    }
}
