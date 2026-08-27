<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OutilCatalogueTest extends WebTestCase
{
    public function testRechercheAjaxCatalogue(): void
    {
        $client = static::createClient();

        // 1. Test de la recherche AJAX
        $client->xmlHttpRequest('GET', '/outil/catalogue', [
            'q' => 'Perceuse'
        ]);

        $this->assertResponseIsSuccessful();

        $responseContent = $client->getResponse()->getContent();
        $this->assertStringContainsString('resultatsRecherche', $responseContent);
    }

    public function testCataloguePageStandard(): void
    {
        $client = static::createClient();

        // 2. Test du comportement sans le paramètre AJAX (requête HTTP classique)
        $client->request('GET', '/outil/catalogue');

        $this->assertResponseIsSuccessful();

        // Vérifie par exemple qu'on récupère la structure complète de la page (ex: balise <html> ou un titre de page)
        $this->assertSelectorExists('nav'); // ou un autre élément propre à ton layout global
    }

    public function testFiltreCategorieCatalogue(): void
    {
        $client = static::createClient();

        // 3. Test d'un filtre par catégorie (adapte le paramètre 'categorie' selon ton code, ex: id ou slug)
        $client->xmlHttpRequest('GET', '/outil/catalogue', [
            'categorie' => 1
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testPaginationCatalogue(): void
    {
        $client = static::createClient();

        // 4. Test de la pagination (adapte le paramètre 'page' selon ton paginateur KnpPaginator, ex: 'page' ou 'p')
        $client->request('GET', '/outil/catalogue', [
            'page' => 2
        ]);

        $this->assertResponseIsSuccessful();
    }
}
