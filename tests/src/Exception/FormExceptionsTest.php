<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm\Exception;

use BadMethodCallException;
use Closure;
use Derafu\Form\Contract\FormFieldInterface;
use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Contract\UiSchema\CategorizationInterface;
use Derafu\Form\Contract\UiSchema\ControlInterface;
use Derafu\Form\Contract\UiSchema\GroupInterface;
use Derafu\Form\Contract\UiSchema\HorizontalLayoutInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaElementInterface;
use Derafu\Form\Contract\UiSchema\VerticalLayoutInterface;
use Derafu\Form\Factory\FormUiSchemaFactory;
use Derafu\Form\Factory\UiSchemaElementFactory;
use Derafu\Form\Renderer\Element\CategorizationRenderer;
use Derafu\Form\Renderer\Element\ControlRenderer;
use Derafu\Form\Renderer\Element\GroupRenderer;
use Derafu\Form\Renderer\Element\HorizontalLayoutRenderer;
use Derafu\Form\Renderer\Element\LabelRenderer;
use Derafu\Form\Renderer\Element\VerticalLayoutRenderer;
use Derafu\Form\Renderer\ElementRendererRegistry;
use Derafu\Form\Renderer\Support\InputActionResolver;
use Derafu\Form\Renderer\Widget\CheckboxWidgetRenderer;
use Derafu\Form\Renderer\Widget\InputWidgetRenderer;
use Derafu\Form\Renderer\Widget\RadioWidgetRenderer;
use Derafu\Form\Renderer\Widget\SelectWidgetRenderer;
use Derafu\Form\Renderer\Widget\SliderWidgetRenderer;
use Derafu\Form\Renderer\Widget\TextareaWidgetRenderer;
use Derafu\Form\Renderer\WidgetRendererRegistry;
use Derafu\Form\Rules\FormRules;
use Derafu\Form\Type\TypeRegistry;
use Derafu\Form\Type\TypeResolver;
use Derafu\Form\UiSchema\Control;
use Derafu\Form\UiSchema\UiSchemaCompositeCondition;
use Derafu\Form\UiSchema\UiSchemaCondition;
use Derafu\Form\UiSchema\UiSchemaRule;
use Derafu\Translation\Contract\TranslatableInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * The errors that the package raises with the exceptions of PHP are
 * translatable, and say what they said before.
 */
#[CoversClass(UiSchemaRule::class)]
#[CoversClass(UiSchemaCondition::class)]
#[CoversClass(UiSchemaCompositeCondition::class)]
#[CoversClass(Control::class)]
#[CoversClass(TypeRegistry::class)]
#[CoversClass(TypeResolver::class)]
#[CoversClass(FormUiSchemaFactory::class)]
#[CoversClass(UiSchemaElementFactory::class)]
#[CoversClass(ElementRendererRegistry::class)]
#[CoversClass(WidgetRendererRegistry::class)]
#[CoversClass(CategorizationRenderer::class)]
#[CoversClass(ControlRenderer::class)]
#[CoversClass(GroupRenderer::class)]
#[CoversClass(HorizontalLayoutRenderer::class)]
#[CoversClass(LabelRenderer::class)]
#[CoversClass(VerticalLayoutRenderer::class)]
#[CoversClass(CheckboxWidgetRenderer::class)]
#[CoversClass(InputWidgetRenderer::class)]
#[CoversClass(RadioWidgetRenderer::class)]
#[CoversClass(SelectWidgetRenderer::class)]
#[CoversClass(FormRules::class)]
#[CoversClass(SliderWidgetRenderer::class)]
#[CoversClass(TextareaWidgetRenderer::class)]
#[UsesClass(InputActionResolver::class)]
final class FormExceptionsTest extends TestCase
{
    /**
     * @return array<string, array{Closure, string}>
     */
    public static function failuresProvider(): array
    {
        return [
            'rule without effect' => [fn () => UiSchemaRule::fromArray([]), 'A UiSchemaRule definition requires an "effect" key.'],
            'rule without condition' => [fn () => UiSchemaRule::fromArray(['effect' => 'HIDE']), 'A UiSchemaRule definition requires a "condition" key.'],
            'condition without scope' => [fn () => UiSchemaCondition::fromArray([]), 'A UiSchemaCondition definition requires a "scope" key.'],
            'condition without schema' => [fn () => UiSchemaCondition::fromArray(['scope' => '#/properties/a']), 'A UiSchemaCondition definition requires a "schema" key.'],
            'composite without type' => [fn () => UiSchemaCompositeCondition::fromArray([]), 'A UiSchemaCompositeCondition definition requires a "type" key.'],
            'composite without conditions' => [fn () => UiSchemaCompositeCondition::fromArray(['type' => 'AND']), 'A UiSchemaCompositeCondition definition requires a non-empty "conditions" array.'],
            'control with a bad scope' => [fn () => (new Control(['type' => 'Control', 'scope' => '#/bad']))->getPropertyPath(), 'Invalid scope format: #/bad. Expected format: #/properties/path/to/property'],
            'type not in the registry' => [fn () => (new TypeRegistry())->get('missing'), "Type 'missing' not found in registry. Available types: ."],
            'type that can not be guessed' => [fn () => (new TypeResolver(new TypeRegistry()))->guess(STDIN), 'Cannot guess type for value of type resource (stream).'],
            'ui schema type' => [fn () => FormUiSchemaFactory::create(['type' => 'Nope']), 'Invalid UI schema type: Nope. Valid types are: '],
            'ui schema element type' => [fn () => UiSchemaElementFactory::create(['type' => 'Nope']), 'Invalid UI schema element type: Nope. Valid types are: '],
            'no element renderers' => [fn () => (new ElementRendererRegistry())->getRenderer('Control'), 'No renderers registered for form elements.'],
            'no widget renderers' => [fn () => (new WidgetRendererRegistry())->getRenderer('input'), 'No renderers registered for form widgets.'],
        ];
    }

    #[DataProvider('failuresProvider')]
    public function testTheFailureIsATranslatableErrorThatSaysTheSame(Closure $failure, string $message): void
    {
        $exception = null;
        try {
            $failure($this);
        } catch (Throwable $e) {
            $exception = $e;
        }

        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
        $this->assertInstanceOf(TranslatableInterface::class, $exception);
        $this->assertStringStartsWith($message, $exception->getMessage());
    }

    public function testFormRulesDoNotSupportOffsetSetAndUnsetWithATranslatableError(): void
    {
        $rules = FormRules::fromArray(['name' => ['cast' => 'string']]);

        foreach ([
            'FormRules does not support offsetSet(). Use fill() to populate resolved rules.' => function () use ($rules) {
                $rules['name'] = ['cast' => 'integer'];
            },
            'FormRules does not support offsetUnset().' => function () use ($rules) {
                unset($rules['name']);
            },
        ] as $message => $failure) {
            $exception = null;
            try {
                $failure();
            } catch (Throwable $e) {
                $exception = $e;
            }

            // Still the exception that whoever uses the rules catches.
            $this->assertInstanceOf(BadMethodCallException::class, $exception);
            $this->assertInstanceOf(TranslatableInterface::class, $exception);
            $this->assertSame($message, $exception->getMessage());
        }
    }

    /**
     * @return array<string, array{Closure(self): mixed, string}>
     */
    public static function renderersProvider(): array
    {
        return [
            'label with another element' => [fn (self $t) => (new LabelRenderer())->render($t->createStub(UiSchemaElementInterface::class), $t->createStub(FormInterface::class)), 'Element must be an instance of Derafu\Form\Contract\UiSchema\LabelInterface in LabelRenderer, '],
            'control with another element' => [fn (self $t) => (new ControlRenderer())->render($t->createStub(UiSchemaElementInterface::class), $t->createStub(FormInterface::class)), 'Element must be an instance of ' . ControlInterface::class . ' in ControlRenderer, '],
            'group with another element' => [fn (self $t) => (new GroupRenderer())->render($t->createStub(UiSchemaElementInterface::class), $t->createStub(FormInterface::class)), 'Element must be an instance of ' . GroupInterface::class . ' in GroupRenderer, '],
            'horizontal layout with another element' => [fn (self $t) => (new HorizontalLayoutRenderer())->render($t->createStub(UiSchemaElementInterface::class), $t->createStub(FormInterface::class)), 'Element must be an instance of ' . HorizontalLayoutInterface::class . ' in HorizontalLayoutRenderer, '],
            'vertical layout with another element' => [fn (self $t) => (new VerticalLayoutRenderer())->render($t->createStub(UiSchemaElementInterface::class), $t->createStub(FormInterface::class)), 'Element must be an instance of ' . VerticalLayoutInterface::class . ' in VerticalLayoutRenderer, '],
            'categorization with another element' => [fn (self $t) => (new CategorizationRenderer())->render($t->createStub(UiSchemaElementInterface::class), $t->createStub(FormInterface::class)), 'Element must be an instance of ' . CategorizationInterface::class . ' in CategorizationRenderer, '],
            'group without a renderer' => [fn (self $t) => (new GroupRenderer())->render($t->createStub(GroupInterface::class), $t->createStub(FormInterface::class)), 'The "renderer" in GroupRenderer must be an instance of FormRenderer.'],
            'horizontal layout without a renderer' => [fn (self $t) => (new HorizontalLayoutRenderer())->render($t->createStub(HorizontalLayoutInterface::class), $t->createStub(FormInterface::class)), 'The "renderer" option in HorizontalLayoutRenderer must be an instance of FormRenderer.'],
            'vertical layout without a renderer' => [fn (self $t) => (new VerticalLayoutRenderer())->render($t->createStub(VerticalLayoutInterface::class), $t->createStub(FormInterface::class)), 'The "renderer" option in VerticalLayoutRenderer must be an instance of FormRendererInterface.'],
            'categorization without a renderer' => [fn (self $t) => (new CategorizationRenderer())->render($t->createStub(CategorizationInterface::class), $t->createStub(FormInterface::class)), 'The "renderer" option in CategorizationRenderer must be an instance of FormRenderer.'],
            'control without a renderer' => [fn (self $t) => (new ControlRenderer())->render($t->createStub(ControlInterface::class), $t->createStub(FormInterface::class)), 'The "renderer" option in ControlRenderer must be an instance of FormRendererInterface.'],
            'checkbox without a renderer' => [fn (self $t) => (new CheckboxWidgetRenderer())->render($t->createStub(FormFieldInterface::class)), 'The "renderer" option in CheckboxWidgetRenderer must be an instance of FormRendererInterface.'],
            'input without a renderer' => [fn (self $t) => (new InputWidgetRenderer())->render($t->createStub(FormFieldInterface::class)), 'The "renderer" option in InputWidgetRenderer must be an instance of FormRendererInterface.'],
            'radio without a renderer' => [fn (self $t) => (new RadioWidgetRenderer())->render($t->createStub(FormFieldInterface::class)), 'The "renderer" option in RadioWidgetRenderer must be an instance of FormRendererInterface.'],
            'select without a renderer' => [fn (self $t) => (new SelectWidgetRenderer())->render($t->createStub(FormFieldInterface::class)), 'The "renderer" option in SelectWidgetRenderer must be an instance of FormRendererInterface.'],
            'slider without a renderer' => [fn (self $t) => (new SliderWidgetRenderer())->render($t->createStub(FormFieldInterface::class)), 'The "renderer" option in SliderWidgetRenderer must be an instance of FormRendererInterface.'],
            'textarea without a renderer' => [fn (self $t) => (new TextareaWidgetRenderer())->render($t->createStub(FormFieldInterface::class)), 'The "renderer" option in TextareaWidgetRenderer must be an instance of FormRendererInterface.'],
        ];
    }

    /**
     * @param Closure(self): mixed $render
     */
    #[DataProvider('renderersProvider')]
    public function testARendererThatIsNotUsedAsExpectedIsATranslatableError(Closure $render, string $message): void
    {
        $exception = null;
        try {
            $render($this);
        } catch (Throwable $e) {
            $exception = $e;
        }

        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
        $this->assertInstanceOf(TranslatableInterface::class, $exception);
        $this->assertStringStartsWith($message, $exception->getMessage());
    }
}
