<?php

declare(strict_types=1);

namespace Wobqqq\AegisAdminIpAccess\Console;

use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Wobqqq\Aegis\Aegis;
use Wobqqq\Aegis\Support\Values;
use Wobqqq\AegisAdminIpAccess\AccessList;
use Wobqqq\AegisAdminIpAccess\AdminIpAccessModule;
use Wobqqq\AegisAdminIpAccess\Support\Ip;

final class AddIpCommand extends Command
{
    /** @var string */
    protected $signature = 'aegis:admin-ip-access:add-ip
        {ip : An IP address or a subnet such as 192.0.2.0/24}
        {--note= : A note shown next to the entry}';

    /** @var string */
    protected $description = 'Add an IP address or a subnet to the Admin IP Access whitelist, for an administrator it locked out.';

    public function handle(): int
    {
        $argument = $this->argument('ip');
        $ip = Ip::normalize($argument);

        if ($ip === null) {
            $this->components->error(sprintf('%s is not an IP address or a subnet.', mb_substr(is_string($argument) ? trim($argument) : '', 0, 100)));

            return self::FAILURE;
        }

        $values = Aegis::settings(AdminIpAccessModule::KEY);
        $rows = AccessList::rows($values['ips'] ?? []);

        if (Ip::covered($ip, array_column($rows, 'ip'))) {
            $this->components->info(sprintf('%s is already on the whitelist.', $ip));

            return self::SUCCESS;
        }

        $note = $this->option('note');
        $rows[] = ['ip' => $ip, 'note' => is_string($note) ? mb_substr(trim($note), 0, 100) : ''];

        try {
            Aegis::save(AdminIpAccessModule::KEY, [
                'enabled' => AccessList::fromArray($values)->enabled,
                'ips' => $rows,
                'view' => AccessList::view(Values::string($values, 'view')),
            ]);
        } catch (ValidationException $e) {
            $this->components->error(implode(' ', $e->validator->errors()->all()));

            return self::FAILURE;
        }

        $this->components->info(sprintf('%s has been added to the whitelist.', $ip));

        return self::SUCCESS;
    }
}
