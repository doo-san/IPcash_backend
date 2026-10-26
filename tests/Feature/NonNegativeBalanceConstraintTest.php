<?php

namespace Tests\Feature;

use App\Models\Account;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Filet de sécurité en base (migration add_non_negative_balance_constraints) :
// même en contournant complètement la couche applicative (verrou pessimiste,
// contrôles de solde des contrôleurs), la base elle-même refuse qu'un solde
// devienne négatif. Vérifié ici directement au niveau Eloquent/SQL plutôt
// qu'au travers d'un endpoint, puisque c'est une garantie indépendante du
// code applicatif.
class NonNegativeBalanceConstraintTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_balance_cannot_go_negative(): void
    {
        // `balance_xof` n'est pas dans `$fillable` d'`Account` (protection
        // contre l'assignation de masse) — assignation directe + `save()`,
        // comme le font les autres tests de ce projet.
        $account = Account::create(['phone_number' => '+221771110001']);
        $account->balance_xof = 500;
        $account->save();

        $this->expectException(QueryException::class);
        $account->decrement('balance_xof', 501);
    }

    public function test_card_balance_cannot_go_negative(): void
    {
        $account = Account::create(['phone_number' => '+221771110002']);
        $card = $account->cards()->create([
            'last4' => '4242', 'expiry_month' => 9, 'expiry_year' => 2030, 'balance_xof' => 500,
        ]);

        $this->expectException(QueryException::class);
        $card->decrement('balance_xof', 501);
    }

    public function test_pocket_balance_cannot_go_negative(): void
    {
        $account = Account::create(['phone_number' => '+221771110003']);
        $pocket = $account->pockets()->create(['name' => 'Projet', 'balance_xof' => 500]);

        $this->expectException(QueryException::class);
        $pocket->decrement('balance_xof', 501);
    }

    public function test_ephemeral_card_balance_cannot_go_negative(): void
    {
        $account = Account::create(['phone_number' => '+221771110004']);
        $ephemeralCard = $account->ephemeralCard()->create([
            'number' => '4000000000000000', 'cvv' => '123',
            'expiry_month' => 1, 'expiry_year' => 2030, 'balance_xof' => 500,
        ]);

        $this->expectException(QueryException::class);
        $ephemeralCard->decrement('balance_xof', 501);
    }

    public function test_foreign_balance_cannot_go_negative(): void
    {
        $account = Account::create(['phone_number' => '+221771110005']);
        $balance = $account->foreignBalances()->create(['currency_code' => 'EUR', 'amount_minor_units' => 500]);

        $this->expectException(QueryException::class);
        $balance->decrement('amount_minor_units', 501);
    }

    public function test_an_exact_zero_balance_is_allowed(): void
    {
        $account = Account::create(['phone_number' => '+221771110006']);
        $account->balance_xof = 500;
        $account->save();

        $account->decrement('balance_xof', 500);

        $this->assertSame(0, $account->fresh()->balance_xof);
    }
}
