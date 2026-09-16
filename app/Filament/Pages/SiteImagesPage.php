<?php

namespace App\Filament\Pages;

class SiteImagesPage extends AbstractSiteSettingsGroupPage
{
    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Images';

    protected static ?string $navigationGroup = 'Site public';

    protected function groupKey(): string
    {
        return 'images';
    }
}
