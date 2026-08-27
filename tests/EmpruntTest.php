<?php

namespace App\Tests;

use App\Entity\User;
use App\Entity\Outil;
use App\Repository\UserRepository;
use App\Repository\OutilRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EmpruntTest extends WebTestCase
{
    public function testEmpruntParUtilisateurConnecte(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        $userRepository = $container->get(UserRepository::class);
        $outilRepository = $container->get(OutilRepository::class);
        $entityManager = $container->get(EntityManagerInterface::class);

        // 1. Gestion de l'utilisateur de test
        $testUser = $userRepository->findOneBy([]);

        if (!$testUser) {
            $testUser = new User();
            $testUser->setEmail('test.emprunt@eco-partage.fr');
            $testUser->setPassword('$2y$13$dummyhashedpasswordforphpunit123456');
            $testUser->setRoles(['ROLE_USER', 'ROLE_MEMBRE']);
            $testUser->setPrenom('Test');
            $testUser->setNom('Utilisateur');
            $testUser->setTelephone('0600000000');
            $testUser->setAdresse('123 rue de Toulouse');
            $testUser->setCodePostal('31000');
            $testUser->setVille('Toulouse');
            $testUser->setPays('France');
            $testUser->setIsCotise(true);

            $entityManager->persist($testUser);
        } else {
            if (!in_array('ROLE_MEMBRE', $testUser->getRoles())) {
                $roles = $testUser->getRoles();
                $roles[] = 'ROLE_MEMBRE';
                $testUser->setRoles($roles);
            }
        }

        // 2. Gestion de l'outil de test (ID 1)
        $testOutil = $outilRepository->find(1);

        if (!$testOutil) {
            $testOutil = new Outil();
            $testOutil->setNom('Perceuse test');
            $testOutil->setDescription('Une perceuse pour les tests unitaires');
            $testOutil->setEtat('Bon');
            $testOutil->setProprietaire($testUser);

            $entityManager->persist($testOutil);
        }

        $entityManager->flush();

        // 3. Simuler la connexion
        $client->loginUser($testUser);

        // 4. Envoyer directement la requête POST d'emprunt
        $client->request('POST', '/outil/1/emprunter');

        // 5. Vérifier que l'application redirige bien vers le profil après l'emprunt
        $this->assertResponseRedirects('/profil');
    }
}
