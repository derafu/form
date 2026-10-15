<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm\Renderer;

use Derafu\Form\Contract\FormFieldInterface;
use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Contract\Renderer\FormRendererInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaElementInterface;
use Derafu\Form\Renderer\FormTwigExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Twig\Markup;

/**
 * The functions of Twig give back what the renderer renders, marked as safe.
 */
#[CoversClass(FormTwigExtension::class)]
final class FormTwigExtensionTest extends TestCase
{
    /**
     * Each function of the extension, the method of the renderer that it calls
     * and the kind of the arguments that it takes.
     *
     * @return array<string, array{string, string, string}>
     */
    public static function functionsProvider(): array
    {
        return [
            'form' => ['form', 'renderForm', 'render'],
            'form_body' => ['form_body', 'renderFormBody', 'renderBody'],
            'form_start' => ['form_start', 'renderFormStart', 'renderStart'],
            'form_end' => ['form_end', 'renderFormEnd', 'renderEnd'],
            'form_label' => ['form_label', 'renderFormLabel', 'renderLabel'],
            'form_errors' => ['form_errors', 'renderFormErrors', 'renderErrors'],
            'form_widget' => ['form_widget', 'renderFormWidget', 'renderWidget'],
            'form_help' => ['form_help', 'renderFormHelp', 'renderHelp'],
            'form_row' => ['form_row', 'renderRow', 'renderRow'],
            'form_rest' => ['form_rest', 'renderRest', 'renderRest'],
            'form_enctype' => ['form_enctype', 'renderEnctype', 'renderEnctype'],
            'form_element' => ['form_element', 'renderElement', 'renderElement'],
            'form_csrf' => ['form_csrf', 'renderCsrf', 'renderCsrf'],
            'form_captcha' => ['form_captcha', 'renderCaptcha', 'renderCaptcha'],
            'form_global_errors' => ['form_global_errors', 'renderGlobalErrors', 'renderGlobalErrors'],
        ];
    }

    #[Test]
    public function shouldRegisterEveryFunctionWithThePrefix(): void
    {
        $extension = new FormTwigExtension($this->createStub(FormRendererInterface::class), 'UTF-8', 'x_');

        $names = array_map(fn ($function) => $function->getName(), $extension->getFunctions());

        $this->assertCount(16, $names);
        $this->assertContains('x_form', $names);
        $this->assertContains('x_form_elements', $names);
        $this->assertContains('x_form_global_errors', $names);
        foreach ($names as $name) {
            $this->assertStringStartsWith('x_form', $name);
        }
    }

    #[Test]
    #[DataProvider('functionsProvider')]
    public function shouldGiveBackWhatTheRendererRendersAsMarkup(string $function, string $method, string $rendererMethod): void
    {
        $renderer = $this->createStub(FormRendererInterface::class);
        $renderer->method($rendererMethod)->willReturn('<b>html</b>');
        $extension = new FormTwigExtension($renderer, 'ISO-8859-1');

        $form = $this->createStub(FormInterface::class);
        $field = $this->createStub(FormFieldInterface::class);
        $element = $this->createStub(UiSchemaElementInterface::class);
        $args = match ($method) {
            'renderFormLabel', 'renderFormErrors', 'renderFormWidget', 'renderFormHelp', 'renderRow' => [$field],
            'renderElement' => [$element, $form],
            default => [$form],
        };

        $result = $extension->$method(...$args);

        $this->assertInstanceOf(Markup::class, $result);
        $this->assertSame('<b>html</b>', (string) $result);
        $this->assertSame('ISO-8859-1', $result->getCharset());
        $this->assertNotSame('', $function);
    }

    #[Test]
    public function shouldGiveBackTheRenderedElementsAsTheyAre(): void
    {
        $renderer = $this->createMock(FormRendererInterface::class);
        $renderer->expects($this->once())->method('renderElements')->willReturn(['a', 'b']);

        $result = (new FormTwigExtension($renderer))->renderElements(
            [$this->createStub(UiSchemaElementInterface::class)],
            $this->createStub(FormInterface::class)
        );

        $this->assertSame(['a', 'b'], $result);
    }

    #[Test]
    public function shouldPassTheLabelAndTheOptionsToTheRenderer(): void
    {
        $field = $this->createStub(FormFieldInterface::class);
        $renderer = $this->createMock(FormRendererInterface::class);
        $renderer->expects($this->once())
            ->method('renderLabel')
            ->with($field, 'Name', ['class' => 'x'])
            ->willReturn('<label>');

        $result = (new FormTwigExtension($renderer))->renderFormLabel($field, 'Name', ['class' => 'x']);

        $this->assertSame('<label>', (string) $result);
    }
}
