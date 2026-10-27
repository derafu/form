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

use Derafu\Twig\Contract\TemplatePathProviderInterface;

final class FormTemplatePathProvider implements TemplatePathProviderInterface
{
    public function getPaths(): array
    {
        return [dirname(__DIR__, 2) . '/resources/templates'];
    }
}
