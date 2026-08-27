<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Enums;

/**
 * BalanceTypeCode — balance type in a coded form.
 * Backs TransactionCashBalance.Type.
 */
enum BalanceTypeCode: string
{
    case ClosingAvailable = 'ClosingAvailable';
    case ClosingBooked = 'ClosingBooked';
    case ClosingCleared = 'ClosingCleared';
    case Expected = 'Expected';
    case ForwardAvailable = 'ForwardAvailable';
    case Information = 'Information';
    case InterimAvailable = 'InterimAvailable';
    case InterimBooked = 'InterimBooked';
    case InterimCleared = 'InterimCleared';
    case OpeningAvailable = 'OpeningAvailable';
    case OpeningBooked = 'OpeningBooked';
    case OpeningCleared = 'OpeningCleared';
    case PreviouslyClosedBooked = 'PreviouslyClosedBooked';
}
