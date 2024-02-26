<?php

namespace Mmartinjoo\Junior;

use Mmartinjoo\Junior\Commands\JuniorCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class JuniorServiceProvider extends PackageServiceProvider
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
