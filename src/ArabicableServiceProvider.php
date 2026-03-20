<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable;

use GoodMaven\Anvil\Fixes\RegisterLaravelBoosterJsonSchemaFix;
use GoodMaven\Arabicable\Commands\ArabicableCommand;
use GoodMaven\Arabicable\Commands\ArabicableCompileDataCommand;
use GoodMaven\Arabicable\Commands\ArabicableSeedCommand;
use GoodMaven\Arabicable\Concerns\HasArabicableMigrationBlueprintMacros;
use GoodMaven\Arabicable\Support\Config\ArabicableConfigValidator;
use GoodMaven\Arabicable\Support\Text\ArabicStemmer;
use GoodMaven\Arabicable\Support\Text\ArabicStopWords;
use GoodMaven\Arabicable\Support\Text\ArabicVocalizations;
use GoodMaven\Arabicable\Support\Text\ArabicWordVariants;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ArabicableServiceProvider extends PackageServiceProvider
{
    use HasArabicableMigrationBlueprintMacros;

    public function configurePackage(Package $package): void
    {
        $package
            ->name('arabicable')
            ->hasConfigFile()
            ->hasMigrations([
                'create_common_arabic_texts_table',
                'create_arabic_stop_words_table',
                'create_quran_index_tables',
                'create_quran_explanations_tables',
            ])
            ->hasAssets()
            ->hasViews()
            ->hasViewComponents('goodm4ven', 'arabicable')
            ->hasCommands([
                ArabicableCommand::class,
                ArabicableCompileDataCommand::class,
                ArabicableSeedCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        RegisterLaravelBoosterJsonSchemaFix::activate();
        ArabicableConfigValidator::validate();

        $this->app->singleton(Arabicable::class, static fn (): Arabicable => new Arabicable);
        $this->app->singleton(CamelTools::class, static fn (): CamelTools => new CamelTools);
        $this->app->singleton(Arabic::class, static fn (): Arabic => new Arabic);
        $this->app->singleton(ArabicFilter::class, static fn (): ArabicFilter => new ArabicFilter);
        $this->app->singleton(ArabicStemmer::class, static fn (): ArabicStemmer => new ArabicStemmer);
        $this->app->singleton(
            ArabicWordVariants::class,
            static fn ($app): ArabicWordVariants => new ArabicWordVariants(
                $app->make(ArabicStemmer::class),
                $app->make(ArabicStopWords::class),
            ),
        );
        $this->app->singleton(ArabicStopWords::class, static fn (): ArabicStopWords => new ArabicStopWords);
        $this->app->singleton(ArabicVocalizations::class, static fn (): ArabicVocalizations => new ArabicVocalizations);
    }

    public function packageBooted(): void
    {
        $this->arabicableMigrationBlueprintMacros();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/raw-data' => resource_path('raw-data'),
            ], 'arabicable-raw-data');
        }

        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'goodm4ven');
    }
}
