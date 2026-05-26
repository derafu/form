<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\UiSchema;

/**
 * Logical operator for composite rule conditions.
 *
 * Matches the JSON Forms rule specification:
 * @see https://jsonforms.io/docs/uischema/rules
 */
enum UiSchemaCompositeConditionType: string
{
    /**
     * All nested conditions must be satisfied.
     */
    case AND = 'AND';

    /**
     * At least one nested condition must be satisfied.
     */
    case OR = 'OR';
}
