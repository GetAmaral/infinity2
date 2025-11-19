<?php

declare(strict_types=1);

namespace App\Tests\Service\WhatsApp;

use App\Service\WhatsApp\WhatsAppWebhookParser;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * WhatsApp Webhook Parser Tests
 *
 * Tests for parsing and validating Meta's webhook payloads
 */
class WhatsAppWebhookParserTest extends TestCase
{
    private WhatsAppWebhookParser $parser;
    private string $appSecret = 'test_secret_key';

    protected function setUp(): void
    {
        $this->parser = new WhatsAppWebhookParser($this->appSecret, new NullLogger());
    }

    public function testParseTextMessage(): void
    {
        // Arrange: Typical Meta webhook payload for text message
        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => '123456789',
                'changes' => [[
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => [
                            'display_phone_number' => '15550000000',
                            'phone_number_id' => '123456789012345',
                        ],
                        'contacts' => [[
                            'profile' => ['name' => 'John Doe'],
                            'wa_id' => '5511999999999',
                        ]],
                        'messages' => [[
                            'from' => '5511999999999',
                            'id' => 'wamid.abc123',
                            'timestamp' => '1234567890',
                            'type' => 'text',
                            'text' => ['body' => 'Hello Luminai'],
                        ]],
                    ],
                    'field' => 'messages',
                ]],
            ]],
        ];

        // Act: Parse the webhook
        $result = $this->parser->parseWebhook($payload);

        // Assert: Verify parsed data
        $this->assertIsArray($result);
        $this->assertEquals('123456789012345', $result['phone_number_id']);
        $this->assertEquals('5511999999999', $result['from']);
        $this->assertEquals('wamid.abc123', $result['message_id']);
        $this->assertEquals('text', $result['type']);
        $this->assertEquals('Hello Luminai', $result['content']);
        $this->assertEquals('John Doe', $result['contact_name']);
    }

    public function testParseImageMessage(): void
    {
        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => '123456789',
                'changes' => [[
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => [
                            'display_phone_number' => '15550000000',
                            'phone_number_id' => '123456789012345',
                        ],
                        'contacts' => [[
                            'profile' => ['name' => 'Jane Doe'],
                            'wa_id' => '5511888888888',
                        ]],
                        'messages' => [[
                            'from' => '5511888888888',
                            'id' => 'wamid.img123',
                            'timestamp' => '1234567890',
                            'type' => 'image',
                            'image' => [
                                'id' => 'media123',
                                'mime_type' => 'image/jpeg',
                                'caption' => 'Check this out',
                            ],
                        ]],
                    ],
                    'field' => 'messages',
                ]],
            ]],
        ];

        $result = $this->parser->parseWebhook($payload);

        $this->assertIsArray($result);
        $this->assertEquals('image', $result['type']);
        $this->assertIsArray($result['content']);
        $this->assertEquals('media123', $result['content']['id']);
        $this->assertEquals('image/jpeg', $result['content']['mime_type']);
        $this->assertEquals('Check this out', $result['content']['caption']);
    }

    public function testParseStatusUpdate(): void
    {
        // Status updates don't have 'messages' field
        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => '123456789',
                'changes' => [[
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => [
                            'display_phone_number' => '15550000000',
                            'phone_number_id' => '123456789012345',
                        ],
                        'statuses' => [[
                            'id' => 'wamid.status123',
                            'status' => 'read',
                        ]],
                    ],
                    'field' => 'messages',
                ]],
            ]],
        ];

        // Status updates should return null (not a message event)
        $result = $this->parser->parseWebhook($payload);
        $this->assertNull($result);
    }

    public function testValidateSignature(): void
    {
        // Arrange: Create payload and signature
        $payload = '{"test":"data"}';
        $expectedSignature = hash_hmac('sha256', $payload, $this->appSecret);

        // Act & Assert: Valid signature
        $this->assertTrue(
            $this->parser->validateSignature($payload, 'sha256=' . $expectedSignature)
        );

        // Act & Assert: Invalid signature
        $this->assertFalse(
            $this->parser->validateSignature($payload, 'sha256=invalid_signature')
        );
    }

    public function testValidateSignatureWithoutPrefix(): void
    {
        $payload = '{"test":"data"}';
        $expectedSignature = hash_hmac('sha256', $payload, $this->appSecret);

        // Test without 'sha256=' prefix
        $this->assertTrue(
            $this->parser->validateSignature($payload, $expectedSignature)
        );
    }

    public function testParseInvalidStructure(): void
    {
        // Missing required fields
        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [],
        ];

        $result = $this->parser->parseWebhook($payload);
        $this->assertNull($result);
    }

    public function testParseLocationMessage(): void
    {
        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => '123456789',
                'changes' => [[
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'metadata' => [
                            'display_phone_number' => '15550000000',
                            'phone_number_id' => '123456789012345',
                        ],
                        'contacts' => [[
                            'profile' => ['name' => 'Bob Smith'],
                            'wa_id' => '5511777777777',
                        ]],
                        'messages' => [[
                            'from' => '5511777777777',
                            'id' => 'wamid.loc123',
                            'timestamp' => '1234567890',
                            'type' => 'location',
                            'location' => [
                                'latitude' => -23.550520,
                                'longitude' => -46.633308,
                                'name' => 'São Paulo',
                                'address' => 'São Paulo, SP, Brazil',
                            ],
                        ]],
                    ],
                    'field' => 'messages',
                ]],
            ]],
        ];

        $result = $this->parser->parseWebhook($payload);

        $this->assertIsArray($result);
        $this->assertEquals('location', $result['type']);
        $this->assertIsArray($result['content']);
        $this->assertEquals(-23.550520, $result['content']['latitude']);
        $this->assertEquals(-46.633308, $result['content']['longitude']);
        $this->assertEquals('São Paulo', $result['content']['name']);
    }
}
