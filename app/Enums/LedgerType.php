<?php

namespace App\Enums;

enum LedgerType: string
{
    case Earn = 'earn';
    case Redeem = 'redeem';
    case Refund = 'refund';
    case Adjust = 'adjust';
    case Birthday = 'birthday';
    case Exam = 'exam';
}
