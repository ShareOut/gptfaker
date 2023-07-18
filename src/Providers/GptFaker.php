<?php

namespace Motivo\GptFaker\Providers;

use Faker\Generator;
use Tectalic\OpenAi\Client;
use Illuminate\Support\Str;
use Tectalic\OpenAi\Manager;
use Http\Discovery\Psr18Client;
use Tectalic\OpenAi\Authentication;
use Tectalic\OpenAi\Models\ChatCompletions\CreateRequest;

class GptFaker extends \Faker\Provider\Base
{
    protected Client $client;

    private string $locale;

    public function __construct(Generator $generator, string $locale)
    {
        parent::__construct($generator);

        $auth = new Authentication(config('fakergpt.openai_api_key'));
        $httpClient = new Psr18Client();
        $this->client = new Client($httpClient, $auth, Manager::BASE_URI);

        $this->locale = $locale;
    }

    public function gpt(string|array $prompt, mixed $fallback = null, bool $returnArray = false)
    {
        // If FakerGPT is not meant to be executed in this environment return the fallback
        if (! $this->runInEnvironment()) {
            if (is_callable($fallback)) {
                return $fallback();
            }

            return $fallback;
        }

        // Make sure the prompt is an array
        if (!is_array($prompt)) {
            $prompt = [$prompt];
        }

        // Tell ChatGPT to respond in another language
        foreach ($prompt as $index => $line) {
            $prompt[$index] = $line . " in language {$this->locale}";
        }

        // Build request
        $request = new \Tectalic\OpenAi\Models\Completions\CreateRequest([
            'model'       => config('fakergpt.model'),
            'max_tokens'  => config('fakergpt.max_tokens'),
            'temperature' => config('fakergpt.temperature'),
            'prompt'      => $prompt,
        ]);

        /** @var \Tectalic\OpenAi\Models\Completions\CreateResponse $response */
        $response = $this->client->completions()->create($request)->toModel();

        // Return the response
        if ($returnArray) {
            return $response->choices;
        } else {
            return (string)Str::of($response->choices[0]->text)->trim();
        }
    }

    protected function runInEnvironment(): bool
    {
        return in_array(config('app.env'), config('fakergpt.environments', []));
    }
}
