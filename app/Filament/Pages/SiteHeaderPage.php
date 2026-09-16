<?php

namespace App\Filament\Pages;

class SiteHeaderPage extends AbstractSiteSettingsGroupPage
{
    protected static ?string $navigationIcon = 'heroicon-o-bars-3';

    protected static ?string $navigationLabel = "Contenu de l'en-tête";

    protected static ?string $navigationGroup = 'Site public';

    protected static ?int $navigationSort = 10;

    protected function groupKey(): string
    {
        return 'header';
    }
}
