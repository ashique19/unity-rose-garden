<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FcmPushService;
use Illuminate\Console\Command;

class PushTestCommand extends Command
{
    protected $signature = 'push:test {user : User ID} {--title=Test notification} {--body=Unity Rose Garden push test}';

    protected $description = 'Send a test FCM push notification to a user device tokens';

    public function handle(FcmPushService $fcm): int
    {
        $userId = (int) $this->argument('user');
        $user = User::query()->find($userId);

        if (! $user) {
            $this->error("User {$userId} not found.");

            return self::FAILURE;
        }

        if (! $fcm->isConfigured()) {
            $this->warn('FCM is not configured (FCM_PROJECT_ID / service account). Message will be skipped.');
        }

        $sent = $fcm->sendToUser(
            $user,
            (string) $this->option('title'),
            (string) $this->option('body'),
            ['source' => 'push:test']
        );

        $this->info("Sent {$sent} notification(s) for user {$user->id} ({$user->name}).");

        return self::SUCCESS;
    }
}
