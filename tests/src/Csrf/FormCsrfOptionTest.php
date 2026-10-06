<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm\Csrf;

use Derafu\Form\Data\FormData;
use Derafu\Form\Form;
use Derafu\Form\Processor\ProcessResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

/**
 * What a form says about its CSRF protection (an option of the form that is on
 * unless the form turns it off) and about its errors as a whole, and how they
 * go from the result of processing it to the form that shows them.
 */
#[CoversClass(Form::class)]
#[CoversClass(ProcessResult::class)]
#[UsesClass(\Derafu\Form\Abstract\AbstractPropertySchema::class)]
#[UsesClass(\Derafu\Form\Abstract\AbstractUiSchemaElement::class)]
#[UsesClass(\Derafu\Form\Data\FormData::class)]
#[UsesClass(\Derafu\Form\Factory\FormUiSchemaFactory::class)]
#[UsesClass(\Derafu\Form\Factory\PropertySchemaFactory::class)]
#[UsesClass(\Derafu\Form\Factory\UiSchemaElementFactory::class)]
#[UsesClass(\Derafu\Form\FormField::class)]
#[UsesClass(\Derafu\Form\Options\FormOptions::class)]
#[UsesClass(\Derafu\Form\Rules\FormRules::class)]
#[UsesClass(\Derafu\Form\Schema\FormSchema::class)]
#[UsesTrait(\Derafu\Form\Schema\ObjectSchemaTrait::class)]
#[UsesClass(\Derafu\Form\Schema\StringSchema::class)]
#[UsesClass(\Derafu\Form\UiSchema\Control::class)]
#[UsesClass(\Derafu\Form\UiSchema\VerticalLayout::class)]
#[UsesClass(\Derafu\Form\Widget\Widget::class)]
#[UsesClass(\Derafu\Form\Widget\WidgetFactory::class)]
final class FormCsrfOptionTest extends TestCase
{
    /**
     * @param array<string, mixed> $options
     */
    private function form(string $name = '', ?array $options = null): Form
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

        return Form::fromArray($definition);
    }

    #[Test]
    public function aFormIsProtectedUnlessItTurnsItOff(): void
    {
        $this->assertTrue($this->form()->isCsrfProtected());
        $this->assertTrue($this->form(options: [])->isCsrfProtected());
        $this->assertTrue($this->form(options: ['csrf_protection' => true])->isCsrfProtected());
        $this->assertFalse($this->form(options: ['csrf_protection' => false])->isCsrfProtected());
    }

    #[Test]
    public function aFormWithoutOptionsIsProtected(): void
    {
        $form = new Form(
            $this->form()->getSchema(),
            $this->form()->getUiSchema()
        );

        $this->assertNull($form->getOptions());
        $this->assertTrue($form->isCsrfProtected());
    }

    #[Test]
    public function theIdOfTheTokenIsTheNameOfTheSchemaOrForm(): void
    {
        $this->assertSame('contact', $this->form('contact')->getCsrfTokenId());
        $this->assertSame('form', $this->form()->getCsrfTokenId());
    }

    #[Test]
    public function aFormHasNoErrorsOfItsOwnUnlessItIsGivenThem(): void
    {
        $form = $this->form();
        $data = FormData::fromArray(['email' => 'ana@example.com']);

        $this->assertSame([], $form->getErrors());
        $this->assertSame([], $form->withData($data)->getErrors());
        $this->assertSame(['Expired.'], $form->withData($data, null, ['Expired.'])->getErrors());
    }

    #[Test]
    public function theResultGivesTheErrorsOfTheFormAndPassesThemToTheForm(): void
    {
        $result = new ProcessResult(
            $this->form(),
            ['email' => 'ana@example.com'],
            ['email' => ['Invalid email.']],
            false,
            ['Expired.']
        );

        $this->assertSame(['Expired.'], $result->getFormErrors());
        $this->assertSame(['Expired.'], $result->getForm()->getErrors());
        $this->assertSame(['Invalid email.'], $result->getForm()->getField('email')?->getErrors());
    }

    #[Test]
    public function theErrorsOfTheFormCountAsErrorsAndComeFirstInTheFlatList(): void
    {
        $result = new ProcessResult(
            $this->form(),
            [],
            ['email' => ['Invalid email.']],
            false,
            ['Expired.']
        );

        $this->assertTrue($result->hasErrors());
        $this->assertSame(['Expired.', 'Invalid email.'], $result->getAllErrors());
    }

    #[Test]
    public function aResultWithOnlyErrorsOfTheFormHasErrors(): void
    {
        $result = new ProcessResult($this->form(), [], [], false, ['Expired.']);

        $this->assertTrue($result->hasErrors());
        $this->assertSame([], $result->getErrors());
        $this->assertFalse((new ProcessResult($this->form(), []))->hasErrors());
    }
}
