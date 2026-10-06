<?php

declare(strict_types=1);

return fn (array $context = []): array => [
    'schema' => [
        'type' => 'object',
        'properties' => [
            'name' => ['type' => 'string', 'title' => $context['_t']('Hello {name}', ['name' => 'Ana'], 'contact')],
        ],
    ],
];
