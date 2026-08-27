<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Support;

/**
 * Base contract for every StatementsAPI DTO.
 *
 * Provides small, dependency-free hydration helpers so that concrete DTOs
 * stay uniform (nested object hydration, list hydration, enum mapping and
 * scalar reads) without pulling in an external DTO library.
 *
 * @template-covariant T of mixed
 */
abstract readonly class BaseDto implements Arrayable, FromArray
{
    /**
     * Hydrate a single nested object DTO, or null when absent.
     *
     * @param  array<string, mixed>|null  $data
     * @param  class-string<FromArray>     $class
     * @return FromArray|null
     */
    protected static function nested(?array $data, string $class): ?FromArray
    {
        return $data === null ? null : $class::fromArray($data);
    }

    /**
     * Hydrate a list of nested object DTOs.
     *
     * @param  array<int, mixed>          $data
     * @param  class-string<FromArray>     $class
     * @return array<int, FromArray>
     */
    protected static function nestedAll(array $data, string $class): array
    {
        return array_map(static fn ($item) => $class::fromArray($item), $data);
    }

    /**
     * Map a raw string value to a native enum case.
     *
     * Uses tryFrom() so an unrecognised/evolving code degrades to null
     * instead of throwing at the boundary.
     *
     * @param  class-string<\UnitEnum>  $class
     * @return \UnitEnum|null
     */
    protected static function enum(string $class, mixed $value): ?\UnitEnum
    {
        return $value === null ? null : $class::tryFrom($value);
    }

    /**
     * Read a nullable scalar (string/number) from raw data by exact key.
     *
     * @param  array<string, mixed>  $data
     */
    protected static function scalar(array $data, string $key): mixed
    {
        return $data[$key] ?? null;
    }
}
