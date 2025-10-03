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
       
       View::composer('*', function ($view) {
        try {
            $count = PTK::where('status', '!=', 'Completed')
                ->whereNull('approver_id')
                ->count();
        } catch (\Throwable $e) {
            $count = 0;
        }
        $view->with('queueCount', $count);
    }); 
    }
}
