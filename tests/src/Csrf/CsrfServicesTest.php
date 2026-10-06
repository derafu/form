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

use Derafu\DataProcessor\Contract\ProcessorInterface;
use Derafu\DataProcessor\ProcessorFactory;
use Derafu\Form\Contract\Csrf\CsrfTokenManagerInterface;
use Derafu\Form\Contract\Processor\FormDataProcessorInterface;
use Derafu\Form\Contract\Renderer\FormRendererInterface;
use Derafu\Form\Form;
use Derafu\Translation\Exception\Core\TranslatableLogicException;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Twig\Error\RuntimeError;

/**
 * The CSRF token manager of an application reaches the renderer and the
 * processor of the forms through the services of the package, and the manager
 * is optional: without it a form that is protected fails, and one that is not
 * protected works.
 */
#[CoversNothing]
final class CsrfServicesTest extends TestCase
{
    private function container(bool $withManager): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', sys_get_temp_dir());
        (new YamlFileLoader($container, new FileLocator(dirname(__DIR__, 3) . '/resources/config')))
            ->load('form-services.yaml');

        $container->register(ProcessorInterface::class, ProcessorInterface::class)
            ->setFactory([ProcessorFactory::class, 'create']);

        if ($withManager) {
            $container->register(InMemoryCsrfTokenManager::class, InMemoryCsrfTokenManager::class)
                ->setPublic(true);
            $container->setAlias(CsrfTokenManagerInterface::class, InMemoryCsrfTokenManager::class);
        }

        $container->getDefinition(FormRendererInterface::class)->setPublic(true);
        $container->getDefinition(FormDataProcessorInterface::class)->setPublic(true);
        $container->compile(true);

        return $container;
    }

    private function form(bool $protected = true): Form
    {
        return Form::fromArray([
            'schema' => [
                'type' => 'object',
                'properties' => ['email' => ['type' => 'string', 'title' => 'Email']],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [['type' => 'Control', 'scope' => '#/properties/email']],
            ],
            'options' => ['csrf_protection' => $protected],
        ]);
    }

    #[Test]
    public function theManagerOfTheApplicationIsUsedToRenderAndToProcess(): void
    {
        $container = $this->container(withManager: true);
        $manager = $container->get(InMemoryCsrfTokenManager::class);
        $this->assertInstanceOf(InMemoryCsrfTokenManager::class, $manager);

        $html = $container->get(FormRendererInterface::class)->render($this->form());
        $this->assertStringContainsString('value="' . $manager->getToken('form') . '"', $html);

        $processor = $container->get(FormDataProcessorInterface::class);
        $this->assertFalse($processor->process($this->form(), ['email' => 'a@b.cl'])->isValid());
        $this->assertTrue($processor->process($this->form(), ['email' => 'a@b.cl', '_token' => $manager->getToken('form')])->isValid());
    }

    #[Test]
    public function withoutAManagerAFormThatIsProtectedFails(): void
    {
        $container = $this->container(withManager: false);

        try {
            $container->get(FormRendererInterface::class)->render($this->form());
            $this->fail('A protected form was rendered without a CSRF token manager.');
        } catch (RuntimeError $e) {
            $this->assertInstanceOf(TranslatableLogicException::class, $e->getPrevious());
        }

        $this->expectException(TranslatableLogicException::class);
        $container->get(FormDataProcessorInterface::class)->process($this->form(), ['email' => 'a@b.cl']);
    }

    #[Test]
    public function withoutAManagerAFormThatIsNotProtectedWorks(): void
    {
        $container = $this->container(withManager: false);

        $html = $container->get(FormRendererInterface::class)->render($this->form(protected: false));
        $this->assertStringNotContainsString('_token', $html);
        $this->assertTrue($container->get(FormDataProcessorInterface::class)->process($this->form(protected: false), ['email' => 'a@b.cl'])->isValid());
    }
}
