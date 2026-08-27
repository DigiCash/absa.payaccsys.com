<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Enums;

/**
 * StatementFeeFrequencyCode — ExternalStatementFeeFrequencyCode.
 * How frequently the fee is applied to the Account. x-namespaced-enum.
 */
enum StatementFeeFrequencyCode: string
{
    case ChargingPeriod = 'ZA.ABSA.ChargingPeriod';
    case PerTransactionAmount = 'ZA.ABSA.PerTransactionAmount';
    case PerTransactionPercentage = 'ZA.ABSA.PerTransactionPercentage';
    case Quarterly = 'ZA.ABSA.Quarterly';
    case StatementMonthly = 'ZA.ABSA.StatementMonthly';
    case Weekly = 'ZA.ABSA.Weekly';
}
