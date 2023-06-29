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
        $this->app->singleton(Generator::class, function ($app) {
            $faker = Factory::create($app['config']->get('app.faker_locale', 'en_US'));
            $faker->addProvider(new GptFaker($faker));
            return $faker;
        });
    }
}
