<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm\Loader;

use Derafu\Form\Abstract\AbstractFileFormLoader;
use Derafu\Form\Contract\Factory\FormFactoryInterface;
use Derafu\Form\Factory\FormFactory;
use Derafu\Form\Loader\PhpFormLoader;
use Derafu\Form\Type\TypeProvider;
use Derafu\Form\Type\TypeRegistry;
use Derafu\Form\Type\TypeResolver;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(PhpFormLoader::class)]
#[UsesClass(AbstractFileFormLoader::class)]
#[UsesClass(FormFactory::class)]
#[UsesClass(\Derafu\Form\Form::class)]
#[UsesClass(\Derafu\Form\Data\FormData::class)]
#[UsesClass(\Derafu\Form\Options\FormOptions::class)]
#[UsesClass(\Derafu\Form\Rules\FormRules::class)]
#[UsesClass(\Derafu\Form\Abstract\AbstractPropertySchema::class)]
#[UsesClass(\Derafu\Form\Abstract\AbstractType::class)]
#[UsesClass(\Derafu\Form\Abstract\AbstractUiSchemaElement::class)]
#[UsesClass(\Derafu\Form\Factory\FormUiSchemaFactory::class)]
#[UsesClass(\Derafu\Form\Factory\PropertySchemaFactory::class)]
#[UsesClass(\Derafu\Form\Factory\UiSchemaElementFactory::class)]
#[UsesClass(\Derafu\Form\Schema\FormSchema::class)]
#[UsesTrait(\Derafu\Form\Schema\ObjectSchemaTrait::class)]
#[UsesClass(\Derafu\Form\Schema\StringSchema::class)]
#[UsesClass(\Derafu\Form\Schema\IntegerSchema::class)]
#[UsesClass(\Derafu\Form\Type\TypeProvider::class)]
#[UsesClass(\Derafu\Form\Type\TypeRegistry::class)]
#[UsesClass(\Derafu\Form\Type\TypeResolver::class)]
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
#[UsesClass(\Derafu\Form\Type\UriType::class)]
#[UsesClass(\Derafu\Form\Type\UrlType::class)]
#[UsesClass(\Derafu\Form\Type\UuidType::class)]
#[UsesClass(\Derafu\Form\Type\WeekType::class)]
#[UsesClass(\Derafu\Form\UiSchema\Control::class)]
#[UsesClass(\Derafu\Form\UiSchema\VerticalLayout::class)]
final class PhpFormLoaderTest extends TestCase
{
    private const FIXTURES = __DIR__ . '/../../fixtures/forms';

    private const OVERRIDES = __DIR__ . '/../../fixtures/forms-override';

    public function testLoadsStaticArray(): void
    {
        $loader = new PhpFormLoader($this->makeFactory());
        $loader->addPath(self::FIXTURES);

        $form = $loader->load('static');

        $this->assertSame('object', $form->getSchema()->getType());
    }

    public function testLoadsClosureAndPassesContext(): void
    {
        $loader = new PhpFormLoader($this->makeFactory());
        $loader->addPath(self::FIXTURES);

        $form = $loader->load('dynamic', ['name' => 'alice']);

        $this->assertSame('alice', $form->getData()?->get('name'));
    }

    public function testDataArgumentMergesIntoDefinition(): void
    {
        $loader = new PhpFormLoader($this->makeFactory());
        $loader->addPath(self::FIXTURES);

        $form = $loader->load('with-data', [], ['name' => 'from-call']);

        $this->assertSame('from-call', $form->getData()?->get('name'));
        $this->assertSame(10, $form->getData()->get('age'));
    }

    public function testMissingFileThrows(): void
    {
        $loader = new PhpFormLoader($this->makeFactory());
        $loader->addPath(self::FIXTURES);

        $this->expectException(RuntimeException::class);
        $loader->load('does-not-exist');
    }

    public function testInvalidNameThrows(): void
    {
        $loader = new PhpFormLoader($this->makeFactory());
        $loader->addPath(self::FIXTURES);

        $this->expectException(InvalidArgumentException::class);
        $loader->load('../evil');
    }

    public function testLastRegisteredPathWins(): void
    {
        $loader = new PhpFormLoader($this->makeFactory());
        $loader->addPath(self::FIXTURES);
        $loader->addPath(self::OVERRIDES);

        $form = $loader->load('static');

        $this->assertSame('overridden', $form->getData()?->get('name'));
    }

    public function testPathsViaConstructor(): void
    {
        $loader = new PhpFormLoader(
            $this->makeFactory(),
            [self::FIXTURES, self::OVERRIDES],
        );

        $form = $loader->load('static');

        // Constructor applies paths via addPath() in order → OVERRIDES wins.
        $this->assertSame('overridden', $form->getData()?->get('name'));
    }

    public function testNestedNameResolves(): void
    {
        $loader = new PhpFormLoader($this->makeFactory());
        $loader->addPath(self::FIXTURES);

        $form = $loader->load('sub/nested');

        $this->assertSame('nested', $form->getData()?->get('tag'));
    }

    private function makeFactory(): FormFactoryInterface
    {
        return new FormFactory(new TypeResolver(new TypeRegistry(new TypeProvider())));
    }
}
