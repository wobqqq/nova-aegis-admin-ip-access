<?php

declare(strict_types=1);

namespace Wobqqq\AegisAdminIpAccess\Support;

use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Parses whitelist entries: an IPv4 or IPv6 address, or a CIDR subnet of one.
 */
final class Ip
{
    /**
     * The entry in its canonical notation (`2001:db8::1`, `10.0.0.0/8`), or null when it is neither an address nor a subnet.
     */
    public static function normalize(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $parts = explode('/', strtolower(trim($value)), 2);
        $address = self::address($parts[0]);

        if ($address === null) {
            return null;
        }

        if (!isset($parts[1])) {
            return $address;
        }

        $mask = $parts[1];

        if ($mask === '' || strlen($mask) > 3 || !ctype_digit($mask) || (int)$mask > self::bits($address)) {
            return null;
        }

        return $address . '/' . (int)$mask;
    }

    /**
     * Whether a listed entry already lets in every address the given one stands for.
     *
     * @param list<string> $entries canonical entries
     */
    public static function covered(string $entry, array $entries): bool
    {
        [$address, $mask] = self::split($entry);

        foreach ($entries as $listed) {
            [$listedAddress, $listedMask] = self::split($listed);

            if (self::bits($address) === self::bits($listedAddress)
                && $mask >= $listedMask
                && IpUtils::checkIp($address, $listedAddress . '/' . $listedMask)) {
                return true;
            }
        }

        return false;
    }

    public static function isSubnet(string $entry): bool
    {
        return str_contains($entry, '/');
    }

    private static function address(string $value): ?string
    {
        if (filter_var($value, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        $packed = inet_pton($value);
        $address = $packed === false ? false : inet_ntop($packed);

        return $address === false ? null : $address;
    }

    private static function bits(string $address): int
    {
        return str_contains($address, ':') ? 128 : 32;
    }

    /**
     * @return array{0: string, 1: int}
     */
    private static function split(string $entry): array
    {
        $parts = explode('/', $entry, 2);

        return [$parts[0], isset($parts[1]) ? (int)$parts[1] : self::bits($parts[0])];
    }
}
