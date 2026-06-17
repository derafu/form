<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Options;

use Derafu\Form\Contract\Options\FormAttributesInterface;
use Derafu\Support\JsonSerializer;

/**
 * Represents the attributes of an HTML form.
 *
 * This class provides a way to set and get common form attributes such as
 * action, method, enctype, etc. It can be initialized either through the
 * constructor or using the static method `fromArray()`.
 */
final class FormAttributes implements FormAttributesInterface
{
    /**
     * Default values for form attributes.
     */
    private const DEFAULT_ACCEPT_CHARSET = 'UTF-8';

    /**
     * Default action for the form.
     */
    private const DEFAULT_ACTION = null;

    /**
     * Default autocomplete for the form.
     */
    private const DEFAULT_AUTOCOMPLETE = false;

    /**
     * Default enctype for the form.
     */
    private const DEFAULT_ENCTYPE = null;

    /**
     * Default ID for the form.
     */
    private const DEFAULT_ID = null;

    /**
     * Default method for the form.
     */
    private const DEFAULT_METHOD = 'post';

    /**
     * Default name for the form.
     */
    private const DEFAULT_NAME = null;

    /**
     * Default novalidate for the form.
     */
    private const DEFAULT_NOVALIDATE = false;

    /**
     * Default rel for the form.
     */
    private const DEFAULT_REL = null;

    /**
     * Default target for the form.
     */
    private const DEFAULT_TARGET = '_self';

    /**
     * Constructor.
     *
     * @param string $acceptCharset The character encoding for the form.
     * @param string|null $action The URL of the form.
     * @param bool $autocomplete Whether the form should be autocomplete.
     * @param string|null $enctype The MIME type of the form data.
     * @param string|null $id The ID of the form.
     * @param string $method The HTTP method to use for the form.
     * @param string|null $name The name of the form.
     * @param bool $novalidate Whether the form should not be validated.
     * @param string|null $rel The relationship between the document and the form.
     * @param string $target The target frame for the form.
     */
    public function __construct(
        private string $acceptCharset = self::DEFAULT_ACCEPT_CHARSET,
        private ?string $action = self::DEFAULT_ACTION,
        private bool $autocomplete = self::DEFAULT_AUTOCOMPLETE,
        private ?string $enctype = self::DEFAULT_ENCTYPE,
        private ?string $id = self::DEFAULT_ID,
        private string $method = self::DEFAULT_METHOD,
        private ?string $name = self::DEFAULT_NAME,
        private bool $novalidate = self::DEFAULT_NOVALIDATE,
        private ?string $rel = self::DEFAULT_REL,
        private string $target = self::DEFAULT_TARGET,
    ) {
        $this->setId($id ?? uniqid('form-'));
        $this->setMethod($method);
        $this->setTarget($target);
    }

    /**
     * {@inheritDoc}
     */
    public function getAcceptCharset(): string
    {
        return $this->acceptCharset;
    }

    /**
     * {@inheritDoc}
     */
    public function setAcceptCharset(string $acceptCharset): static
    {
        $this->acceptCharset = $acceptCharset;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * {@inheritDoc}
     */
    public function setAction(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getAutocomplete(): bool
    {
        return $this->autocomplete;
    }

    /**
     * {@inheritDoc}
     */
    public function setAutocomplete(bool $autocomplete = true): static
    {
        $this->autocomplete = $autocomplete;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getEnctype(): ?string
    {
        return $this->enctype;
    }

    /**
     * {@inheritDoc}
     */
    public function setEnctype(string $enctype): static
    {
        $this->enctype = strtolower($enctype);
        $this->method = 'post';

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * {@inheritDoc}
     */
    public function setMethod(string $method): static
    {
        $this->method = strtolower($method);

        if ($this->method !== 'post') {
            $this->enctype = null;
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getNovalidate(): bool
    {
        return $this->novalidate;
    }

    /**
     * {@inheritDoc}
     */
    public function setNovalidate(bool $novalidate = true): static
    {
        $this->novalidate = $novalidate;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getRel(): ?string
    {
        return $this->rel;
    }

    /**
     * {@inheritDoc}
     */
    public function setRel(string $rel): static
    {
        $this->rel = $rel;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * {@inheritDoc}
     */
    public function setTarget(string $target): static
    {
        $this->target = strtolower($target);

        if (!in_array($this->target, ['_blank', '_self', '_parent', '_top'])) {
            $this->target = '_self';
        }

        if ($this->target === '_blank') {
            $this->rel = 'noopener noreferrer';
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'accept-charset' => $this->acceptCharset,
            'action' => $this->action,
            'autocomplete' => $this->autocomplete,
            'enctype' => $this->enctype,
            'id' => $this->id,
            'method' => $this->method,
            'name' => $this->name,
            'novalidate' => $this->novalidate,
            'rel' => $this->rel,
            'target' => $this->target,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function toJson(): string
    {
        return JsonSerializer::serialize($this->jsonSerialize());
    }

    /**
     * {@inheritDoc}
     */
    public static function fromArray(array $attributes): static
    {
        return new static(
            $attributes['accept-charset'] ?? self::DEFAULT_ACCEPT_CHARSET,
            $attributes['action'] ?? self::DEFAULT_ACTION,
            $attributes['autocomplete'] ?? self::DEFAULT_AUTOCOMPLETE,
            $attributes['enctype'] ?? self::DEFAULT_ENCTYPE,
            $attributes['id'] ?? self::DEFAULT_ID,
            $attributes['method'] ?? self::DEFAULT_METHOD,
            $attributes['name'] ?? self::DEFAULT_NAME,
            $attributes['novalidate'] ?? self::DEFAULT_NOVALIDATE,
            $attributes['rel'] ?? self::DEFAULT_REL,
            $attributes['target'] ?? self::DEFAULT_TARGET,
        );
    }
}
