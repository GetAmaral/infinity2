<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    /**
     */
    public function testHomepageLoads(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('title', 'Welcome to Luminai');
        $this->assertSelectorTextContains('h1', 'Welcome to Luminai');
        $this->assertSelectorExists('.luminai-card');
    }

    /**
     */
    public function testNavigationLinks(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('a[href="/organization"]');
        $this->assertSelectorExists('a[href="/user"]');
        $this->assertSelectorExists('a[href="/api"]');
        $this->assertSelectorTextContains('nav', '🚀 Luminai');
    }
}
