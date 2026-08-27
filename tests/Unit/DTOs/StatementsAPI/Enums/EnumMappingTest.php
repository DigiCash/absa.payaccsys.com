<?php

declare(strict_types=1);

use App\DTOs\StatementsAPI\Enums\BalanceSubType;
use App\DTOs\StatementsAPI\Enums\BalanceTypeCode;
use App\DTOs\StatementsAPI\Enums\CreditDebitCode;
use App\DTOs\StatementsAPI\Enums\CreditLineTypeCode;
use App\DTOs\StatementsAPI\Enums\EntryStatusCode;
use App\DTOs\StatementsAPI\Enums\ExternalBalanceType;
use App\DTOs\StatementsAPI\Enums\StatementAmountTypeCode;
use App\DTOs\StatementsAPI\Enums\StatementFeeFrequencyCode;
use App\DTOs\StatementsAPI\Enums\StatementFeeTypeCode;
use App\DTOs\StatementsAPI\Enums\StatementTypeCode;

/*
 * Hermetic enum-mapping assertions. No DB / HTTP.
 *
 * Two kinds of enum are exercised here:
 *  - plain string-backed enums whose case name === backing value
 *    (e.g. CreditDebitCode::Credit === 'Credit');
 *  - x-namespaced enums whose case name is a safe suffix but whose backing
 *    value is the full "ZA.ABSA.*" code (e.g. StatementFeeTypeCode::CashFee
 *    === 'ZA.ABSA.CashFee').
 */

it('maps plain string-backed enums by their exact code value', function () {
    expect(CreditDebitCode::tryFrom('Credit'))->toBe(CreditDebitCode::Credit);
    expect(CreditDebitCode::Credit->value)->toBe('Credit');
    expect(CreditDebitCode::Debit->value)->toBe('Debit');

    expect(EntryStatusCode::tryFrom('Booked'))->toBe(EntryStatusCode::Booked);
    expect(EntryStatusCode::tryFrom('Pending'))->toBe(EntryStatusCode::Pending);

    expect(BalanceTypeCode::tryFrom('ClosingBooked'))->toBe(BalanceTypeCode::ClosingBooked);
    expect(BalanceTypeCode::ClosingBooked->value)->toBe('ClosingBooked');
});

it('maps the namespaced ZA.ABSA.* enums to their full backing value', function () {
    expect(StatementFeeTypeCode::tryFrom('ZA.ABSA.CashFee'))->toBe(StatementFeeTypeCode::CashFee);
    expect(StatementFeeTypeCode::CashFee->value)->toBe('ZA.ABSA.CashFee');
    expect(StatementFeeTypeCode::SwitchFee->value)->toBe('ZA.ABSA.SwitchFee');

    expect(StatementAmountTypeCode::tryFrom('ZA.ABSA.ClosingBalance'))
        ->toBe(StatementAmountTypeCode::ClosingBalance);
    expect(StatementAmountTypeCode::TotalCredits->value)->toBe('ZA.ABSA.TotalCredits');

    expect(StatementFeeFrequencyCode::tryFrom('ZA.ABSA.Quarterly'))
        ->toBe(StatementFeeFrequencyCode::Quarterly);
    expect(StatementFeeFrequencyCode::StatementMonthly->value)
        ->toBe('ZA.ABSA.StatementMonthly');
});

it('resolves ExternalBalanceType (4-letter codes) and CreditLineTypeCode (incl. Pre-Agreed)', function () {
    expect(ExternalBalanceType::tryFrom('CLAV'))->toBe(ExternalBalanceType::ClosingAvailable);
    expect(ExternalBalanceType::ClosingAvailable->value)->toBe('CLAV');
    expect(ExternalBalanceType::ExpectedClosed->value)->toBe('XPCD');

    // "Pre-Agreed" contains a hyphen, so the safe case name is PreAgreed
    // but the backing value is the exact spec code "Pre-Agreed".
    expect(CreditLineTypeCode::tryFrom('Pre-Agreed'))->toBe(CreditLineTypeCode::PreAgreed);
    expect(CreditLineTypeCode::PreAgreed->value)->toBe('Pre-Agreed');
});

it('resolves StatementTypeCode and BalanceSubType plain codes', function () {
    expect(StatementTypeCode::tryFrom('RegularPeriodic'))->toBe(StatementTypeCode::RegularPeriodic);
    expect(StatementTypeCode::Interim->value)->toBe('Interim');

    expect(BalanceSubType::tryFrom('BCUR'))->toBe(BalanceSubType::BCur);
    expect(BalanceSubType::LCur->value)->toBe('LCUR');
});

it('degrades an unrecognised code to null instead of throwing', function () {
    expect(CreditDebitCode::tryFrom('NotACode'))->toBeNull();
    expect(StatementFeeTypeCode::tryFrom('CashFee'))->toBeNull(); // missing ZA.ABSA. prefix
    expect(StatementAmountTypeCode::tryFrom('ZA.ABSA.Nope'))->toBeNull();
    expect(ExternalBalanceType::tryFrom('ZZZZ'))->toBeNull();
});

it('keeps every enum case name free of dots (PHP-safe names)', function () {
    foreach ([
        BalanceSubType::cases(),
        BalanceTypeCode::cases(),
        CreditDebitCode::cases(),
        CreditLineTypeCode::cases(),
        EntryStatusCode::cases(),
        ExternalBalanceType::cases(),
        StatementAmountTypeCode::cases(),
        StatementFeeFrequencyCode::cases(),
        StatementFeeTypeCode::cases(),
        StatementTypeCode::cases(),
    ] as $cases) {
        foreach ($cases as $case) {
            expect($case->name)->not->toContain('.');
        }
    }
});

it('round-trips every enum value back to its own case via tryFrom()', function () {
    foreach ([
        ...CreditDebitCode::cases(),
        ...EntryStatusCode::cases(),
        ...BalanceTypeCode::cases(),
        ...BalanceSubType::cases(),
        ...StatementTypeCode::cases(),
        ...StatementFeeTypeCode::cases(),
        ...StatementAmountTypeCode::cases(),
        ...StatementFeeFrequencyCode::cases(),
        ...ExternalBalanceType::cases(),
        ...CreditLineTypeCode::cases(),
    ] as $case) {
        $class = get_class($case);
        expect($class::tryFrom($case->value))->toBe($case);
    }
});