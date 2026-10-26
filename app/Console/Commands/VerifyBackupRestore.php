<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Backup\BackupDestination\BackupDestination;
use Throwable;
use ZipArchive;

// Une sauvegarde n'a de valeur que si elle se restaure : déchiffre la plus
// récente, recharge sa base dans une base temporaire, vérifie les tables
// critiques, puis supprime tout. Planifiée chaque semaine ; un échec est
// remonté à Sentry via report().
class VerifyBackupRestore extends Command
{
    protected $signature = 'backup:verify-restore';

    protected $description = 'Restaure la dernière sauvegarde dans une base temporaire et vérifie qu\'elle est exploitable';

    private const CRITICAL_TABLES = ['migrations', 'users', 'accounts', 'transactions', 'kyc_documents', 'insurance_policies'];

    private const CHECK_CONNECTION = 'backup_restore_check';

    public function handle(): int
    {
        $workDir = storage_path('app/backup-temp/restore-check-'.Str::random(8));
        $cleanup = fn () => null;

        try {
            File::ensureDirectoryExists($workDir);
            $this->guardEncryption();

            $sqlPath = $this->extractDatabaseDump($workDir);
            $cleanup = $this->restore($sqlPath, $workDir);
            $this->checkRestoredDatabase();

            $this->info('Sauvegarde restaurée et vérifiée.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            report(new RuntimeException('Vérification de restauration échouée : '.$e->getMessage(), previous: $e));
            $this->error('Échec : '.$e->getMessage());

            return self::FAILURE;
        } finally {
            DB::purge(self::CHECK_CONNECTION);
            $cleanup();
            File::deleteDirectory($workDir);
        }
    }

    private function guardEncryption(): void
    {
        if (app()->isProduction() && blank(config('backup.backup.password'))) {
            throw new RuntimeException('BACKUP_ARCHIVE_PASSWORD absent : les sauvegardes (pièces KYC) ne sont pas chiffrées.');
        }
    }

    private function extractDatabaseDump(string $workDir): string
    {
        $backup = BackupDestination::create(
            config('backup.backup.destination.disks')[0],
            config('backup.backup.name'),
        )->newestBackup();

        if ($backup === null) {
            throw new RuntimeException('aucune sauvegarde trouvée.');
        }
        $this->line("Sauvegarde : {$backup->path()} ({$backup->date()->toDateTimeString()})");

        $zipPath = "{$workDir}/backup.zip";
        $target = fopen($zipPath, 'w');
        stream_copy_to_stream($backup->stream(), $target);
        fclose($target);

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('archive illisible.');
        }
        if (filled(config('backup.backup.password'))) {
            $zip->setPassword(config('backup.backup.password'));
        }

        $dumpEntry = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_starts_with($name, 'db-dumps/') && ! str_ends_with($name, '/')) {
                $dumpEntry = $name;
            }
        }
        if ($dumpEntry === null || ! $zip->extractTo($workDir, $dumpEntry)) {
            $zip->close();
            throw new RuntimeException('dump de base absent ou impossible à déchiffrer.');
        }
        $this->line('Fichiers sauvegardés : '.($zip->numFiles - 1));
        $zip->close();

        $dumpPath = "{$workDir}/{$dumpEntry}";
        if (! str_ends_with($dumpPath, '.gz')) {
            return $dumpPath;
        }

        $sqlPath = substr($dumpPath, 0, -3);
        $sql = gzdecode((string) file_get_contents($dumpPath));
        if ($sql === false || $sql === '') {
            throw new RuntimeException('dump de base vide ou corrompu.');
        }
        file_put_contents($sqlPath, $sql);

        return $sqlPath;
    }

    /** @return callable(): void Nettoyage de la base temporaire. */
    private function restore(string $sqlPath, string $workDir): callable
    {
        $connection = config('database.connections.'.config('database.default'));

        return match ($connection['driver']) {
            'sqlite' => $this->restoreSqlite($sqlPath, $workDir),
            'pgsql' => $this->restorePgsql($sqlPath, $connection),
            default => throw new RuntimeException("pilote {$connection['driver']} non pris en charge."),
        };
    }

    private function restoreSqlite(string $sqlPath, string $workDir): callable
    {
        $database = "{$workDir}/restored.sqlite";
        Process::input((string) file_get_contents($sqlPath))
            ->run(['sqlite3', '-bail', $database])
            ->throw();

        config(['database.connections.'.self::CHECK_CONNECTION => ['driver' => 'sqlite', 'database' => $database]]);

        return fn () => null;
    }

    // Base temporaire sur le même serveur PostgreSQL (droit CREATEDB requis)
    // et `psql` disponible sur la machine qui lance la commande.
    private function restorePgsql(string $sqlPath, array $connection): callable
    {
        $database = $connection['database'].'_restore_check_'.Str::lower(Str::random(6));
        DB::statement("CREATE DATABASE \"{$database}\"");
        $drop = fn () => DB::statement("DROP DATABASE IF EXISTS \"{$database}\"");

        Process::env(['PGPASSWORD' => (string) $connection['password']])
            ->timeout(1800)
            ->run([
                'psql', '-v', 'ON_ERROR_STOP=1', '-q',
                '-h', (string) $connection['host'], '-p', (string) $connection['port'],
                '-U', (string) $connection['username'], '-d', $database, '-f', $sqlPath,
            ])
            ->throw();

        config(['database.connections.'.self::CHECK_CONNECTION => array_merge($connection, ['database' => $database])]);

        return $drop;
    }

    private function checkRestoredDatabase(): void
    {
        $restored = DB::connection(self::CHECK_CONNECTION);
        $missing = array_filter(
            self::CRITICAL_TABLES,
            fn (string $table) => ! Schema::connection(self::CHECK_CONNECTION)->hasTable($table),
        );
        if ($missing !== []) {
            throw new RuntimeException('tables absentes après restauration : '.implode(', ', $missing));
        }
        if ($restored->table('migrations')->count() === 0) {
            throw new RuntimeException('table des migrations vide.');
        }

        $this->table(
            ['Table', 'Lignes restaurées', 'Lignes en production'],
            array_map(fn (string $table) => [
                $table,
                $restored->table($table)->count(),
                DB::table($table)->count(),
            ], self::CRITICAL_TABLES),
        );
    }
}
