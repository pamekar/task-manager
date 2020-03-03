<?php

namespace Tests\Controller;

use App\Entity\Task;

use App\Tests\TestCase;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;

class TaskControllerTest extends TestCase
{
    private $faker;

    public function __construct()
    {
        parent::__construct();
        $this->faker = \Faker\Factory::create();
    }

    /**
     * @throws \Exception
     */
    public function testCanCreateTask()
    {
        $data = [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'start_at' => $this->faker->dateTimeBetween('-2 weeks', 'now')->format('Y-m-d H:i:s'),
            'end_at' => $this->faker->dateTimeBetween('now', '+2 weeks')->format('Y-m-d H:i:s'),
            'status' => 'pending'
        ];

        $response = $this->requestCreateTask($data);
        $responseData = json_decode($response->getBody()->getContents(), true);
        $result = $responseData['data'];
        $result['start_at'] = isset($result['start_at']) ? (new \DateTime($result['start_at']))->format('Y-m-d H:i:s') : null;
        $result['end_at'] = isset($result['end_at']) ? (new \DateTime($result['end_at']))->format('Y-m-d H:i:s') : null;

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertArraySubset($data, $result);
    }

    public function testCanShowTask()
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

        $taskResponse = $this->requestCreateTask(null, $token);
        $task = json_decode($taskResponse->getBody()->getContents(), true);

        $client = new Client();
        $response = $client->request('GET', 'http://localhost:8000/api/tasks/' . $task['data']['id'], [
            'headers' => [
                'Authorization' => "Bearer " . $token['access_token']
            ]]);
        $result = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertArrayHasKey('start_at', $result);
        $this->assertArrayHasKey('end_at', $result);
        $this->assertArraySubset($user['data'], $result['author']);

    }

    public function testCanUpdateTask()
    {
        $userData = [
            'username' => $this->faker->userName,
            'password' => 'password',
            'email' => $this->faker->email,
            'full_name' => $this->faker->name()
        ];
        $userResponse = $this->requestCreateUser($userData);
        $user = json_decode($userResponse->getBody()->getContents(), true);

        $tokenResponse = $this->requestCreateToken(null, $user);
        $token = json_decode($tokenResponse->getBody()->getContents(), true);

        $taskResponse = $this->requestCreateTask(null, $token);
        $task = json_decode($taskResponse->getBody()->getContents(), true);

        $client = new Client();
        $data = [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'status' => $this->faker->randomElement(['active', 'completed'])
        ];
        $response = $client->request('PUT', 'http://localhost:8000/api/tasks/' . $task['data']['id'], [
            'headers' => [
                'Authorization' => "Bearer " . $token['access_token']
            ], 'json' => $data]);
        $result = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArraySubset($user['data'], $result['data']['author']);
        $this->assertArraySubset($data, $result['data']);

        // Double check if task was persisted to DB
        $client = new Client();
        $response = $client->request('GET', 'http://localhost:8000/api/tasks/' . $task['data']['id'], [
            'headers' => [
                'Authorization' => "Bearer " . $token['access_token']
            ]]);
        $result = json_decode($response->getBody()->getContents(), true);
        $this->assertArraySubset($data, $result);
    }

    public function testCanListTasks()
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
        $tasks = [];
        foreach (range(0, 4) as $index) {
            $tasks[] = $this->requestCreateTask(null, $token);
        }

        $client = new Client();
        $response = $client->request('GET', 'http://localhost:8000/api/tasks/', [
            'headers' => [
                'Authorization' => "Bearer " . $token['access_token']
            ]]);
        $results = json_decode($response->getBody()->getContents(), true);

        $this->assertEquals(200, $response->getStatusCode());

        foreach ($results as $result) {
            $this->assertArrayHasKey('title', $result);
            $this->assertArrayHasKey('description', $result);
            $this->assertArrayHasKey('start_at', $result);
            $this->assertArrayHasKey('end_at', $result);

            // Confirm that this is user's task
            $this->assertArraySubset($user['data'], $result['author']);
        }
    }

    public function testCanDeleteTask()
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

        $taskResponse = $this->requestCreateTask(null, $token);
        $task = json_decode($taskResponse->getBody()->getContents(), true);

        $client = new Client();
        $response = $client->request('DELETE', 'http://localhost:8000/api/tasks/' . $task['data']['id'], [
            'headers' => [
                'Authorization' => "Bearer " . $token['access_token']
            ]]);

        $this->assertEquals(204, $response->getStatusCode());
    }

    /**
     * @param array|null $data
     * @param array|null $token
     * @return ResponseInterface
     */
    public function requestCreateTask(array $data = null, array $token = null)
    {
        $data = $data ?? [
                'title' => $this->faker->sentence,
                'description' => $this->faker->paragraph,
                'start_at' => $this->faker->dateTimeBetween('-2 weeks', 'now')->format('Y-m-d H:i:s'),
                'end_at' => $this->faker->dateTimeBetween('now', '+2 weeks')->format('Y-m-d H:i:s'),
                'status' => 'pending'
            ];

        $tokenResponse = $this->requestCreateToken();
        $token = $token ?? json_decode($tokenResponse->getBody()->getContents(), true);

        $client = new Client();

        $response = $client->request('POST', 'http://localhost:8000/api/tasks/', [
            'headers' => [
                'Authorization' => "Bearer " . $token['access_token'],
            ], 'json' => $data]);
        return $response;
    }

}