<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm\Fixture;

use Derafu\Form\Contract\Processor\UiSchemaRuleEvaluatorInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaRuleInterface;

final class InactiveRuleEvaluator implements UiSchemaRuleEvaluatorInterface
{
    public int $calls = 0;

    public function isActive(UiSchemaRuleInterface $rule, array $data): bool
    {
        ++$this->calls;

        return false;
    }
}
