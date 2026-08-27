<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Enums;

/**
 * CreditDebitCode — whether an amount, balance or transaction is a credit or a debit.
 * A zero amount is considered a credit.
 */
enum CreditDebitCode: string
{
    case Credit = 'Credit';
    case Debit = 'Debit';
}
