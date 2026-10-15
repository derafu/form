<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm\Schema;

use Derafu\Form\Abstract\AbstractPropertySchema;
use Derafu\Form\Factory\PropertySchemaFactory;
use Derafu\Form\Schema\PropertyDefinition;
use Derafu\Form\Schema\StringSchema;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * The choices of a property: the value and the title of each one are texts, so
 * two different values are never the same key.
 */
#[CoversClass(AbstractPropertySchema::class)]
#[UsesClass(PropertySchemaFactory::class)]
#[UsesClass(StringSchema::class)]
#[UsesClass(PropertyDefinition::class)]
final class ChoicesTest extends TestCase
{
    #[Test]
    public function shouldWriteTheValuesOfAnEnumAsText(): void
    {
        $schema = PropertySchemaFactory::create(['type' => 'string', 'enum' => ['a', 1, 1.5, true]]);

        $this->assertSame(
            ['a' => 'a', 1 => '1', '1.5' => '1.5'],
            $schema->getChoices(),
            'true is "1", the same key as 1.'
        );
    }

    #[Test]
    public function shouldUseTheTitleAsTheLabelAndTheValueWhenThereIsNoTitle(): void
    {
        $schema = PropertySchemaFactory::create(['type' => 'string', 'oneOf' => [
            ['const' => 'a', 'title' => 'A'],
            ['const' => 2.5],
            ['const' => 3, 'title' => 4],
        ]]);

        $this->assertSame(['a' => 'A', '2.5' => '2.5', 3 => '4'], $schema->getChoices());
    }

    #[Test]
    public function shouldHaveNoChoicesWhenThereAreNone(): void
    {
        $this->assertNull(PropertySchemaFactory::create(['type' => 'string'])->getChoices());
        $this->assertNull(PropertySchemaFactory::create(['type' => 'string', 'enum' => []])->getChoices());
        $this->assertNull(PropertySchemaFactory::create(['type' => 'string', 'oneOf' => [['title' => 'No const']]])->getChoices());
    }

    #[Test]
    public function shouldRejectAValueThatIsNotScalar(): void
    {
        $schema = PropertySchemaFactory::create(['type' => 'string', 'oneOf' => [['const' => ['a'], 'title' => 'A']]]);

        $this->expectException(TranslatableInvalidArgumentException::class);
        $this->expectExceptionMessage('The value or the title of a choice must be a string, a number or a boolean, array given.');

        $schema->getChoices();
    }

    #[Test]
    public function shouldRejectATitleThatIsNotScalar(): void
    {
        $schema = PropertySchemaFactory::create(['type' => 'string', 'oneOf' => [['const' => 'a', 'title' => ['A']]]]);

        $this->expectException(TranslatableInvalidArgumentException::class);
        $this->expectExceptionMessage('The value or the title of a choice must be a string, a number or a boolean, array given.');

        $schema->getChoices();
    }
}
