<?php

namespace App\Controller;

use App\Entity\Outil;
use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_USER')]
#[Route('/messagerie')]
class MessageController extends AbstractController
{
    #[Route('/', name: 'app_message_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        // Récupérer tous les messages de l'utilisateur (envoyés ou reçus)
        $messages = $em->getRepository(Message::class)->createQueryBuilder('m')
            ->where('m.expediteur = :user OR m.destinataire = :user')
            ->setParameter('user', $user)
            ->orderBy('m.dateEnvoi', 'DESC')
            ->getQuery()
            ->getResult();

        // Grouper par Outil pour afficher des fils de discussion propres
        $conversations = [];
        foreach ($messages as $message) {
            $outil = $message->getOutil();
            if ($outil) {
                $conversations[$outil->getId()]['outil'] = $outil;
                $conversations[$outil->getId()]['dernier_message'] = $message;
            }
        }

        return $this->render('message/index.html.twig', [
            'conversations' => $conversations,
        ]);
    }

    #[Route('/envoyer/{id}', name: 'app_message_envoyer', methods: ['POST'])]
    public function envoyer(
        Outil $outil,
        Request $request,
        EntityManagerInterface $em,
        #[Autowire('@limiter.contact')] RateLimiterFactory $contactLimiter
    ): Response
    {
        // 1. Application du Rate Limiting basé sur l'adresse IP
        $limiter = $contactLimiter->create($request->getClientIp());

        if (false === $limiter->consume(1)->isAccepted()) {
            // Au lieu de lever une exception brute, on ajoute un message flash et on redirige
            $this->addFlash('error', 'Vous avez envoyé trop de messages. Veuillez patienter avant de réessayer.');
            return $this->redirectToRoute('app_message_show', ['id' => $outil->getId()]);
        }

        $expediteur = $this->getUser();
        $proprietaire = $outil->getProprietaire();

        $destinataire = null;

        if ($expediteur === $proprietaire) {
            $dernierMessage = $em->getRepository(Message::class)->findOneBy(
                ['outil' => $outil, 'destinataire' => $proprietaire],
                ['dateEnvoi' => 'DESC']
            );
            if (!$dernierMessage) {
                $autreMessage = $em->getRepository(Message::class)->findOneBy(
                    ['outil' => $outil],
                    ['dateEnvoi' => 'ASC']
                );
                $destinataire = $autreMessage ? ($autreMessage->getExpediteur() === $proprietaire ? $autreMessage->getDestinataire() : $autreMessage->getExpediteur()) : null;
            } else {
                $destinataire = $dernierMessage->getExpediteur();
            }
        } else {
            $destinataire = $proprietaire;
        }

        if (!$destinataire) {
            $this->addFlash('error', "Impossible de déterminer le destinataire du message.");
            return $this->redirectToRoute('app_outil_show', ['id' => $outil->getId()]);
        }

        $contenu = $request->request->get('contenu');

        if (!empty(trim($contenu))) {
            $message = new Message();
            $message->setContenu($contenu);
            $message->setExpediteur($expediteur);
            $message->setDestinataire($destinataire);
            $message->setOutil($outil);
            $message->setLu(false); // Par défaut, un nouveau message n'est pas lu

            $em->persist($message);
            $em->flush();

            $this->addFlash('success', "Message envoyé !");
        }

        return $this->redirectToRoute('app_message_show', ['id' => $outil->getId()]);
    }

    #[Route('/discussion/{id}', name: 'app_message_show', methods: ['GET'])]
    public function show(Outil $outil, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        // Récupérer tous les messages concernant cet outil pour cet utilisateur
        $messages = $em->getRepository(Message::class)->createQueryBuilder('m')
            ->where('m.outil = :outil')
            ->andWhere('m.expediteur = :user OR m.destinataire = :user')
            ->setParameter('outil', $outil)
            ->setParameter('user', $user)
            ->orderBy('m.dateEnvoi', 'ASC')
            ->getQuery()
            ->getResult();

        // Marquer comme lus tous les messages reçus par l'utilisateur connecté dans cette discussion
        $hasChanges = false;
        foreach ($messages as $message) {
            if ($message->getDestinataire() === $user && !$message->isLu()) {
                $message->setLu(true);
                $hasChanges = true;
            }
        }

        if ($hasChanges) {
            $em->flush();
        }

        return $this->render('message/show.html.twig', [
            'outil' => $outil,
            'messages' => $messages,
        ]);
    }

    #[Route('/supprimer/{id}', name: 'app_message_supprimer', methods: ['POST'])]
    public function supprimer(Message $message, EntityManagerInterface $em, Request $request): Response
    {
        $user = $this->getUser();

        // Vérifier que l'utilisateur connecté est bien l'expéditeur ou le destinataire du message
        if ($message->getExpediteur() !== $user && $message->getDestinataire() !== $user) {
            throw $this->createAccessDeniedException("Vous n'avez pas le droit de supprimer ce message.");
        }

        $outilId = $message->getOutil() ? $message->getOutil()->getId() : null;

        $em->remove($message);
        $em->flush();

        $this->addFlash('success', "Le message a bien été supprimé.");

        if ($outilId) {
            return $this->redirectToRoute('app_message_show', ['id' => $outilId]);
        }

        return $this->redirectToRoute('app_message_index');
    }

    #[Route('/supprimer-conversation/{id}', name: 'app_message_supprimer_conversation', methods: ['POST'])]
    public function supprimerConversation(Outil $outil, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        // Récupérer tous les messages de cet outil où l'utilisateur est impliqué (expéditeur ou destinataire)
        $messages = $em->getRepository(Message::class)->createQueryBuilder('m')
            ->where('m.outil = :outil')
            ->andWhere('m.expediteur = :user OR m.destinataire = :user')
            ->setParameter('outil', $outil)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        foreach ($messages as $message) {
            $em->remove($message);
        }

        $em->flush();

            $this->addFlash('success', "La conversation a bien été supprimée.");

        return $this->redirectToRoute('app_message_index');
    }
}
