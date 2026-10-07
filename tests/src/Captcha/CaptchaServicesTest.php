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

use Derafu\DataProcessor\Contract\ProcessorInterface;
use Derafu\DataProcessor\ProcessorFactory;
use Derafu\Form\Contract\Captcha\CaptchaProviderInterface;
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
 * The captcha provider of an application reaches the renderer and the processor
 * of the forms through the services of the package, and it is optional: without
 * it a form that is protected with the captcha fails.
 */
#[CoversNothing]
final class CaptchaServicesTest extends TestCase
{
    private function container(bool $withProvider): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', sys_get_temp_dir());
        (new YamlFileLoader($container, new FileLocator(dirname(__DIR__, 3) . '/resources/config')))
            ->load('form-services.yaml');

        $container->register(ProcessorInterface::class, ProcessorInterface::class)
            ->setFactory([ProcessorFactory::class, 'create']);

        if ($withProvider) {
            $container->register(InMemoryCaptchaProvider::class, InMemoryCaptchaProvider::class);
            $container->setAlias(CaptchaProviderInterface::class, InMemoryCaptchaProvider::class);
        }

        $container->getDefinition(FormRendererInterface::class)->setPublic(true);
        $container->getDefinition(FormDataProcessorInterface::class)->setPublic(true);
        $container->compile(true);

        return $container;
    }

    private function form(): Form
    {
        return Form::fromArray([
            'schema' => [
                'name' => 'contact',
                'type' => 'object',
                'properties' => ['email' => ['type' => 'string', 'title' => 'Email']],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [['type' => 'Control', 'scope' => '#/properties/email']],
            ],
            'options' => ['captcha_protection' => true, 'csrf_protection' => false],
        ]);
    }

    #[Test]
    public function theProviderOfTheApplicationIsUsedToRenderAndToProcess(): void
    {
        $container = $this->container(withProvider: true);

        $html = $container->get(FormRendererInterface::class)->render($this->form());
        $this->assertStringContainsString('<div class="test-captcha" data-form="contact"></div>', $html);

        $processor = $container->get(FormDataProcessorInterface::class);
        $this->assertFalse($processor->process($this->form(), ['email' => 'a@b.cl'])->isValid());
        $this->assertTrue($processor->process($this->form(), ['email' => 'a@b.cl', 'test-captcha-response' => 'solved-contact'])->isValid());
    }

    #[Test]
    public function withoutAProviderAFormThatIsProtectedFails(): void
    {
        $container = $this->container(withProvider: false);

        try {
            $container->get(FormRendererInterface::class)->render($this->form());
            $this->fail('A protected form was rendered without a captcha.');
        } catch (RuntimeError $e) {
            $this->assertInstanceOf(TranslatableLogicException::class, $e->getPrevious());
        }

        $this->expectException(TranslatableLogicException::class);
        $container->get(FormDataProcessorInterface::class)->process($this->form(), ['email' => 'a@b.cl']);
    }
}
