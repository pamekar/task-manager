<?php

namespace App\Tests;

use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Psr\Http\Message\ResponseInterface;

class TestCase extends WebTestCase
{
    private $faker;

    public function __construct()
    {
        parent::__construct();
        $this->faker = \Faker\Factory::create();
    }

    /**
     * @param array $client
     * @param array $user
     * @return ResponseInterface
     */
    public function requestCreateToken(array $client = null, array $user = null)
    {
        $userResponse = $this->requestCreateUser();
        $user = $user ?? json_decode($userResponse->getBody()->getContents(), true);

        $clientResponse = $this->requestCreateClient();
        $client = $client ?? json_decode($clientResponse->getBody()->getContents(), true);

        $data = [
            'client_id' => $client['client_id'],
            'client_secret' => $client['client_secret'],
            'grant_type' => 'password',
            'username' => $user['data']['username'],
            'password' => 'password'
        ];

        $client = new Client();
        $response = $client->request('POST', 'http://localhost:8000/oauth/v2/token',
            ['form_params' => $data]);
        return $response;
    }

    /**
     * @param null $data
     * @return ResponseInterface
     */
    public function requestCreateUser($data = null)
    {
        $data = $data ?? [
                'username' => $this->faker->userName,
                'password' => 'password',
                'email' => $this->faker->email,
                'full_name' => $this->faker->name()
            ];
        $client = new Client();
        $response = $client->request('POST', 'http://localhost:8000/auth/register',
            ['json' => $data]);

        return $response;
    }

    /**
     * @return ResponseInterface
     */
    public function requestCreateClient()
    {
        $data = ['redirect-uri' => $this->faker->url, 'grant-type' => 'password'];
        $client = new Client();
        $response = $client->request('POST', 'http://localhost:8000/auth/createClient',
            ['json' => $data]);

        return $response;
    }

}
