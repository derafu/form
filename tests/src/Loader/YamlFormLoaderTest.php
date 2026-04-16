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
use Derafu\Form\Loader\YamlFormLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(YamlFormLoader::class)]
#[UsesClass(AbstractFileFormLoader::class)]
final class YamlFormLoaderTest extends TestCase
{
    private const FIXTURES = __DIR__ . '/../../fixtures/forms';

    public function testLoadsValidYaml(): void
    {
        $captured = null;
        $loader = new YamlFormLoader($this->makeFactory($captured));
        $loader->addPath(self::FIXTURES);

        $loader->load('static');

        $this->assertSame('from-yaml', $captured['data']['name']);
    }

    public function testDataArgumentMerges(): void
    {
        $captured = null;
        $loader = new YamlFormLoader($this->makeFactory($captured));
        $loader->addPath(self::FIXTURES);

        $loader->load('static', [], ['name' => 'from-call']);

        $this->assertSame('from-call', $captured['data']['name']);
    }

    public function testContextIsIgnored(): void
    {
        $captured = null;
        $loader = new YamlFormLoader($this->makeFactory($captured));
        $loader->addPath(self::FIXTURES);

        $loader->load('static', ['ignored' => 'value']);

        $this->assertSame('from-yaml', $captured['data']['name']);
    }

    public function testInvalidYamlThrows(): void
    {
        $loader = new YamlFormLoader($this->makeFactory($_unused));
        $loader->addPath(self::FIXTURES);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to parse YAML');
        $loader->load('broken');
    }

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
