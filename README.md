# GPTFaker

This package adds the possibility to use GPT to generate fake text. It completely integrates with Laravel and their factories.

## Installation

Install the package using composer:

```bash
composer require dejury/gptfaker --dev
```

Add this to your `.env` file:

```php
FAKERGPT_OPENAI_API_KEY=<your-api-key>
```

## Config

The config file can be published with the following command:

```
php artisan vendor:publish --provider="Motivo\GptFaker\GptFakerServiceProvider"
```

## Usage

Use it in your Laravel Factory:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class PageFactory extends Factory
{

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Page::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name'    => $this->faker->unique()->gpt('Create an title for the page'),
            'slug'    => $this->faker->slug,
            'content' => $this->faker->gpt('Create a short paragraph for the page'),
            'visible' => true,
        ];
    }
}
```

### Fallback

A fallback can be supplied in case ChatGPT is unable to create a response, the api key is not set or if FakerGPT should not be executed for the given environment.
By default this value is set to `null` so it is highly recommended to provide a valid fallback.

```php
$this->faker->gpt(
    'Create a short paragraph for the page', 
    $this->faker->paragraph
),
```

### Multi-language

Prompts are accepted in every language ChatGPT understands. The results will be translated to your configured faker locale.

### Multiple prompts

It is possible to give an array with prompts:

```php
$choices = $this->faker->unique()->gpt(
    prompt: [
        "Maak een functietitel binnen de zorg aan",
        "Maak een korte titel over het werken in de zorg",
        "Maak een korte quote over het werken in de zorg",
        "Maak een korte alinea over het werken in de zorg",
    ],
    returnArray: true
);

return [
    'overview_pretitle' => Str::of($choices[0]?->text)->trim(),
];

```

### Environments

You can configure for which environments FakerGPT should be executed. By default this is only for `local` environment.
This can be changed by publishing the config file as described above and overwriting the `environment` config.

### Trimming quotes

ChatGPT has a tendency to start end and sentences with quotes, especially when requesting titles, this is often undesirable. 
These quotes can be trimmed as follows:

```php
$this->faker->unique()->gpt(
    prompt: "Maak een functietitel binnen de zorg aan",
    trimQuotes: true
);
```

### Performance mode

Performance mode can be enabled to drastically improve the speed of your factories/seeders. 
This executes the same prompt several times at once instead of making multiple requests and caches the results. Each result will only be used once. When the cache runs empty a new batch will be fetched.
This is an opt-in feature, as it may expend tokens to cache results that might never be used, and as a result it becomes more expensive to use.

This feature can be enabled by adding the following line to your `.env`

```
FAKERGPT_PERFORMANCE_MODE=true
```

### Persistent Cache

Often it is enough to generate fake data once rather than for every time the seeders are being executed, both for monetary and execution speed reasons.
For these situations persistent cache can be enabled. This will cache the prompts and their results in `.fakergpt_cache.php`.

```
FAKERGPT_PERSISTENT_CACHE=true
```

Persistent cache will have the side effect (and potential benefit) that generated content is predictable.

Unlike most cache files it's highly recommended to commit the `.fakergpt_cache.php` when this feature is enabled.
