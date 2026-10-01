<?php

declare(strict_types=1);

return [
    'label' => 'Admin IP Access',
    'description' => "Opens Nova (its pages, its API and the tools' routes) only to the listed IP addresses and subnets. Any other address gets a 403.",

    'fields' => [
        'enabled' => 'Restrict Nova to the listed addresses',
        'ips' => 'Allowed addresses',
        'ip' => 'IP address or subnet',
        'note' => 'Note',
        'view' => 'Denied page view',
    ],

    'help' => [
        'enabled' => 'While the list is empty every address is let in, so a mistake never locks every administrator out.',
        'ips' => 'IPv4 or IPv6 addresses and CIDR subnets, up to :max entries.',
        'current_ip' => 'Your address is :ip: an enabled list has to include it.',
        'view' => "The Blade view answered with a 403 to any other address. A view that does not exist falls back to the module's page.",
    ],

    'status' => [
        'off' => 'Admin IP Access is off: Nova is open to every address.',
        'empty' => 'Admin IP Access is on, but the list is empty: every address is let in.',
        'on' => 'Nova is open to one address or subnet only.|Nova is open to :count addresses and subnets only.',
    ],

    'checks' => [
        'routes' => [
            'label' => 'Nova routes behind Admin IP Access',
            'off' => 'Admin IP Access is off.',
            'pass' => "Every Nova route checks the visitor's address.",
            'fail' => ":count Nova routes skip the address check: :routes. Register them with Nova's middleware groups.",
        ],
    ],

    'validation' => [
        'ip' => '":value" is not an IP address or a subnet such as 192.0.2.0/24.',
        'current_ip' => 'The list does not include your address :ip: saving it would lock you out of Nova.',
    ],

    'denied' => [
        'title' => 'Access denied',
        'message' => 'Your address is not allowed to open this page.',
        'your_ip' => 'Your address: :ip',
    ],
];
