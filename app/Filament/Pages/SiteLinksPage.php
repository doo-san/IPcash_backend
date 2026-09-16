<?php

namespace App\Filament\Pages;

class SiteLinksPage extends AbstractSiteSettingsGroupPage
{
    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'Réseaux sociaux & téléchargement';

    protected static ?string $navigationGroup = 'Site public';

    protected function groupKey(): string
    {
        return 'links';
    }
}
