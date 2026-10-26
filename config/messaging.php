<?php

declare(strict_types=1);

return [
    'source' => env('SERVICE_NAME', 'catalog'),

    'consumer_group' => env('KAFKA_CONSUMER_GROUP_ID', env('SERVICE_NAME', 'catalog')),

    // Event type => handler. Every type listed here needs its topic in deploy/values.yaml → consumers.
    // catalog consumes nothing (ADR-0001).
    'handlers' => [],

    'max_attempts' => 3,

    'relay_batch_size' => 100,
];
