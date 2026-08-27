<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs\StatementsAPI\Support;

use App\DTOs\StatementsAPI\Enums\CreditDebitCode;
use App\DTOs\StatementsAPI\Models\CurrencyAndAmountDTO;
use App\DTOs\StatementsAPI\Support\Arrayable;
use App\DTOs\StatementsAPI\Support\BaseDto;
use App\DTOs\StatementsAPI\Support\FromArray;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Hermetic unit tests for the StatementsAPI Support contract layer
 * (BaseDto + FromArray + Arrayable). These protected static helpers are otherwise
 * only exercised indirectly through concrete DTOs, and BaseDto::scalar() is not
 * reached by any production path, so they get a dedicated, DB-free test here.
 *
 * The helpers are protected, so they are invoked via reflection on the abstract
 * BaseDto itself -- no concrete subclass or interface stubs are required.
 */
final class BaseDtoTest extends TestCase
{
    public static function call(string $method, mixed ...$args): mixed
     {
        return (new ReflectionMethod(BaseDto::class, $method))->invoke(null, ...$args);
     }
}

it('nested hydrates a single nested FromArray from a raw array', function (): void {
     $amount = BaseDtoTest::call('nested', ['Amount' => '150.00', 'Currency' => 'ZAR'], CurrencyAndAmountDTO::class);

    expect($amount)->toBeInstanceOf(CurrencyAndAmountDTO::class)
          ->and($amount->Amount)->toBe('150.00')
          ->and($amount->Currency)->toBe('ZAR');
});

it('nested returns null when the raw array is absent', function (): void {
    expect(BaseDtoTest::call('nested', null, CurrencyAndAmountDTO::class))->toBeNull();
});

it('nestedAll hydrates a list of FromArray objects', function (): void {
     $list = BaseDtoTest::call('nestedAll', [
         ['Amount' => '10.00', 'Currency' => 'ZAR'],
         ['Amount' => '20.00', 'Currency' => 'USD'],
     ], CurrencyAndAmountDTO::class);

    expect($list)->toHaveCount(2)
          ->and($list[0])->toBeInstanceOf(CurrencyAndAmountDTO::class)
          ->and($list[1]->Currency)->toBe('USD');
});

it('nestedAll returns an empty list for empty input', function (): void {
    expect(BaseDtoTest::call('nestedAll', [], CurrencyAndAmountDTO::class))->toBeEmpty();
});

it('enum maps a recognised code to its native case', function (): void {
     $code = BaseDtoTest::call('enum', CreditDebitCode::class, 'Credit');

    expect($code)->toBe(CreditDebitCode::Credit);
});

it('enum degrades an unrecognised or null code to null', function (): void {
    expect(BaseDtoTest::call('enum', CreditDebitCode::class, 'NotACode'))->toBeNull()
          ->and(BaseDtoTest::call('enum', CreditDebitCode::class, null))->toBeNull();
});

it('scalar reads a present key and degrades a missing key to null', function (): void {
    expect(BaseDtoTest::call('scalar', ['AccountId' => 'ACC-1'], 'AccountId'))->toBe('ACC-1')
          ->and(BaseDtoTest::call('scalar', ['AccountId' => 'ACC-1'], 'Missing'))->toBeNull();
});

it('exposes the Arrayable and FromArray contracts on BaseDto and concrete DTOs', function (): void {
    expect(class_implements(BaseDto::class))
          ->toContain(Arrayable::class)
          ->toContain(FromArray::class)
          ->and(class_implements(CurrencyAndAmountDTO::class))
          ->toContain(Arrayable::class)
          ->toContain(FromArray::class);
});
