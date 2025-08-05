<?php

namespace App\Console\Commands;

use App\Models\ApiToken;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneExpiredTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:prune 
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--days=30 : Delete tokens expired more than X days ago}
                            {--inactive : Also delete inactive tokens}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune expired and old API tokens from the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $days = (int) $this->option('days');
        $includeInactive = $this->option('inactive');

        $this->info("🔍 Scanning for tokens to prune...");
        
        $cutoffDate = now()->subDays($days);
        
        // Build query for expired tokens
        $expiredQuery = ApiToken::where('expires_at', '<', now())
            ->where('expires_at', '<', $cutoffDate);
            
        // Build query for inactive tokens if requested
        $inactiveQuery = $includeInactive 
            ? ApiToken::where('is_active', false)->where('updated_at', '<', $cutoffDate)
            : null;

        // Count tokens to be deleted
        $expiredCount = $expiredQuery->count();
        $inactiveCount = $inactiveQuery ? $inactiveQuery->count() : 0;
        $totalCount = $expiredCount + $inactiveCount;

        if ($totalCount === 0) {
            $this->info("✅ No tokens found to prune.");
            return Command::SUCCESS;
        }

        // Show summary
        $this->table(['Type', 'Count'], [
            ['Expired tokens (older than ' . $days . ' days)', $expiredCount],
            ['Inactive tokens (older than ' . $days . ' days)', $inactiveCount],
            ['Total', $totalCount]
        ]);

        if ($dryRun) {
            $this->warn("🔍 DRY RUN: No tokens were actually deleted.");
            
            // Show some examples of what would be deleted
            $examples = $expiredQuery->with(['application', 'user'])->limit(5)->get();
            if ($examples->count() > 0) {
                $this->info("\nExamples of expired tokens that would be deleted:");
                foreach ($examples as $token) {
                    $this->line("- Token '{$token->name}' for {$token->application->name} (expired: {$token->expires_at})");
                }
            }
            
            return Command::SUCCESS;
        }

        // Confirm deletion
        if (!$this->confirm("Are you sure you want to delete {$totalCount} tokens?")) {
            $this->info("Operation cancelled.");
            return Command::SUCCESS;
        }

        // Perform deletion with progress bar
        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->start();

        DB::transaction(function () use ($expiredQuery, $inactiveQuery, $progressBar) {
            // Delete expired tokens
            if ($expiredQuery->count() > 0) {
                $expiredQuery->chunk(100, function ($tokens) use ($progressBar) {
                    foreach ($tokens as $token) {
                        $token->delete();
                        $progressBar->advance();
                    }
                });
            }

            // Delete inactive tokens
            if ($inactiveQuery && $inactiveQuery->count() > 0) {
                $inactiveQuery->chunk(100, function ($tokens) use ($progressBar) {
                    foreach ($tokens as $token) {
                        $token->delete();
                        $progressBar->advance();
                    }
                });
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ Successfully pruned {$totalCount} tokens.");
        
        // Show remaining token statistics
        $remainingTokens = ApiToken::count();
        $activeTokens = ApiToken::where('is_active', true)->count();
        
        $this->table(['Statistic', 'Count'], [
            ['Remaining tokens', $remainingTokens],
            ['Active tokens', $activeTokens],
            ['Inactive tokens', $remainingTokens - $activeTokens]
        ]);

        return Command::SUCCESS;
    }
}
