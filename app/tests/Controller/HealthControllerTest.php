<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HealthControllerTest extends WebTestCase
{
    public function testHealthEndpoint(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $content = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($content);
        $this->assertArrayHasKey('status', $content);
        $this->assertArrayHasKey('timestamp', $content);
        $this->assertArrayHasKey('version', $content);
        $this->assertEquals('OK', $content['status']);
        $this->assertEquals('1.0.0', $content['version']);
        $this->assertNotEmpty($content['timestamp']);
    }
}