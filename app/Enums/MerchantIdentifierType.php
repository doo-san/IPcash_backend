<?php

namespace App\Enums;

// Miroir de `MerchantIdentifierType` dans merchant.dart côté Flutter.
enum MerchantIdentifierType: string
{
    case PhoneNumber = 'phoneNumber';
    case MerchantId = 'merchantId';
    case QrCode = 'qrCode';
}
