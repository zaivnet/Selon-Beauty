<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Services\EmployeeService;
use Illuminate\Console\Command;

class CleanupDeletedEmployeePiiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-deleted-employee-pii {--dry-run : Scan and report eligible legacy deleted employees without mutating database} {--force : Perform database cleanup without interactive prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up PII (email/phone) and revoke logins for legacy soft-deleted employees';

    /**
     * Execute the console command.
     */
    public function handle(EmployeeService $employeeService): int
    {
        $isForce = (bool) $this->option('force');
        $isDryRun = (bool) $this->option('dry-run');

        // Default to dry-run mode if neither --force nor --dry-run is specified
        if (! $isForce && ! $isDryRun) {
            if ($this->input->isInteractive()) {
                if (! $this->confirm('Running without --force option. Perform dry-run scan only?', true)) {
                    $this->info('Operation cancelled.');
                    return Command::SUCCESS;
                }
            }
            $isDryRun = true;
        }

        $this->info('=====================================================');
        $this->info(' Legacy Deleted Employee PII Cleanup Tool');
        $this->info(' Mode: ' . ($isDryRun ? 'DRY-RUN (Simulated)' : 'FORCE (Mutating Database)'));
        $this->info('=====================================================');

        // Query candidates: onlyTrashed with non-null email or phone
        $candidates = Employee::onlyTrashed()
            ->where(function ($q) {
                $q->whereNotNull('email')->orWhereNotNull('phone');
            })
            ->with('user')
            ->get();

        $totalScanned = $candidates->count();

        if ($totalScanned === 0) {
            $this->info('No legacy soft-deleted employees with PII found.');
            $this->info('Everything is clean and up to date.');

            if ($isDryRun) {
                $this->info('-----------------------------------------------------');
                $this->info("Scanned: 0");
                $this->info("Candidates: 0");
                $this->info("Ready: 0");
                $this->info("Conflicts: 0");
                $this->info("Would Clean: 0");
                $this->info('-----------------------------------------------------');
            } else {
                $this->info('-----------------------------------------------------');
                $this->info("Candidates: 0");
                $this->info("Cleaned: 0");
                $this->info("Skipped: 0");
                $this->info("Conflicts: 0");
                $this->info("Failed: 0");
                $this->info('-----------------------------------------------------');
            }

            return Command::SUCCESS;
        }

        $rows = [];
        $readyCount = 0;
        $conflictCount = 0;
        $cleanedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        foreach ($candidates as $employee) {
            $user = $employee->user;
            $hasEmail = ! is_null($employee->email) ? 'YES' : 'NO';
            $hasPhone = ! is_null($employee->phone) ? 'YES' : 'NO';
            $linkedUserStr = $user ? "YES (ID: {$user->id}, Role: {$user->role})" : 'NO';

            $isPrivileged = $user && in_array($user->role, ['superadmin', 'owner', 'admin'], true);

            if ($isDryRun) {
                if ($isPrivileged) {
                    $status = 'CONFLICT';
                    $conflictCount++;
                } else {
                    $status = 'READY';
                    $readyCount++;
                }

                $rows[] = [
                    'ID' => $employee->id,
                    'Code' => $employee->employee_code,
                    'Name' => $employee->full_name,
                    'Deleted At' => $employee->deleted_at?->toDateTimeString(),
                    'Has Email' => $hasEmail,
                    'Has Phone' => $hasPhone,
                    'Linked User' => $linkedUserStr,
                    'Status' => $status,
                ];
            } else {
                // FORCE mode: execute actual cleanup
                $result = $employeeService->cleanupLegacyDeletedEmployeePii($employee);

                if ($result['status'] === 'cleaned') {
                    $cleanedCount++;
                    $status = 'CLEANED';
                } elseif ($result['status'] === 'conflict') {
                    $conflictCount++;
                    $status = 'CONFLICT';
                } elseif ($result['status'] === 'skipped') {
                    $skippedCount++;
                    $status = 'SKIPPED';
                } else {
                    $failedCount++;
                    $status = 'FAILED';
                    $this->error("Failed to clean employee ID {$employee->id}: " . ($result['message'] ?? 'Unknown error'));
                }

                $rows[] = [
                    'ID' => $employee->id,
                    'Code' => $employee->employee_code,
                    'Name' => $employee->full_name,
                    'Deleted At' => $employee->deleted_at?->toDateTimeString(),
                    'Has Email' => $hasEmail,
                    'Has Phone' => $hasPhone,
                    'Linked User' => $linkedUserStr,
                    'Status' => $status,
                ];
            }
        }

        $this->table(
            ['ID', 'Code', 'Name', 'Deleted At', 'Has Email', 'Has Phone', 'Linked User', 'Status'],
            $rows
        );

        $this->info('-----------------------------------------------------');
        if ($isDryRun) {
            $this->info("Scanned: {$totalScanned}");
            $this->info("Candidates: {$totalScanned}");
            $this->info("Ready: {$readyCount}");
            $this->info("Conflicts: {$conflictCount}");
            $this->info("Would Clean: {$readyCount}");
        } else {
            $this->info("Candidates: {$totalScanned}");
            $this->info("Cleaned: {$cleanedCount}");
            $this->info("Skipped: {$skippedCount}");
            $this->info("Conflicts: {$conflictCount}");
            $this->info("Failed: {$failedCount}");
        }
        $this->info('-----------------------------------------------------');

        if ($failedCount > 0) {
            $this->error("Cleanup completed with {$failedCount} failure(s).");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
