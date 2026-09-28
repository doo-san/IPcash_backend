<?php

namespace App\Filament\Pages;

use App\Models\AppSetting;
use App\Support\AppConfig;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

// Réglages de l'application mobile : support, limites, maintenance, version
// minimale et services ouverts. Définis dans config/app_config.php, stockés
// dans `app_settings` (audités, voir AppSetting) et servis à l'app par
// `GET /config` (ConfigController). Chaque réglage est réellement appliqué par
// l'API (montant minimum, middlewares `feature` et `maintenance`).
class AppConfigPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = "Réglages de l'application";

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = "Réglages de l'application";

    protected static string $view = 'filament.pages.site-settings-group-page';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $filled = [];
        foreach (AppConfig::fields() as $key => $field) {
            $filled[$key] = AppConfig::get($key);
        }

        $this->form->fill($filled);
    }

    public function form(Form $form): Form
    {
        $sections = [];
        foreach (config('app_config') as $group) {
            $fields = [];
            foreach ($group['fields'] as $key => $field) {
                $fields[] = match ($field['type']) {
                    'toggle' => Toggle::make($key)->label($field['label']),
                    'textarea' => Textarea::make($key)->label($field['label'])->rows(3)->columnSpanFull(),
                    'integer' => TextInput::make($key)
                        ->label($field['label'])
                        ->numeric()
                        ->integer()
                        ->minValue($field['min'] ?? 0)
                        ->required(),
                    default => TextInput::make($key)->label($field['label']),
                };
            }

            $sections[] = Section::make($group['label'])->schema($fields)->columns(2);
        }

        return $form->schema($sections)->statePath('data');
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

        foreach (AppConfig::fields() as $key => $field) {
            $value = $state[$key] ?? null;
            $stored = match ($field['type']) {
                'toggle' => $value ? '1' : '0',
                default => $value === null ? '' : (string) $value,
            };

            AppSetting::query()->updateOrCreate(['key' => $key], ['value' => $stored]);
        }

        Notification::make()->title('Réglages enregistrés')->success()->send();
    }
}
