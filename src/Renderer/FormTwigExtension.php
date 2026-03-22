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
use Derafu\Form\Contract\Renderer\FormRendererInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaElementInterface;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

/**
 * Twig extension for rendering forms.
 *
 * This extension provides Twig functions for rendering forms and form elements.
 * It delegates the actual rendering to a FormRendererInterface implementation.
 */
final class FormTwigExtension extends AbstractExtension
{
    /**
     * Constructor.
     *
     * @param FormRendererInterface $renderer The form renderer to use.
     * @param string $charset The character encoding to use (default UTF-8).
     */
    public function __construct(
        private readonly FormRendererInterface $renderer,
        private readonly string $charset = 'UTF-8'
    ) {
    }

    /**
     * Returns the Twig functions provided by this extension.
     *
     * @return TwigFunction[] An array of Twig functions.
     */
    public function getFunctions(): array
    {
        return [
            // Standard functions (compatible with Symfony naming).
            new TwigFunction('form', [$this, 'renderForm']),
            new TwigFunction('form_body', [$this, 'renderFormBody']),
            new TwigFunction('form_start', [$this, 'renderFormStart']),
            new TwigFunction('form_end', [$this, 'renderFormEnd']),
            new TwigFunction('form_label', [$this, 'renderFormLabel']),
            new TwigFunction('form_errors', [$this, 'renderFormErrors']),
            new TwigFunction('form_widget', [$this, 'renderFormWidget']),
            new TwigFunction('form_help', [$this, 'renderFormHelp']),
            new TwigFunction('form_row', [$this, 'renderRow']),
            new TwigFunction('form_rest', [$this, 'renderRest']),
            new TwigFunction('form_enctype', [$this, 'renderEnctype']),

            // Custom functions.
            new TwigFunction('form_element', [$this, 'renderElement']),
            new TwigFunction('form_elements', [$this, 'renderElements']),
            new TwigFunction('form_csrf', [$this, 'renderCsrf']),
        ];
    }

    /**
     * Renders the entire form.
     *
     * @param FormInterface $form The form to render.
     * @param array $options Additional options for rendering.
     * @return Markup The rendered form HTML.
     */
    public function renderForm(FormInterface $form, array $options = []): Markup
    {
        return new Markup(
            $this->renderer->render($form, $options),
            $this->charset
        );
    }

    /**
     * Renders the form body: all fields with layout and CSRF token.
     *
     * @param FormInterface $form The form to render the body for.
     * @param array $options Additional options for rendering.
     * @return Markup The rendered form body HTML.
     */
    public function renderFormBody(FormInterface $form, array $options = []): Markup
    {
        return new Markup(
            $this->renderer->renderBody($form, $options),
            $this->charset
        );
    }

    /**
     * Renders the opening form tag.
     *
     * @param FormInterface $form The form to start rendering.
     * @param array $options Additional options for rendering.
     * @return Markup The rendered form start HTML.
     */
    public function renderFormStart(FormInterface $form, array $options = []): Markup
    {
        return new Markup(
            $this->renderer->renderStart($form, $options),
            $this->charset
        );
    }

    /**
     * Renders the closing form tag.
     *
     * @param FormInterface $form The form to end rendering.
     * @param array $options Additional options for rendering.
     * @return Markup The rendered form end HTML.
     */
    public function renderFormEnd(FormInterface $form, array $options = []): Markup
    {
        $html = $this->renderer->renderRest($form, $options);
        $html = $this->renderer->renderEnd($form, $options);

        return new Markup($html, $this->charset);
    }

    /**
     * Renders a label for a form field.
     *
     * @param FormFieldInterface $field The field to render the label for.
     * @param string|null $label Optional custom label text.
     * @param array $options Additional options for rendering.
     * @return Markup The rendered label HTML.
     */
    public function renderFormLabel(
        FormFieldInterface $field,
        ?string $label = null,
        array $options = []
    ): Markup {
        return new Markup(
            $this->renderer->renderLabel($field, $label, $options),
            $this->charset
        );
    }

    /**
     * Renders validation errors for a form field.
     *
     * @param FormFieldInterface $field The field to render errors for.
     * @param array $options Additional options for rendering.
     * @return Markup The rendered errors HTML.
     */
    public function renderFormErrors(
        FormFieldInterface $field,
        array $options = []
    ): Markup {
        return new Markup(
            $this->renderer->renderErrors($field, $options),
            $this->charset
        );
    }

    /**
     * Renders the widget (input element) for a form field.
     *
     * @param FormFieldInterface $field The field to render the widget for.
     * @param array $options Additional options for rendering.
     * @return Markup The rendered widget HTML.
     */
    public function renderFormWidget(
        FormFieldInterface $field,
        array $options = []
    ): Markup {
        return new Markup(
            $this->renderer->renderWidget($field, $options),
            $this->charset
        );
    }

    /**
     * Renders help text for a form field.
     *
     * @param FormFieldInterface $field The field to render help text for.
     * @param array $options Additional options for rendering.
     * @return Markup The rendered help text HTML.
     */
    public function renderFormHelp(
        FormFieldInterface $field,
        array $options = []
    ): Markup {
        return new Markup(
            $this->renderer->renderHelp($field, $options),
            $this->charset
        );
    }

    /**
     * Renders a complete form row (label, widget, errors, and help).
     *
     * @param FormFieldInterface $field The field to render.
     * @param array $options Additional options for rendering.
     * @return Markup The rendered field HTML.
     */
    public function renderRow(
        FormFieldInterface $field,
        array $options = []
    ): Markup {
        return new Markup(
            $this->renderer->renderRow($field, $options),
            $this->charset
        );
    }

    /**
     * Renders all remaining/unrendered fields in the form.
     *
     * @param FormInterface $form The form to render remaining fields for.
     * @param array $options Additional options for rendering.
     * @return Markup The rendered fields HTML.
     */
    public function renderRest(
        FormInterface $form,
        array $options = []
    ): Markup {
        return new Markup(
            $this->renderer->renderRest($form, $options),
            $this->charset
        );
    }

    /**
     * Renders the enctype attribute for a form.
     *
     * @param FormInterface $form The form to render the enctype for.
     * @return Markup The rendered enctype HTML.
     */
    public function renderEnctype(FormInterface $form): Markup
    {
        return new Markup(
            $this->renderer->renderEnctype($form),
            $this->charset
        );
    }

    /**
     * Renders a UI element.
     *
     * @param UiSchemaElementInterface $element The element to render.
     * @param FormInterface $form The form containing the element.
     * @param array $options Additional rendering options.
     * @return Markup The rendered HTML.
     */
    public function renderElement(
        UiSchemaElementInterface $element,
        FormInterface $form,
        array $options = []
    ): Markup {
        return new Markup($this->renderer->renderElement($element, $form, $options), $this->charset);
    }

    /**
     * Renders a collection of UI elements.
     *
     * @param array $elements The elements to render.
     * @param FormInterface $form The form containing the elements.
     * @param array $options Additional rendering options.
     * @return array The rendered HTML of each element.
     */
    public function renderElements(
        array $elements,
        FormInterface $form,
        array $options = []
    ): array {
        return $this->renderer->renderElements($elements, $form, $options);
    }

    /**
     * Renders the CSRF token for a form.
     *
     * @param FormInterface $form The form to render the CSRF token for.
     * @return Markup The rendered CSRF token HTML.
     */
    public function renderCsrf(FormInterface $form): Markup
    {
        return new Markup($this->renderer->renderCsrf($form), $this->charset);
    }
}
