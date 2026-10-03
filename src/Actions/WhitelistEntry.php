<?php

declare(strict_types=1);

namespace Wobqqq\AegisAdminIpAccess\Actions;

use InvalidArgumentException;
use Wobqqq\AegisAdminIpAccess\Support\Ip;

final readonly class WhitelistEntry
{
    public string $ip;

    public string $note;

    public function __construct(string $ip, string $note = '')
    {
        $this->ip = Ip::normalize($ip) ?? throw new InvalidArgumentException(sprintf('"%s" is not an IP address or a subnet.', $ip));
        $this->note = mb_substr(trim($note), 0, 100);
    }
}
