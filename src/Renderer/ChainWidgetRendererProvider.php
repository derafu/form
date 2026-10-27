<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Renderer;

use Derafu\Form\Contract\Renderer\WidgetRendererInterface;
use Derafu\Form\Contract\Renderer\WidgetRendererProviderInterface;

/**
 * Combines providers in priority order; the first renderer for a type wins.
 */
final class ChainWidgetRendererProvider implements WidgetRendererProviderInterface
{
    /**
     * @param iterable<WidgetRendererProviderInterface> $providers
     */
    public function __construct(private readonly iterable $providers = [])
    {
    }

    /**
     * @return array<string,WidgetRendererInterface>
     */
    public function getRenderers(): array
    {
        $renderers = [];
        foreach ($this->providers as $provider) {
            $renderers += $provider->getRenderers();
        }

        return $renderers;
    }
}
