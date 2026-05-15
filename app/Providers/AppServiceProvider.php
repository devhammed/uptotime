<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models;
use Carbon\CarbonImmutable;
use Dedoc\Scramble\Scramble;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Database\Eloquent\Relations\Relation;

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
        $this->configureDefaults();

        $this->configurePackages();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Model::unguard();

        Model::automaticallyEagerLoadRelationships();

        Model::shouldBeStrict(! app()->isProduction());

        DB::prohibitDestructiveCommands(app()->isProduction());

        Date::use(CarbonImmutable::class);

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(8)
                ->max(72)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );

        Relation::enforceMorphMap([
            'monitor' => Models\Monitor::class,
            'monitor_check' => Models\MonitorCheck::class,
            'personal_access_token' => Models\PersonalAccessToken::class,
            'user' => Models\User::class,
        ]);
    }

    /**
     * Configure application packages.
     */
    protected function configurePackages(): void
    {
        Sanctum::usePersonalAccessTokenModel(Models\PersonalAccessToken::class);

        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('bearer'),
                );
            });
    }
}
