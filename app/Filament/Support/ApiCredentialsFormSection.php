<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

// Section de formulaire réutilisée par `MobileMoneyProviderResource`,
// `CreditOperatorResource` et `BillProviderResource` — un seul endroit
// pour ce bloc plutôt que trois copies. Ne préjuge jamais qu'une
// intégration existe (CLAUDE.md règle 9) : ces champs stockent des
// identifiants pour le jour où un vrai prestataire sera branché, aucun
// code métier ne les consomme encore.
class ApiCredentialsFormSection
{
    public static function make(): Section
    {
        return Section::make('Intégration API')
            ->description(
                'Identifiants du prestataire réel, pour le jour où l\'intégration sera '.
                'branchée — aucune opération ne les utilise encore.',
            )
            ->collapsed()
            ->columns(2)
            ->schema([
                TextInput::make('api_base_url')
                    ->label('URL de base de l\'API')
                    ->url()
                    ->columnSpanFull(),
                TextInput::make('api_key')
                    ->label('Clé API')
                    ->password()
                    ->revealable()
                    ->dehydrated(fn (?string $state) => filled($state)),
                TextInput::make('api_secret')
                    ->label('Secret API')
                    ->password()
                    ->revealable()
                    ->dehydrated(fn (?string $state) => filled($state)),
                Toggle::make('is_live')
                    ->label('Mode production')
                    ->helperText('Désactivé = environnement de test (sandbox) du prestataire.')
                    ->columnSpanFull(),
            ]);
    }
}
