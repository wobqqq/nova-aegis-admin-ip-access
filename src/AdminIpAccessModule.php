<?php

declare(strict_types=1);

namespace Wobqqq\AegisAdminIpAccess;

use Closure;
use Override;
use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Contracts\Module;
use Wobqqq\Aegis\Settings\Field;
use Wobqqq\AegisAdminIpAccess\Rules\CoversCurrentIp;
use Wobqqq\AegisAdminIpAccess\Rules\IpOrSubnet;
use Wobqqq\AegisAdminIpAccess\Support\Message;

final readonly class AdminIpAccessModule implements Module
{
    public const string KEY = 'admin-ip-access';

    public const string DEFAULT_VIEW = 'aegis-admin-ip-access::denied';

    public const string VIEW_PATTERN = '/^(?:[A-Za-z0-9_-]+::)?[A-Za-z0-9_-]+(?:\.[A-Za-z0-9_-]+)*$/';

    public const int MAX_ENTRIES = 100;

    /**
     * @param Closure(): ?string $currentIp the address of the administrator using the Aegis page, null in the console
     */
    public function __construct(private Closure $currentIp)
    {
    }

    #[Override]
    public function key(): string
    {
        return self::KEY;
    }

    #[Override]
    public function label(): string
    {
        return Message::get('aegis-admin-ip-access::admin-ip-access.label');
    }

    #[Override]
    public function description(): string
    {
        return Message::get('aegis-admin-ip-access::admin-ip-access.description');
    }

    /**
     * The administrator's own address is preset, so that the first list saved lets them in.
     */
    #[Override]
    public function defaults(): array
    {
        $ip = $this->currentIp();

        return [
            'enabled' => false,
            'ips' => $ip === null ? [] : [['ip' => $ip, 'note' => '']],
            'view' => self::DEFAULT_VIEW,
        ];
    }

    #[Override]
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'ips' => ['present', 'array', 'max:' . self::MAX_ENTRIES, new CoversCurrentIp($this->currentIp())],
            'ips.*' => ['array:ip,note'],
            'ips.*.ip' => ['nullable', 'string', 'max:100', new IpOrSubnet()],
            'ips.*.note' => ['nullable', 'string', 'max:100'],
            'view' => ['required', 'string', 'max:100', 'regex:' . self::VIEW_PATTERN],
        ];
    }

    /**
     * @return list<Field>
     */
    #[Override]
    public function fields(): array
    {
        $field = static fn (string $name): string => Message::get('aegis-admin-ip-access::admin-ip-access.fields.' . $name);
        $ip = $this->currentIp();
        $help = static fn (string $name): string => Message::get('aegis-admin-ip-access::admin-ip-access.help.' . $name, ['max' => self::MAX_ENTRIES, 'ip' => (string)$ip]);

        return [
            Field::toggle('enabled', $field('enabled'), $help('enabled')),
            Field::table('ips', $field('ips'), [
                Field::text('ip', $field('ip'), placeholder: '203.0.113.7, 10.0.0.0/8, 2001:db8::/32'),
                Field::text('note', $field('note')),
            ], $ip === null ? $help('ips') : $help('ips') . ' ' . $help('current_ip')),
            Field::text('view', $field('view'), $help('view'), self::DEFAULT_VIEW),
        ];
    }

    #[Override]
    public function status(array $values): CheckResult
    {
        $list = AccessList::fromArray($values);
        $label = $this->label();

        if (!$list->enabled) {
            return CheckResult::warn(self::KEY, $label, Message::get('aegis-admin-ip-access::admin-ip-access.status.off'));
        }

        if ($list->isEmpty()) {
            return CheckResult::warn(self::KEY, $label, Message::get('aegis-admin-ip-access::admin-ip-access.status.empty'));
        }

        return CheckResult::pass(self::KEY, $label, trans_choice('aegis-admin-ip-access::admin-ip-access.status.on', $list->count()));
    }

    private function currentIp(): ?string
    {
        $ip = ($this->currentIp)();

        return is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : null;
    }
}
