<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Tests\Processor;

use Derafu\DataProcessor\Exception\ValidationException;
use Derafu\DataProcessor\ProcessorFactory;
use Derafu\Form\Abstract\AbstractPropertySchema;
use Derafu\Form\Abstract\AbstractUiSchemaElement;
use Derafu\Form\Factory\FormUiSchemaFactory;
use Derafu\Form\Factory\PropertySchemaFactory;
use Derafu\Form\Factory\UiSchemaElementFactory;
use Derafu\Form\Form;
use Derafu\Form\FormField;
use Derafu\Form\Options\FormOptions;
use Derafu\Form\Processor\FormRulesResolver;
use Derafu\Form\Rules\FormRules;
use Derafu\Form\Schema\ArraySchema;
use Derafu\Form\Schema\BooleanSchema;
use Derafu\Form\Schema\FormSchema;
use Derafu\Form\Schema\IntegerSchema;
use Derafu\Form\Schema\NumberSchema;
use Derafu\Form\Schema\ObjectSchemaTrait;
use Derafu\Form\Schema\StringSchema;
use Derafu\Form\UiSchema\Control;
use Derafu\Form\UiSchema\VerticalLayout;
use Derafu\Form\Widget\Widget;
use Derafu\Form\Widget\WidgetFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for FormRulesResolver + Derafu DataProcessor.
 *
 * Verifies that the rules produced by the resolver are correctly consumed by
 * the DataProcessor: trimming, casting, transformation, and validation all
 * behave as expected for real form inputs.
 */
#[CoversClass(FormRulesResolver::class)]
#[CoversClass(PropertySchemaFactory::class)]
#[CoversClass(FormRules::class)]
#[CoversClass(AbstractPropertySchema::class)]
#[CoversClass(AbstractUiSchemaElement::class)]
#[CoversClass(FormUiSchemaFactory::class)]
#[CoversClass(UiSchemaElementFactory::class)]
#[CoversClass(Form::class)]
#[CoversClass(FormField::class)]
#[CoversClass(FormOptions::class)]
#[CoversClass(ArraySchema::class)]
#[CoversClass(BooleanSchema::class)]
#[CoversClass(FormSchema::class)]
#[CoversClass(IntegerSchema::class)]
#[CoversClass(NumberSchema::class)]
#[CoversTrait(ObjectSchemaTrait::class)]
#[CoversClass(StringSchema::class)]
#[CoversClass(Control::class)]
#[CoversClass(VerticalLayout::class)]
#[CoversClass(Widget::class)]
#[CoversClass(WidgetFactory::class)]
final class FormRulesResolverIntegrationTest extends TestCase
{
    private FormRulesResolver $resolver;

    private $processor;

    protected function setUp(): void
    {
        $this->resolver = new FormRulesResolver();
        $this->processor = ProcessorFactory::create();
    }

    // =========================================================================
    // mapSchemaToRules + DataProcessor — end-to-end per-field processing
    // =========================================================================

    public function testEmailFieldTrimsAndLowercases(): void
    {
        $rules = $this->resolver->mapSchemaToRules(
            PropertySchemaFactory::create([
                'type' => 'string',
                'format' => 'email',
                'minLength' => 3,
                'maxLength' => 80,
            ])
        );

        $result = $this->processor->process(' TEST@EXAMPLE.COM ', $rules);
        $this->assertSame('test@example.com', $result);
    }

    public function testEmailFieldRejectsInvalidEmail(): void
    {
        $rules = $this->resolver->mapSchemaToRules(
            PropertySchemaFactory::create([
                'type' => 'string',
                'format' => 'email',
            ])
        );

        $this->expectException(ValidationException::class);
        $this->processor->process('invalid-email', $rules);
    }

    public function testStringFieldTrimsWhitespace(): void
    {
        $rules = $this->resolver->mapSchemaToRules(
            PropertySchemaFactory::create([
                'type' => 'string',
                'minLength' => 3,
                'maxLength' => 100,
            ])
        );

        $this->assertSame('John Doe', $this->processor->process('  John Doe  ', $rules));
    }

    public function testStringFieldRejectsTooShortValue(): void
    {
        $rules = $this->resolver->mapSchemaToRules(
            PropertySchemaFactory::create([
                'type' => 'string',
                'minLength' => 3,
            ])
        );

        $this->expectException(ValidationException::class);
        $this->processor->process('Jo', $rules);
    }

    public function testIntegerFieldCastsStringToInt(): void
    {
        $rules = $this->resolver->mapSchemaToRules(
            PropertySchemaFactory::create([
                'type' => 'integer',
                'minimum' => 18,
                'maximum' => 99,
            ])
        );

        $this->assertSame(25, $this->processor->process('25', $rules));
    }

    public function testIntegerFieldRejectsBelowMinimum(): void
    {
        $rules = $this->resolver->mapSchemaToRules(
            PropertySchemaFactory::create([
                'type' => 'integer',
                'minimum' => 18,
            ])
        );

        $this->expectException(ValidationException::class);
        $this->processor->process('15', $rules);
    }

    public function testArrayFieldValidatesItemCount(): void
    {
        $rules = $this->resolver->mapSchemaToRules(
            PropertySchemaFactory::create([
                'type' => 'array',
                'minItems' => 1,
                'maxItems' => 5,
                'uniqueItems' => true,
            ])
        );

        $result = $this->processor->process(['php', 'form', 'validation'], $rules);
        $this->assertSame(['php', 'form', 'validation'], $result);
    }

    public function testArrayFieldRejectsTooManyItems(): void
    {
        $rules = $this->resolver->mapSchemaToRules(
            PropertySchemaFactory::create([
                'type' => 'array',
                'maxItems' => 5,
            ])
        );

        $this->expectException(ValidationException::class);
        $this->processor->process(['a', 'b', 'c', 'd', 'e', 'f'], $rules);
    }

    public function testEnumFieldAcceptsValidValue(): void
    {
        $rules = $this->resolver->mapSchemaToRules(
            PropertySchemaFactory::create([
                'type' => 'string',
                'enum' => ['pending', 'approved', 'rejected'],
            ])
        );

        $this->assertSame('approved', $this->processor->process('  approved  ', $rules));
    }

    public function testEnumFieldRejectsInvalidValue(): void
    {
        $rules = $this->resolver->mapSchemaToRules(
            PropertySchemaFactory::create([
                'type' => 'string',
                'enum' => ['pending', 'approved'],
            ])
        );

        $this->expectException(ValidationException::class);
        $this->processor->process('invalid', $rules);
    }

    public function testOneOfFieldAcceptsValidConst(): void
    {
        $rules = $this->resolver->mapSchemaToRules(
            PropertySchemaFactory::create([
                'type' => 'string',
                'oneOf' => [
                    ['const' => 'draft', 'title' => 'Draft'],
                    ['const' => 'active', 'title' => 'Active'],
                ],
            ])
        );

        $this->assertSame('active', $this->processor->process('  active  ', $rules));
    }

    public function testOneOfFieldRejectsValueNotInConsts(): void
    {
        $rules = $this->resolver->mapSchemaToRules(
            PropertySchemaFactory::create([
                'type' => 'string',
                'oneOf' => [
                    ['const' => 'draft', 'title' => 'Draft'],
                    ['const' => 'active', 'title' => 'Active'],
                ],
            ])
        );

        $this->expectException(ValidationException::class);
        $this->processor->process('invalid', $rules);
    }

    // =========================================================================
    // resolve() + DataProcessor — processing via real Form objects
    // =========================================================================

    public function testResolveProducesUsableRulesForMultipleFields(): void
    {
        $form = Form::fromArray([
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string', 'minLength' => 3, 'maxLength' => 100],
                    'email' => ['type' => 'string', 'format' => 'email'],
                    'age' => ['type' => 'integer', 'minimum' => 18, 'maximum' => 99],
                ],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    ['type' => 'Control', 'scope' => '#/properties/name'],
                    ['type' => 'Control', 'scope' => '#/properties/email'],
                    ['type' => 'Control', 'scope' => '#/properties/age'],
                ],
            ],
        ]);

        $this->resolver->resolve($form);

        $name  = $this->processor->process('  John Doe  ', $form->getRules()['name']);
        $email = $this->processor->process('  TEST@EXAMPLE.COM  ', $form->getRules()['email']);
        $age   = $this->processor->process('25', $form->getRules()['age']);

        $this->assertSame('John Doe', $name);
        $this->assertSame('test@example.com', $email);
        $this->assertSame(25, $age);
    }

    public function testExplicitRulesMergedCorrectlyInResolve(): void
    {
        // sort_order is declared as string but cast to integer via rules section.
        $form = Form::fromArray([
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'sort_order' => ['type' => 'string'],
                    'slug' => ['type' => 'string'],
                    'sku' => ['type' => 'string'],
                ],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    ['type' => 'Control', 'scope' => '#/properties/sort_order'],
                    ['type' => 'Control', 'scope' => '#/properties/slug'],
                    ['type' => 'Control', 'scope' => '#/properties/sku'],
                ],
            ],
            'rules' => [
                'sort_order' => ['cast' => 'integer'],
                'slug' => ['transform' => ['lowercase', 'slug']],
                'sku' => ['transform' => ['uppercase'], 'sanitize' => ['spaces']],
            ],
        ]);

        $this->resolver->resolve($form);

        // sort_order cast overridden to integer.
        $sortOrder = $this->processor->process('3', $form->getRules()['sort_order']);
        $this->assertSame(3, $sortOrder);

        // slug lowercased and slugified.
        $slug = $this->processor->process('Wireless Bluetooth Headphones', $form->getRules()['slug']);
        $this->assertSame('wireless-bluetooth-headphones', $slug);

        // sku uppercased.
        $sku = $this->processor->process('wbh-001-blk', $form->getRules()['sku']);
        $this->assertSame('WBH-001-BLK', $sku);
    }
}
