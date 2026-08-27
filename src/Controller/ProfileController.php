<?php

namespace App\Controller;

use App\Form\UserProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_USER')] // 🔒 Seuls les utilisateurs connectés ont accès
class ProfileController extends AbstractController
{
    #[Route('/mon-profil', name: 'app_profile')]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        // On récupère l'utilisateur actuellement connecté
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // On crée le formulaire lié à cet utilisateur
        $form = $this->createForm(UserProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Pas besoin de persist() car Doctrine surveille déjà l'entité de l'utilisateur connecté
            $entityManager->flush();

            $this->addFlash('success', 'Vos informations personnelles ont bien été mises à jour !');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profil/edit.html.twig', [
            'profileForm' => $form->createView(),
        ]);
    }
}
