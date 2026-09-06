<?php

namespace App\Providers;

use App\Services\NotificacionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        View::composer('layouts.principal', function ($view) {
            if (Auth::check()) {
                $service = app(NotificacionService::class);
                $datosNotif = $service->obtenerParaUsuario(Auth::user());
                $view->with('notificacionesData', $datosNotif);
            }
        });
    }
}
