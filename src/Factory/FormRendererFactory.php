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
     */
    public static function create(array $options = []): FormRendererInterface
    {
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
            $widgetRendererRegistry
        );

        $twigService->getTwig()->addExtension(new FormTwigExtension($formRenderer));

        return $formRenderer;
    }
}
