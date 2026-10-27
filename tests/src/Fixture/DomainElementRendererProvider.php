<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm\Fixture;

use Derafu\Form\Contract\Renderer\ElementRendererInterface;
use Derafu\Form\Contract\Renderer\ElementRendererProviderInterface;

final class DomainElementRendererProvider implements ElementRendererProviderInterface
{
    public function __construct(private readonly ElementRendererInterface $renderer)
    {
    }

    public function getRenderers(): array
    {
        return ['Label' => $this->renderer];
    }
}
