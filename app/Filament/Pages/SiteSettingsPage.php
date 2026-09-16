<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

// Réglages globaux du site public (header/footer) : liens réseaux sociaux,
// liens de téléchargement (App Store/Google Play) et images — voir
// config/site_settings.php pour la liste des champs et
// app/Support/helpers.php (site_setting()/site_setting_image_url()) pour
// la lecture côté vue. Un champ vidé retombe sur son défaut (le lien/image
// livré avec le code) plutôt que de casser l'affichage.
class SiteSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Réglages du site';

    protected static ?string $navigationGroup = 'Contenu';

    protected static string $view = 'filament.pages.site-settings-page';

    /** @var array<string, array<string, string|null>> */
    public array $data = [];

    public function mount(): void
    {
        $stored = SiteSetting::query()->pluck('value', 'key');

        $filled = [];
        foreach (config('site_settings') as $group => $groupConfig) {
            foreach ($groupConfig['fields'] as $key => $field) {
                $filled[$group][$key] = $stored[$key] ?? null;
            }
        }

        $this->form->fill($filled);
    }

    public function form(Form $form): Form
    {
        $sections = [];
        foreach (config('site_settings') as $group => $groupConfig) {
            $fields = [];
            foreach ($groupConfig['fields'] as $key => $field) {
                $fields[] = $field['type'] === 'image'
                    ? FileUpload::make("{$group}.{$key}")
                        ->label($field['label'])
                        ->image()
                        ->disk('public')
                        ->directory('site-settings')
                        ->imagePreviewHeight('100')
                        ->helperText("Par défaut : {$field['default']}")
                    : TextInput::make("{$group}.{$key}")
                        ->label($field['label'])
                        ->url()
                        ->placeholder('https://…');
            }
            $sections[] = Section::make($groupConfig['label'])->schema($fields)->columns(2);
        }

        return $form->schema($sections)->statePath('data')->columns(1);
    }

    public function getFormActions(): array
    {
        return [
            Action::make('save')->label('Enregistrer')->submit('save'),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($state as $group => $fields) {
            foreach ($fields as $key => $value) {
                SiteSetting::query()->updateOrCreate(
                    ['key' => $key],
                    ['value' => $value],
                );
            }
        }

        Notification::make()->title('Réglages enregistrés')->success()->send();
    }
}
