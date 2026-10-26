<?php

namespace Tests\Feature;

use App\Models\Account;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

// Sauvegarde réelle (dump sqlite3 + zip chiffré) puis restauration dans une
// base temporaire, sur une base fichier dédiée au test.
class BackupRestoreTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir().'/ipcash-backup-test-'.uniqid();
        File::ensureDirectoryExists($this->dir.'/backups');
        touch($this->dir.'/db.sqlite');

        config([
            'database.connections.sqlite.database' => $this->dir.'/db.sqlite',
            'filesystems.disks.backups' => ['driver' => 'local', 'root' => $this->dir.'/backups', 'throw' => true],
            'backup.backup.password' => 'mot-de-passe-test',
            'backup.backup.source.databases' => ['sqlite'],
        ]);
        DB::purge('sqlite');
        Artisan::call('migrate', ['--force' => true]);

        $account = Account::create(['phone_number' => '+221770000501']);
        $account->balance_xof = 1000;
        $account->save();
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        File::deleteDirectory($this->dir);

        parent::tearDown();
    }

    private function runBackup(): void
    {
        $this->assertSame(0, Artisan::call('backup:run', ['--only-db' => true, '--disable-notifications' => true]));
    }

    public function test_the_latest_backup_restores_with_its_data(): void
    {
        $this->runBackup();

        $this->artisan('backup:verify-restore')
            ->expectsOutputToContain('Sauvegarde restaurée et vérifiée.')
            ->assertExitCode(0);
    }

    public function test_a_backup_that_cannot_be_decrypted_fails_verification(): void
    {
        $this->runBackup();
        config(['backup.backup.password' => 'autre-mot-de-passe']);

        $this->artisan('backup:verify-restore')
            ->expectsOutputToContain('impossible à déchiffrer')
            ->assertExitCode(1);
    }

    public function test_missing_backups_fail_verification(): void
    {
        $this->artisan('backup:verify-restore')
            ->expectsOutputToContain('aucune sauvegarde trouvée')
            ->assertExitCode(1);
    }

    public function test_production_refuses_unencrypted_backups(): void
    {
        $this->runBackup();
        config(['backup.backup.password' => null]);
        $this->app['env'] = 'production';

        $this->artisan('backup:verify-restore')
            ->expectsOutputToContain('BACKUP_ARCHIVE_PASSWORD absent')
            ->assertExitCode(1);
    }
}
