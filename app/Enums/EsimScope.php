<?php

namespace App\Enums;

// Miroir de `_EsimScope` dans esim_plan_screen.dart côté Flutter.
enum EsimScope: string
{
    case Local = 'local';
    case Regional = 'regional';
    case Global = 'global';
}
