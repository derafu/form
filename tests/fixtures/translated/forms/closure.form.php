<?php

declare(strict_types=1);

return fn (array $context = []): array => [
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
