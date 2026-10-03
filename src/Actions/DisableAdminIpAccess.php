<?php

declare(strict_types=1);

namespace Wobqqq\AegisAdminIpAccess\Actions;

use Wobqqq\Aegis\Aegis;
use Wobqqq\Aegis\Support\Values;
use Wobqqq\AegisAdminIpAccess\AccessList;
use Wobqqq\AegisAdminIpAccess\AdminIpAccessModule;

final readonly class DisableAdminIpAccess
{
    public function handle(): void
    {
        $values = Aegis::settings(AdminIpAccessModule::KEY);

        // Only valid rows are kept, so that a list the rules would refuse cannot block the way back in.
        Aegis::save(AdminIpAccessModule::KEY, [
            'enabled' => false,
            'ips' => array_slice(AccessList::rows($values['ips'] ?? []), 0, AdminIpAccessModule::MAX_ENTRIES),
            'view' => AccessList::view(Values::string($values, 'view')),
        ]);
    }
}
