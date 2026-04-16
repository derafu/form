<?php

declare(strict_types=1);

return [
    'schema' => [
        'type' => 'object',
        'properties' => [
            'tag' => ['type' => 'string'],
        ],
    ],
    'data' => ['tag' => 'nested'],
];
