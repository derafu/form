<?php

declare(strict_types=1);

return function (array $context = []): array {
    /** @var array{_t?: callable(string, array<string, mixed>=, string=): string, translator?: \Symfony\Contracts\Translation\TranslatorInterface} $context */
    return [
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
};
