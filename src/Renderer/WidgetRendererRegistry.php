<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Renderer;

use Derafu\Form\Contract\Renderer\WidgetRendererInterface;
use Derafu\Form\Contract\Renderer\WidgetRendererProviderInterface;
use Derafu\Form\Contract\Renderer\WidgetRendererRegistryInterface;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;

/**
 * Registry for widget renderers.
 *
 * This class manages renderers for form widgets like text inputs, selects, etc.
 */
final class WidgetRendererRegistry implements WidgetRendererRegistryInterface
{
    /**
     * Map of widget type to renderer.
     *
     * @var array<string,WidgetRendererInterface>
     */
    private array $renderers = [];

    /**
     * Default renderer to use when no specific renderer is found.
     *
     * @var WidgetRendererInterface
     */
    private WidgetRendererInterface $defaultRenderer;

    /**
     * Constructor.
     *
     * @param array<string,WidgetRendererInterface>|WidgetRendererProviderInterface $renderers
     */
    public function __construct(array|WidgetRendererProviderInterface $renderers = [])
    {
        if ($renderers instanceof WidgetRendererProviderInterface) {
            $renderers = $renderers->getRenderers();
        }

        foreach ($renderers as $widgetType => $renderer) {
            $this->registerRenderer($widgetType, $renderer);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function registerRenderer(
        string $widgetType,
        WidgetRendererInterface $renderer
    ): static {
        $this->renderers[$widgetType] = $renderer;

        if (!isset($this->defaultRenderer)) {
            $this->defaultRenderer = $renderer;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getRenderer(string $widgetType): WidgetRendererInterface
    {
        if (isset($this->renderers[$widgetType])) {
            return $this->renderers[$widgetType];
        }

        if (isset($this->defaultRenderer)) {
            return $this->defaultRenderer;
        }

        throw new InvalidArgumentException(
            'No renderers registered for form widgets.'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function hasRenderer(string $widgetType): bool
    {
        return isset($this->renderers[$widgetType]);
    }
}
