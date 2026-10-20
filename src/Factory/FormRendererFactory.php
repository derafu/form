<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Factory;

use Derafu\Form\Contract\Factory\FormRendererFactoryInterface;
use Derafu\Form\Contract\Renderer\FormRendererInterface;
use Derafu\Form\Processor\UiSchemaRuleEvaluator;
use Derafu\Form\Renderer\ElementRendererProvider;
use Derafu\Form\Renderer\ElementRendererRegistry;
use Derafu\Form\Renderer\FormRenderer;
use Derafu\Form\Renderer\FormTwigExtension;
use Derafu\Form\Renderer\WidgetRendererProvider;
use Derafu\Form\Renderer\WidgetRendererRegistry;
use Derafu\Renderer\Engine\Html\TwigHtmlEngine;
use Derafu\Renderer\Factory\RendererFactory;

/**
 * Factory class for creating form renderer instances.
 */
final class FormRendererFactory implements FormRendererFactoryInterface
{
    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $options
     */
    public static function create(array $options = []): FormRendererInterface
    {
        /**
         * @var array{
         *     renderer: array{
         *         paths?: list<string>,
         *         engines?: array<\Derafu\Renderer\Contract\EngineInterface|string>,
         *         formatters?: array<string, string|array<mixed>|object>,
         *         extensions?: array<\Twig\Extension\ExtensionInterface>,
         *         extra?: bool,
         *         components?: \Derafu\Twig\Contract\ComponentProviderInterface|iterable<class-string|object>,
         *         pdf?: array<string, mixed>
         *     },
         *     element_renderers?: array<string, \Derafu\Form\Contract\Renderer\ElementRendererInterface>|\Derafu\Form\Contract\Renderer\ElementRendererProviderInterface,
         *     widget_renderers?: array<string, \Derafu\Form\Contract\Renderer\WidgetRendererInterface>|\Derafu\Form\Contract\Renderer\WidgetRendererProviderInterface,
         *     csrf_token_manager?: \Derafu\Form\Contract\Csrf\CsrfTokenManagerInterface|null,
         *     captcha_provider?: \Derafu\Form\Contract\Captcha\CaptchaProviderInterface|null
         * } $options
         */
        $options['renderer']['paths'] = array_merge(
            $options['renderer']['paths'] ?? [],
            [__DIR__ . '/../../resources/templates']
        );
        $twigService = RendererFactory::createTwigService($options['renderer']);
        $twigEngine = new TwigHtmlEngine($twigService);
        $options['renderer']['engines'] = [$twigEngine];

        $renderer = RendererFactory::create($options['renderer']);

        // Rule evaluator is shared between ControlRenderer (initial render
        // state) and, via the DI container, FormDataProcessor (processing).
        $ruleEvaluator = new UiSchemaRuleEvaluator();

        $elementRendererRegistry = new ElementRendererRegistry(
            $options['element_renderers'] ?? new ElementRendererProvider($ruleEvaluator)
        );

        $widgetRendererRegistry = new WidgetRendererRegistry(
            $options['widget_renderers'] ?? new WidgetRendererProvider()
        );

        $formRenderer = new FormRenderer(
            $renderer,
            $elementRendererRegistry,
            $widgetRendererRegistry,
            $options['csrf_token_manager'] ?? null,
            $options['captcha_provider'] ?? null
        );

        $twigService->getTwig()->addExtension(new FormTwigExtension($formRenderer));

        return $formRenderer;
    }
}
