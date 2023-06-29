# GPTFaker
This package adds the possibility to use GPT to generate fake text. It completely integrates with Laravel and their factories.

## Installation
Install the package using composer:
```bash
composer require dejury/gptfaker
```

Add this to your `.env` file:
```php
OPENAI_API_KEY=<your-api-key>
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
### Multilanguage
Just type the prompt in the language you desire

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
