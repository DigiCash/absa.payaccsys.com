<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Enums;

/**
 * StatementAmountTypeCode — ExternalStatementAmountTypeCode.
 * Amount type in a coded form. x-namespaced-enum.
 */
enum StatementAmountTypeCode: string
{
    case ClosingBalance = 'ZA.ABSA.ClosingBalance';
    case CreditLimit = 'ZA.ABSA.CreditLimit';
    case StartingBalance = 'ZA.ABSA.StartingBalance';
    case TotalCharges = 'ZA.ABSA.TotalCharges';
    case TotalCredits = 'ZA.ABSA.TotalCredits';
    case TotalDebits = 'ZA.ABSA.TotalDebits';
}
