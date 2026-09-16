<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

// Base commune aux sous-pages du groupe de navigation "Site public" (voir
// SiteLinksPage, SiteImagesPage) — chacune n'édite qu'un seul groupe de
// config/site_settings.php (`groupKey()`), plutôt qu'un unique gros
// formulaire à sections. app/Support/helpers.php (site_setting()/
// site_setting_image_url()) lit ces réglages côté vue publique.
abstract class AbstractSiteSettingsGroupPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.pages.site-settings-group-page';

    /** @var array<string, string|null> */
    public array $data = [];

    abstract protected function groupKey(): string;

    /**
     * @return array{label: string, fields: array<string, array{label: string, type: string, default: string}>}
     */
    protected function groupConfig(): array
    {
        return config('site_settings.'.$this->groupKey());
    }

    public function mount(): void
    {
        $keys = array_keys($this->groupConfig()['fields']);
        $stored = SiteSetting::query()->whereIn('key', $keys)->pluck('value', 'key');

        $filled = [];
        foreach ($keys as $key) {
            $filled[$key] = $stored[$key] ?? null;
        }

        $this->form->fill($filled);
    }

    public function form(Form $form): Form
    {
        $fields = [];
        foreach ($this->groupConfig()['fields'] as $key => $field) {
            $fields[] = $field['type'] === 'image'
                ? FileUpload::make($key)
                    ->label($field['label'])
                    ->image()
                    ->disk('public')
                    ->directory('site-settings')
                    ->imagePreviewHeight('100')
                    ->helperText("Par défaut : {$field['default']}")
                : TextInput::make($key)
                    ->label($field['label'])
                    ->url()
                    ->placeholder('https://…');
        }

        return $form->schema($fields)->statePath('data')->columns(2);
    }

    public function getFormActions(): array
    {
        return [
            Action::make('save')->label('Enregistrer')->submit('save'),
        ];
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            SiteSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Notification::make()->title('Réglages enregistrés')->success()->send();
    }
}
