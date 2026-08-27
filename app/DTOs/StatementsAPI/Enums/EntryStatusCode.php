<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Enums;

/**
 * EntryStatusCode — status of a transaction entry on the books of the
 * account servicer. Backs TransactionDetail.Status.
 */
enum EntryStatusCode: string
{
    case Booked = 'Booked';
    case Pending = 'Pending';
}
