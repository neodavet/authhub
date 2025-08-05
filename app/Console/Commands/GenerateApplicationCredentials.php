<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateApplicationCredentials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create 
                            {name : The application name}
                            {--user= : User email or ID to assign the application to}
                            {--description= : Application description}
                            {--scopes=read : Comma-separated list of allowed scopes}
                            {--rate-limit=1000 : Rate limit per hour}
                            {--callback-urls= : Comma-separated list of callback URLs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new application with generated credentials';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $userIdentifier = $this->option('user');
        $description = $this->option('description');
        $scopes = $this->option('scopes');
        $rateLimit = (int) $this->option('rate-limit');
        $callbackUrls = $this->option('callback-urls');

        $this->info("🚀 Creating new application: {$name}");

        // Find or prompt for user
        $user = $this->resolveUser($userIdentifier);
        
        if (!$user) {
            $this->error("❌ Could not find or create user.");
            return Command::FAILURE;
        }

        // Parse scopes
        $allowedScopes = array_map('trim', explode(',', $scopes));
        
        // Parse callback URLs
        $parsedCallbackUrls = $callbackUrls 
            ? array_map('trim', explode(',', $callbackUrls))
            : null;

        // Validate callback URLs
        if ($parsedCallbackUrls) {
            foreach ($parsedCallbackUrls as $url) {
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    $this->error("❌ Invalid callback URL: {$url}");
                    return Command::FAILURE;
                }
            }
        }

        try {
            // Create the application
            $application = $user->applications()->create([
                'name' => $name,
                'description' => $description,
                'allowed_scopes' => $allowedScopes,
                'rate_limit' => $rateLimit,
                'callback_urls' => $parsedCallbackUrls,
                'is_active' => true,
            ]);

            $this->info("✅ Application created successfully!");
            
            // Display the credentials in a nice format
            $this->newLine();
            $this->line("📋 <comment>Application Details:</comment>");
            $this->table(['Field', 'Value'], [
                ['ID', $application->id],
                ['Name', $application->name],
                ['Description', $application->description ?: 'N/A'],
                ['Owner', $user->name . ' (' . $user->email . ')'],
                ['Client ID', $application->client_id],
                ['Client Secret', $application->client_secret],
                ['Allowed Scopes', implode(', ', $application->allowed_scopes)],
                ['Rate Limit', $application->rate_limit . ' requests/hour'],
                ['Callback URLs', $parsedCallbackUrls ? implode(', ', $parsedCallbackUrls) : 'None'],
                ['Status', $application->is_active ? 'Active' : 'Inactive'],
                ['Created', $application->created_at->format('Y-m-d H:i:s')],
            ]);

            $this->newLine();
            $this->warn("🔒 <comment>IMPORTANT: Store these credentials securely!</comment>");
            $this->warn("The client secret cannot be retrieved again after this command.");
            
            // Optionally save to file
            if ($this->confirm('Would you like to save these credentials to a file?')) {
                $this->saveCredentialsToFile($application, $user);
            }

            // Show example usage
            $this->newLine();
            $this->info("🔧 <comment>Example API Usage:</comment>");
            $this->line("curl -X POST " . config('app.url') . "/api/oauth/token \\");
            $this->line("  -H 'Content-Type: application/json' \\");
            $this->line("  -d '{");
            $this->line("    \"client_id\": \"" . $application->client_id . "\",");
            $this->line("    \"client_secret\": \"" . $application->client_secret . "\",");
            $this->line("    \"grant_type\": \"client_credentials\",");
            $this->line("    \"scope\": \"" . implode(' ', $allowedScopes) . "\"");
            $this->line("  }'");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Failed to create application: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Resolve user from identifier or prompt for creation
     */
    private function resolveUser(?string $identifier): ?User
    {
        if ($identifier) {
            // Try to find by email first
            $user = User::where('email', $identifier)->first();
            
            if (!$user && is_numeric($identifier)) {
                // Try to find by ID
                $user = User::find($identifier);
            }
            
            if ($user) {
                $this->info("👤 Found user: {$user->name} ({$user->email})");
                return $user;
            }
            
            $this->error("❌ User not found: {$identifier}");
            return null;
        }

        // No user specified, prompt for creation or selection
        $this->info("👤 No user specified. Let's find or create one.");
        
        $email = $this->ask('Enter user email');
        
        $user = User::where('email', $email)->first();
        
        if ($user) {
            $this->info("👤 Found existing user: {$user->name}");
            return $user;
        }
        
        if ($this->confirm("User not found. Create new user with email {$email}?")) {
            $name = $this->ask('Enter user name');
            $password = $this->secret('Enter password');
            
            return User::create([
                'name' => $name,
                'email' => $email,
                'password' => bcrypt($password),
            ]);
        }
        
        return null;
    }

    /**
     * Save credentials to file
     */
    private function saveCredentialsToFile(Application $application, User $user): void
    {
        $filename = 'app_credentials_' . Str::slug($application->name) . '_' . date('Y-m-d_H-i-s') . '.json';
        
        $credentials = [
            'application' => [
                'id' => $application->id,
                'name' => $application->name,
                'description' => $application->description,
                'client_id' => $application->client_id,
                'client_secret' => $application->client_secret,
                'allowed_scopes' => $application->allowed_scopes,
                'rate_limit' => $application->rate_limit,
                'callback_urls' => $application->callback_urls,
                'created_at' => $application->created_at->toISOString(),
            ],
            'owner' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'generated_at' => now()->toISOString(),
        ];

        file_put_contents($filename, json_encode($credentials, JSON_PRETTY_PRINT));
        
        $this->info("💾 Credentials saved to: {$filename}");
        $this->warn("🔒 Remember to store this file securely and delete it after use!");
    }
}
