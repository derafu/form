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

use Derafu\Form\Factory\ChoicesShorthand;
use Derafu\Form\Factory\FormFactory;
use Derafu\Form\Factory\FormRendererFactory;
use Derafu\Form\FormDefinition;
use Derafu\Form\Renderer\Widget\RadioWidgetRenderer;
use Derafu\Form\Renderer\Widget\SelectWidgetRenderer;
use Derafu\Form\Renderer\Widget\TextareaWidgetRenderer;
use Derafu\Form\Schema\PropertyDefinition;
use Derafu\Form\Type\TypeProvider;
use Derafu\Form\Type\TypeRegistry;
use Derafu\Form\Type\TypeResolver;
use Derafu\Form\UiSchema\UiSchemaElementDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

/**
 * A control shows the value that the form has: the one that was sent or, if
 * there is none, the default of the schema.
 */
#[CoversClass(RadioWidgetRenderer::class)]
#[CoversClass(SelectWidgetRenderer::class)]
#[CoversClass(TextareaWidgetRenderer::class)]
#[UsesClass(\Derafu\Form\Abstract\AbstractPropertySchema::class)]
#[UsesClass(\Derafu\Form\Abstract\AbstractType::class)]
#[UsesClass(\Derafu\Form\Abstract\AbstractUiSchemaElement::class)]
#[UsesClass(\Derafu\Form\Data\FormData::class)]
#[UsesClass(\Derafu\Form\Factory\FormFactory::class)]
#[UsesClass(\Derafu\Form\Factory\FormRendererFactory::class)]
#[UsesClass(\Derafu\Form\Factory\FormUiSchemaFactory::class)]
#[UsesClass(\Derafu\Form\Factory\PropertySchemaFactory::class)]
#[UsesClass(\Derafu\Form\Factory\UiSchemaElementFactory::class)]
#[UsesClass(\Derafu\Form\Form::class)]
#[UsesClass(\Derafu\Form\FormField::class)]
#[UsesClass(\Derafu\Form\Options\FormOptions::class)]
#[UsesClass(\Derafu\Form\Renderer\ElementRendererProvider::class)]
#[UsesClass(\Derafu\Form\Renderer\ElementRendererRegistry::class)]
#[UsesClass(\Derafu\Form\Renderer\Element\ControlRenderer::class)]
#[UsesClass(\Derafu\Form\Renderer\Element\VerticalLayoutRenderer::class)]
#[UsesClass(\Derafu\Form\Renderer\FormRenderer::class)]
#[UsesClass(\Derafu\Form\Renderer\FormTwigExtension::class)]
#[UsesClass(\Derafu\Form\Renderer\Support\InputActionResolver::class)]
#[UsesClass(\Derafu\Form\Renderer\WidgetRendererProvider::class)]
#[UsesClass(\Derafu\Form\Renderer\WidgetRendererRegistry::class)]
#[UsesClass(\Derafu\Form\Renderer\Widget\InputWidgetRenderer::class)]
#[UsesClass(\Derafu\Form\Renderer\Widget\SliderWidgetRenderer::class)]
#[UsesClass(\Derafu\Form\Rules\FormRules::class)]
#[UsesClass(\Derafu\Form\Schema\ArraySchema::class)]
#[UsesClass(\Derafu\Form\Schema\FormSchema::class)]
#[UsesTrait(\Derafu\Form\Schema\ObjectSchemaTrait::class)]
#[UsesClass(\Derafu\Form\Schema\StringSchema::class)]
#[UsesClass(\Derafu\Form\Type\BooleanType::class)]
#[UsesClass(\Derafu\Form\Type\ChoiceType::class)]
#[UsesClass(\Derafu\Form\Type\ColorType::class)]
#[UsesClass(\Derafu\Form\Type\DateType::class)]
#[UsesClass(\Derafu\Form\Type\DatetimeType::class)]
#[UsesClass(\Derafu\Form\Type\EmailType::class)]
#[UsesClass(\Derafu\Form\Type\FloatType::class)]
#[UsesClass(\Derafu\Form\Type\IntegerType::class)]
#[UsesClass(\Derafu\Form\Type\Ipv4Type::class)]
#[UsesClass(\Derafu\Form\Type\Ipv6Type::class)]
#[UsesClass(\Derafu\Form\Type\MonthType::class)]
#[UsesClass(\Derafu\Form\Type\TextType::class)]
#[UsesClass(\Derafu\Form\Type\TextareaType::class)]
#[UsesClass(\Derafu\Form\Type\TimeType::class)]
#[UsesClass(\Derafu\Form\Type\TypeProvider::class)]
#[UsesClass(\Derafu\Form\Type\TypeRegistry::class)]
#[UsesClass(\Derafu\Form\Type\TypeResolver::class)]
#[UsesClass(\Derafu\Form\Type\UriType::class)]
#[UsesClass(\Derafu\Form\Type\UrlType::class)]
#[UsesClass(\Derafu\Form\Type\UuidType::class)]
#[UsesClass(\Derafu\Form\Type\WeekType::class)]
#[UsesClass(\Derafu\Form\UiSchema\Control::class)]
#[UsesClass(\Derafu\Form\UiSchema\VerticalLayout::class)]
#[UsesClass(\Derafu\Form\Widget\Widget::class)]
#[UsesClass(\Derafu\Form\Widget\WidgetFactory::class)]
#[UsesClass(PropertyDefinition::class)]
#[UsesClass(UiSchemaElementDefinition::class)]
#[UsesClass(ChoicesShorthand::class)]
#[UsesClass(FormDefinition::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\Renderer\Support\RequiredField::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\Options\FormAttributes::class)]
final class WidgetValueRenderingTest extends TestCase
{
    /**
     * Renders a form with a single control.
     *
     * @param array<string, mixed> $property
     * @param array<string, mixed> $options
     * @param array<string, mixed>|null $data
     */
    private function render(
        array $property,
        array $options = [],
        ?array $data = null,
        bool $required = false,
    ): string {
        $definition = [
            'options' => ['csrf_protection' => false],
            'schema' => [
                'type' => 'object',
                'properties' => ['field' => $property + ['title' => 'Field']],
                'required' => $required ? ['field'] : [],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [[
                    'type' => 'Control',
                    'scope' => '#/properties/field',
                    'options' => $options,
                ]],
            ],
        ];
        if ($data !== null) {
            $definition['data'] = $data;
        }

        $form = (new FormFactory(new TypeResolver(new TypeRegistry(new TypeProvider()))))->create($definition);

        return html_entity_decode(FormRendererFactory::create()->render($form));
    }

    /**
     * The properties of a choice with the default that the test says.
     *
     * @return array<string, mixed>
     */
    private function choices(?string $default = null): array
    {
        $property = ['type' => 'string', 'enum' => ['a', 'b', 'c']];
        if ($default !== null) {
            $property['default'] = $default;
        }

        return $property;
    }

    /**
     * The JSON of the `data-cascade` attribute of the only select that has it.
     *
     * @return array<string, mixed>
     */
    private function cascade(string $html): array
    {
        if (preg_match('/data-cascade="(\{.*\})"/', $html, $matches) !== 1) {
            $this->fail('The select does not have the attribute data-cascade.');
        }

        /** @var array<string, mixed> */
        return json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
    }

    // Select.

    #[Test]
    public function aSelectShowsTheDefaultWhetherOrNotItIsRequired(): void
    {
        foreach ([false, true] as $required) {
            $html = $this->render($this->choices('b'), required: $required);

            $this->assertStringContainsString('<option value="b" selected="selected">', $html);
            $this->assertSame(1, substr_count($html, 'selected="selected"'));
        }
    }

    #[Test]
    public function aSelectShowsTheValueThatWasSentBeforeTheDefaultWhetherOrNotItIsRequired(): void
    {
        foreach ([false, true] as $required) {
            $html = $this->render($this->choices('b'), data: ['field' => 'c'], required: $required);

            $this->assertStringContainsString('<option value="c" selected="selected">', $html);
            $this->assertSame(1, substr_count($html, 'selected="selected"'));
        }
    }

    #[Test]
    public function aMultipleSelectThatIsRequiredShowsTheValuesThatWereSent(): void
    {
        $html = $this->render(
            ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['a', 'b', 'c']]],
            ['widget' => 'select'],
            ['field' => ['a', 'c']],
            required: true,
        );

        $this->assertStringContainsString('<option value="a" selected="selected">', $html);
        $this->assertStringContainsString('<option value="c" selected="selected">', $html);
        $this->assertStringNotContainsString('<option value="b" selected="selected">', $html);
    }

    // Textarea.

    #[Test]
    public function aTextareaThatIsRequiredShowsItsTextAndNotTheNameOfTheAttribute(): void
    {
        $html = $this->render(['type' => 'string'], ['type' => 'textarea'], ['field' => 'hello'], required: true);

        $this->assertMatchesRegularExpression('/<textarea[^>]*required="required"[^>]*>hello<\/textarea>/', $html);
    }

    #[Test]
    public function aTextareaThatIsRequiredShowsTheDefault(): void
    {
        $html = $this->render(['type' => 'string', 'default' => 'hello'], ['type' => 'textarea'], required: true);

        $this->assertMatchesRegularExpression('/<textarea[^>]*>hello<\/textarea>/', $html);
    }

    // Radio.

    #[Test]
    public function aRadioChecksTheDefault(): void
    {
        $html = $this->render($this->choices('b'), ['type' => 'radio']);

        $this->assertSame(1, preg_match_all('/checked="checked"/', $html));
        $this->assertMatchesRegularExpression('/value="b"[^>]*checked="checked"|checked="checked"[^>]*value="b"/', $html);
    }

    #[Test]
    public function aRadioChecksTheValueThatWasSentBeforeTheDefault(): void
    {
        $html = $this->render($this->choices('b'), ['type' => 'radio'], ['field' => 'c']);

        $this->assertSame(1, preg_match_all('/checked="checked"/', $html));
        $this->assertMatchesRegularExpression('/value="c"[^>]*checked="checked"|checked="checked"[^>]*value="c"/', $html);
    }

    #[Test]
    public function aRadioWithoutValueChecksNothingAndEachOptionKeepsItsOwnValue(): void
    {
        $html = $this->render($this->choices(), ['type' => 'radio']);

        $this->assertStringNotContainsString('checked', $html);
        foreach (['a', 'b', 'c'] as $value) {
            $this->assertStringContainsString('value="' . $value . '"', $html);
        }
    }

    // Cascade.

    /**
     * @return array<string, mixed>
     */
    private function cascadeOptions(): array
    {
        return [
            'type' => 'choice',
            'cascade' => [
                'dependsOn' => '#/properties/parent',
                'choices' => ['p' => ['x' => 'X', 'y' => 'Y']],
            ],
        ];
    }

    #[Test]
    public function aCascadeCarriesTheValueThatWasSentInItsConfiguration(): void
    {
        $cascade = $this->cascade($this->render(['type' => 'string'], $this->cascadeOptions(), ['field' => 'y']));

        $this->assertSame('y', $cascade['value']);
        $this->assertSame('#/properties/parent', $cascade['dependsOn']);
        $this->assertSame(['p' => ['x' => 'X', 'y' => 'Y']], $cascade['choices']);
    }

    #[Test]
    public function aCascadeCarriesTheDefaultWhenNothingWasSent(): void
    {
        $cascade = $this->cascade($this->render(['type' => 'string', 'default' => 'x'], $this->cascadeOptions()));

        $this->assertSame('x', $cascade['value']);
    }

    #[Test]
    public function aCascadeWithoutValueDoesNotCarryTheKey(): void
    {
        $cascade = $this->cascade($this->render(['type' => 'string'], $this->cascadeOptions()));

        $this->assertArrayNotHasKey('value', $cascade);
    }
}
