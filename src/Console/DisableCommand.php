<?php

declare(strict_types=1);

namespace Wobqqq\AegisAdminIpAccess\Console;

use Illuminate\Console\Command;
use Wobqqq\AegisAdminIpAccess\Actions\DisableAdminIpAccess;

final class DisableCommand extends Command
{
    /** @var string */
    protected $signature = 'aegis:admin-ip-access:disable';

    /** @var string */
    protected $description = 'Turn Admin IP Access off and open Nova to every address, for an administrator it locked out.';

    public function handle(DisableAdminIpAccess $disable): int
    {
        $disable->handle();

        $this->components->info('Admin IP Access is off: Nova is open to every address.');

        return self::SUCCESS;
    }
}
