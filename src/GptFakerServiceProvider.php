<?php

namespace Motivo\GptFaker;

use Faker\Factory;
use Faker\Generator;
use Illuminate\Support\ServiceProvider;
use Motivo\GptFaker\Providers\GptFaker;

class GptFakerServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/fakergpt.php',
            'fakergpt',
        );

        if (function_exists('fake') && class_exists(\Faker\Factory::class)) {
            fake()->addProvider(new GptFaker(fake(), $this->app->getLocale()));
        }

        $this->app->singleton(Generator::class, function ($app) {
            $locale = $app['config']->get('app.faker_locale', 'en_US');

            $faker = Factory::create($locale);
            $faker->addProvider(new GptFaker($faker, $locale));
            return $faker;
        });
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/config/fakergpt.php' => config_path('fakergpt.php'),
        ]);
    }
}
