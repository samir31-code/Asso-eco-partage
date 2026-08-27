<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminAccessTest extends WebTestCase
{
    public function testAdminAccessDeniedForAnonymous(): void
    {
        $client = static::createClient();

        // 1. Essayer d'accéder à une route admin sans être connecté
        $client->request('GET', '/admin');

        // 2. Vérifier qu'on est bien redirigé (généralement vers le login)
        $this->assertResponseRedirects('/login');
    }
}
