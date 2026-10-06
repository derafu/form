<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm\Processor;

use Derafu\DataProcessor\ProcessorFactory;
use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Form;
use Derafu\Form\Processor\FormDataProcessor;
use Derafu\Form\Processor\FormRulesResolver;
use Derafu\Form\Processor\ProcessResult;
use Derafu\TestsForm\Csrf\InMemoryCsrfTokenManager;
use Derafu\Translation\Exception\Core\TranslatableLogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

/**
 * The CSRF token of a form that is protected is checked when its data is
 * processed, and it is not part of the data.
 */
#[CoversClass(FormDataProcessor::class)]
#[CoversClass(ProcessResult::class)]
#[CoversClass(Form::class)]
#[UsesClass(\Derafu\Form\Abstract\AbstractPropertySchema::class)]
#[UsesClass(\Derafu\Form\Abstract\AbstractUiSchemaElement::class)]
#[UsesClass(\Derafu\Form\Factory\FormUiSchemaFactory::class)]
#[UsesClass(\Derafu\Form\Factory\PropertySchemaFactory::class)]
#[UsesClass(\Derafu\Form\Factory\UiSchemaElementFactory::class)]
#[UsesClass(\Derafu\Form\FormField::class)]
#[UsesClass(\Derafu\Form\Options\FormOptions::class)]
#[UsesClass(\Derafu\Form\Processor\FormRulesResolver::class)]
#[UsesClass(\Derafu\Form\Rules\FormRules::class)]
#[UsesClass(\Derafu\Form\Schema\FormSchema::class)]
#[UsesTrait(\Derafu\Form\Schema\ObjectSchemaTrait::class)]
#[UsesClass(\Derafu\Form\Schema\StringSchema::class)]
#[UsesClass(\Derafu\Form\UiSchema\Control::class)]
#[UsesClass(\Derafu\Form\UiSchema\VerticalLayout::class)]
#[UsesClass(\Derafu\Form\Widget\Widget::class)]
#[UsesClass(\Derafu\Form\Widget\WidgetFactory::class)]
final class FormDataProcessorCsrfTest extends TestCase
{
    private InMemoryCsrfTokenManager $manager;

    private FormDataProcessor $processor;

    protected function setUp(): void
    {
        $this->manager = new InMemoryCsrfTokenManager();
        $this->processor = $this->processor();
    }

    private function processor(?Translator $translator = null, bool $withManager = true): FormDataProcessor
    {
        return new FormDataProcessor(
            new FormRulesResolver(),
            (new ProcessorFactory())->create(),
            translator: $translator,
            locale: $translator?->getLocale(),
            csrfTokenManager: $withManager ? $this->manager : null,
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    private function form(string $name = '', array $options = []): Form
    {
        return Form::fromArray([
            'schema' => [
                'name' => $name,
                'type' => 'object',
                'properties' => ['email' => ['type' => 'string', 'format' => 'email', 'title' => 'Email']],
                'required' => ['email'],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [['type' => 'Control', 'scope' => '#/properties/email']],
            ],
            'options' => $options,
        ]);
    }

    #[Test]
    public function aValidTokenIsAcceptedAndItIsNotPartOfTheData(): void
    {
        $token = $this->manager->getToken('form');

        $result = $this->processor->process($this->form(), [
            'email' => 'ana@example.com',
            FormInterface::CSRF_FIELD => $token,
        ]);

        $this->assertTrue($result->isValid());
        $this->assertSame([], $result->getFormErrors());
        $this->assertSame(['email' => 'ana@example.com'], $result->getProcessedData());
    }

    #[Test]
    public function aMissingTokenIsNotValid(): void
    {
        $result = $this->processor->process($this->form(), ['email' => 'ana@example.com']);

        $this->assertFalse($result->isValid());
        $this->assertSame(
            ['The form is not valid or has expired. Reload the page and try again.'],
            $result->getFormErrors()
        );
        $this->assertSame([], $result->getErrors());
    }

    #[Test]
    public function aTokenThatIsNotTheOneOfTheFormIsNotValid(): void
    {
        $this->manager->getToken('form');

        $result = $this->processor->process($this->form(), [
            'email' => 'ana@example.com',
            FormInterface::CSRF_FIELD => 'forged',
        ]);

        $this->assertFalse($result->isValid());
        $this->assertCount(1, $result->getFormErrors());
    }

    #[Test]
    public function aTokenThatIsNotAStringIsNotValid(): void
    {
        $this->manager->getToken('form');

        $result = $this->processor->process($this->form(), [
            'email' => 'ana@example.com',
            FormInterface::CSRF_FIELD => ['x'],
        ]);

        $this->assertFalse($result->isValid());
        $this->assertCount(1, $result->getFormErrors());
    }

    #[Test]
    public function theTokenOfAnotherFormIsNotValid(): void
    {
        $other = $this->manager->getToken('login');

        $result = $this->processor->process($this->form('contact'), [
            'email' => 'ana@example.com',
            FormInterface::CSRF_FIELD => $other,
        ]);

        $this->assertFalse($result->isValid());
    }

    #[Test]
    public function theTokenIsAskedForTheNameOfTheSchemaOfTheForm(): void
    {
        $token = $this->manager->getToken('contact');

        $result = $this->processor->process($this->form('contact'), [
            'email' => 'ana@example.com',
            FormInterface::CSRF_FIELD => $token,
        ]);

        $this->assertTrue($result->isValid());
    }

    #[Test]
    public function theErrorsOfTheFieldsAreReportedAlongWithTheTokenOnes(): void
    {
        $result = $this->processor->process($this->form(), ['email' => 'not-an-email']);

        $this->assertFalse($result->isValid());
        $this->assertCount(1, $result->getFormErrors());
        $this->assertTrue($result->hasFieldErrors('email'));
    }

    #[Test]
    public function aFormThatIsNotProtectedIsProcessedWithoutTokenAndWithoutManager(): void
    {
        $form = $this->form(options: ['csrf_protection' => false]);

        $result = $this->processor(withManager: false)->process($form, ['email' => 'ana@example.com']);

        $this->assertTrue($result->isValid());
        $this->assertSame([], $result->getFormErrors());
    }

    #[Test]
    public function aFormThatIsProtectedCanNotBeProcessedWithoutAManager(): void
    {
        $this->expectException(TranslatableLogicException::class);
        $this->expectExceptionMessage('The form "contact" is protected with a CSRF token, but there is no CSRF token manager.');

        $this->processor(withManager: false)->process($this->form('contact'), ['email' => 'ana@example.com']);
    }

    #[Test]
    public function theMessageIsTranslatedWhenThereIsATranslator(): void
    {
        $translator = new Translator('es');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', [
            'The form is not valid or has expired. Reload the page and try again.' => 'El formulario ha expirado.',
        ], 'es', 'errors');

        $result = $this->processor($translator)->process($this->form(), ['email' => 'ana@example.com']);

        $this->assertSame(['El formulario ha expirado.'], $result->getFormErrors());
    }
}
