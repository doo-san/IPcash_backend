<?php

namespace App\Filament\Pages;

use App\Models\SiteContent;
use Filament\Actions\Action;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

// Édition des textes clés du site public (resources/views/site/*.blade.php)
// sans toucher au code — voir config/site_content.php pour la liste des
// champs éditables par page (label + texte par défaut) et
// app/Support/helpers.php (site_content()) pour la lecture côté vue.
// Un champ vidé ici retombe sur son défaut plutôt que d'afficher une
// section blanche (voir site_content()) — donc "vider" équivaut à
// "réinitialiser", pas à "effacer définitivement".
class SiteContentPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Contenu du site';

    protected static ?string $navigationGroup = 'Contenu';

    protected static string $view = 'filament.pages.site-content-page';

    /** @var array<string, array<string, string>> */
    public array $data = [];

    public function mount(): void
    {
        $filled = [];
        foreach (config('site_content') as $page => $pageConfig) {
            foreach ($pageConfig['fields'] as $key => $field) {
                $filled[$page][$key] = site_content($page, $key);
            }
        }

        $this->form->fill($filled);
    }

    public function form(Form $form): Form
    {
        $tabs = [];
        foreach (config('site_content') as $page => $pageConfig) {
            $fields = [];
            foreach ($pageConfig['fields'] as $key => $field) {
                $component = match ($field['type']) {
                    'textarea' => Textarea::make("{$page}.{$key}")->rows(3),
                    default => TextInput::make("{$page}.{$key}"),
                };
                $fields[] = $component->label($field['label'])->columnSpanFull();
            }
            $tabs[] = Tab::make($pageConfig['label'])->schema($fields);
        }

        return $form
            ->schema([Tabs::make('Pages')->tabs($tabs)->columnSpanFull()])
            ->statePath('data')
            ->columns(1);
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

        foreach ($state as $page => $fields) {
            foreach ($fields as $key => $value) {
                SiteContent::query()->updateOrCreate(
                    ['page' => $page, 'key' => $key],
                    ['value' => $value],
                );
            }
        }

        Notification::make()->title('Contenu enregistré')->success()->send();
    }
}
