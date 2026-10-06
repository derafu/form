<?php

declare(strict_types=1);

return fn (array $context = []): array => [
        'schema' => [
            'type' => 'object',
            'properties' => [
                'a' => [
                    'type' => 'string',
                    'title' => isset($context['_t']) ? $context['_t']('Hello {name}', ['name' => 'Ana'], 'contact') : 'no _t',
                    'description' => isset($context['translator']) ? $context['translator']::class : 'no translator',
                ],
            ],
        ],
    ];
