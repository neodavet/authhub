<?php

namespace App\Console\Commands;

use App\Models\ApiToken;
use App\Models\Application;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TokenStatistics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:stats 
                            {--application= : Show stats for specific application ID}
                            {--user= : Show stats for specific user ID or email}
                            {--detailed : Show detailed breakdown}
                            {--export= : Export stats to file (json|csv)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display comprehensive token usage statistics';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $applicationId = $this->option('application');
        $userIdentifier = $this->option('user');
        $detailed = $this->option('detailed');
        $exportFormat = $this->option('export');

        $this->info("📊 Generating Token Statistics...");
        $this->newLine();

        // Filter by application if specified
        $applicationFilter = null;
        if ($applicationId) {
            $applicationFilter = Application::find($applicationId);
            if (!$applicationFilter) {
                $this->error("❌ Application not found: {$applicationId}");
                return Command::FAILURE;
            }
            $this->info("🔍 Filtering by application: {$applicationFilter->name}");
        }

        // Filter by user if specified
        $userFilter = null;
        if ($userIdentifier) {
            $userFilter = is_numeric($userIdentifier) 
                ? User::find($userIdentifier)
                : User::where('email', $userIdentifier)->first();
                
            if (!$userFilter) {
                $this->error("❌ User not found: {$userIdentifier}");
                return Command::FAILURE;
            }
            $this->info("🔍 Filtering by user: {$userFilter->name} ({$userFilter->email})");
        }

        // Build query
        $tokensQuery = ApiToken::query();
        if ($applicationFilter) {
            $tokensQuery->where('application_id', $applicationFilter->id);
        }
        if ($userFilter) {
            $tokensQuery->where('user_id', $userFilter->id);
        }

        // Basic statistics
        $this->displayBasicStats($tokensQuery);
        
        // Status breakdown
        $this->displayStatusBreakdown($tokensQuery);
        
        // Expiration analysis
        $this->displayExpirationAnalysis($tokensQuery);
        
        // Usage patterns
        $this->displayUsagePatterns($tokensQuery);

        if ($detailed) {
            $this->displayDetailedBreakdown($tokensQuery);
        }

        // Application statistics
        if (!$applicationFilter) {
            $this->displayApplicationStats($userFilter);
        }

        // User statistics  
        if (!$userFilter) {
            $this->displayUserStats($applicationFilter);
        }

        // Scope analysis
        $this->displayScopeAnalysis($tokensQuery);

        if ($exportFormat) {
            $this->exportStats($tokensQuery, $exportFormat);
        }

        return Command::SUCCESS;
    }

    private function displayBasicStats($tokensQuery): void
    {
        $totalTokens = $tokensQuery->count();
        $activeTokens = $tokensQuery->where('is_active', true)->count();
        $inactiveTokens = $totalTokens - $activeTokens;
        
        $expiredTokens = $tokensQuery->where('expires_at', '<', now())->count();
        $neverExpireTokens = $tokensQuery->whereNull('expires_at')->count();

        $this->table(['Metric', 'Count', 'Percentage'], [
            ['Total Tokens', $totalTokens, '100%'],
            ['Active Tokens', $activeTokens, $this->percentage($activeTokens, $totalTokens)],
            ['Inactive Tokens', $inactiveTokens, $this->percentage($inactiveTokens, $totalTokens)],
            ['Expired Tokens', $expiredTokens, $this->percentage($expiredTokens, $totalTokens)],
            ['Never Expire', $neverExpireTokens, $this->percentage($neverExpireTokens, $totalTokens)],
        ]);
        $this->newLine();
    }

    private function displayStatusBreakdown($tokensQuery): void
    {
        $this->info("🔄 <comment>Token Status Breakdown:</comment>");
        
        $statusStats = $tokensQuery->select('is_active', DB::raw('count(*) as count'))
            ->groupBy('is_active')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->is_active ? 'Active' : 'Inactive' => $item->count];
            });

        foreach ($statusStats as $status => $count) {
            $this->line("  {$status}: {$count} tokens");
        }
        $this->newLine();
    }

    private function displayExpirationAnalysis($tokensQuery): void
    {
        $this->info("⏰ <comment>Expiration Analysis:</comment>");
        
        $now = now();
        $expirationStats = [
            'Already Expired' => $tokensQuery->where('expires_at', '<', $now)->count(),
            'Expires in 24h' => $tokensQuery->whereBetween('expires_at', [$now, $now->copy()->addDay()])->count(),
            'Expires in 7 days' => $tokensQuery->whereBetween('expires_at', [$now, $now->copy()->addWeek()])->count(),
            'Expires in 30 days' => $tokensQuery->whereBetween('expires_at', [$now, $now->copy()->addMonth()])->count(),
            'Never Expires' => $tokensQuery->whereNull('expires_at')->count(),
        ];

        foreach ($expirationStats as $period => $count) {
            if ($count > 0) {
                $this->line("  {$period}: {$count} tokens");
            }
        }
        $this->newLine();
    }

    private function displayUsagePatterns($tokensQuery): void
    {
        $this->info("📈 <comment>Usage Patterns:</comment>");
        
        $usageStats = [
            'Used in last 24h' => $tokensQuery->where('last_used_at', '>=', now()->subDay())->count(),
            'Used in last week' => $tokensQuery->where('last_used_at', '>=', now()->subWeek())->count(),
            'Used in last month' => $tokensQuery->where('last_used_at', '>=', now()->subMonth())->count(),
            'Never used' => $tokensQuery->whereNull('last_used_at')->count(),
        ];

        foreach ($usageStats as $period => $count) {
            if ($count > 0) {
                $this->line("  {$period}: {$count} tokens");
            }
        }
        $this->newLine();
    }

    private function displayDetailedBreakdown($tokensQuery): void
    {
        $this->info("🔍 <comment>Detailed Token Breakdown:</comment>");
        
        $tokens = $tokensQuery->with(['application', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        if ($tokens->count() > 0) {
            $tableData = [];
            foreach ($tokens as $token) {
                $tableData[] = [
                    substr($token->token, 0, 16) . '...',
                    $token->name,
                    $token->application->name ?? 'N/A',
                    $token->user->name ?? 'N/A',
                    $token->is_active ? '✅' : '❌',
                    $token->expires_at ? $token->expires_at->format('Y-m-d') : 'Never',
                    $token->last_used_at ? $token->last_used_at->diffForHumans() : 'Never',
                ];
            }

            $this->table([
                'Token (partial)', 'Name', 'Application', 'User', 'Active', 'Expires', 'Last Used'
            ], $tableData);
        }
        $this->newLine();
    }

    private function displayApplicationStats($userFilter): void
    {
        $this->info("📱 <comment>Top Applications by Token Count:</comment>");
        
        $query = Application::withCount(['apiTokens' => function ($query) use ($userFilter) {
            if ($userFilter) {
                $query->where('user_id', $userFilter->id);
            }
        }])->orderBy('api_tokens_count', 'desc')->limit(10);

        $applications = $query->get();
        
        if ($applications->count() > 0) {
            foreach ($applications as $app) {
                if ($app->api_tokens_count > 0) {
                    $this->line("  {$app->name}: {$app->api_tokens_count} tokens");
                }
            }
        }
        $this->newLine();
    }

    private function displayUserStats($applicationFilter): void
    {
        $this->info("👥 <comment>Top Users by Token Count:</comment>");
        
        $query = User::withCount(['apiTokens' => function ($query) use ($applicationFilter) {
            if ($applicationFilter) {
                $query->where('application_id', $applicationFilter->id);
            }
        }])->orderBy('api_tokens_count', 'desc')->limit(10);

        $users = $query->get();
        
        if ($users->count() > 0) {
            foreach ($users as $user) {
                if ($user->api_tokens_count > 0) {
                    $this->line("  {$user->name} ({$user->email}): {$user->api_tokens_count} tokens");
                }
            }
        }
        $this->newLine();
    }

    private function displayScopeAnalysis($tokensQuery): void
    {
        $this->info("🔐 <comment>Scope Usage Analysis:</comment>");
        
        $tokens = $tokensQuery->whereNotNull('abilities')->get();
        $scopeCounts = [];
        
        foreach ($tokens as $token) {
            foreach ($token->abilities as $scope) {
                $scopeCounts[$scope] = ($scopeCounts[$scope] ?? 0) + 1;
            }
        }
        
        arsort($scopeCounts);
        
        foreach (array_slice($scopeCounts, 0, 10, true) as $scope => $count) {
            $this->line("  {$scope}: {$count} tokens");
        }
        $this->newLine();
    }

    private function exportStats($tokensQuery, string $format): void
    {
        $stats = [
            'generated_at' => now()->toISOString(),
            'total_tokens' => $tokensQuery->count(),
            'active_tokens' => $tokensQuery->where('is_active', true)->count(),
            'expired_tokens' => $tokensQuery->where('expires_at', '<', now())->count(),
        ];

        $filename = 'token_stats_' . date('Y-m-d_H-i-s') . '.' . $format;
        
        if ($format === 'json') {
            file_put_contents($filename, json_encode($stats, JSON_PRETTY_PRINT));
        } elseif ($format === 'csv') {
            $csv = "Metric,Value\n";
            foreach ($stats as $key => $value) {
                $csv .= "{$key},{$value}\n";
            }
            file_put_contents($filename, $csv);
        }
        
        $this->info("📁 Stats exported to: {$filename}");
    }

    private function percentage(int $part, int $total): string
    {
        if ($total === 0) return '0%';
        return round(($part / $total) * 100, 1) . '%';
    }
}
