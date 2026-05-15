<?php

declare(strict_types=1);

return [

    'monitor_down' => [
        'mail' => [
            'subject' => 'Site Down: :url',
            'detected' => 'We detected that your monitored site is down.',
            'url' => 'URL: :url',
            'threshold' => 'The site failed to respond after :threshold consecutive check(s).',
        ],
    ],

    'monitor_up' => [
        'mail' => [
            'subject' => 'Site Recovered: :url',
            'recovered' => 'Good news! Your monitored site is back up.',
            'url' => 'URL: :url',
        ],
    ],

];
