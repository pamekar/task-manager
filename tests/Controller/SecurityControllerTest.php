<?php

namespace App\Tests\Controller;

use App\Entity\Task;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Tests\TestCase;

class SecurityControllerTest extends TestCase
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


    public function testCanCreateClient()
    {
        $response = $this->requestCreateClient();
        $result = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertArrayHasKey('client_id', $result);
        $this->assertArrayHasKey('client_secret', $result);
    }

    public function testCanCreateToken()
    {
        $response = $this->requestCreateToken();
        $result = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('expires_in', $result);
        $this->assertArrayHasKey('token_type', $result);
        $this->assertArrayHasKey('scope', $result);
        $this->assertArrayHasKey('refresh_token', $result);

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

        $tokenResponse = $this->requestCreateToken(null, $user);
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