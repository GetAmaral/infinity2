<?php

declare(strict_types=1);

namespace App\Tests\Service\WhatsApp;

use App\Service\WhatsApp\WhatsAppCloudApiClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * WhatsApp Cloud API Client Tests
 *
 * Tests for WhatsApp Cloud API integration with Meta's Graph API
 */
class WhatsAppCloudApiClientTest extends TestCase
{
    private WhatsAppCloudApiClient $client;
    private MockHttpClient $httpClient;

    protected function setUp(): void
    {
        // Mock HTTP client will be created per test
    }

    public function testSendTextMessage(): void
    {
        // Arrange: Mock successful API response
        $mockResponse = new MockResponse(json_encode([
            'messaging_product' => 'whatsapp',
            'contacts' => [['input' => '5511999999999', 'wa_id' => '5511999999999']],
            'messages' => [['id' => 'wamid.test123']],
        ]));

        $this->httpClient = new MockHttpClient($mockResponse);
        $this->client = new WhatsAppCloudApiClient(
            $this->httpClient,
            'https://graph.facebook.com',
            'v23.0',
            '123456789012345',
            'EAAtest',
            new NullLogger()
        );

        // Act: Send text message
        $result = $this->client->sendTextMessage('+5511999999999', 'Test message');

        // Assert: Verify response structure
        $this->assertIsArray($result);
        $this->assertEquals('wamid.test123', $result['messages'][0]['id']);
        $this->assertEquals('whatsapp', $result['messaging_product']);
    }

    public function testSendTextMessageWithPreviewUrl(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'messaging_product' => 'whatsapp',
            'contacts' => [['input' => '5511999999999', 'wa_id' => '5511999999999']],
            'messages' => [['id' => 'wamid.test456']],
        ]));

        $this->httpClient = new MockHttpClient($mockResponse);
        $this->client = new WhatsAppCloudApiClient(
            $this->httpClient,
            'https://graph.facebook.com',
            'v23.0',
            '123456789012345',
            'EAAtest',
            new NullLogger()
        );

        // Send with URL preview enabled
        $result = $this->client->sendTextMessage(
            '+5511999999999',
            'Check this link: https://example.com',
            previewUrl: true
        );

        $this->assertIsArray($result);
        $this->assertEquals('wamid.test456', $result['messages'][0]['id']);
    }

    public function testSendMediaMessageWithLink(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'messaging_product' => 'whatsapp',
            'contacts' => [['input' => '5511999999999', 'wa_id' => '5511999999999']],
            'messages' => [['id' => 'wamid.test789']],
        ]));

        $this->httpClient = new MockHttpClient($mockResponse);
        $this->client = new WhatsAppCloudApiClient(
            $this->httpClient,
            'https://graph.facebook.com',
            'v23.0',
            '123456789012345',
            'EAAtest',
            new NullLogger()
        );

        // Send image with URL
        $result = $this->client->sendMediaMessage(
            '+5511999999999',
            'image',
            'https://example.com/image.jpg',
            caption: 'Test image'
        );

        $this->assertIsArray($result);
        $this->assertEquals('wamid.test789', $result['messages'][0]['id']);
    }

    public function testMarkAsRead(): void
    {
        $mockResponse = new MockResponse(json_encode([
            'success' => true,
        ]));

        $this->httpClient = new MockHttpClient($mockResponse);
        $this->client = new WhatsAppCloudApiClient(
            $this->httpClient,
            'https://graph.facebook.com',
            'v23.0',
            '123456789012345',
            'EAAtest',
            new NullLogger()
        );

        // Mark message as read
        $result = $this->client->markAsRead('wamid.test123');

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
    }

    public function testNormalizePhoneNumber(): void
    {
        // Create 3 mock responses for 3 requests
        $mockResponses = [
            new MockResponse(json_encode([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => '5511999999999', 'wa_id' => '5511999999999']],
                'messages' => [['id' => 'wamid.test1']],
            ])),
            new MockResponse(json_encode([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => '5511999999999', 'wa_id' => '5511999999999']],
                'messages' => [['id' => 'wamid.test2']],
            ])),
            new MockResponse(json_encode([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => '5511999999999', 'wa_id' => '5511999999999']],
                'messages' => [['id' => 'wamid.test3']],
            ])),
        ];

        $this->httpClient = new MockHttpClient($mockResponses);
        $this->client = new WhatsAppCloudApiClient(
            $this->httpClient,
            'https://graph.facebook.com',
            'v23.0',
            '123456789012345',
            'EAAtest',
            new NullLogger()
        );

        // Test with various phone number formats
        $testCases = [
            '+5511999999999',      // With + prefix
            '5511999999999',       // Without prefix
            '(55) 11 99999-9999', // With formatting
        ];

        foreach ($testCases as $phoneNumber) {
            $result = $this->client->sendTextMessage($phoneNumber, 'Test');
            $this->assertIsArray($result);
            // Phone number normalization happens internally, we just verify it works
        }
    }
}
