<?php

namespace Motivo\GptFaker\Providers;

use Faker\Generator;
use Tectalic\OpenAi\Client;
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

    public function gpt(string $prompt): string
    {
        $request = new CreateRequest([
            'model'    => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        /** @var \Tectalic\OpenAi\Models\ChatCompletions\CreateResponse $response */
        $response = $this->client->chatCompletions()->create($request)->toModel();

        return $response->choices[0]->message->content;


    }
}
