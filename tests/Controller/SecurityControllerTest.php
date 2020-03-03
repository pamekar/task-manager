<?php

namespace Tests\Controller;

use App\Entity\Task;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class SecurityControllerTest extends WebTestCase
{
    private $faker;

    public function __construct()
    {
        parent::__construct();
        $this->faker = \Faker\Factory::create();
    }

    public function testCanRegisterUser()
    {
        $data = [
            'username' => $this->faker->userName,
            'password' => 'password',
            'email' => $this->faker->email,
            'full_name' => $this->faker->name()
        ];
        $response = $this->requestCreateUser($data);
        $result = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(201, $response->getStatusCode());
        unset($data['password']);
        $this->assertArraySubset($data, $result['data']);
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

    public function testCanCreateClient()
    {
        $response = $this->requestCreateClient();
        $result = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertArrayHasKey('client_id', $result);
        $this->assertArrayHasKey('client_secret', $result);
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

    public function testCanCreateToken()
    {
        $userResponse = $this->requestCreateUser();
        $user = json_decode($userResponse->getBody()->getContents(), true);

        $clientResponse = $this->requestCreateClient();
        $client = json_decode($clientResponse->getBody()->getContents(), true);

        $response = $this->requestCreateToken($client, $user['data']);
        $result = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('expires_in', $result);
        $this->assertArrayHasKey('token_type', $result);
        $this->assertArrayHasKey('scope', $result);
        $this->assertArrayHasKey('refresh_token', $result);

    }

    /**
     * @param array $client
     * @param array $user
     * @return ResponseInterface
     */
    public function requestCreateToken(array $client, array $user)
    {
        $data = [
            'client_id' => $client['client_id'],
            'client_secret' => $client['client_secret'],
            'grant_type' => 'password',
            'username' => $user['username'],
            'password' => 'password'
        ];

        $client = new Client();
        $response = $client->request('POST', 'http://localhost:8000/oauth/v2/token',
            ['form_params' => $data]);
        return $response;
    }

    public function testCanViewUser()
    {
        $data = [
            'username' => $this->faker->userName,
            'password' => 'password',
            'email' => $this->faker->email,
            'full_name' => $this->faker->name()
        ];
        $userResponse = $this->requestCreateUser($data);
        $user = json_decode($userResponse->getBody()->getContents(), true);

        $clientResponse = $this->requestCreateClient();
        $client = json_decode($clientResponse->getBody()->getContents(), true);

        $tokenResponse = $this->requestCreateToken($client, $user['data']);
        $token = json_decode($tokenResponse->getBody()->getContents(), true);

        $client = new Client();

        $response = $client->request('GET', 'http://localhost:8000/api/user/', [
            'headers' => [
                'Authorization' => "Bearer " . $token['access_token'],
            ]]);
        $result = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(200, $response->getStatusCode());
        unset($data['password']);
        $this->assertArraySubset($data, $result);

    }
}