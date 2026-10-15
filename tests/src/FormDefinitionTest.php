<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm;

use Derafu\Form\FormDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The first level of the definition of a form is read with the type that each
 * key has: the keys that are not declared are ignored.
 */
#[CoversClass(FormDefinition::class)]
final class FormDefinitionTest extends TestCase
{
    #[Test]
    public function shouldReadEveryKey(): void
    {
        $definition = FormDefinition::fromArray([
            'schema' => ['type' => 'object'],
            'uischema' => ['type' => 'VerticalLayout', 'elements' => []],
            'rules' => ['name' => ['validate' => ['required']]],
            'data' => ['name' => 'A'],
            'options' => ['csrf_protection' => false],
            'errors' => ['name' => ['Required.']],
        ]);

        $this->assertSame(['type' => 'object'], $definition->schema);
        $this->assertSame(['type' => 'VerticalLayout', 'elements' => []], $definition->uischema);
        $this->assertSame(['name' => ['validate' => ['required']]], $definition->rules);
        $this->assertSame(['name' => 'A'], $definition->data);
        $this->assertSame(['csrf_protection' => false], $definition->options);
        $this->assertSame(['name' => ['Required.']], $definition->errors);
    }

    #[Test]
    public function shouldHaveNoValuesWhenTheDefinitionIsEmpty(): void
    {
        $definition = FormDefinition::fromArray([]);

        $this->assertSame([], $definition->schema);
        $this->assertSame([], $definition->uischema);
        $this->assertSame([], $definition->rules);
        $this->assertNull($definition->data);
        $this->assertSame([], $definition->options);
        $this->assertNull($definition->errors);
        $this->assertSame([], $definition->toArray());
    }

    #[Test]
    public function shouldKeepEmptyDataThatIsNotNull(): void
    {
        $this->assertSame([], FormDefinition::fromArray(['data' => []])->data);
        $this->assertSame(['data' => []], FormDefinition::fromArray(['data' => []])->toArray());
    }

    #[Test]
    public function shouldWriteTheKeysInOrderAndIgnoreTheOnesThatItDoesNotDeclare(): void
    {
        $definition = FormDefinition::fromArray([
            'errors' => ['a' => ['x']],
            'options' => ['o' => 1],
            'data' => null,
            'name' => 'ignored',
            'schema' => ['type' => 'object'],
        ]);

        $this->assertSame(['schema', 'options', 'errors'], array_keys($definition->toArray()));
    }

    #[Test]
    public function shouldReturnAnotherDefinitionWithTheChangesThatAreAsked(): void
    {
        $definition = FormDefinition::fromArray(['schema' => ['a' => 1], 'options' => ['o' => 1]]);

        $changed = $definition
            ->withSchema(['b' => 2])
            ->withUiSchema(['type' => 'VerticalLayout'])
            ->withData(['name' => 'A']);

        $this->assertSame(['a' => 1], $definition->schema);
        $this->assertSame(['b' => 2], $changed->schema);
        $this->assertSame(['type' => 'VerticalLayout'], $changed->uischema);
        $this->assertSame(['name' => 'A'], $changed->data);
        $this->assertSame(['o' => 1], $changed->options);
    }
}
