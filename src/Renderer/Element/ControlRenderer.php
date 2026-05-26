<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Renderer\Element;

use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Contract\Processor\UiSchemaRuleEvaluatorInterface;
use Derafu\Form\Contract\Renderer\ElementRendererInterface;
use Derafu\Form\Contract\Renderer\FormRendererInterface;
use Derafu\Form\Contract\UiSchema\ControlInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaElementInterface;
use Derafu\Form\UiSchema\UiSchemaRuleEffect;
use InvalidArgumentException;

/**
 * Renderer for control elements.
 *
 * Controls are UI elements that render form fields (inputs, selects, etc).
 *
 * When a UiSchemaRuleEvaluator is provided, the renderer also evaluates the
 * control's rule against the form's current data to determine the initial
 * visibility and disabled state of the field:
 *
 *   - SHOW / HIDE rules → adds Bootstrap's "d-none" class when initially hidden.
 *   - ENABLE / DISABLE rules → adds "disabled" attribute when initially disabled.
 *
 * The rule is serialised as a "data-rule" attribute so that client-side JS
 * can react to field changes without a full page reload.
 */
final class ControlRenderer implements ElementRendererInterface
{
    /**
     * @param UiSchemaRuleEvaluatorInterface|null $ruleEvaluator Optional
     * evaluator for computing initial render state from UI schema rules.
     * When null the renderer skips rule evaluation entirely (backwards
     * compatible with code that instantiates ControlRenderer directly).
     */
    public function __construct(
        private readonly ?UiSchemaRuleEvaluatorInterface $ruleEvaluator = null,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function render(
        UiSchemaElementInterface $element,
        FormInterface $form,
        array $options = []
    ): string {
        // Check if the element is a control.
        if (!$element instanceof ControlInterface) {
            throw new InvalidArgumentException(sprintf(
                'Element must be an instance of %s in ControlRenderer, %s given.',
                ControlInterface::class,
                get_class($element)
            ));
        }

        // Get the main form renderer from options.
        $formRenderer = $options['renderer'] ?? null;
        if (!$formRenderer instanceof FormRendererInterface) {
            throw new InvalidArgumentException(
                'The "renderer" option in ControlRenderer must be an instance of FormRendererInterface.'
            );
        }

        // Find the field from the form using the property name.
        $propertyName = $element->getPropertyName();
        $field = $form->getField($propertyName);

        if (!$field) {
            throw new InvalidArgumentException(sprintf(
                'Field with property name "%s" not found in form.',
                $propertyName
            ));
        }

        // Merge control options with passed options.
        $fieldOptions = array_merge($element->getOptions(), $options);

        // Evaluate initial rule state when an evaluator is available.
        $rule = $element->getRule();
        if ($rule !== null && $this->ruleEvaluator !== null) {
            $data = $form->getData()?->all() ?? [];
            $initiallyActive = $this->ruleEvaluator->isActive($rule, $data);

            // Expose the rule definition so client-side JS can evaluate
            // subsequent changes without a page reload.
            $fieldOptions['rule'] = $rule->toArray();

            $effect = $rule->getEffect();

            // SHOW / HIDE → visibility rule.
            if (
                !$initiallyActive
                && ($effect === UiSchemaRuleEffect::SHOW || $effect === UiSchemaRuleEffect::HIDE)
            ) {
                $fieldOptions['rule_initially_hidden'] = true;
            }

            // ENABLE / DISABLE → editability rule.
            // Pass disabled via options['attr'] so every widget renderer picks
            // it up through their final array_merge($attrs, $options['attr']).
            if (
                !$initiallyActive
                && ($effect === UiSchemaRuleEffect::ENABLE || $effect === UiSchemaRuleEffect::DISABLE)
            ) {
                $fieldOptions['attr']['disabled'] = true;
            }
        }

        // Determine if we need to render a full field or just the widget.
        $renderMode = $options['render_mode'] ?? 'row';

        // Render based on the mode.
        if ($renderMode === 'widget') {
            // Render just the widget.
            return $formRenderer->renderWidget($field, $fieldOptions);
        } else {
            // Render the complete field.
            return $formRenderer->renderRow($field, $fieldOptions);
        }
    }
}
