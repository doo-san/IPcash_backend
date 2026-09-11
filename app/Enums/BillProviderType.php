<?php

namespace App\Enums;

// Miroir de `BillProviderType` dans bill_provider.dart côté Flutter.
enum BillProviderType: string
{
    case Senelec = 'senelec';
    case Woyofal = 'woyofal';
    case SenEau = 'senEau';
    case CanalPlus = 'canalPlus';
    case Rapido = 'rapido';
}
