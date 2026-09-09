<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Tests\Renderer;

use Derafu\Form\Renderer\Element\ControlRenderer;
use Derafu\Form\Renderer\ElementRendererProvider;
use Derafu\Form\Renderer\Support\InputActionResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[CoversClass(ElementRendererProvider::class)]
#[CoversClass(ControlRenderer::class)]
#[CoversClass(InputActionResolver::class)]
final class ElementRendererProviderTest extends TestCase
{
    public function testForwardsTheGivenActionResolverToTheControlRenderer(): void
    {
        $actionResolver = new InputActionResolver();

        $provider = new ElementRendererProvider(actionResolver: $actionResolver);
        $renderers = $provider->getRenderers();

        $this->assertInstanceOf(ControlRenderer::class, $renderers['Control']);

        $property = new ReflectionProperty(ControlRenderer::class, 'actionResolver');
        $this->assertSame($actionResolver, $property->getValue($renderers['Control']));
    }

    public function testWithoutAnActionResolverTheControlRendererGetsItsOwnDefault(): void
    {
        $provider = new ElementRendererProvider();
        $renderers = $provider->getRenderers();

        $property = new ReflectionProperty(ControlRenderer::class, 'actionResolver');
        $this->assertInstanceOf(InputActionResolver::class, $property->getValue($renderers['Control']));
    }
}
