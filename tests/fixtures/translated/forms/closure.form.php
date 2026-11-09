<?php

declare(strict_types=1);

return function (array $context = []): array {
    /** @var array{_t: callable(string, array<string, mixed>=, string=): string, user: string} $context */
    return [
        'options' => ['translation_domain' => 'contact'],
        'schema' => [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'title' => $context['_t']('Hello {name}', ['name' => $context['user']], 'contact'),
                    'description' => 'Your name',
                ],
            ],
        ],
    ];
};
