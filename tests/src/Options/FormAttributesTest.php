<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm\Options;

use Derafu\Form\Options\FormAttributes;
use Derafu\Form\Options\FormOptions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The attributes of the HTML element of the form, and the options of the form.
 */
#[CoversClass(FormAttributes::class)]
#[CoversClass(FormOptions::class)]
final class FormAttributesTest extends TestCase
{
    #[Test]
    public function shouldHaveTheDefaultValues(): void
    {
        $attributes = new FormAttributes();

        $this->assertSame('UTF-8', $attributes->getAcceptCharset());
        $this->assertNull($attributes->getAction());
        $this->assertFalse($attributes->getAutocomplete());
        $this->assertNull($attributes->getEnctype());
        $this->assertSame('post', $attributes->getMethod());
        $this->assertNull($attributes->getName());
        $this->assertFalse($attributes->getNovalidate());
        $this->assertNull($attributes->getRel());
        $this->assertSame('_self', $attributes->getTarget());
        $this->assertStringStartsWith('form-', $attributes->getId());
    }

    #[Test]
    public function shouldChangeEveryValue(): void
    {
        $attributes = (new FormAttributes())
            ->setAcceptCharset('ISO-8859-1')
            ->setAction('/send')
            ->setAutocomplete()
            ->setId('my-form')
            ->setMethod('GET')
            ->setName('contact')
            ->setNovalidate()
            ->setRel('nofollow');

        $this->assertSame('ISO-8859-1', $attributes->getAcceptCharset());
        $this->assertSame('/send', $attributes->getAction());
        $this->assertTrue($attributes->getAutocomplete());
        $this->assertSame('my-form', $attributes->getId());
        $this->assertSame('get', $attributes->getMethod());
        $this->assertSame('contact', $attributes->getName());
        $this->assertTrue($attributes->getNovalidate());
        $this->assertSame('nofollow', $attributes->getRel());
        $this->assertNull($attributes->setName(null)->getName());
    }

    #[Test]
    public function shouldMakeTheMethodPostWhenTheEnctypeIsSet(): void
    {
        $attributes = (new FormAttributes())->setMethod('get')->setEnctype('Multipart/Form-Data');

        $this->assertSame('multipart/form-data', $attributes->getEnctype());
        $this->assertSame('post', $attributes->getMethod());
    }

    #[Test]
    public function shouldKeepTheTargetThatIsValidAndReplaceTheOneThatIsNot(): void
    {
        $this->assertSame('_top', (new FormAttributes())->setTarget('_TOP')->getTarget());
        $this->assertSame('_self', (new FormAttributes())->setTarget('elsewhere')->getTarget());
    }

    #[Test]
    public function shouldProtectTheLinksThatOpenInAnotherWindow(): void
    {
        $attributes = (new FormAttributes())->setTarget('_blank');

        $this->assertSame('_blank', $attributes->getTarget());
        $this->assertSame('noopener noreferrer', $attributes->getRel());
    }

    #[Test]
    public function shouldBeCreatedFromAnArrayAndWrittenBack(): void
    {
        $attributes = FormAttributes::fromArray([
            'accept-charset' => 'ISO-8859-1',
            'action' => '/send',
            'autocomplete' => true,
            'id' => 'my-form',
            'method' => 'get',
            'name' => 'contact',
            'novalidate' => true,
            'target' => '_top',
        ]);

        $expected = [
            'accept-charset' => 'ISO-8859-1',
            'action' => '/send',
            'autocomplete' => true,
            'enctype' => null,
            'id' => 'my-form',
            'method' => 'get',
            'name' => 'contact',
            'novalidate' => true,
            'rel' => null,
            'target' => '_top',
        ];

        $this->assertSame($expected, $attributes->toArray());
        $this->assertSame($expected, $attributes->jsonSerialize());
        $this->assertJsonStringEqualsJsonString((string) json_encode($expected), $attributes->toJson());
    }

    #[Test]
    public function shouldGiveTheOptionsByTheirPathAndTheAttributesOfTheForm(): void
    {
        $options = FormOptions::fromArray([
            'csrf_protection' => false,
            'attributes' => ['action' => '/send', 'method' => 'get'],
            'nested' => ['deep' => ['value' => 1]],
        ]);

        $this->assertFalse($options->get('csrf_protection'));
        $this->assertSame(1, $options->get('nested.deep.value'));
        $this->assertSame('d', $options->get('nested.missing', 'd'));
        $this->assertSame('d', $options->get('csrf_protection.x', 'd'));
        $this->assertSame('/send', $options->getAttributes()->getAction());
        $this->assertSame('get', $options->getAttributes()->getMethod());

        $options->set('other', 'x')->setAttributes(new FormAttributes(action: '/other'));
        $this->assertSame('x', $options->get('other'));
        $this->assertSame('/other', $options->getAttributes()->getAction());
        $this->assertSame($options->toArray(), $options->jsonSerialize());
        $this->assertJsonStringEqualsJsonString((string) json_encode($options->toArray()), $options->toJson());
    }
}
