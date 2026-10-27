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

use Composer\InstalledVersions;
use Derafu\Form\Contract\Factory\FormFactoryInterface;
use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Contract\Processor\FormDataProcessorInterface;
use Derafu\Form\Contract\Processor\UiSchemaRuleEvaluatorInterface;
use Derafu\Form\Contract\Renderer\FormRendererInterface;
use Derafu\Form\Renderer\ChainElementRendererProvider;
use Derafu\Form\Renderer\ChainWidgetRendererProvider;
use Derafu\Form\Renderer\FormExtensionProvider;
use Derafu\Form\Renderer\FormTemplatePathProvider;
use Derafu\Form\Renderer\FormTwigExtension;
use Derafu\Renderer\Contract\RendererInterface;
use Derafu\Renderer\Engine\Html\TwigHtmlEngine;
use Derafu\TestsForm\Fixture\InactiveRuleEvaluator;
use Derafu\Twig\Contract\TwigServiceInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Twig\Extension\StringLoaderExtension;

#[CoversClass(ChainElementRendererProvider::class)]
#[CoversClass(ChainWidgetRendererProvider::class)]
#[CoversClass(FormExtensionProvider::class)]
#[CoversClass(FormTemplatePathProvider::class)]
#[UsesNamespace('Derafu\Form')]
final class RendererCompositionTest extends TestCase
{
    private function container(string $file = 'composition.yaml'): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', dirname(__DIR__, 2) . '/fixtures/project');
        (new YamlFileLoader($container, new FileLocator([
            dirname(__DIR__, 2) . '/fixtures/config',
            (InstalledVersions::getInstallPath('derafu/renderer')
                ?? throw new RuntimeException('derafu/renderer must be installed for these tests.')) . '/resources/config',
        ])))->load($file);
        foreach ($container->getDefinitions() as $definition) {
            $definition->setPublic(true);
        }
        foreach ($container->getAliases() as $alias) {
            $alias->setPublic(true);
        }
        $container->compile();

        return $container;
    }

    /**
     * @template T of object
     * @param class-string<T> $id
     * @return T
     */
    private function service(ContainerInterface $container, string $id): object
    {
        $service = $container->get($id);
        self::assertInstanceOf($id, $service);

        return $service;
    }

    private function form(ContainerInterface $container): FormInterface
    {
        return $this->service($container, FormFactoryInterface::class)->create([
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string', 'description' => 'Your name'],
                    'email' => ['type' => 'string', 'format' => 'email'],
                ],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    ['type' => 'Label', 'text' => 'Original label'],
                    ['type' => 'Control', 'scope' => '#/properties/name'],
                    ['type' => 'Control', 'scope' => '#/properties/email'],
                ],
            ],
            'options' => ['csrf_protection' => false],
        ]);
    }

    public function testProvidersOverrideTheirOwnTypesAndPreserveTheOtherDefaults(): void
    {
        $container = $this->container();
        $html = $this->service($container, FormRendererInterface::class)->render($this->form($container));
        self::assertStringContainsString('<aside>Domain label</aside>', $html);
        self::assertStringContainsString('<input data-domain-widget name="name">', $html);
        self::assertStringContainsString('type="email"', $html);
        self::assertStringContainsString('<p data-domain-help>Your name</p>', $html);
        self::assertStringContainsString('<form', $html);
        self::assertStringContainsString('</form>', $html);
    }

    public function testFormAndApplicationShareTheRendererEnvironmentAndContributions(): void
    {
        $container = $this->container();
        $applicationTwig = $this->service($container, TwigServiceInterface::class);
        $applicationRenderer = $this->service($container, RendererInterface::class);
        $formRenderer = $this->service($container, FormRendererInterface::class);
        self::assertSame($applicationRenderer, $formRenderer->getRenderer());
        $engine = $applicationRenderer->getEngine('twig');
        self::assertInstanceOf(TwigHtmlEngine::class, $engine);
        self::assertSame($applicationTwig->getTwig(), $engine->getTwig());
        self::assertTrue($applicationTwig->getTwig()->hasExtension(FormTwigExtension::class));
        self::assertTrue($applicationTwig->getTwig()->getLoader()->exists('form/form.html.twig'));
        self::assertStringContainsString('<form', $applicationRenderer->renderFromString(
            '{{ form(form) }}',
            ['form' => $this->form($container)]
        ));
        self::assertSame('<strong>Shared component</strong>', $applicationRenderer->renderFromString(
            '<twig:form-domain content="Shared component" />'
        ));
        $data = ['value' => 'extension'];
        self::assertSame('Custom extension', $applicationTwig->renderFromString(
            "{{ include(template_from_string('Custom {{ value }}')) }}",
            $data
        ));
        self::assertTrue($applicationTwig->getTwig()->hasExtension(StringLoaderExtension::class));
        self::assertNotNull($applicationTwig->getTwig()->getFunction('component'));
    }

    public function testTheSameConfiguredEvaluatorControlsRenderingAndProcessing(): void
    {
        $container = $this->container('evaluator.yaml');
        $form = $this->service($container, FormFactoryInterface::class)->create([
            'schema' => ['type' => 'object', 'properties' => ['name' => ['type' => 'string']]],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [[
                    'type' => 'Control',
                    'scope' => '#/properties/name',
                    'rule' => [
                        'effect' => 'ENABLE',
                        'condition' => ['scope' => '#/properties/name', 'schema' => ['const' => 'Ada']],
                    ],
                ]],
            ],
            'data' => ['name' => 'Ada'],
            'options' => ['csrf_protection' => false],
        ]);
        $evaluator = $this->service($container, UiSchemaRuleEvaluatorInterface::class);
        self::assertInstanceOf(InactiveRuleEvaluator::class, $evaluator);
        $html = $this->service($container, FormRendererInterface::class)->render($form);
        self::assertMatchesRegularExpression('/<input[^>]*name="name"[^>]*disabled/', html_entity_decode($html));
        self::assertSame(1, $evaluator->calls);
        $result = $this->service($container, FormDataProcessorInterface::class)->process($form, ['name' => 'Ada']);
        self::assertTrue($result->isValid());
        self::assertSame([], $result->getReader()->toArray());
        self::assertSame(2, $evaluator->calls);
    }

    public function testTheCompiledContainerRendersWithTheContributionsAndWithoutACircularDependency(): void
    {
        $container = $this->container();
        $class = 'FormRendererCompositionContainer';
        $code = '';
        if (!class_exists($class, false)) {
            $dump = (new PhpDumper($container))->dump(['class' => $class]);
            self::assertIsString($dump);
            $code = substr($dump, 5);
        }
        $compiled = eval($code . "\nreturn new " . $class . '();');
        self::assertInstanceOf(ContainerInterface::class, $compiled);
        self::assertSame(
            $this->service($compiled, RendererInterface::class),
            $this->service($compiled, FormRendererInterface::class)->getRenderer()
        );
        $html = $this->service($compiled, FormRendererInterface::class)->render($this->form($compiled));
        self::assertStringContainsString('<aside>Domain label</aside>', $html);
        self::assertStringContainsString('data-domain-widget', $html);
        self::assertStringContainsString('<form', $html);
    }
}
