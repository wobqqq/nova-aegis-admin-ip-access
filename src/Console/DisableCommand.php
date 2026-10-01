<?php

declare(strict_types=1);

namespace Wobqqq\AegisAdminIpAccess\Console;

use Illuminate\Console\Command;
use Wobqqq\Aegis\Settings\SettingsRepository;
use Wobqqq\AegisAdminIpAccess\AccessList;
use Wobqqq\AegisAdminIpAccess\AdminIpAccessModule;

final class DisableCommand extends Command
{
    /** @var string */
    protected $signature = 'aegis:admin-ip-access:disable';

    /** @var string */
    protected $description = 'Turn Admin IP Access off and open Nova to every address, for an administrator it locked out.';

    public function handle(SettingsRepository $settings): int
    {
        $values = $settings->section(AdminIpAccessModule::KEY);

        // Only valid rows are kept, so that a list the rules would refuse cannot block the way back in.
        $settings->save(AdminIpAccessModule::KEY, [
            'enabled' => false,
            'ips' => array_slice(AccessList::rows($values['ips'] ?? []), 0, AdminIpAccessModule::MAX_ENTRIES),
            'view' => AccessList::view(is_string($values['view'] ?? null) ? $values['view'] : ''),
        ]);

        $this->components->info('Admin IP Access is off: Nova is open to every address.');

        return self::SUCCESS;
    }
}
