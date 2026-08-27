<?php

namespace App\Controller;

use App\Entity\Outil;
use App\Entity\Photo;
use App\Form\AvisType;
use App\Form\OutilType;
use App\Entity\Historique;
use App\Repository\OutilRepository;
use App\Service\NotificationService;
use App\Repository\CategorieRepository;
use App\Repository\HistoriqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/outil')]
class OutilController extends AbstractController
{
    #[Route('/catalogue', name: 'app_outil_index', methods: ['GET'])]
    public function index(
        Request $request,
        OutilRepository $outilRepository,
        CategorieRepository $categorieRepository,
        PaginatorInterface $paginator
    ): Response {
        $recherche = $request->query->get('q');
        $categorieParam = $request->query->get('categorie');
        $etat = $request->query->get('etat', 'all');
        $ville = $request->query->get('ville');

        $qb = $outilRepository->createQueryBuilder('o')
            ->leftJoin('o.proprietaire', 'p')
            ->orderBy('o.id', 'DESC');

        if ($recherche) {
            $qb->andWhere('o.nom LIKE :recherche OR o.description LIKE :recherche')
               ->setParameter('recherche', '%' . $recherche . '%');
        }

        if ($categorieParam && $categorieParam !== 'all') {
            $qb->innerJoin('o.categorie', 'c');
            if (is_numeric($categorieParam)) {
                $qb->andWhere('c.id = :catId')
                   ->setParameter('catId', (int) $categorieParam);
            } else {
                $qb->andWhere('LOWER(c.nom) LIKE LOWER(:catNom)')
                   ->setParameter('catNom', '%' . trim($categorieParam) . '%');
            }
        }

        if ($etat === 'disponible') {
            $qb->andWhere('o.emprunteur IS NULL');
        } elseif ($etat === 'emprunte') {
            $qb->andWhere('o.emprunteur IS NOT NULL');
        }

        if (!empty($ville)) {
            $qb->andWhere('p.ville LIKE :ville')
               ->setParameter('ville', '%' . $ville . '%');
        }

        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            9
        );

        return $this->render('outil/index.html.twig', [
            'outils' => $pagination,
            'categories' => $categorieRepository->findAll(),
            'current_recherche' => $recherche,
            'current_categorie' => $categorieParam,
            'current_etat' => $etat,
            'current_ville' => $ville,
        ]);
    }

    #[Route('/new', name: 'app_outil_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $userConnected = $this->getUser();
        if (!$userConnected instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException("Action interdite pour ce type de compte.");
        }

        $outil = new Outil();
        $form = $this->createForm(OutilType::class, $outil);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFiles = $form->get('image')->getData();

            if ($imageFiles) {
                foreach ($imageFiles as $imageFile) {
                    $newFilename = uniqid().'.'.$imageFile->guessExtension();

                    try {
                        $imageFile->move(
                            $this->getParameter('kernel.project_dir') . '/public/uploads/outils',
                            $newFilename
                        );
                    } catch (FileException $e) {
                        $this->addFlash('danger', 'Erreur lors du transfert de l\'un de vos fichiers.');
                        return $this->render('outil/new.html.twig', [
                            'outil' => $outil,
                            'form' => $form,
                        ]);
                    }

                    $photo = new Photo();
                    $photo->setNom($newFilename);

                    $outil->addPhoto($photo);
                    $entityManager->persist($photo);
                }
            }

            $outil->setProprietaire($userConnected);

            $entityManager->persist($outil);
            $entityManager->flush();

            $this->addFlash('success', 'L\'outil et ses photos ont bien été enregistrés !');
            return $this->redirectToRoute('app_outil_index');
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
    public function edit(Request $request, Outil $outil, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $userConnected = $this->getUser();
        if (!$userConnected instanceof \App\Entity\User || $outil->getProprietaire() !== $userConnected) {
            throw $this->currentAccessDeniedException ?? $this->createAccessDeniedException("Vous n'êtes pas autorisé à modifier cet outil !");
        }

        $ancienneImage = $outil->getImage();

        $tableauCaracteristiques = $outil->getCaracteristiques() ?? [];
        $texteBrut = "";
        foreach ($tableauCaracteristiques as $cle => $valeur) {
            $texteBrut .= $cle . ": " . $valeur . "\n";
        }

        $form = $this->createForm(OutilType::class, $outil);
        $form->get('optionsTexte')->setData(trim($texteBrut));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/outils',
                        $newFilename
                    );
                    $outil->setImage($newFilename);

                    if ($ancienneImage && file_exists($this->getParameter('kernel.project_dir') . '/public/uploads/outils/' . $ancienneImage)) {
                        unlink($this->getParameter('kernel.project_dir') . '/public/uploads/outils/' . $ancienneImage);
                    }
                } catch (FileException $e) {
                    $this->addFlash('danger', "Erreur lors du transfert de la nouvelle photo.");
                }
            } else {
                $outil->setImage($ancienneImage);
            }

            $texteCaracteristiques = $form->get('optionsTexte')->getData();
            $tableauFinal = [];

            if ($texteCaracteristiques) {
                $lignes = explode("\n", str_replace("\r", "", $texteCaracteristiques));
                foreach ($lignes as $ligne) {
                    $parties = explode(":", $ligne, 2);
                    if (count($parties) === 2) {
                        $cle = trim($parties[0]);
                        $valeur = trim($parties[1]);
                        if ($cle !== '') {
                            $tableauFinal[$cle] = $valeur;
                        }
                    }
                }
            }
            $outil->setCaracteristiques($tableauFinal);

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
        $userConnected = $this->getUser();
        if (!$userConnected instanceof \App\Entity\User || $outil->getProprietaire() !== $userConnected) {
            throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à supprimer cet outil !");
        }

        if ($this->isCsrfTokenValid('delete'.$outil->getId(), $request->getPayload()->getString('_token'))) {
            $imageName = $outil->getImage();
            if ($imageName && file_exists($this->getParameter('kernel.project_dir') . '/public/uploads/outils/' . $imageName)) {
                unlink($this->getParameter('kernel.project_dir') . '/public/uploads/outils/' . $imageName);
            }

            $entityManager->remove($outil);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_outil_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/emprunter', name: 'app_outil_emprunter', methods: ['POST'])]
    public function emprunter(
        Request $request,
        Outil $outil,
        EntityManagerInterface $entityManager,
        NotificationService $notifier
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_MEMBRE');

        $userConnected = $this->getUser();
        if (!$userConnected instanceof \App\Entity\User) {
            $this->addFlash('danger', 'Ce type de compte ne peut pas effectuer cette action.');
            return $this->redirectToRoute('app_outil_index');
        }

        if ($this->getParameter('kernel.environment') !== 'test' && !$this->isCsrfTokenValid('emprunter'.$outil->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($outil->getProprietaire() === $userConnected) {
            $this->addFlash('danger', 'Vous ne pouvez pas emprunter votre propre matériel.');
            return $this->redirectToRoute('app_profil');
        }

        if ($outil->getEmprunteur() !== null) {
            $this->addFlash('danger', 'Désolé, cet outil est déjà emprunté.');
            return $this->redirectToRoute('app_profil');
        }

        $userEntity = $entityManager->getRepository(\App\Entity\User::class)->find($userConnected->getId());

        $historique = new Historique();
        $historique->setOutil($outil);
        $historique->setUser($userEntity);
        $historique->setStatut('en_attente');

        $now = new \DateTimeImmutable();
        $historique->setDateDebut($now);

        $entityManager->persist($historique);
        $entityManager->flush();

        $notifier->sendDemandeEnAttenteNotification($historique);
        $notifier->sendNotificationProprietaire($historique);

        $this->addFlash('success', 'Votre demande d\'emprunt a bien été envoyée au propriétaire.');

        return $this->redirectToRoute('app_profil');
    }

    #[Route('/{id}/rendre', name: 'app_outil_rendre', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function rendre(
        Request $request,
        Outil $outil,
        EntityManagerInterface $entityManager,
        HistoriqueRepository $historiqueRepository,
        NotificationService $notifier
    ): Response {
        $userConnected = $this->getUser();

        if (!$userConnected instanceof \App\Entity\User) {
            return $this->redirectToRoute('app_outil_index');
        }

        if ($outil->getEmprunteur() !== $userConnected) {
            return $this->redirectToRoute('app_profil');
        }

        $historiqueEnCours = $historiqueRepository->findOneBy([
            'outil' => $outil,
            'user' => $userConnected,
            'dateFin' => null
        ]);

        if (!$historiqueEnCours) {
            $this->addFlash('danger', 'Aucun emprunt actif trouvé pour cet outil.');
            return $this->redirectToRoute('app_profil');
        }

        $form = $this->createForm(AvisType::class, $historiqueEnCours);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $noteSaisie = $request->request->get('note');
            if ($noteSaisie !== null) {
                $historiqueEnCours->setNote((int)$noteSaisie);
            }

            $data = $request->request->all();
            $commentaire = $data['avis']['commentaire'] ?? $form->get('commentaire')->getData() ?? null;
            if ($commentaire) {
                $historiqueEnCours->setCommentaire($commentaire);
            }

            $historiqueEnCours->setDateFin(new \DateTimeImmutable());
            $outil->setEmprunteur(null);

            $entityManager->flush();

            $notifier->sendRetourNotification($historiqueEnCours);

            $this->addFlash('success', 'L\'outil a bien été rendu !');
            return $this->redirectToRoute('app_profil');
        }

        return $this->render('outil/rendre.html.twig', [
            'outil' => $outil,
            'form' => $form->createView()
        ]);
    }

    #[Route('/historique/{id}/valider', name: 'app_historique_valider', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function valider(
        Request $request,
        Historique $historique,
        EntityManagerInterface $entityManager,
        NotificationService $notifier
    ): Response {
        $userConnected = $this->getUser();
        if (!$userConnected instanceof \App\Entity\User || $historique->getOutil()->getProprietaire() !== $userConnected) {
            throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à valider cet emprunt.");
        }

        if (!$this->isCsrfTokenValid('valider'.$historique->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException("Jeton de sécurité invalide.");
        }

        $historique->setStatut('valide');
        $historique->getOutil()->setEmprunteur($historique->getUser());

        $entityManager->flush();

        $notifier->sendReponseEmpruntNotification($historique);

        $this->addFlash('success', 'La demande d\'emprunt a été validée avec succès.');
        return $this->redirectToRoute('app_profil');
    }

    #[Route('/historique/{id}/refuser', name: 'app_historique_refuser', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function refuser(
        Request $request,
        Historique $historique,
        EntityManagerInterface $entityManager,
        NotificationService $notifier
    ): Response {
        $userConnected = $this->getUser();
        if (!$userConnected instanceof \App\Entity\User || $historique->getOutil()->getProprietaire() !== $userConnected) {
            throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à refuser cet emprunt.");
        }

        if (!$this->isCsrfTokenValid('refuser'.$historique->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException("Jeton de sécurité invalide.");
        }

        $historique->setStatut('refuse');
        $historique->getOutil()->setEmprunteur(null);

        $entityManager->flush();

        $notifier->sendReponseEmpruntNotification($historique);

        $this->addFlash('info', 'La demande d\'emprunt a été refusée.');
        return $this->redirectToRoute('app_profil');
    }
}
