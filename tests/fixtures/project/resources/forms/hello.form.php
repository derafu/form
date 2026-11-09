<?php

declare(strict_types=1);

return function (array $context = []): array {
    /** @var array{_t: callable(string, array<string, mixed>=, string=): string} $context */
    return [
    'schema' => [
        'type' => 'object',
        'properties' => [
            'name' => ['type' => 'string', 'title' => $context['_t']('Hello {name}', ['name' => 'Ana'], 'contact')],
        ],
    ],
];
};
