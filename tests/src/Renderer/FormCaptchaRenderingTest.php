<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm\Renderer;

use Derafu\Form\Contract\Renderer\FormRendererInterface;
use Derafu\Form\Factory\FormRendererFactory;
use Derafu\Form\Form;
use Derafu\Form\Renderer\FormRenderer;
use Derafu\Form\Renderer\FormTwigExtension;
use Derafu\TestsForm\Captcha\InMemoryCaptchaProvider;
use Derafu\TestsForm\Csrf\InMemoryCsrfTokenManager;
use Derafu\Translation\Exception\Core\TranslatableLogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;
use Twig\Error\RuntimeError;

/**
 * The captcha of a form is rendered when the form asks for it and the
 * application has one, with the id of the form, before the CSRF token.
 */
#[CoversClass(FormRenderer::class)]
#[CoversClass(FormTwigExtension::class)]
#[UsesClass(\Derafu\Form\Abstract\AbstractPropertySchema::class)]
#[UsesClass(\Derafu\Form\Abstract\AbstractUiSchemaElement::class)]
#[UsesClass(\Derafu\Form\Factory\FormRendererFactory::class)]
#[UsesClass(\Derafu\Form\Factory\FormUiSchemaFactory::class)]
#[UsesClass(\Derafu\Form\Factory\PropertySchemaFactory::class)]
#[UsesClass(\Derafu\Form\Factory\UiSchemaElementFactory::class)]
#[UsesClass(\Derafu\Form\Form::class)]
#[UsesClass(\Derafu\Form\FormField::class)]
#[UsesClass(\Derafu\Form\Options\FormAttributes::class)]
#[UsesClass(\Derafu\Form\Options\FormOptions::class)]
#[UsesClass(\Derafu\Form\Renderer\ElementRendererProvider::class)]
#[UsesClass(\Derafu\Form\Renderer\ElementRendererRegistry::class)]
#[UsesClass(\Derafu\Form\Renderer\Element\ControlRenderer::class)]
#[UsesClass(\Derafu\Form\Renderer\Element\VerticalLayoutRenderer::class)]
#[UsesClass(\Derafu\Form\Renderer\Support\InputActionResolver::class)]
#[UsesClass(\Derafu\Form\Renderer\WidgetRendererProvider::class)]
#[UsesClass(\Derafu\Form\Renderer\WidgetRendererRegistry::class)]
#[UsesClass(\Derafu\Form\Renderer\Widget\InputWidgetRenderer::class)]
#[UsesClass(\Derafu\Form\Renderer\Widget\RadioWidgetRenderer::class)]
#[UsesClass(\Derafu\Form\Renderer\Widget\SliderWidgetRenderer::class)]
#[UsesClass(\Derafu\Form\Rules\FormRules::class)]
#[UsesClass(\Derafu\Form\Schema\FormSchema::class)]
#[UsesTrait(\Derafu\Form\Schema\ObjectSchemaTrait::class)]
#[UsesClass(\Derafu\Form\Schema\StringSchema::class)]
#[UsesClass(\Derafu\Form\UiSchema\Control::class)]
#[UsesClass(\Derafu\Form\UiSchema\VerticalLayout::class)]
#[UsesClass(\Derafu\Form\Widget\Widget::class)]
#[UsesClass(\Derafu\Form\Widget\WidgetFactory::class)]
final class FormCaptchaRenderingTest extends TestCase
{
    private function renderer(?InMemoryCaptchaProvider $captcha): FormRendererInterface
    {
        return FormRendererFactory::create([
            'csrf_token_manager' => new InMemoryCsrfTokenManager(),
            'captcha_provider' => $captcha,
        ]);
    }

    private function form(string $name = '', bool $captcha = true): Form
    {
        return Form::fromArray([
            'schema' => [
                'name' => $name,
                'type' => 'object',
                'properties' => ['email' => ['type' => 'string', 'title' => 'Email']],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [['type' => 'Control', 'scope' => '#/properties/email']],
            ],
            'options' => ['captcha_protection' => $captcha],
        ]);
    }

    #[Test]
    public function aFormThatIsProtectedWithTheCaptchaHasItWithItsId(): void
    {
        $html = $this->renderer(new InMemoryCaptchaProvider())->render($this->form('contact'));

        $this->assertStringContainsString('<div class="test-captcha" data-form="contact"></div>', $html);
    }

    #[Test]
    public function theCaptchaIsAfterTheFieldsAndBeforeTheCsrfToken(): void
    {
        $html = $this->renderer(new InMemoryCaptchaProvider())->render($this->form());

        $captcha = strpos($html, 'test-captcha');
        $this->assertGreaterThan(strpos($html, 'name="email"'), $captcha);
        $this->assertLessThan(strpos($html, 'name="_token"'), $captcha);
    }

    #[Test]
    public function theBodyHasTheCaptchaToo(): void
    {
        $html = $this->renderer(new InMemoryCaptchaProvider())->renderBody($this->form());

        $this->assertStringContainsString('test-captcha', $html);
    }

    #[Test]
    public function aFormThatIsNotProtectedHasNoneEvenIfTheApplicationHasOne(): void
    {
        $renderer = $this->renderer(new InMemoryCaptchaProvider());

        $this->assertSame('', $renderer->renderCaptcha($this->form(captcha: false)));
        $this->assertStringNotContainsString('test-captcha', $renderer->render($this->form(captcha: false)));
    }

    #[Test]
    public function aFormThatIsProtectedCanNotBeRenderedWithoutACaptchaInTheApplication(): void
    {
        $this->expectException(TranslatableLogicException::class);
        $this->expectExceptionMessage('The form "contact" is protected with a captcha, but the application has none.');

        $this->renderer(null)->renderCaptcha($this->form('contact'));
    }

    #[Test]
    public function theErrorSaysWhatToConfigure(): void
    {
        try {
            $this->renderer(null)->renderCaptcha($this->form('contact'));
            $this->fail('A protected form was rendered without a captcha.');
        } catch (TranslatableLogicException $e) {
            $this->assertStringContainsString('CAPTCHA_PROVIDER=altcha', $e->getMessage());
            $this->assertStringContainsString('"captcha_protection"', $e->getMessage());
        }
    }

    #[Test]
    public function aCaptchaThatIsNotAvailableIsTheSameAsNone(): void
    {
        $this->expectException(TranslatableLogicException::class);

        $this->renderer(new InMemoryCaptchaProvider(available: false))->renderCaptcha($this->form());
    }

    #[Test]
    public function theTemplateOfTheFormFailsWithTheSameExceptionWhenThereIsNoCaptcha(): void
    {
        try {
            $this->renderer(null)->render($this->form('contact'));
            $this->fail('A protected form was rendered without a captcha.');
        } catch (RuntimeError $e) {
            $this->assertInstanceOf(TranslatableLogicException::class, $e->getPrevious());
        }
    }

    #[Test]
    public function anApplicationThatDecidedNotToHaveACaptchaRendersNothingAndThereIsNoError(): void
    {
        $renderer = $this->renderer(new InMemoryCaptchaProvider(available: false, disabled: true));

        $this->assertSame('', $renderer->renderCaptcha($this->form()));
        $this->assertStringNotContainsString('test-captcha', $renderer->render($this->form()));
    }

    #[Test]
    public function aFormThatIsNotProtectedNeedsNoCaptchaInTheApplication(): void
    {
        $this->assertSame('', $this->renderer(null)->renderCaptcha($this->form(captcha: false)));
        $this->assertStringNotContainsString('test-captcha', $this->renderer(null)->render($this->form(captcha: false)));
    }
}
