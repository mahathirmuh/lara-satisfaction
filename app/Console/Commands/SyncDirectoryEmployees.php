<?php

namespace App\Console\Commands;

use App\Services\Audit\AuditLogger;
use App\Services\Auth\DirectoryUnavailableException;
use App\Services\Auth\EmployeeDirectorySync;
use App\Services\Auth\EmployeePhotoSync;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

/**
 * Daily job (see routes/console.php): imports the employees of the assessed
 * departments from the network directory (see EmployeeDirectorySync), then
 * their photos from the access card system (see EmployeePhotoSync).
 */
#[Signature('users:sync-directory {--dry-run : Only report what would change} {--without-photos : Do not take the photos}')]
#[Description('Import the employees of the assessed departments from the network directory (LDAP) and their photos')]
class SyncDirectoryEmployees extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(EmployeeDirectorySync $sync, EmployeePhotoSync $photos, AuditLogger $audit): int
    {
        $dryRun = (bool) $this->option('dry-run');

        try {
            $result = $sync->sync($dryRun);
        } catch (DirectoryUnavailableException $exception) {
            report($exception);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        // The daily run is recorded in the audit trail when it changed an account (as "System").
        if (! $dryRun && EmployeeDirectorySync::changedAnything($result)) {
            $audit->log(AuditLogger::USERS_SYNCED, new: EmployeeDirectorySync::auditValues($result));
        }

        $this->info(($dryRun ? 'Dry run, nothing saved. ' : '').sprintf(
            '%d employees in the assessed departments: %d created, %d updated, %d unchanged. %d deactivated (disabled in the directory), %d no longer in an assessed department, %d deleted here (not restored).',
            $result['employees'], $result['created'], $result['updated'], $result['unchanged'],
            $result['deactivated'], $result['left'], $result['skipped_deleted'],
        ));

        if (! $dryRun && ! $this->option('without-photos') && $photos->isConfigured()) {
            try {
                $taken = $photos->sync();
                $this->info(sprintf('Photos: %d updated, %d unchanged, %d without a photo in the card system.', $taken['updated'], $taken['unchanged'], $taken['missing']));
            } catch (Throwable $exception) {
                // The employees are imported; only the photos wait for the next run.
                report($exception);
                $this->warn('The photos could not be taken: '.$exception->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
