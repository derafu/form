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

use Derafu\Form\Contract\FormFieldInterface;
use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Contract\Renderer\ElementRendererRegistryInterface;
use Derafu\Form\Contract\Renderer\FormRendererInterface;
use Derafu\Form\Contract\Renderer\WidgetRendererRegistryInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaElementInterface;
use Derafu\Renderer\Contract\RendererInterface;

/**
 * Form renderer implementation.
 */
final class FormRenderer implements FormRendererInterface
{
    public function __construct(
        private readonly RendererInterface $renderer,
        private readonly ElementRendererRegistryInterface $elementRendererRegistry,
        private readonly WidgetRendererRegistryInterface $widgetRendererRegistry
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function render(FormInterface $form, array $options = []): string
    {
        $context = [
            'form' => $form,
            'options' => $options,
        ];

        return $this->renderer->render('form/form', $context);
    }

    /**
     * {@inheritDoc}
     */
    public function renderStart(
        FormInterface $form,
        array $options = []
    ): string {
        $formOptions = $form->getOptions()?->toArray() ?? [];
        $options = array_merge(
            array_filter($formOptions, static fn ($v) => $v !== null && $v !== ''),
            $options,
        );

        $context = [
            'form' => $form,
            'options' => $options,
        ];

        return $this->renderer->render('form/form_start', $context);
    }

    /**
     * {@inheritDoc}
     */
    public function renderEnd(
        FormInterface $form,
        array $options = []
    ): string {
        $context = [
            'form' => $form,
            'options' => $options,
        ];

        return $this->renderer->render('form/form_end', $context);
    }

    /**
     * {@inheritDoc}
     */
    public function renderLabel(
        FormFieldInterface $field,
        ?string $label = null,
        array $options = []
    ): string {
        $context = [
            'field' => $field,
            'label' => $label
                ?? $field->getControl()->getLabel()
                ?? $field->getProperty()->getTitle()
                ?? $field->getProperty()->getName()
            ,
            'options' => $options,
        ];

        return $this->renderer->render('form/label', $context);
    }

    /**
     * {@inheritDoc}
     */
    public function renderErrors(
        FormFieldInterface $field,
        array $options = []
    ): string {
        $context = [
            'field' => $field,
            'errors' => $field->getErrors(),
            'options' => $options,
        ];

        return $this->renderer->render('form/errors', $context);
    }

    /**
     * {@inheritDoc}
     */
    public function renderWidget(
        FormFieldInterface $field,
        array $options = []
    ): string {
        $renderer = $this->widgetRendererRegistry->getRenderer(
            $field->getWidget()->getType()
        );

        $html = $renderer->render($field, array_merge([
            'renderer' => $this,
        ], $options));

        $field->setRendered();

        return $html;
    }

    /**
     * {@inheritDoc}
     */
    public function renderHelp(
        FormFieldInterface $field,
        array $options = []
    ): string {
        $context = [
            'field' => $field,
            'help' => $field->getProperty()->getDescription(),
            'options' => $options,
        ];

        return $this->renderer->render('form/help', $context);
    }

    /**
     * {@inheritDoc}
     */
    public function renderRow(
        FormFieldInterface $field,
        array $options = []
    ): string {
        $options = array_merge([
            'floating_labels' => $field->getWidget()->supportsFloatingLabel(),
        ], $options);

        if (
            $options['floating_labels']
            && empty($options['attr']['placeholder'])
        ) {
            $options['attr']['placeholder'] = $field->getControl()->getLabel();
        }

        $context = [
            'field' => $field,
            'label' => $this->renderLabel(
                $field,
                $options['label'] ?? null,
                $options
            ),
            'errors' => $this->renderErrors($field, $options),
            'widget' => $this->renderWidget($field, $options),
            'help' => $this->renderHelp($field, $options),
            'options' => $options,
        ];

        return $this->renderer->render('form/row', $context);
    }

    /**
     * {@inheritDoc}
     */
    public function renderBody(
        FormInterface $form,
        array $options = []
    ): string {
        $html = $this->renderElement($form->getUiSchema(), $form, $options);

        $csrfProtection = $options['csrf_protection'] ?? true;
        if ($csrfProtection) {
            $html .= $this->renderCsrf($form);
        }

        return $html;
    }

    /**
     * {@inheritDoc}
     */
    public function renderRest(
        FormInterface $form,
        array $options = []
    ): string {
        $fields = $form->getFields();

        $html = '';
        foreach ($fields as $field) {
            if ($field->isRendered()) {
                continue;
            }
            $html .= $this->renderElement($field->getControl(), $form, $options);
        }

        return $html;
    }

    /**
     * {@inheritDoc}
     */
    public function renderEnctype(FormInterface $form): string
    {
        $fields = $form->getFields();

        foreach ($fields as $field) {
            if (($field->getControl()->getOptions()['type'] ?? null) === 'file') {
                return 'multipart/form-data';
            }
        }

        return 'application/x-www-form-urlencoded';
    }

    /**
     * {@inheritDoc}
     */
    public function renderElement(
        UiSchemaElementInterface $element,
        FormInterface $form,
        array $options = []
    ): string {
        $renderer = $this->elementRendererRegistry->getRenderer(
            $element->getType()
        );

        return $renderer->render($element, $form, array_merge([
            'renderer' => $this,
        ], $options));
    }

    /**
     * {@inheritDoc}
     */
    public function renderElements(
        array $elements,
        FormInterface $form,
        array $options = []
    ): array {
        $html = [];
        foreach ($elements as $element) {
            $html[] = $this->renderElement($element, $form, $options);
        }

        return $html;
    }

    /**
     * {@inheritDoc}
     */
    public function renderCsrf(FormInterface $form): string
    {
        $context = [
            'form' => $form,
        ];

        return $this->renderer->render('form/csrf', $context);
    }

    /**
     * {@inheritDoc}
     */
    public function getRenderer(): RendererInterface
    {
        return $this->renderer;
    }
}
