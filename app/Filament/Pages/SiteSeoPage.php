<?php

namespace App\Filament\Pages;

class SiteSeoPage extends AbstractSiteSettingsGroupPage
{
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationLabel = 'Référencement (SEO)';

    protected static ?string $navigationGroup = 'Site public';

    protected static ?int $navigationSort = 30;

    protected function groupKey(): string
    {
        return 'seo';
    }
}
