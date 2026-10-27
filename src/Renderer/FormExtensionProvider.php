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

use Derafu\Form\Contract\Renderer\FormRendererInterface;
use Derafu\Twig\Contract\ExtensionProviderInterface;

final class FormExtensionProvider implements ExtensionProviderInterface
{
    public function __construct(private readonly FormRendererInterface $renderer)
    {
    }

    public function getExtensions(array $options = []): iterable
    {
        yield new FormTwigExtension($this->renderer);
    }
}
