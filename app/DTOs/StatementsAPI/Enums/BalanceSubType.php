<?php

declare(strict_types=1);

namespace App\DTOs\StatementsAPI\Enums;

/**
 * BalanceSubType — balance sub type in a coded form.
 * Default if not specified is BCUR of the account. Backs CurrencyAmountSubType.SubType.
 */
enum BalanceSubType: string
{
    case AdjT = 'ADJT';
    case BCur = 'BCUR';
    case BLCK = 'BLCK';
    case BLDK = 'BLKD';
    case DLod = 'DLOD';
    case East = 'EAST';
    case FCol = 'FCOL';
    case FCou = 'FCOU';
    case ForC = 'FORC';
    case Fund = 'FUND';
    case IntM = 'INTM';
    case LCur = 'LCUR';
    case LRld = 'LRLD';
    case Note = 'NOTE';
    case PDng = 'PDNG';
    case PIpo = 'PIPO';
    case PraV = 'PRAV';
    case ResV = 'RESV';
    case SCol = 'SCOL';
    case SCou = 'SCOU';
    case ThrE = 'THRE';
}
