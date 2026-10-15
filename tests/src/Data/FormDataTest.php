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

use Derafu\Form\Data\FormData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The data of a form, read and written by the path of a property.
 */
#[CoversClass(FormData::class)]
final class FormDataTest extends TestCase
{
    #[Test]
    public function shouldReadAValueByItsPath(): void
    {
        $data = FormData::fromArray(['name' => 'Ana', 'address' => ['city' => 'Santiago', 'tags' => ['a', 'b']]]);

        $this->assertSame('Ana', $data->get('name'));
        $this->assertSame('Santiago', $data->get('address.city'));
        $this->assertSame('b', $data->get('address.tags.1'));
        $this->assertSame('d', $data->get('address.country', 'd'));
        $this->assertSame('d', $data->get('name.first', 'd'), 'A value that is not an array has no keys.');
        $this->assertNull($data->get('missing'));
    }

    #[Test]
    public function shouldReadAValueByTheScopeOfItsProperty(): void
    {
        $data = new FormData(['address' => ['city' => 'Santiago']]);

        $this->assertSame('Santiago', $data->get('#/properties/address/properties/city'));
        $this->assertTrue($data->has('#/properties/address/properties/city'));
        $this->assertFalse($data->has('#/properties/address/properties/zip'));
    }

    #[Test]
    public function shouldTellIfThereIsAValue(): void
    {
        $data = new FormData(['name' => null, 'address' => ['city' => 'Santiago']]);

        $this->assertTrue($data->has('name'), 'A value that is null is there.');
        $this->assertTrue($data->has('address.city'));
        $this->assertFalse($data->has('address.zip'));
        $this->assertFalse($data->has('missing'));
        $this->assertFalse($data->has('name.first'));
    }

    #[Test]
    public function shouldWriteAValueCreatingTheLevels(): void
    {
        $data = new FormData(['name' => 'Ana']);

        $data->set('name', 'Eva')->set('address.city', 'Lima')->set('#/properties/phone/properties/home', '123');

        $this->assertSame(
            ['name' => 'Eva', 'address' => ['city' => 'Lima'], 'phone' => ['home' => '123']],
            $data->all()
        );
        $this->assertSame($data->all(), $data->toArray());
        $this->assertSame($data->all(), $data->jsonSerialize());
        $this->assertJsonStringEqualsJsonString((string) json_encode($data->all()), $data->toJson());
    }
}
