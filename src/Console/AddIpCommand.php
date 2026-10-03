<?php

declare(strict_types=1);

namespace Wobqqq\AegisAdminIpAccess\Console;

use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Wobqqq\AegisAdminIpAccess\Actions\AddIpToWhitelist;
use Wobqqq\AegisAdminIpAccess\Actions\WhitelistEntry;
use Wobqqq\AegisAdminIpAccess\Support\Ip;

final class AddIpCommand extends Command
{
    /** @var string */
    protected $signature = 'aegis:admin-ip-access:add-ip
        {ip : An IP address or a subnet such as 192.0.2.0/24}
        {--note= : A note shown next to the entry}';

    /** @var string */
    protected $description = 'Add an IP address or a subnet to the Admin IP Access whitelist, for an administrator it locked out.';

    public function handle(AddIpToWhitelist $addIp): int
    {
        $argument = $this->argument('ip');
        $ip = is_string($argument) ? trim($argument) : '';

        if (Ip::normalize($ip) === null) {
            $this->components->error(sprintf('%s is not an IP address or a subnet.', mb_substr($ip, 0, 100)));

            return self::FAILURE;
        }

        $note = $this->option('note');
        $entry = new WhitelistEntry($ip, is_string($note) ? $note : '');

        try {
            $added = $addIp->handle($entry);
        } catch (ValidationException $validationException) {
            $this->components->error(implode(' ', $validationException->validator->errors()->all()));

            return self::FAILURE;
        }

        $this->components->info(sprintf($added ? '%s has been added to the whitelist.' : '%s is already on the whitelist.', $entry->ip));

        return self::SUCCESS;
    }
}
