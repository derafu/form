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
use Derafu\Form\Data\FormData;
use Derafu\Form\Factory\FormRendererFactory;
use Derafu\Form\Form;
use Derafu\Form\Renderer\FormRenderer;
use Derafu\Form\Renderer\FormTwigExtension;
use Derafu\TestsForm\Csrf\InMemoryCsrfTokenManager;
use Derafu\Translation\Exception\Core\TranslatableLogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;
use Twig\Error\RuntimeError;

/**
 * The CSRF token of a form that is protected is rendered with the token that
 * the manager gives for the form, and the errors of the form as a whole are
 * rendered before its fields.
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
#[UsesClass(\Derafu\Form\Data\FormData::class)]
final class FormCsrfRenderingTest extends TestCase
{
    private InMemoryCsrfTokenManager $manager;

    protected function setUp(): void
    {
        $this->manager = new InMemoryCsrfTokenManager();
    }

    private function renderer(bool $withManager = true): FormRendererInterface
    {
        return FormRendererFactory::create($withManager ? ['csrf_token_manager' => $this->manager] : []);
    }

    /**
     * @param array<string, mixed>|null $options
     * @param array<string>|null $formErrors
     */
    private function form(string $name = '', ?array $options = null, ?array $formErrors = null): Form
    {
        $definition = [
            'schema' => [
                'name' => $name,
                'type' => 'object',
                'properties' => ['email' => ['type' => 'string', 'title' => 'Email']],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [['type' => 'Control', 'scope' => '#/properties/email']],
            ],
        ];

        if ($options !== null) {
            $definition['options'] = $options;
        }

        $form = Form::fromArray($definition);

        return $formErrors === null ? $form : $form->withData(FormData::fromArray([]), null, $formErrors);
    }

    #[Test]
    public function theFormHasTheTokenOfTheManager(): void
    {
        $html = $this->renderer()->render($this->form());

        $token = $this->manager->getToken('form');
        $this->assertStringContainsString('<input type="hidden" name="_token" value="' . $token . '">', $html);
        $this->assertSame(32, strlen($token));
    }

    #[Test]
    public function theTokenIsTheOneOfTheNameOfTheSchema(): void
    {
        $html = $this->renderer()->render($this->form('contact'));

        $this->assertStringContainsString('value="' . $this->manager->getToken('contact') . '"', $html);
        $this->assertStringNotContainsString('value="' . $this->manager->getToken('form') . '"', $html);
    }

    #[Test]
    public function renderCsrfGivesTheFieldOfTheToken(): void
    {
        $html = $this->renderer()->renderCsrf($this->form());

        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString($this->manager->getToken('form'), $html);
    }

    #[Test]
    public function theBodyHasTheTokenAfterTheFields(): void
    {
        $html = $this->renderer()->renderBody($this->form());

        $this->assertGreaterThan(strpos($html, 'name="email"'), strpos($html, 'name="_token"'));
    }

    #[Test]
    public function aFormThatIsNotProtectedHasNoTokenAndNeedsNoManager(): void
    {
        $form = $this->form(options: ['csrf_protection' => false]);
        $renderer = $this->renderer(withManager: false);

        $this->assertStringNotContainsString('_token', $renderer->render($form));
        $this->assertSame('', $renderer->renderCsrf($form));
    }

    #[Test]
    public function aFormThatIsProtectedCanNotBeRenderedWithoutAManager(): void
    {
        $this->expectException(TranslatableLogicException::class);
        $this->expectExceptionMessage('The form "contact" is protected with a CSRF token, but there is no CSRF token manager.');

        $this->renderer(withManager: false)->renderCsrf($this->form('contact'));
    }

    #[Test]
    public function theTemplateOfTheFormFailsWithTheSameExceptionWhenThereIsNoManager(): void
    {
        try {
            $this->renderer(withManager: false)->render($this->form('contact'));
            $this->fail('The form was rendered without a CSRF token manager.');
        } catch (RuntimeError $e) {
            $this->assertInstanceOf(TranslatableLogicException::class, $e->getPrevious());
        }
    }

    #[Test]
    public function theErrorsOfTheFormAreRenderedBeforeItsFields(): void
    {
        $html = $this->renderer()->render($this->form(formErrors: ['The form has expired.']));

        $this->assertStringContainsString('The form has expired.', $html);
        $this->assertLessThan(strpos($html, 'name="email"'), strpos($html, 'The form has expired.'));
    }

    #[Test]
    public function theBodyHasTheErrorsOfTheFormBeforeItsFields(): void
    {
        $html = $this->renderer()->renderBody($this->form(formErrors: ['The form has expired.']));

        $this->assertLessThan(strpos($html, 'name="email"'), strpos($html, 'The form has expired.'));
    }

    #[Test]
    public function theErrorsOfTheFormAreEscaped(): void
    {
        $html = $this->renderer()->renderGlobalErrors($this->form(formErrors: ['<b>x</b>']));

        $this->assertStringContainsString('&lt;b&gt;x&lt;/b&gt;', $html);
        $this->assertStringNotContainsString('<b>x</b>', $html);
    }

    #[Test]
    public function aFormWithoutErrorsHasNoListOfThem(): void
    {
        $renderer = $this->renderer();

        $this->assertSame('', $renderer->renderGlobalErrors($this->form()));
        $this->assertStringNotContainsString('text-danger', $renderer->render($this->form()));
        $this->assertStringContainsString('text-danger', $renderer->renderGlobalErrors($this->form(formErrors: ['x'])));
    }
}
