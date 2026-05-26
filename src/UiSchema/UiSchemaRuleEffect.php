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
 * Defines the possible effects of a UI Schema rule.
 *
 * Follows the JSON Forms rule effect specification:
 * https://jsonforms.io/docs/uischema/rules
 */
enum UiSchemaRuleEffect: string
{
    /**
     * Show the element when the condition is met, hide it otherwise.
     */
    case SHOW = 'SHOW';

    /**
     * Hide the element when the condition is met, show it otherwise.
     */
    case HIDE = 'HIDE';

    /**
     * Enable the element when the condition is met, disable it otherwise.
     */
    case ENABLE = 'ENABLE';

    /**
     * Disable the element when the condition is met, enable it otherwise.
     */
    case DISABLE = 'DISABLE';
}
