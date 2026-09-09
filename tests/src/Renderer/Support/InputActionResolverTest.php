<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Tests\Renderer\Support;

use Derafu\Form\Contract\FormFieldInterface;
use Derafu\Form\Contract\Schema\PropertySchemaInterface;
use Derafu\Form\Renderer\Support\InputActionResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(InputActionResolver::class)]
final class InputActionResolverTest extends TestCase
{
    private function fieldWithTitle(?string $title, string $name = 'email'): FormFieldInterface
    {
        $property = $this->createStub(PropertySchemaInterface::class);
        $property->method('getTitle')->willReturn($title);
        $property->method('getName')->willReturn($name);

        $field = $this->createStub(FormFieldInterface::class);
        $field->method('getProperty')->willReturn($property);

        return $field;
    }

    public function testWithoutTranslatorUsesEnglishLabels(): void
    {
        $resolver = new InputActionResolver();

        $actions = $resolver->resolve(['toggle-password'], $this->fieldWithTitle('Password'));

        $this->assertSame('Toggle password visibility', $actions[0]['label']);
    }

    public function testWithoutTranslatorStillSubstitutesTheCopyMessageParameter(): void
    {
        $resolver = new InputActionResolver();

        $actions = $resolver->resolve(['copy'], $this->fieldWithTitle('Email'));

        $this->assertStringContainsString('Value from field \"Email\" copied.', $actions[0]['onclick']);
    }

    public function testTranslatesBuiltInLabelsWhenTranslatorIsProvided(): void
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            function (?string $id, array $parameters, ?string $domain, ?string $locale): string {
                $this->assertSame('form+intl-icu', $domain);
                $this->assertSame('es', $locale);

                return match ($id) {
                    'Toggle password visibility' => 'Mostrar u ocultar la contraseña',
                    'Generate a random password' => 'Generar una contraseña aleatoria',
                    'Copy value' => 'Copiar valor',
                    default => $id,
                };
            }
        );

        $resolver = new InputActionResolver($translator, 'es');

        $actions = $resolver->resolve(
            ['toggle-password', 'generate-password', 'copy'],
            $this->fieldWithTitle('Password')
        );

        $this->assertSame('Mostrar u ocultar la contraseña', $actions[0]['label']);
        $this->assertSame('Generar una contraseña aleatoria', $actions[1]['label']);
        $this->assertSame('Copiar valor', $actions[2]['label']);
    }

    public function testTranslatesTheDefaultCopyMessageWithTheFieldTitleAsAnIcuParameter(): void
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            function (?string $id, array $parameters): string {
                if ($id === 'Value from field "{title}" copied.') {
                    return sprintf('Valor del campo "%s" copiado.', $parameters['title']);
                }

                return $id;
            }
        );

        $resolver = new InputActionResolver($translator, 'es');

        $actions = $resolver->resolve(['copy'], $this->fieldWithTitle('Correo'));

        $this->assertStringContainsString('Valor del campo \"Correo\" copiado.', $actions[0]['onclick']);
    }

    public function testExplicitLabelOverrideIsNeverTranslated(): void
    {
        // Use "toggle-password" (not "copy") so the only thing this
        // resolver could possibly translate is the label itself.
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->never())->method('trans');

        $resolver = new InputActionResolver($translator, 'es');

        $actions = $resolver->resolve(
            [['type' => 'toggle-password', 'label' => 'Custom label']],
            $this->fieldWithTitle('Password')
        );

        $this->assertSame('Custom label', $actions[0]['label']);
    }

    public function testExplicitMessageOverrideIsNeverTranslated(): void
    {
        // The "copy" label itself is still translated (it wasn't
        // overridden); only the message override must be left untouched.
        $translatedIds = [];
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            function (?string $id) use (&$translatedIds): string {
                $translatedIds[] = $id;

                return $id;
            }
        );

        $resolver = new InputActionResolver($translator, 'es');

        $actions = $resolver->resolve(
            [['type' => 'copy', 'message' => 'Custom message']],
            $this->fieldWithTitle('Email')
        );

        $this->assertNotContains('Custom message', $translatedIds);
        $this->assertStringContainsString('Custom message', $actions[0]['onclick']);
    }
}
