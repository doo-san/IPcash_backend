<?php

namespace App\Filament\Resources;

use App\Enums\CardStatus;
use App\Filament\Resources\CardResource\Pages;
use App\Models\Card;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// Le PAN/CVV complets ne sont jamais stockés (voir migration) — cette
// ressource ne manipule que `last4` et les limites.
//
// La liste ne fait qu'afficher les informations : la seule action de ligne
// est « Modifier ». C'est sur la page d'édition (EditCard) que se trouvent
// les vraies actions — geler/dégeler, bloquer/débloquer, supprimer — via
// des actions dédiées plutôt qu'en changeant `status` à la main, pour
// rester cohérent avec l'audit attendu (même logique que le déverrouillage
// de compte). Distinction gel / blocage : le gel est réversible par le
// client depuis l'app, le blocage (`CardStatus::Blocked`) non — seul le
// staff peut le lever (voir CardController::unfreeze). « Supprimer »
// reverse toujours le solde restant sur le compte avant de supprimer la
// ligne — même logique que `DELETE /cards/{id}` côté API mobile, jamais
// d'argent perdu.
class CardResource extends Resource
{
    protected static ?string $model = Card::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Cartes';

    protected static ?string $navigationGroup = 'Argent';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('daily_limit_xof')
                ->label('Plafond journalier (XOF)')
                ->numeric(),
            Forms\Components\TextInput::make('monthly_limit_xof')
                ->label('Plafond mensuel (XOF)')
                ->numeric(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('account.phone_number')
                    ->label('Client')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last4')
                    ->label('4 derniers chiffres')
                    ->formatStateUsing(fn (string $state) => "•••• {$state}")
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (CardStatus $state) => match ($state) {
                        CardStatus::Active => 'success',
                        CardStatus::Frozen => 'warning',
                        CardStatus::Blocked => 'danger',
                    }),
                Tables\Columns\TextColumn::make('expiry')
                    ->label('Expire')
                    ->state(fn (Card $record) => sprintf('%02d/%d', $record->expiry_month, $record->expiry_year)),
                Tables\Columns\TextColumn::make('balance_xof')
                    ->label('Solde')
                    ->formatStateUsing(fn (int $state) => number_format($state, 0, ',', ' ').' F'),
                Tables\Columns\TextColumn::make('daily_limit_xof')
                    ->label('Plafond/jour')
                    ->formatStateUsing(fn (?int $state) => $state ? number_format($state, 0, ',', ' ').' F' : '—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'frozen' => 'Gelée',
                        'blocked' => 'Bloquée',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifier'),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCards::route('/'),
            'edit' => Pages\EditCard::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
