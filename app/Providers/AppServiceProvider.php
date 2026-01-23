<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\PTK;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share badge count only to navigation views
        View::composer(['layouts.navigation', 'components.layouts.app', 'layouts.app'], function ($view) {
            $approvalQueueCount = 0;

            if (auth()->check() && auth()->user()->can('menu.queue')) {
                try {
                    $user = auth()->user();

                    // Gunakan logika yang sama dengan PTKController::queue
                    $base = PTK::visibleTo($user);

                    if ($user->hasRole('director')) {
                        // Stage 2: Waiting Director
                        $approvalQueueCount = $base->where('status', 'Waiting Director')
                            ->whereNull('approved_stage2_at')
                            ->count();
                    } else {
                        // Stage 1: Submitted (Kabag/Manager)
                        $approvalQueueCount = $base->where('status', 'Submitted')
                            ->whereNull('approved_stage1_at')
                            ->count();
                    }
                } catch (\Throwable $e) {
                    // Fail silent (e.g. migration not ready)
                    $approvalQueueCount = 0;
                }
            }

            $view->with('approvalQueueCount', $approvalQueueCount);
        });
    }
}
