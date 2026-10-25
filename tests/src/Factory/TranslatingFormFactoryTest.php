<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm\Factory;

use Derafu\Array\ArrayPath;
use Derafu\Form\Contract\Factory\FormFactoryInterface;
use Derafu\Form\Contract\Loader\FormLoaderInterface;
use Derafu\Form\Factory\ChoicesShorthand;
use Derafu\Form\Factory\FormFactory;
use Derafu\Form\Factory\TranslatingFormFactory;
use Derafu\Form\FormDefinition;
use Derafu\Form\Loader\JsonFormLoader;
use Derafu\Form\Loader\PhpFormLoader;
use Derafu\Form\Loader\YamlFormLoader;
use Derafu\Form\Schema\PropertyDefinition;
use Derafu\Form\Type\TypeProvider;
use Derafu\Form\Type\TypeRegistry;
use Derafu\Form\Type\TypeResolver;
use Derafu\Form\UiSchema\UiSchemaElementDefinition;
use Derafu\Translation\SimpleTranslationResourceProvider;
use Derafu\Translation\TranslatorFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The forms are created with their texts translated, from every source of a
 * definition, with the real loaders, the real form factory and a real
 * translator with a catalogue.
 */
#[CoversClass(TranslatingFormFactory::class)]
#[CoversClass(PhpFormLoader::class)]
#[UsesClass(\Derafu\Form\Abstract\AbstractPropertySchema::class)]
#[UsesClass(\Derafu\Form\Abstract\AbstractType::class)]
#[UsesClass(\Derafu\Form\Abstract\AbstractUiSchemaElement::class)]
#[UsesClass(\Derafu\Form\Factory\FormFactory::class)]
#[UsesClass(\Derafu\Form\Factory\FormUiSchemaFactory::class)]
#[UsesClass(\Derafu\Form\Factory\PropertySchemaFactory::class)]
#[UsesClass(\Derafu\Form\Factory\UiSchemaElementFactory::class)]
#[UsesClass(\Derafu\Form\Form::class)]
#[UsesClass(\Derafu\Form\Loader\JsonFormLoader::class)]
#[UsesClass(\Derafu\Form\Loader\YamlFormLoader::class)]
#[UsesClass(\Derafu\Form\Options\FormOptions::class)]
#[UsesClass(\Derafu\Form\Rules\FormRules::class)]
#[UsesClass(\Derafu\Form\Schema\ArraySchema::class)]
#[UsesClass(\Derafu\Form\Schema\FormSchema::class)]
#[UsesTrait(\Derafu\Form\Schema\ObjectSchemaTrait::class)]
#[UsesClass(\Derafu\Form\Schema\StringSchema::class)]
#[UsesClass(\Derafu\Form\Translation\FormTexts::class)]
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
#[UsesClass(\Derafu\Form\UiSchema\Group::class)]
#[UsesClass(\Derafu\Form\UiSchema\VerticalLayout::class)]
#[UsesClass(PropertyDefinition::class)]
#[UsesClass(UiSchemaElementDefinition::class)]
#[UsesClass(ChoicesShorthand::class)]
#[UsesClass(FormDefinition::class)]
final class TranslatingFormFactoryTest extends TestCase
{
    private const FORMS = __DIR__ . '/../../fixtures/translated/forms';

    private function translator(string $locale = 'es'): Translator
    {
        return TranslatorFactory::create($locale, ['en'], [
            new SimpleTranslationResourceProvider([__DIR__ . '/../../fixtures/translated/translations']),
        ]);
    }

    private function factory(?Translator $translator = null): FormFactoryInterface
    {
        return new TranslatingFormFactory(
            new FormFactory(new TypeResolver(new TypeRegistry(new TypeProvider()))),
            $translator ?? $this->translator()
        );
    }

    #[Test]
    public function translatesTheFormOfAYamlFile(): void
    {
        $form = (new YamlFormLoader($this->factory(), [self::FORMS]))->load('contact')->toArray();

        $this->assertSame('Contáctanos', ArrayPath::get($form, 'schema.title'));
        $this->assertSame('Te responderemos pronto', ArrayPath::get($form, 'schema.description'));
        $this->assertSame('Tu nombre', ArrayPath::get($form, 'schema.properties.name.title'));
        $this->assertSame('Como en tu cédula', ArrayPath::get($form, 'schema.properties.name.description'));
        $this->assertSame('Tipo', ArrayPath::get($form, 'schema.properties.kind.title'));
        $this->assertSame('Etiquetas', ArrayPath::get($form, 'schema.properties.tags.title'));

        // Options of a list, in the property.
        $this->assertSame(
            ['Opción A', 'Opción B'],
            [
                ArrayPath::get($form, 'schema.properties.tags.items.oneOf.0.title'),
                ArrayPath::get($form, 'schema.properties.tags.items.oneOf.1.title'),
            ]
        );

        // The choices of the shorthand are promoted to options of the schema,
        // with their title translated and their value as it was.
        $this->assertSame(
            [['const' => 'sales', 'title' => 'Ventas'], ['const' => 'support', 'title' => 'Soporte']],
            ArrayPath::get($form, 'schema.properties.kind.oneOf')
        );

        // The UI schema.
        $this->assertSame('Escribe aquí', ArrayPath::get($form, 'uischema.elements.0.options.placeholder'));
        $this->assertSame('Texto de ayuda', ArrayPath::get($form, 'uischema.elements.0.options.help'));
        $this->assertSame('días', ArrayPath::get($form, 'uischema.elements.0.options.input_group_append_text'));
        $this->assertSame('Detalles', ArrayPath::get($form, 'uischema.elements.1.label'));
    }

    #[Test]
    public function whatIsDataIsNotTranslated(): void
    {
        $form = (new YamlFormLoader($this->factory(), [self::FORMS]))->load('contact')->toArray();

        // The default has the same text as the title, and it is a value.
        $this->assertSame('Your name', ArrayPath::get($form, 'schema.properties.name.default'));
        $this->assertSame('#/properties/name', ArrayPath::get($form, 'uischema.elements.0.scope'));
    }

    #[Test]
    public function translatesTheFormOfAJsonFileAndOfAPhpFile(): void
    {
        $json = (new JsonFormLoader($this->factory(), [self::FORMS]))->load('contact')->toArray();
        $php = (new PhpFormLoader($this->factory(), [self::FORMS]))->load('contact')->toArray();

        $this->assertSame('Tu nombre', ArrayPath::get($json, 'schema.properties.name.title'));
        $this->assertSame('Tu nombre', ArrayPath::get($php, 'schema.properties.name.title'));
    }

    #[Test]
    public function translatesADefinitionThatIsGivenAsAnArray(): void
    {
        $form = $this->factory()->create([
            'options' => ['translation_domain' => 'contact'],
            'schema' => ['type' => 'object', 'properties' => ['name' => ['type' => 'string', 'title' => 'Your name']]],
        ])->toArray();

        $this->assertSame('Tu nombre', ArrayPath::get($form, 'schema.properties.name.title'));
    }

    #[Test]
    public function aDefinitionWithoutADomainIsNotTouched(): void
    {
        $form = (new YamlFormLoader($this->factory(), [self::FORMS]))->load('without-domain')->toArray();

        $this->assertSame('Your name', ArrayPath::get($form, 'schema.properties.name.title'));
    }

    #[Test]
    public function aTextWithoutAnEntryStaysAsItWasWritten(): void
    {
        $form = $this->factory()->create([
            'options' => ['translation_domain' => 'contact'],
            'schema' => ['type' => 'object', 'properties' => ['name' => ['type' => 'string', 'title' => 'Not in the catalogue']]],
        ])->toArray();

        $this->assertSame('Not in the catalogue', ArrayPath::get($form, 'schema.properties.name.title'));
    }

    #[Test]
    public function aDomainWithoutACatalogueLeavesTheTextsAsTheyWere(): void
    {
        $form = $this->factory()->create([
            'options' => ['translation_domain' => 'another-domain'],
            'schema' => ['type' => 'object', 'properties' => ['name' => ['type' => 'string', 'title' => 'Your name']]],
        ])->toArray();

        $this->assertSame('Your name', ArrayPath::get($form, 'schema.properties.name.title'));
    }

    #[Test]
    public function theFormIsCreatedInTheLanguageOfTheTranslatorAtThatMoment(): void
    {
        $translator = $this->translator('es');
        $loader = new YamlFormLoader($this->factory($translator), [self::FORMS]);

        $this->assertSame('Tu nombre', $this->nameTitle($loader->load('contact')->toArray()));

        // A language without catalogue: the original texts.
        $translator->setLocale('fr');
        $this->assertSame('Your name', $this->nameTitle($loader->load('contact')->toArray()));

        $translator->setLocale('es');
        $this->assertSame('Tu nombre', $this->nameTitle($loader->load('contact')->toArray()));
    }

    #[Test]
    public function theClosureOfAPhpFileTranslatesWhatIsNotInTheList(): void
    {
        $loader = new PhpFormLoader($this->factory(), [self::FORMS], $this->translator());

        $form = $loader->load('closure', ['user' => 'Ana'])->toArray();

        // What the closure translates with `_t` (with a parameter).
        $this->assertSame('Hola Ana', ArrayPath::get($form, 'schema.properties.name.title'));
        // And the texts of the list of the result, by the factory.
        $this->assertSame('Tu nombre', ArrayPath::get($form, 'schema.properties.name.description'));
    }

    #[Test]
    public function theClosureReceivesTheTranslatorAndTheFunctionOnlyWhenTheLoaderHasATranslator(): void
    {
        $with = (new PhpFormLoader($this->factory(), [self::FORMS], $this->translator()))->load('context')->toArray();
        $without = (new PhpFormLoader($this->factory(), [self::FORMS]))->load('context')->toArray();

        $this->assertSame('Hola Ana', ArrayPath::get($with, 'schema.properties.a.title'));
        $this->assertSame(Translator::class, ArrayPath::get($with, 'schema.properties.a.description'));

        $this->assertSame('no _t', ArrayPath::get($without, 'schema.properties.a.title'));
        $this->assertSame('no translator', ArrayPath::get($without, 'schema.properties.a.description'));
    }

    #[Test]
    public function whatTheCallerPutsInTheContextIsNotReplaced(): void
    {
        $loader = new PhpFormLoader($this->factory(), [self::FORMS], $this->translator());

        $form = $loader->load('context', ['_t' => fn (): string => 'mine'])->toArray();

        $this->assertSame('mine', ArrayPath::get($form, 'schema.properties.a.title'));
    }

    #[Test]
    public function theFunctionOfTheContextUsesTheDomainMessagesWhenItIsNotGiven(): void
    {
        $translator = $this->translator();
        $loader = new PhpFormLoader($this->factory($translator), [self::FORMS], $translator);

        $form = $loader->load('default-domain')->toArray();

        $this->assertSame('Buen día', ArrayPath::get($form, 'schema.properties.a.title'));
    }

    /**
     * The container of an application that imports the services of the package
     * and has a translator.
     */
    private function container(string $projectDir): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', $projectDir);
        (new YamlFileLoader($container, new FileLocator(dirname(__DIR__, 3) . '/resources/config')))
            ->load('form-services.yaml');
        $container->register(Translator::class, Translator::class)
            ->setFactory([TranslatorFactory::class, 'create'])
            ->setArguments(['es', ['en'], [new SimpleTranslationResourceProvider([__DIR__ . '/../../fixtures/translated/translations'])]])
            ->setPublic(true);
        $container->setAlias(TranslatorInterface::class, Translator::class);

        return $container;
    }

    /**
     * The decoration is in the services of the package: the factory that the
     * container gives is the one that translates.
     */
    #[Test]
    public function theServicesOfThePackageDecorateTheFactoryWithTheTranslating(): void
    {
        $container = $this->container(dirname(__DIR__, 3));
        $container->getDefinition(FormFactoryInterface::class)->setPublic(true);
        $container->compile();

        $factory = $this->service($container, FormFactoryInterface::class);

        $this->assertInstanceOf(TranslatingFormFactory::class, $factory);
        $form = $factory->create([
            'options' => ['translation_domain' => 'contact'],
            'schema' => ['type' => 'object', 'properties' => ['name' => ['type' => 'string', 'title' => 'Your name']]],
        ])->toArray();
        $this->assertSame('Tu nombre', ArrayPath::get($form, 'schema.properties.name.title'));
    }

    /**
     * The loaders read `resources/forms` of the application, create the form with
     * the factory that translates, and the PHP one gives the closure the
     * translator of the container.
     */
    #[Test]
    public function theServicesOfThePackageHaveTheLoadersOfTheFormsOfTheApplication(): void
    {
        $container = $this->container(__DIR__ . '/../../fixtures/project');
        $container->getDefinition(PhpFormLoader::class)->setPublic(true);
        $container->getDefinition(YamlFormLoader::class)->setPublic(true);
        $container->compile();

        $yaml = $this->service($container, YamlFormLoader::class)->load('hello')->toArray();
        $php = $this->service($container, PhpFormLoader::class)->load('hello')->toArray();

        $this->assertSame('Tu nombre', ArrayPath::get($yaml, 'schema.properties.name.title'));
        $this->assertSame('Hola Ana', ArrayPath::get($php, 'schema.properties.name.title'));
    }

    /**
     * Which loader is `FormLoaderInterface` is a decision of each application.
     */
    #[Test]
    public function theServicesOfThePackageDoNotSetTheLoaderInterface(): void
    {
        // Before compiling: once compiled, a private service that is not used is
        // removed, and this would be true even if it were set.
        $container = $this->container(__DIR__ . '/../../fixtures/project');

        $this->assertFalse($container->has(FormLoaderInterface::class));
        $this->assertFalse($container->hasDefinition(FormLoaderInterface::class));
        $this->assertFalse($container->hasAlias(FormLoaderInterface::class));
    }

    /**
     * Private and not used: an application that does not use the loaders does not
     * have them in its container.
     */
    #[Test]
    public function theLoadersAreNotInTheContainerOfAnApplicationThatDoesNotUseThem(): void
    {
        $container = $this->container(__DIR__ . '/../../fixtures/project');
        $container->compile();

        $this->assertFalse($container->hasDefinition(PhpFormLoader::class));
        $this->assertFalse($container->hasDefinition(YamlFormLoader::class));
    }

    /**
     * @template T of object
     * @param class-string<T> $id
     * @return T
     */
    private function service(ContainerBuilder $container, string $id): object
    {
        $service = $container->get($id);
        $this->assertInstanceOf($id, $service);

        return $service;
    }

    /**
     * @param array<string, mixed> $form
     */
    private function nameTitle(array $form): mixed
    {
        return ArrayPath::get($form, 'schema.properties.name.title');
    }
}
