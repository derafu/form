<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Factory;

use Derafu\Form\Contract\Factory\FormUiSchemaFactoryInterface;
use Derafu\Form\Contract\UiSchema\FormUiSchemaInterface;
use Derafu\Form\UiSchema\Categorization;
use Derafu\Form\UiSchema\Group;
use Derafu\Form\UiSchema\HorizontalLayout;
use Derafu\Form\UiSchema\VerticalLayout;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;

/**
 * Factory class for creating FormUiSchema instances.
 */
final class FormUiSchemaFactory implements FormUiSchemaFactoryInterface
{
    /**
     * Valid types of UI schema.
     *
     * @var string[]
     */
    private const VALID_TYPES = [
        'Categorization' => Categorization::class,
        'Group' => Group::class,
        'HorizontalLayout' => HorizontalLayout::class,
        'VerticalLayout' => VerticalLayout::class,
    ];

    /**
     * {@inheritDoc}
     */
    public static function create(array $definition): FormUiSchemaInterface
    {
        $type = $definition['type'] ?? 'VerticalLayout';

        if (!isset(self::VALID_TYPES[$type])) {
            throw new InvalidArgumentException([
                'Invalid UI schema type: {type}. Valid types are: {types}.',
                'type' => $type,
                'types' => implode(', ', self::VALID_TYPES),
            ]);
        }

        $class = self::VALID_TYPES[$type];

        return $class::fromArray($definition);
    }
}
