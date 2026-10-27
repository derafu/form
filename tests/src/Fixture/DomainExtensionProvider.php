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

use Derafu\Twig\Contract\ExtensionProviderInterface;
use Twig\Extension\StringLoaderExtension;

final class DomainExtensionProvider implements ExtensionProviderInterface
{
    public function getExtensions(array $options = []): iterable
    {
        yield new StringLoaderExtension();
    }
}
