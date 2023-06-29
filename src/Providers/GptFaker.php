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

    public function __construct(Generator $generator)
    {
        parent::__construct($generator);

        $auth = new Authentication(getenv('OPENAI_API_KEY'));
        $httpClient = new Psr18Client();
        $this->client = new Client($httpClient, $auth, Manager::BASE_URI);
    }

    public function gpt(string|array $prompt, bool $returnArray = false)
    {
        if (!is_array($prompt)) {
            $prompt = [$prompt];
        }

        $request = new \Tectalic\OpenAi\Models\Completions\CreateRequest([
            'model'       => 'text-davinci-003',
            'prompt'      => $prompt,
            'max_tokens'  => 256,
            'temperature' => 0.7,
        ]);


        /** @var \Tectalic\OpenAi\Models\Completions\CreateResponse $response */
        $response = $this->client->completions()->create($request)->toModel();


        if ($returnArray) {
            return $response->choices;
        } else {
            return (string)Str::of($response->choices[0]->text)->trim();
        }
    }
}
