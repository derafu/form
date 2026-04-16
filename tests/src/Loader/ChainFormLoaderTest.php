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
use Derafu\Form\Loader\ChainFormLoader;
use Derafu\Form\Loader\JsonFormLoader;
use Derafu\Form\Loader\PhpFormLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(ChainFormLoader::class)]
#[UsesClass(AbstractFileFormLoader::class)]
#[UsesClass(PhpFormLoader::class)]
#[UsesClass(JsonFormLoader::class)]
final class ChainFormLoaderTest extends TestCase
{
    private const FIXTURES = __DIR__ . '/../../fixtures/forms';

    public function testFirstLoaderThatResolvesWins(): void
    {
        $captured = null;
        $factory = $this->makeFactory($captured);

        $php = new PhpFormLoader($factory);
        $php->addPath(self::FIXTURES);
        $json = new JsonFormLoader($factory);
        $json->addPath(self::FIXTURES);

        // Both have 'static', PHP comes first → PHP wins.
        $chain = new ChainFormLoader([$php, $json]);
        $chain->load('static');

        // PHP static fixture has no 'data' key; JSON version has name=from-json.
        $this->assertArrayNotHasKey('data', $captured);
    }

    public function testFallsThroughToSecondLoader(): void
    {
        $captured = null;
        $factory = $this->makeFactory($captured);

        $php = new PhpFormLoader($factory);
        $php->addPath(self::FIXTURES);
        $json = new JsonFormLoader($factory);
        $json->addPath(self::FIXTURES);

        // 'only-json' exists only as JSON → PHP fails, JSON resolves.
        $chain = new ChainFormLoader([$php, $json]);
        $chain->load('only-json');

        $this->assertSame('json-only', $captured['data']['foo']);
    }

    public function testAllLoadersFailThrows(): void
    {
        $factory = $this->createMock(FormFactoryInterface::class);

        $php = new PhpFormLoader($factory);
        $php->addPath(self::FIXTURES);
        $json = new JsonFormLoader($factory);
        $json->addPath(self::FIXTURES);

        $chain = new ChainFormLoader([$php, $json]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No loader could resolve');
        $chain->load('does-not-exist-anywhere');
    }

    public function testNoLoadersThrows(): void
    {
        $chain = new ChainFormLoader([]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No loaders registered');
        $chain->load('whatever');
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
