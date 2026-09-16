<?php

namespace App\Filament\Pages;

class SiteImagesPage extends AbstractSiteSettingsGroupPage
{
    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Autres images';

    protected static ?string $navigationGroup = 'Site public';

    protected static ?int $navigationSort = 40;

    protected function groupKey(): string
    {
        return 'images';
    }
}
