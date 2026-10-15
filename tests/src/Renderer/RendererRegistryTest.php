<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm\Renderer;

use Derafu\Form\Contract\Renderer\ElementRendererInterface;
use Derafu\Form\Contract\Renderer\WidgetRendererInterface;
use Derafu\Form\Renderer\ElementRendererRegistry;
use Derafu\Form\Renderer\WidgetRendererRegistry;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The registries give the renderer of a type, the first one that was registered
 * when the type has none, and fail when there are no renderers at all.
 */
#[CoversClass(ElementRendererRegistry::class)]
#[CoversClass(WidgetRendererRegistry::class)]
final class RendererRegistryTest extends TestCase
{
    #[Test]
    public function shouldGiveTheRendererOfAnElementTypeOrTheFirstOneRegistered(): void
    {
        $first = $this->createStub(ElementRendererInterface::class);
        $second = $this->createStub(ElementRendererInterface::class);
        $registry = new ElementRendererRegistry(['A' => $first]);

        $this->assertSame($registry, $registry->registerRenderer('B', $second));
        $this->assertSame($first, $registry->getRenderer('A'));
        $this->assertSame($second, $registry->getRenderer('B'));
        $this->assertSame($first, $registry->getRenderer('Unknown'));
        $this->assertTrue($registry->hasRenderer('B'));
        $this->assertFalse($registry->hasRenderer('Unknown'));
    }

    #[Test]
    public function shouldGiveTheRendererOfAWidgetTypeOrTheFirstOneRegistered(): void
    {
        $first = $this->createStub(WidgetRendererInterface::class);
        $second = $this->createStub(WidgetRendererInterface::class);
        $registry = new WidgetRendererRegistry(['a' => $first]);

        $this->assertSame($registry, $registry->registerRenderer('b', $second));
        $this->assertSame($first, $registry->getRenderer('a'));
        $this->assertSame($second, $registry->getRenderer('b'));
        $this->assertSame($first, $registry->getRenderer('unknown'));
        $this->assertTrue($registry->hasRenderer('b'));
        $this->assertFalse($registry->hasRenderer('unknown'));
    }

    #[Test]
    public function shouldFailWhenThereAreNoElementRenderers(): void
    {
        $this->expectException(TranslatableInvalidArgumentException::class);

        (new ElementRendererRegistry())->getRenderer('A');
    }

    #[Test]
    public function shouldFailWhenThereAreNoWidgetRenderers(): void
    {
        $this->expectException(TranslatableInvalidArgumentException::class);

        (new WidgetRendererRegistry())->getRenderer('a');
    }
}
