<?php

declare(strict_types=1);

return [
    'schema' => [
        'type' => 'object',
        'properties' => [
            'email' => ['type' => 'string'],
        ],
    ],
    'options' => [
        'csrf_token_id' => 'authenticate',
    ],
];
