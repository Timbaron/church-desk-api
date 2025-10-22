<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Transfer = 'Bank Transfer';
    case Cash = 'Cash';
    case Cheque = 'Cheque';
}
