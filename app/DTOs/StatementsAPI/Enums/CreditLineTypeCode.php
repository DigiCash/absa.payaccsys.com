<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Enums;

/**
 * CreditLineTypeCode — the Type field of CreditLineTypeDetail.
 * Inline enum from the spec; "Pre-Agreed" uses a safe case name.
 */
enum CreditLineTypeCode: string
{
    case Available = 'Available';
    case Credit = 'Credit';
    case Emergency = 'Emergency';
    case PreAgreed = 'Pre-Agreed';
    case Temporary = 'Temporary';
}
