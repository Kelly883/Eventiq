<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Factory;

class FirebaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Messaging::class, function () {
            $credentialsPath = config('firebase.credentials_path');

            if (! $credentialsPath || ! is_file($credentialsPath)) {
                Log::warning(
                    'FirebaseServiceProvider: credentials file not found at ' . $credentialsPath .
                    ' - push notifications will not be sent until this is configured.'
                );

                // Return null rather than throw, so app boot doesn't fail
                // just because Firebase isn't configured yet in this
                // environment. Consumers (PushNotificationService) check
                // for this and no-op gracefully.
                return null;
            }

            $factory = (new Factory)->withServiceAccount($credentialsPath);

            return $factory->createMessaging();
        });
    }
}
