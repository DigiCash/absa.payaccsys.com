<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Enums;

/**
 * StatementTypeCode — ExternalStatementTypeCode. Statement type in a coded form.
 * Backs StatementDetail.Type.
 */
enum StatementTypeCode: string
{
    case AccountClosure = 'AccountClosure';
    case AccountOpening = 'AccountOpening';
    case Annual = 'Annual';
    case Interim = 'Interim';
    case RegularPeriodic = 'RegularPeriodic';
}
