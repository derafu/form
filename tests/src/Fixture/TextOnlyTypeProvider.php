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

use Derafu\Form\Contract\Type\TypeProviderInterface;
use Derafu\Form\Type\TextType;

/**
 * An application that treats strings as text without guessing specialized types.
 */
final class TextOnlyTypeProvider implements TypeProviderInterface
{
    public function getTypes(): array
    {
        return [TextType::class];
    }
}
