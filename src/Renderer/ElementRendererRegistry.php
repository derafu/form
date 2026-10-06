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

use Derafu\Form\Contract\Renderer\ElementRendererInterface;
use Derafu\Form\Contract\Renderer\ElementRendererProviderInterface;
use Derafu\Form\Contract\Renderer\ElementRendererRegistryInterface;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;

/**
 * Registry for element renderers.
 *
 * This class manages a collection of element renderers, allowing for
 * registration and retrieval of renderers by element type. It also provides a
 * default renderer that will be used if no specific renderer is found for a
 * given element type.
 */
final class ElementRendererRegistry implements ElementRendererRegistryInterface
{
    /**
     * Map of element type to renderer.
     *
     * @var array<string,ElementRendererInterface>
     */
    private array $renderers = [];

    /**
     * Default renderer to use when no specific renderer is found.
     *
     * @var ElementRendererInterface
     */
    private ElementRendererInterface $defaultRenderer;

    /**
     * Constructor.
     *
     * @param array<string,ElementRendererInterface>|ElementRendererProviderInterface $renderers
     */
    public function __construct(array|ElementRendererProviderInterface $renderers = [])
    {
        if ($renderers instanceof ElementRendererProviderInterface) {
            $renderers = $renderers->getRenderers();
        }

        foreach ($renderers as $elementType => $renderer) {
            $this->registerRenderer($elementType, $renderer);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function registerRenderer(string $elementType, ElementRendererInterface $renderer): static
    {
        $this->renderers[$elementType] = $renderer;

        if (!isset($this->defaultRenderer)) {
            $this->defaultRenderer = $renderer;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getRenderer(string $elementType): ElementRendererInterface
    {
        if (isset($this->renderers[$elementType])) {
            return $this->renderers[$elementType];
        }

        if (isset($this->defaultRenderer)) {
            return $this->defaultRenderer;
        }

        throw new InvalidArgumentException(
            'No renderers registered for form elements.'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function hasRenderer(string $elementType): bool
    {
        return isset($this->renderers[$elementType]);
    }
}
