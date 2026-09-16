<?php

namespace App\Filament\Pages;

class SiteFooterPage extends AbstractSiteSettingsGroupPage
{
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Contenu du pied de page';

    protected static ?string $navigationGroup = 'Site public';

    protected static ?int $navigationSort = 20;

    protected function groupKey(): string
    {
        return 'footer';
    }
}
