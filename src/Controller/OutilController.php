<?php

namespace App\Controller;

use App\Entity\Outil;
use App\Form\AvisType;
use App\Form\OutilType;
use App\Entity\Historique;
use App\Repository\OutilRepository;
use App\Repository\HistoriqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/outil')]
class OutilController extends AbstractController
{
    #[Route(name: 'app_outil_index', methods: ['GET'])]
    public function index(OutilRepository $outilRepository): Response
    {
        return $this->render('outil/index.html.twig', [
            'outils' => $outilRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_outil_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $outil = new Outil();
        $form = $this->createForm(OutilType::class, $outil);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $outil->setProprietaire($this->getUser());
            $entityManager->persist($outil);
            $entityManager->flush();

            return $this->redirectToRoute('app_outil_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('outil/new.html.twig', [
            'outil' => $outil,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_outil_show', methods: ['GET'])]
    public function show(Outil $outil): Response
    {
        return $this->render('outil/show.html.twig', [
            'outil' => $outil,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_outil_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Outil $outil, EntityManagerInterface $entityManager): Response
    {
        if ($outil->getProprietaire() !== $this->getUser()) {
            throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à modifier cet outil !");
        }

        $form = $this->createForm(OutilType::class, $outil);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_outil_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('outil/edit.html.twig', [
            'outil' => $outil,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_outil_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, Outil $outil, EntityManagerInterface $entityManager): Response
    {
        if ($outil->getProprietaire() !== $this->getUser()) {
            throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à supprimer cet outil !");
        }

        if ($this->isCsrfTokenValid('delete'.$outil->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($outil);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_outil_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/emprunter', name: 'app_outil_emprunter', methods: ['POST'])]
    public function emprunter(Request $request, Outil $outil, EntityManagerInterface $entityManager): Response
    {
        // 🔒 Sécurité 1 : Vérification que l'utilisateur a payé sa cotisation Stripe (ROLE_MEMBRE)
        $this->denyAccessUnlessGranted('ROLE_MEMBRE');

        // 🔒 Sécurité 2 : Vérification du jeton CSRF obligatoire en POST
        if (!$this->isCsrfTokenValid('emprunter'.$outil->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException("Jeton de sécurité invalide.");
        }

        // 🔒 Sécurité 3 : On ne peut pas emprunter son propre outil
        if ($outil->getProprietaire() === $this->getUser()) {
            $this->addFlash('danger', 'Vous ne pouvez pas emprunter votre propre matériel.');
            return $this->redirectToRoute('app_profil');
        }

        // 🔒 Sécurité 4 : On ne peut pas emprunter un outil déjà pris
        if ($outil->getEmprunteur() !== null) {
            $this->addFlash('danger', 'Désolé, cet outil est déjà emprunté.');
            return $this->redirectToRoute('app_profil');
        }

        /** @var \App\Entity\User $userConnected */
        $userConnected = $this->getUser();
        $userEntity = $entityManager->getRepository(\App\Entity\User::class)->find($userConnected->getId());

        // Logique d'affectation
        $outil->setEmprunteur($userEntity);

        // ✨ Création de la ligne d'historique
        $historique = new Historique();
        $historique->setOutil($outil);
        $historique->setUser($userEntity);
        $historique->setDateDebut(new \DateTimeImmutable());

        $entityManager->persist($historique);
        $entityManager->flush();

        $this->addFlash('success', 'Félicitations ! L\'emprunt a bien été enregistré. Prenez-en soin !');

        return $this->redirectToRoute('app_profil');
    }

    #[Route('/{id}/rendre', name: 'app_outil_rendre', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function rendre(
        Request $request,
        Outil $outil,
        EntityManagerInterface $entityManager,
        HistoriqueRepository $historiqueRepository
    ): Response {
        $userConnected = $this->getUser();

        // Sécurité : Seul l'emprunteur actuel peut rendre l'outil
        if ($outil->getEmprunteur() !== $userConnected) {
            return $this->redirectToRoute('app_profil');
        }

        // On récupère la ligne d'historique en cours
        $historiqueEnCours = $historiqueRepository->findOneBy([
            'outil' => $outil,
            'user' => $userConnected,
            'dateFin' => null
        ]);

        if (!$historiqueEnCours) {
            $this->addFlash('danger', 'Aucun emprunt actif trouvé pour cet outil.');
            return $this->redirectToRoute('app_profil');
        }

        // On crée le formulaire lié à notre historique
        $form = $this->createForm(AvisType::class, $historiqueEnCours);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Clôture de l'emprunt
            $historiqueEnCours->setDateFin(new \DateTimeImmutable());
            $outil->setEmprunteur(null);

            $entityManager->flush();

            $this->addFlash('success', 'L\'outil a bien été rendu et votre avis a été enregistré !');
            return $this->redirectToRoute('app_profil');
        }

        return $this->render('outil/rendre.html.twig', [
            'outil' => $outil,
            'form' => $form->createView()
        ]);
    }
}
