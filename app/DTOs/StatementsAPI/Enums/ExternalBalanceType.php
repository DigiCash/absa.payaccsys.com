<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Enums;

/**
 * ExternalBalanceType — balance type in a coded form.
 * Backs BalancesDetail.Type and IntraDay balances.
 */
enum ExternalBalanceType: string
{
    case ClosingAvailable = 'CLAV';
    case ClosingBooked = 'CLBD';
    case ForwardAvailable = 'FWAV';
    case Information = 'INFO';
    case InterimAvailable = 'ITAV';
    case InterimBooked = 'ITBD';
    case OpeningAvailable = 'OPAV';
    case OpeningBooked = 'OPBD';
    case PreviouslyClosedBooked = 'PRCD';
    case ExpectedClosed = 'XPCD';
}
