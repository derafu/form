<?php

declare(strict_types=1);

return [
    'schema' => [
        'type' => 'object',
        'properties' => [
            'name' => ['type' => 'string'],
            'age' => ['type' => 'integer'],
        ],
    ],
    'data' => [
        'name' => 'from-file',
        'age' => 10,
    ],
];
