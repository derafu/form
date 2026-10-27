<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm;

use Composer\InstalledVersions;
use Derafu\Form\Contract\Factory\FormFactoryInterface;
use Derafu\Form\Contract\FormFieldInterface;
use Derafu\Form\Contract\Processor\FormDataProcessorInterface;
use Derafu\Form\Contract\Processor\FormRulesResolverInterface;
use Derafu\Form\Contract\Renderer\FormRendererInterface;
use Derafu\Form\Contract\Type\TypeRegistryInterface;
use Derafu\Form\Contract\Type\TypeResolverInterface;
use Derafu\Form\Factory\TranslatingFormFactory;
use Derafu\Form\Renderer\FormExtensionProvider;
use Derafu\Form\Renderer\FormTwigExtension;
use Derafu\Form\Renderer\Support\InputActionResolver;
use Derafu\Form\Translation\FormTranslationResourceProvider;
use Derafu\Form\Type\IntegerType;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The package's complete service configuration, with the real Data Processor
 * configuration and the translator supplied by a consuming application.
 * Focused loader, CSRF and Captcha behavior remains in their existing tests.
 */
#[CoversNothing]
final class ServicesTest extends TestCase
{
    private function loadContainer(string $configuration = 'services.yaml'): ContainerBuilder
    {
        $root = dirname(__DIR__, 2);
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', __DIR__ . '/../fixtures/project');
        (new YamlFileLoader($container, new FileLocator([
            $root . '/tests/fixtures/config',
            (InstalledVersions::getInstallPath('derafu/renderer')
                ?? throw new RuntimeException('derafu/renderer must be installed for these tests.')) . '/resources/config',
        ])))
            ->load($configuration);

        return $container;
    }

    private function compile(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $definition) {
            $definition->setPublic(true);
        }
        foreach ($container->getAliases() as $alias) {
            $alias->setPublic(true);
        }
        $container->compile();
    }

    /**
     * @template T of object
     * @param class-string<T> $id
     * @return T
     */
    private function service(ContainerBuilder $container, string $id): object
    {
        $service = $container->get($id);
        self::assertInstanceOf($id, $service);

        return $service;
    }

    public function testEveryConfiguredServiceAndAliasCanBeConstructed(): void
    {
        $container = $this->loadContainer();
        $ids = array_unique(array_merge(array_keys($container->getDefinitions()), array_keys($container->getAliases())));
        $this->compile($container);
        // Explicitly request every original definition: otherwise unused
        // private services can disappear and conceal a broken constructor.
        foreach ($ids as $id) {
            $container->get($id);
        }
        self::assertInstanceOf(TranslatingFormFactory::class, $container->get(FormFactoryInterface::class));
        self::assertInstanceOf(FormDataProcessorInterface::class, $container->get(FormDataProcessorInterface::class));
        self::assertInstanceOf(FormRendererInterface::class, $container->get(FormRendererInterface::class));
    }

    public function testConfiguredTypeServicesCanResolveRegisteredTypes(): void
    {
        $container = $this->loadContainer();
        $this->compile($container);
        $registry = $this->service($container, TypeRegistryInterface::class);
        $resolver = $this->service($container, TypeResolverInterface::class);

        self::assertInstanceOf(IntegerType::class, $registry->get(IntegerType::class));
        self::assertSame($registry->get(IntegerType::class), $resolver->guess(12));
    }

    public function testTheConfiguredTypeProviderAffectsInferenceRenderingAndProcessing(): void
    {
        $container = $this->loadContainer('text-types.yaml');
        $this->compile($container);
        $form = $this->service($container, FormFactoryInterface::class)->create([
            'data' => ['contact' => 'ada@example.com'],
            'options' => ['csrf_protection' => false],
        ]);

        $html = $this->service($container, FormRendererInterface::class)->render($form);
        self::assertStringContainsString('type="text"', $html);
        self::assertStringNotContainsString('type="email"', $html);
        $result = $this->service($container, FormDataProcessorInterface::class)->process($form, ['contact' => 'plain text']);
        self::assertTrue($result->isValid());
        self::assertSame('plain text', $result->getReader()->requireString('contact'));
    }

    public function testTheTaggedProviderExposesBothPackageCatalogues(): void
    {
        $container = $this->loadContainer();
        self::assertArrayHasKey(
            FormTranslationResourceProvider::class,
            $container->findTaggedServiceIds('derafu_translation.resource_provider')
        );
        $this->compile($container);
        $provider = $this->service($container, FormTranslationResourceProvider::class);
        $directories = [...$provider->getDirectories()];
        self::assertCount(1, $directories);
        self::assertSame(realpath(dirname(__DIR__, 2) . '/resources/translations'), realpath($directories[0]));
        self::assertFileExists($directories[0] . '/errors+intl-icu.es.php');
        self::assertFileExists($directories[0] . '/form+intl-icu.es.php');
    }

    public function testTheConfiguredRendererUsesTranslatedInputActions(): void
    {
        $container = $this->loadContainer();
        $this->compile($container);
        $form = $this->service($container, FormFactoryInterface::class)->create([
            'schema' => [
                'type' => 'object',
                'properties' => ['password' => ['type' => 'string', 'title' => 'Password']],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [[
                    'type' => 'Control',
                    'scope' => '#/properties/password',
                    'options' => ['type' => 'password', 'actions' => ['toggle-password', 'copy']],
                ]],
            ],
            'options' => ['csrf_protection' => false],
        ]);
        $field = $form->getField('password');
        self::assertInstanceOf(FormFieldInterface::class, $field);
        $actions = $this->service($container, InputActionResolver::class)->resolve(['copy'], $field);
        self::assertSame('Copiar valor', $actions[0]['label']);
        $html = $this->service($container, FormRendererInterface::class)->render($form);
        self::assertStringContainsString('Mostrar u ocultar la contraseña', $html);
        self::assertStringContainsString('Copiar valor', $html);
        self::assertStringContainsString('name="password"', $html);
        $provider = $this->service($container, FormExtensionProvider::class);
        $extension = [...$provider->getExtensions()][0];
        self::assertInstanceOf(FormTwigExtension::class, $extension);
        self::assertSame('form', $extension->getFunctions()[0]->getName());
    }

    public function testTheConfiguredProcessorUsesDerivedRulesAndTaggedTranslations(): void
    {
        $container = $this->loadContainer();
        $this->compile($container);
        $form = $this->service($container, FormFactoryInterface::class)->create([
            'schema' => [
                'type' => 'object',
                'required' => ['name'],
                'properties' => ['name' => ['type' => 'string', 'minLength' => 3]],
            ],
            'options' => ['csrf_protection' => false],
        ]);
        $this->service($container, FormRulesResolverInterface::class)->resolve($form);
        self::assertContains('min_length:3', $form->getRules()->getValidateRules('name'));
        $processor = $this->service($container, FormDataProcessorInterface::class);
        $valid = $processor->process($form, ['name' => 'Ada']);
        self::assertTrue($valid->isValid());
        self::assertSame('Ada', $valid->getReader()->requireString('name'));
        $invalid = $processor->process($form, ['name' => 'ab']);
        self::assertFalse($invalid->isValid());
        self::assertSame(['Debe tener al menos 3 caracteres.'], $invalid->getFieldErrors('name'));
    }

    public function testTheTaggedResourcesTranslateARealFormFactoryError(): void
    {
        $container = $this->loadContainer();
        $this->compile($container);
        $translator = $this->service($container, TranslatorInterface::class);
        $factory = $this->service($container, FormFactoryInterface::class);
        try {
            $factory->create([]);
            self::fail('A definition without schema or data was accepted.');
        } catch (TranslatableInvalidArgumentException $exception) {
            self::assertSame(
                'La definición del esquema del formulario debe asignarse si no se entregan los datos del formulario.',
                $exception->trans($translator)
            );
        }
    }
}
