<?php

namespace App\Tests\Controller\Api;

use PHPUnit\Framework\TestCase;

class OrderControllerTest extends TestCase
{

    private $client;


    protected function setUp(): void
    {
        $this->client = new \GuzzleHttp\Client([
            'base_uri' => 'http://127.0.0.1:8001',
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ]);
    }


    public function testLogin()
    {
        $response = $this->client->post(
            '/api/login',
            [
                'json' => [
                    'username' => "customer1@gmail.com",
                    'password' => "customer"
                ],
            ]
        );

        $responseContent = json_decode($response->getBody()->getContents(), true);

        $JWToken = $responseContent['token'];


        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(1, count($responseContent));
        $this->assertArrayHasKey('token', $responseContent);


        return $JWToken;
    }


    /**
     * @depends testLogin
     */
    public function testListOrder($JWToken)
    {

        $response = $this->client->get(
            '/api/order/list', [
                'headers' => [
                    'Authorization' => "Bearer $JWToken"
                ]
            ]
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testLogin
     */
    public function testDetailOrder($JWToken)
    {

        $response = $this->client->get(
            '/api/order/detail/1', [
                'headers' => [
                    'Authorization' => "Bearer $JWToken"
                ]
            ]
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testLogin
     */
    public function testAddOrder($JWToken)
    {

        $response = $this->client->post(
            '/api/order/add', [
                'headers' => [
                    'Authorization' => "Bearer $JWToken",
                ],
                'form_params' => [
                    'orderCode' => 'CodeTest',
                    'quantity' => '10,7,5',
                    'address' => 'İstanbul',
                    'shippingDate' => '2022-10-07',
                    'productId' => '1,3,4',
                ]
            ]
        );

        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * @depends testLogin
     */
    public function testUpdateOrder($JWToken)
    {

        $response = $this->client->put(
            '/api/order/update/1', [
                'headers' => [
                    'Authorization' => "Bearer $JWToken",
                ],
                'form_params' => [
                    'quantity' => 17,
                    'address' => 'Tokyo',
                    'productId' => 1,
                ]
            ]
        );

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testLogin
     */
    public function testDeleteOrder($JWToken)
    {

        $response = $this->client->delete(
            '/api/order/delete/1', [
                'headers' => [
                    'Authorization' => "Bearer $JWToken",
                ]
            ]
        );

        $this->assertEquals(204, $response->getStatusCode());
    }


}