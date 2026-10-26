<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Filet de sécurité en base pour chaque solde : même si un bug applicatif
// contournait un jour le verrou pessimiste (`LocksRowsForUpdate`), la base
// refuse elle-même toute écriture qui ferait passer un solde sous zéro.
// Postgres et MySQL (8.0.16+) supportent `CHECK` nativement ; SQLite (utilisé
// par la suite de tests) ne permet pas d'ajouter une contrainte `CHECK` via
// `ALTER TABLE` — on obtient le même effet avec un trigger qui lève une
// erreur sur la même condition.
return new class extends Migration
{
    /** @var array<string, string> Table => colonne à protéger. */
    private const COLUMNS = [
        'accounts' => 'balance_xof',
        'cards' => 'balance_xof',
        'pockets' => 'balance_xof',
        'ephemeral_cards' => 'balance_xof',
        'foreign_balances' => 'amount_minor_units',
    ];

    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        foreach (self::COLUMNS as $table => $column) {
            if ($driver === 'sqlite') {
                DB::statement(<<<SQL
                    CREATE TRIGGER chk_{$table}_{$column}_non_negative_insert
                    BEFORE INSERT ON {$table}
                    WHEN NEW.{$column} < 0
                    BEGIN
                        SELECT RAISE(ABORT, '{$column} must be >= 0');
                    END;
                SQL);
                DB::statement(<<<SQL
                    CREATE TRIGGER chk_{$table}_{$column}_non_negative_update
                    BEFORE UPDATE ON {$table}
                    WHEN NEW.{$column} < 0
                    BEGIN
                        SELECT RAISE(ABORT, '{$column} must be >= 0');
                    END;
                SQL);
            } else {
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT chk_{$table}_{$column}_non_negative CHECK ({$column} >= 0)");
            }
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        foreach (self::COLUMNS as $table => $column) {
            if ($driver === 'sqlite') {
                DB::statement("DROP TRIGGER IF EXISTS chk_{$table}_{$column}_non_negative_insert");
                DB::statement("DROP TRIGGER IF EXISTS chk_{$table}_{$column}_non_negative_update");
            } else {
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT chk_{$table}_{$column}_non_negative");
            }
        }
    }
};
