<?php

namespace Mmartinjoo\JuniorArtisan;

use Mmartinjoo\JuniorArtisan\Commands\JuniorCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class JuniorArtisanServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('junior-artisan')
            ->hasConfigFile()
            ->hasCommand(JuniorCommand::class);
    }
}
