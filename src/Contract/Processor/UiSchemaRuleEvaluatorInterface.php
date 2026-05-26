<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Contract\Processor;

use Derafu\Form\Contract\UiSchema\UiSchemaRuleInterface;

/**
 * Evaluates UI schema rules against a data set.
 *
 * A rule is considered "active" when its effect and condition together result
 * in the control being visible and interactive:
 *
 *   SHOW    → active when condition IS met.
 *   HIDE    → active when condition is NOT met.
 *   ENABLE  → active when condition IS met.
 *   DISABLE → active when condition is NOT met.
 *
 * This contract is shared between FormDataProcessor (server-side processing)
 * and ControlRenderer (initial render state calculation).
 */
interface UiSchemaRuleEvaluatorInterface
{
    /**
     * Returns true when the rule allows the field to be active.
     *
     * @param UiSchemaRuleInterface $rule The rule to evaluate.
     * @param array $data The data to evaluate the rule against (associative
     * array of field names to values, e.g. from FormData::all() or $_POST).
     * @return bool True if the rule allows the field to be active.
     */
    public function isActive(UiSchemaRuleInterface $rule, array $data): bool;
}
