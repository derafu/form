<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\ExamplesForm;

use Derafu\Form\Contract\Factory\FormFactoryInterface;
use Derafu\Form\Contract\FormInterface;
use Derafu\Support\ArrayReader;
use Derafu\Support\ArrayType;
use InvalidArgumentException;

/**
 * Example model representing a form example.
 *
 * This class handles loading and processing form example definitions stored as
 * JSON files, providing methods to access their metadata, content, and create
 * form instances from them.
 */
final class Example
{
    /**
     * Form factory.
     *
     * @var FormFactoryInterface
     */
    private static FormFactoryInterface $formFactory;

    /**
     * Directory containing the example JSON files.
     */
    private const EXAMPLES_DIR = __DIR__ . '/../fixtures/examples';

    /**
     * Cached example data.
     *
     * @var array<string, mixed>
     */
    private array $data;

    /**
     * Cached form instance.
     *
     * @var FormInterface
     */
    private FormInterface $form;

    /**
     * Cached previous example.
     *
     * @var Example|false
     */
    private Example|false $previous;

    /**
     * Cached next example.
     *
     * @var Example|false
     */
    private Example|false $next;

    /**
     * Constructor.
     *
     * @param string $file Path to the example JSON file.
     */
    public function __construct(private readonly string $file)
    {
    }

    /**
     * Get the raw data from the example JSON file.
     *
     * Results are cached after the first call.
     *
     * @return array<string, mixed> The raw example data.
     */
    public function getData(): array
    {
        if (!isset($this->data)) {
            $json = file_get_contents($this->file);
            if ($json === false) {
                throw new InvalidArgumentException(sprintf('Example file "%s" can not be read.', $this->file));
            }

            $this->data = ArrayType::map()->parse(
                json_decode($json, true, flags: JSON_THROW_ON_ERROR),
                $this->file
            );
        }

        return $this->data;
    }

    /**
     * Create a form instance from the example definition.
     *
     * @return FormInterface The instantiated form.
     */
    public function getForm(): FormInterface
    {
        if (!isset($this->form)) {
            $this->form = self::$formFactory->create(
                ArrayReader::from($this->getData())->require('form', ArrayType::map())
            );
        }

        return $this->form;
    }

    /**
     * Get the previous example in numerical order.
     *
     * @return Example|null The previous example or `null` if this is the first.
     */
    public function getPrevious(): ?Example
    {
        if (!isset($this->previous)) {
            $previous = self::findByNumber($this->getNumber() - 1);

            $this->previous = $previous === null ? false : $previous;
        }

        return $this->previous === false ? null : $this->previous;
    }

    /**
     * Get the next example in numerical order.
     *
     * @return Example|null The next example or `null` if this is the last.
     */
    public function getNext(): ?Example
    {
        if (!isset($this->next)) {
            $next = self::findByNumber($this->getNumber() + 1);

            $this->next = $next === null ? false : $next;
        }

        return $this->next === false ? null : $this->next;
    }

    /**
     * Get the example's unique identifier.
     *
     * @return string The example ID.
     */
    public function getId(): string
    {
        return substr(basename($this->file), 0, -5);
    }

    /**
     * Get the example's numerical order.
     *
     * Examples are numbered with a three-digit prefix (e.g., 001, 002).
     *
     * @return int The example number.
     */
    public function getNumber(): int
    {
        return (int) substr($this->getId(), 0, 3);
    }

    /**
     * Get the example's code (slug).
     *
     * This is the part of the ID after the number prefix.
     *
     * @return string The example code.
     */
    public function getCode(): string
    {
        return substr($this->getId(), 4);
    }

    /**
     * Get the example's display name.
     *
     * Falls back to the code if no name is defined in metadata.
     *
     * @return string The example name.
     */
    public function getName(): string
    {
        return ArrayReader::from($this->getMeta())->string('name') ?? $this->getCode();
    }

    /**
     * Get the example's description.
     *
     * @return string|null The description or `null` if not defined.
     */
    public function getDescription(): ?string
    {
        return ArrayReader::from($this->getMeta())->string('description');
    }

    /**
     * Get the example's tags.
     *
     * @return list<string> List of tags.
     */
    public function getTags(): array
    {
        return ArrayReader::from($this->getMeta())->stringList('tags') ?? [];
    }

    /**
     * Get all metadata for the example.
     *
     * @return array<string, mixed> Metadata as an associative array.
     */
    public function getMeta(): array
    {
        return ArrayReader::from($this->getData())->map('meta') ?? [];
    }

    /**
     * Find an example by its code.
     *
     * @param string $id The example code to find.
     * @return static The found example.
     * @throws InvalidArgumentException If no example with the given code exists.
     */
    public static function find(string $id): static
    {
        $files = glob(sprintf(
            '%s/[0-9][0-9][0-9]_%s.json',
            static::EXAMPLES_DIR,
            $id
        ));

        if (empty($files)) {
            throw new InvalidArgumentException(sprintf(
                'Example "%s" not found.',
                $id
            ));
        }

        return new static($files[0]);
    }

    /**
     * Find an example by its numerical order.
     *
     * @param int $number The example number to find.
     * @return static|null The found example or `null` if none exists.
     */
    public static function findByNumber(int $number): ?static
    {
        $files = glob(sprintf(
            '%s/%03d_*.json',
            static::EXAMPLES_DIR,
            $number
        ));

        if (empty($files)) {
            return null;
        }

        return new static($files[0]);
    }

    /**
     * Get all available examples.
     *
     * @return Example[] Array of all examples.
     */
    public static function all(): array
    {
        $files = glob(self::EXAMPLES_DIR . '/[0-9][0-9][0-9]_*.json') ?: [];

        $examples = [];
        foreach ($files as $file) {
            $examples[] = new self($file);
        }

        return $examples;
    }

    /**
     * Set the form factory.
     *
     * @param FormFactoryInterface $formFactory The form factory.
     */
    public static function setFormFactory(FormFactoryInterface $formFactory): void
    {
        self::$formFactory = $formFactory;
    }
}
