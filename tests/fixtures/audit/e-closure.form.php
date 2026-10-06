<?php

declare(strict_types=1);

return function (array $context = []): array {
    $t = fn (string $id): string => $context['_t']($id, [], 'contact');
    $id = $context['id'];

    return [
        'translationDomain' => 'contact',
        'schema' => [
            'type' => 'object',
            'properties' => [
                'a' => ['type' => 'string', 'title' => $t('Your name')],
                'b' => ['type' => 'string', 'title' => $context['_t']('Hello {name}', ['name' => 'x'], 'contact')],
            ],
        ],
        'uischema' => [
            'type' => 'VerticalLayout',
            'elements' => [
                [
                    'type' => 'Control',
                    'scope' => '#/properties/a',
                    'options' => ['attr' => ['title' => $context['_t']('days', [], 'contact')]],
                ],
                [
                    'type' => 'Control',
                    'scope' => '#/properties/b',
                    'options' => ['attr' => ['title' => $context['_t']($id)]],
                ],
                [
                    'type' => 'Control',
                    'scope' => '#/properties/b',
                    'options' => ['attr' => ['title' => $context['_t']('Good day')]],
                ],
            ],
        ],
    ];
};
