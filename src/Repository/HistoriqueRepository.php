<?php

namespace App\Repository;

use App\Entity\Historique;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Historique>
 */
class HistoriqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Historique::class);
    }

    /**
     * Récupère le nombre d'emprunts par mois pour l'année en cours
     */
    public function findEmpruntsParMois(): array
    {
        $year = (new \DateTime())->format('Y');

        $qb = $this->createQueryBuilder('h')
            ->select('SUBSTRING(h.dateDebut, 6, 2) as mois, COUNT(h.id) as total')
            ->where('SUBSTRING(h.dateDebut, 1, 4) = :year')
            ->setParameter('year', $year)
            ->groupBy('mois')
            ->orderBy('mois', 'ASC');

        $resultats = $qb->getQuery()->getResult();

        $stats = array_fill(1, 12, 0);

        foreach ($resultats as $row) {
            $stats[(int)$row['mois']] = (int)$row['total'];
        }

        return array_values($stats);
    }

    /**
     * Récupère les emprunts validés et en cours pour un utilisateur
     * @return Historique[]
     */
    public function findEmpruntsEnCoursByUser($user): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.user = :user')
            ->andWhere('h.statut = :statut')
            ->andWhere('h.dateFin IS NULL')
            ->setParameter('user', $user)
            ->setParameter('statut', 'valide')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne tous les emprunts en cours qui sont en retard (date de retour prévue dépassée)
     */
    public function findRetards()
    {
        return $this->createQueryBuilder('h')
            ->where('h.dateFin IS NULL')
            ->andWhere('h.dateRetourPrevu < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    /**
     * Méthode demandée par le DashboardController
     */
    public function findEmpruntsEnRetard(): array
    {
        return $this->findRetards();
    }

    /**
     * Récupère les derniers emprunts
     */
    public function findDerniersEmprunts(int $limit = 5): array
    {
        return $this->createQueryBuilder('h')
            ->orderBy('h.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les demandes en attente pour un utilisateur
     * (qu'il soit l'emprunteur ou le propriétaire de l'outil)
     */
    public function findDemandesEnAttenteByUser($user): array
    {
        return $this->createQueryBuilder('h')
            ->leftJoin('h.outil', 'o')
            ->where('h.statut = :statut')
            ->andWhere('h.user = :user OR o.proprietaire = :user')
            ->setParameter('statut', 'en_attente')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}
