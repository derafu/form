<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm\Data;

use Derafu\Form\Data\FieldPath;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The value of a field of an object is in the object of the data.
 */
#[CoversClass(FieldPath::class)]
final class FieldPathTest extends TestCase
{
    #[Test]
    public function shouldGetTheValueOfAFieldByItsName(): void
    {
        $data = ['name' => 'Ana', 'address' => ['city' => 'Lima', 'tags' => ['a']], 'a.b' => 'whole'];

        $this->assertSame('Ana', FieldPath::get($data, 'name'));
        $this->assertSame('Lima', FieldPath::get($data, 'address.city'));
        $this->assertSame('whole', FieldPath::get($data, 'a.b'), 'A key with the whole name is the value of the field.');
        $this->assertNull(FieldPath::get($data, 'address.zip'));
        $this->assertNull(FieldPath::get($data, 'missing.city'));
        $this->assertNull(FieldPath::get($data, 'name.first'), 'A value that is not an object has no fields.');
    }

    #[Test]
    public function shouldSetTheValueCreatingTheObjects(): void
    {
        $data = FieldPath::set(['name' => 'Ana'], 'address.city', 'Lima');
        $data = FieldPath::set($data, 'address.zip', '123');
        $data = FieldPath::set($data, 'name', 'Eva');

        $this->assertSame(['name' => 'Eva', 'address' => ['city' => 'Lima', 'zip' => '123']], $data);
    }
}
