<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Renderer\Support;

use Derafu\Form\Contract\FormFieldInterface;

/**
 * Resolves the `options.actions` UI schema option into ready-to-render
 * button definitions for input-group controls (e.g. show/hide password,
 * copy the field's value, generate a random password).
 *
 * Each entry accepted in `options.actions` can be:
 *
 *   - A shorthand string naming a known action (e.g. `'copy'`).
 *   - An array with a `type` key naming a known action, plus optional
 *     overrides (`icon`, `label`, and action-specific keys such as
 *     `message` for `copy` or `length` for `generate-password`).
 *   - An array with an explicit `onclick` (and no recognized `type`, or a
 *     custom one), used as-is. This is the escape hatch for actions this
 *     resolver does not know about.
 *
 * The JavaScript referenced by the built-in actions (`FormFields.*`,
 * `UI.copy`) is provided by the `derafu-js` package and is not bundled here,
 * consistently with the rest of this package's client-side integrations.
 */
final class InputActionResolver
{
    /**
     * Known action types and their defaults.
     *
     * @var array<string, array{icon: string, label: string}>
     */
    private const ACTIONS = [
        'toggle-password' => [
            'icon' => 'fa-regular fa-eye fa-fw',
            'label' => 'Toggle password visibility',
        ],
        'generate-password' => [
            'icon' => 'fa-solid fa-arrows-rotate fa-fw',
            'label' => 'Generate a random password',
        ],
        'copy' => [
            'icon' => 'fa-regular fa-copy fa-fw',
            'label' => 'Copy value',
        ],
    ];

    /**
     * Resolves the raw `options.actions` value into a list of renderable
     * button definitions.
     *
     * @param mixed $actions Raw `options['actions']` value. Accepts a single
     * action (string or array), a list of actions, or an empty/null value.
     * @param FormFieldInterface $field The field the actions belong to, used
     * to build a default, human readable message for the `copy` action.
     * @return array<int, array{icon: string, label: string, onclick: string}>
     */
    public function resolve(mixed $actions, FormFieldInterface $field): array
    {
        if (empty($actions)) {
            return [];
        }

        // A single action, not wrapped in a list, is also accepted.
        if (is_string($actions)) {
            $actions = [$actions];
        } elseif (is_array($actions) && !array_is_list($actions)) {
            $actions = [$actions];
        } elseif (!is_array($actions)) {
            return [];
        }

        $resolved = [];

        foreach ($actions as $action) {
            $resolvedAction = $this->resolveAction($action, $field);
            if ($resolvedAction !== null) {
                $resolved[] = $resolvedAction;
            }
        }

        return $resolved;
    }

    /**
     * Resolves a single action entry.
     *
     * @param mixed $action The raw action entry (string or array).
     * @param FormFieldInterface $field The field the action belongs to.
     * @return array{icon: string, label: string, onclick: string}|null `null`
     * when the action cannot be resolved (unknown type without an explicit
     * `onclick` override).
     */
    private function resolveAction(mixed $action, FormFieldInterface $field): ?array
    {
        if (is_string($action)) {
            $action = ['type' => $action];
        }

        if (!is_array($action)) {
            return null;
        }

        $type = $action['type'] ?? null;
        $defaults = $type !== null ? (self::ACTIONS[$type] ?? []) : [];

        $onclick = $action['onclick'] ?? $this->buildOnclick($type, $action, $field);
        if ($onclick === null) {
            return null;
        }

        return [
            'icon' => $action['icon'] ?? $defaults['icon'] ?? '',
            'label' => $action['label'] ?? $defaults['label'] ?? '',
            'onclick' => $onclick,
        ];
    }

    /**
     * Builds the `onclick` handler for a known action type.
     *
     * @param string|null $type The action type.
     * @param array $action The raw action entry (for per-action overrides).
     * @param FormFieldInterface $field The field the action belongs to.
     * @return string|null `null` when the type is not a known built-in.
     */
    private function buildOnclick(?string $type, array $action, FormFieldInterface $field): ?string
    {
        return match ($type) {
            'toggle-password' => 'FormFields.showPassword(this)',
            'generate-password' => sprintf(
                'FormFields.generatePassword(this, %d)',
                $action['length'] ?? 16
            ),
            'copy' => sprintf(
                "UI.copy(this.closest('.input-group').querySelector('input, textarea'), %s)",
                json_encode($action['message'] ?? $this->buildCopyMessage($field))
            ),
            default => null,
        };
    }

    /**
     * Builds the default confirmation message for the `copy` action.
     *
     * @param FormFieldInterface $field The field being copied.
     * @return string
     */
    private function buildCopyMessage(FormFieldInterface $field): string
    {
        $property = $field->getProperty();
        $title = $property->getTitle() ?? $property->getName();

        return sprintf('Value from field "%s" copied.', $title);
    }
}
