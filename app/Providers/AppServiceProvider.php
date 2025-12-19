<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
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
        View::composer('*', function ($view) {

            $count = 0;

            try {
                $user = Auth::user();

                if (!$user) {
                    $view->with('approvalQueueCount', 0);
                    return;
                }

                // ===============================
                // Stage 2 — Direktur
                // ===============================
                if ($user->hasRole('director')) {
                    $count = PTK::where('status', PTK::STATUS_WAITING_DIRECTOR)
                        ->whereNull('approved_stage2_at')
                        ->count();
                }

                // ===============================
                // Stage 1 — Kabag / Manager
                // ===============================
                elseif ($user->hasAnyRole([
                    'admin_qc_flange',
                    'admin_qc_fitting',
                    'admin_hr',
                    'admin_k3',
                ])) {
                    $count = PTK::where('status', PTK::STATUS_SUBMITTED)
                        ->whereNull('approved_stage1_at')
                        ->count();
                }

            } catch (\Throwable $e) {
                $count = 0;
            }

            $view->with('approvalQueueCount', $count);
        });
    }
}
