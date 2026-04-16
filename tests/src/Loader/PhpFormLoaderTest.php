<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm\Loader;

use Derafu\Form\Abstract\AbstractFileFormLoader;
use Derafu\Form\Contract\Factory\FormFactoryInterface;
use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Loader\PhpFormLoader;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(PhpFormLoader::class)]
#[UsesClass(AbstractFileFormLoader::class)]
final class PhpFormLoaderTest extends TestCase
{
    private const FIXTURES = __DIR__ . '/../../fixtures/forms';

    private const OVERRIDES = __DIR__ . '/../../fixtures/forms-override';

    public function testLoadsStaticArray(): void
    {
        $captured = null;
        $loader = new PhpFormLoader($this->makeFactory($captured));
        $loader->addPath(self::FIXTURES);

        $loader->load('static');

        $this->assertIsArray($captured);
        $this->assertSame('object', $captured['schema']['type']);
    }

    public function testLoadsClosureAndPassesContext(): void
    {
        $captured = null;
        $loader = new PhpFormLoader($this->makeFactory($captured));
        $loader->addPath(self::FIXTURES);

        $loader->load('dynamic', ['name' => 'alice']);

        $this->assertSame('alice', $captured['data']['name']);
    }

    public function testDataArgumentMergesIntoDefinition(): void
    {
        $captured = null;
        $loader = new PhpFormLoader($this->makeFactory($captured));
        $loader->addPath(self::FIXTURES);

        $loader->load('with-data', [], ['name' => 'from-call']);

        $this->assertSame('from-call', $captured['data']['name']);
        $this->assertSame(10, $captured['data']['age']);
    }

    public function testMissingFileThrows(): void
    {
        $loader = new PhpFormLoader($this->makeFactory($_unused));
        $loader->addPath(self::FIXTURES);

        $this->expectException(RuntimeException::class);
        $loader->load('does-not-exist');
    }

    public function testInvalidNameThrows(): void
    {
        $loader = new PhpFormLoader($this->makeFactory($_unused));
        $loader->addPath(self::FIXTURES);

        $this->expectException(InvalidArgumentException::class);
        $loader->load('../evil');
    }

    public function testLastRegisteredPathWins(): void
    {
        $captured = null;
        $loader = new PhpFormLoader($this->makeFactory($captured));
        $loader->addPath(self::FIXTURES);
        $loader->addPath(self::OVERRIDES);

        $loader->load('static');

        $this->assertSame('overridden', $captured['data']['name']);
    }

    public function testNestedNameResolves(): void
    {
        $captured = null;
        $loader = new PhpFormLoader($this->makeFactory($captured));
        $loader->addPath(self::FIXTURES);

        $loader->load('sub/nested');

        $this->assertSame('nested', $captured['data']['tag']);
    }

    /**
     * Returns a mocked FormFactoryInterface that captures the definition
     * passed to `create()` into `$captured` by reference.
     */
    private function makeFactory(mixed &$captured): FormFactoryInterface
    {
        $factory = $this->createMock(FormFactoryInterface::class);
        $form = $this->createMock(FormInterface::class);
        $factory->method('create')->willReturnCallback(
            function (array $definition) use (&$captured, $form): FormInterface {
                $captured = $definition;
                return $form;
            }
        );

        return $factory;
    }
}
