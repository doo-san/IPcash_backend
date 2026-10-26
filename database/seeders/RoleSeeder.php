<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

// Deux rôles staff minimaux (Filament, pas de rapport avec les comptes
// clients) : `super_admin` peut tout faire, `support` a accès au panneau
// mais pas aux actions qui déplacent de l'argent ou changent une décision
// KYC (voir les `->visible()` dans TransactionResource/KycDocumentResource/
// FeeRuleResource/AppSettingResource/MobileMoneyProviderResource/
// ExchangeRateResource). Aucun rôle n'est assigné automatiquement : un
// compte `users` sans rôle ne peut plus accéder au panneau du tout
// (voir `User::canAccessPanel()`).
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::findOrCreate('super_admin');
        Role::findOrCreate('support');
    }
}
