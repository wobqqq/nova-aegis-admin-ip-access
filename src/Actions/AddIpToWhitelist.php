<?php

declare(strict_types=1);

namespace Wobqqq\AegisAdminIpAccess\Actions;

use Illuminate\Validation\ValidationException;
use Wobqqq\Aegis\Aegis;
use Wobqqq\Aegis\Support\Values;
use Wobqqq\AegisAdminIpAccess\AccessList;
use Wobqqq\AegisAdminIpAccess\AdminIpAccessModule;
use Wobqqq\AegisAdminIpAccess\Support\Ip;

final readonly class AddIpToWhitelist
{
    /**
     * @throws ValidationException when the list with the entry breaks the module's rules
     *
     * @return bool false when the address is already covered by the list
     */
    public function handle(WhitelistEntry $entry): bool
    {
        $values = Aegis::settings(AdminIpAccessModule::KEY);
        $rows = AccessList::rows($values['ips'] ?? []);

        if (Ip::covered($entry->ip, array_column($rows, 'ip'))) {
            return false;
        }

        $rows[] = ['ip' => $entry->ip, 'note' => $entry->note];

        Aegis::save(AdminIpAccessModule::KEY, [
            'enabled' => AccessList::fromArray($values)->enabled,
            'ips' => $rows,
            'view' => AccessList::view(Values::string($values, 'view')),
        ]);

        return true;
    }
}
