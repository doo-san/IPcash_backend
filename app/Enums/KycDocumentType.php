<?php

namespace App\Enums;

enum KycDocumentType: string
{
    case NationalId = 'nationalId';
    case Passport = 'passport';
    case DriverLicense = 'driverLicense';
}
