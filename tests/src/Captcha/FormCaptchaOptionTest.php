<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm\Captcha;

use Derafu\Form\Form;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

/**
 * What a form says about its captcha: an option of the form that is off unless
 * the form turns it on, and its id.
 */
#[CoversClass(Form::class)]
#[UsesClass(\Derafu\Form\Abstract\AbstractPropertySchema::class)]
#[UsesClass(\Derafu\Form\Abstract\AbstractUiSchemaElement::class)]
#[UsesClass(\Derafu\Form\Factory\FormUiSchemaFactory::class)]
#[UsesClass(\Derafu\Form\Factory\PropertySchemaFactory::class)]
#[UsesClass(\Derafu\Form\Factory\UiSchemaElementFactory::class)]
#[UsesClass(\Derafu\Form\Options\FormOptions::class)]
#[UsesClass(\Derafu\Form\Rules\FormRules::class)]
#[UsesClass(\Derafu\Form\Schema\FormSchema::class)]
#[UsesTrait(\Derafu\Form\Schema\ObjectSchemaTrait::class)]
#[UsesClass(\Derafu\Form\Schema\StringSchema::class)]
#[UsesClass(\Derafu\Form\UiSchema\Control::class)]
#[UsesClass(\Derafu\Form\UiSchema\VerticalLayout::class)]
final class FormCaptchaOptionTest extends TestCase
{
    /**
     * @param array<string, mixed>|null $options
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
    public function aFormIsNotProtectedWithTheCaptchaUnlessItTurnsItOn(): void
    {
        $this->assertFalse($this->form()->isCaptchaProtected());
        $this->assertFalse($this->form(options: [])->isCaptchaProtected());
        $this->assertFalse($this->form(options: ['captcha_protection' => false])->isCaptchaProtected());
        $this->assertTrue($this->form(options: ['captcha_protection' => true])->isCaptchaProtected());
    }

    #[Test]
    public function aFormWithoutOptionsIsNotProtectedWithTheCaptcha(): void
    {
        $form = new Form($this->form()->getSchema(), $this->form()->getUiSchema());

        $this->assertNull($form->getOptions());
        $this->assertFalse($form->isCaptchaProtected());
    }

    #[Test]
    public function theIdOfAFormIsTheNameOfItsSchemaOrForm(): void
    {
        $this->assertSame('contact', $this->form('contact')->getId());
        $this->assertSame('form', $this->form()->getId());
    }

    #[Test]
    public function theIdOfTheCsrfTokenIsTheIdOfTheForm(): void
    {
        $this->assertSame('contact', $this->form('contact')->getCsrfTokenId());
        $this->assertSame('form', $this->form()->getCsrfTokenId());
    }
}
