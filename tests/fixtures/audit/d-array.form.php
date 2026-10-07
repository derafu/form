<?php

declare(strict_types=1);

return [
    'options' => ['translation_domain' => 'contact'],
    'schema' => [
        'type' => 'object',
        'properties' => [
            'tags' => ['type' => 'string', 'title' => 'Tags', 'default' => 'Tags'],
            'size' => ['type' => 'integer', 'title' => 'Size ' . 'in cm'],
        ],
    ],
    'uischema' => [
        'type' => 'VerticalLayout',
        'elements' => [
            ['type' => 'Control', 'scope' => '#/properties/tags', 'options' => ['help' => 'Help text']],
        ],
    ],
];
