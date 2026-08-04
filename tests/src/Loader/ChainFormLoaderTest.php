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
use Derafu\Form\Loader\ChainFormLoader;
use Derafu\Form\Loader\JsonFormLoader;
use Derafu\Form\Loader\PhpFormLoader;
use Derafu\Form\Type\TypeProvider;
use Derafu\Form\Type\TypeRegistry;
use Derafu\Form\Type\TypeResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(ChainFormLoader::class)]
#[UsesClass(AbstractFileFormLoader::class)]
#[UsesClass(PhpFormLoader::class)]
#[UsesClass(JsonFormLoader::class)]
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
final class ChainFormLoaderTest extends TestCase
{
    private const FIXTURES = __DIR__ . '/../../fixtures/forms';

    public function testFirstLoaderThatResolvesWins(): void
    {
        $factory = $this->makeFactory();

        $php = new PhpFormLoader($factory);
        $php->addPath(self::FIXTURES);
        $json = new JsonFormLoader($factory);
        $json->addPath(self::FIXTURES);

        // Both have 'static', PHP comes first → PHP wins.
        $chain = new ChainFormLoader([$php, $json]);
        $form = $chain->load('static');

        // PHP static fixture has no 'data' key; JSON version has name=from-json.
        $this->assertNull($form->getData());
    }

    public function testFallsThroughToSecondLoader(): void
    {
        $factory = $this->makeFactory();

        $php = new PhpFormLoader($factory);
        $php->addPath(self::FIXTURES);
        $json = new JsonFormLoader($factory);
        $json->addPath(self::FIXTURES);

        // 'only-json' exists only as JSON → PHP fails, JSON resolves.
        $chain = new ChainFormLoader([$php, $json]);
        $form = $chain->load('only-json');

        $this->assertSame('json-only', $form->getData()?->get('foo'));
    }

    public function testAllLoadersFailThrows(): void
    {
        $factory = $this->makeFactory();

        $php = new PhpFormLoader($factory);
        $php->addPath(self::FIXTURES);
        $json = new JsonFormLoader($factory);
        $json->addPath(self::FIXTURES);

        $chain = new ChainFormLoader([$php, $json]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No loader could resolve');
        $chain->load('does-not-exist-anywhere');
    }

    public function testNoLoadersThrows(): void
    {
        $chain = new ChainFormLoader([]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No loaders registered');
        $chain->load('whatever');
    }

    private function makeFactory(): FormFactoryInterface
    {
        return new FormFactory(new TypeResolver(new TypeRegistry(new TypeProvider())));
    }
}
