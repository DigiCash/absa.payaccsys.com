<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Enums;

/**
 * StatementFeeTypeCode — ExternalStatementFeeTypeCode.
 * x-namespaced-enum: case names are the suffix after "ZA.ABSA.", the backing
 * value is the full namespaced code (PHP case names cannot contain dots).
 */
enum StatementFeeTypeCode: string
{
    case CashFee = 'ZA.ABSA.CashFee';
    case TransactionFee = 'ZA.ABSA.TransactionFee';
    case SwitchFee = 'ZA.ABSA.SwitchFee';
    case AdminCharge = 'ZA.ABSA.AdminCharge';
    case TransactionSurcharge = 'ZA.ABSA.TransactionSurcharge';
    case POSCashFee = 'ZA.ABSA.POSCashFee';
    case ServiceFee = 'ZA.ABSA.ServiceFee';
}
