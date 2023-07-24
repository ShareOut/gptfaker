<?php

namespace Motivo\GptFaker\Providers;

use Exception;
use Faker\Generator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Stringable;
use Tectalic\OpenAi\Client;
use Illuminate\Support\Str;
use Tectalic\OpenAi\Manager;
use Http\Discovery\Psr18Client;
use Tectalic\OpenAi\Authentication;
use Tectalic\OpenAi\Models\Completions\CreateRequest;

class GptFaker extends \Faker\Provider\Base
{
    protected ?Client $client = null;

    private string $locale;

    protected static array $cachedPrompts = [];

    public const CACHE_AMOUNT = 15;

    public function __construct(Generator $generator, string $locale)
    {
        $apiKey = config('fakergpt.openai_api_key');

        if ($apiKey) {
            parent::__construct($generator);

            $auth = new Authentication($apiKey);
            $httpClient = new Psr18Client();
            $this->client = new Client($httpClient, $auth, Manager::BASE_URI);

            $this->locale = $locale;
        }
    }

    public function gpt(string|array $prompt, mixed $fallback = null, bool $returnArray = false, bool $trimQuotes = false)
    {
        // If FakerGPT is not meant to be executed in this environment
        // or if api key is missing return the fallback
        if (! $this->client || ! $this->runInEnvironment()) {
            if (is_callable($fallback)) {
                return $fallback();
            }

            return $fallback;
        }

        // Make sure the prompt is an array
        if (!is_array($prompt)) {
            $prompt = [$prompt];
        }

        $response = [];
        try {
            foreach ($prompt as $item) {
                $response[] = $this->getGptResponse($item, $trimQuotes);
            }
        } catch (Exception $exception) {
            Log::warning('FakerGTP call failed, returning fallback', [
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'message' => $exception->getMessage(),
                'trace' => $exception->getTrace(),
            ]);

            return $fallback;
        }

        if (! $returnArray) {
            return $response[0];
        }

        return $response;
    }

    protected function getGptResponse(string $prompt, bool $trimQuotes): string
    {
        // If performance mode is enabled responses get cached, attempt to fetch from cache before making new request
        if ($response = $this->getCachedResponse($prompt)) {
            return $response;
        }

        // Tell ChatGPT to respond in another language
        $localizedPrompt = $prompt . " in language {$this->locale}";

        $gptPrompts = [$localizedPrompt];

        // If performance mode is enabled repeat the same prompts 10 times
        if (config('fakergpt.performance_mode')) {
            for ($i = 0; $i < static::CACHE_AMOUNT - 1; $i++) {
                $gptPrompts[] = $localizedPrompt;
            }
        }

        // Build request
        $request = new CreateRequest([
            'model'       => config('fakergpt.model'),
            'max_tokens'  => (int) config('fakergpt.max_tokens'),
            'temperature' => (float) config('fakergpt.temperature'),
            'prompt'      => $gptPrompts,
        ]);

        /** @var \Tectalic\OpenAi\Models\Completions\CreateResponse $response */
        $response = $this->client->completions()->create($request)->toModel();

        if (config('fakergpt.performance_mode')) {
            $values = $response->choices;

            // Trim all values
            foreach ($values as $index => $value) {
                $values[$index] = $this->trimText($response->choices[$index]->text, $trimQuotes);
            }

            // Store the cached values
            static::$cachedPrompts[$prompt] = $values;

            // Now cached values exists, get the cached response
            return $this->getCachedResponse($prompt);
        } else {
            // Return the response as string
            return $this->trimText($response->choices[0]->text, $trimQuotes);
        }
    }

    protected function getCachedResponse(string $prompt): string|array|null
    {
        if (array_key_exists($prompt, static::$cachedPrompts) && is_array(static::$cachedPrompts[$prompt]) && ! empty(static::$cachedPrompts[$prompt])) {
            $value = array_pop(static::$cachedPrompts[$prompt]);

            if (empty(static::$cachedPrompts[$prompt])) {
                unset(static::$cachedPrompts[$prompt]);
            }

            return $value;
        }

        return null;
    }

    protected function runInEnvironment(): bool
    {
        return in_array(config('app.env'), config('fakergpt.environments', []));
    }

    protected function trimText($text, bool $trimQuotes): string
    {
        $value = Str::of($text)
            ->trim();

        if ($trimQuotes) {
            $value = $value->whenStartsWith(['\'', '"'], fn (Stringable $string) => $string->substr(1))
                ->whenEndsWith(['\'', '"'], fn (Stringable $string) => $string->substr(0, -1));
        }

        return (string) $value;
    }
}
