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

use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Contract\Renderer\ElementRendererInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaElementInterface;

final class DomainElementRenderer implements ElementRendererInterface
{
    public function render(UiSchemaElementInterface $element, FormInterface $form, array $options = []): string
    {
        return '<aside>Domain label</aside>';
    }
}
