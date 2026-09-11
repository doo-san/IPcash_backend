<?php

namespace App\Enums;

enum MobileMoneyFlow: string
{
    case Redirect = 'redirect';
    case Ussd = 'ussd';
}
