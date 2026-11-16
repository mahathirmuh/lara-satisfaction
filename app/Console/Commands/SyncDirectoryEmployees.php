<?php

namespace App\Console\Commands;

use App\Services\Auth\DirectoryUnavailableException;
use App\Services\Auth\EmployeeDirectorySync;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Daily job (see routes/console.php): imports the employees of the assessed
 * departments from the network directory (see EmployeeDirectorySync).
 */
#[Signature('users:sync-directory {--dry-run : Only report what would change}')]
#[Description('Import the employees of the assessed departments from the network directory (LDAP)')]
class SyncDirectoryEmployees extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(EmployeeDirectorySync $sync): int
    {
        try {
            $result = $sync->sync((bool) $this->option('dry-run'));
        } catch (DirectoryUnavailableException $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(($this->option('dry-run') ? 'Dry run, nothing saved. ' : '').sprintf(
            '%d employees in the assessed departments: %d created, %d updated, %d unchanged. %d deactivated (disabled in the directory), %d no longer in an assessed department, %d deleted here (not restored).',
            $result['employees'], $result['created'], $result['updated'], $result['unchanged'],
            $result['deactivated'], $result['left'], $result['skipped_deleted'],
        ));

        return self::SUCCESS;
    }
}
