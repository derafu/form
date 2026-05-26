<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Renderer;

use Derafu\Form\Contract\Processor\UiSchemaRuleEvaluatorInterface;
use Derafu\Form\Contract\Renderer\ElementRendererProviderInterface;
use Derafu\Form\Renderer\Element\CategorizationRenderer;
use Derafu\Form\Renderer\Element\ControlRenderer;
use Derafu\Form\Renderer\Element\GroupRenderer;
use Derafu\Form\Renderer\Element\HorizontalLayoutRenderer;
use Derafu\Form\Renderer\Element\LabelRenderer;
use Derafu\Form\Renderer\Element\VerticalLayoutRenderer;

/**
 * Element renderer provider.
 */
final class ElementRendererProvider implements ElementRendererProviderInterface
{
    /**
     * @param UiSchemaRuleEvaluatorInterface|null $ruleEvaluator Optional
     * evaluator forwarded to ControlRenderer for initial render-state
     * computation. When null, rule evaluation is skipped (backwards compatible).
     */
    public function __construct(
        private readonly ?UiSchemaRuleEvaluatorInterface $ruleEvaluator = null,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getRenderers(): array
    {
        return [
            'Control' => new ControlRenderer($this->ruleEvaluator),
            'Categorization' => new CategorizationRenderer(),
            'Group' => new GroupRenderer(),
            'HorizontalLayout' => new HorizontalLayoutRenderer(),
            'Label' => new LabelRenderer(),
            'VerticalLayout' => new VerticalLayoutRenderer(),
        ];
    }
}
