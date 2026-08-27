<?php

namespace App\Repository;

use App\Entity\Outil;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Outil>
 */
class OutilRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Outil::class);
    }

    public function countEmpruntes(): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('count(o.id)')
            ->where('o.emprunteur IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les outils les plus empruntés
     */
    public function findTopOutils(int $limit = 5): array
    {
        return $this->createQueryBuilder('o')
            ->select('o as outil, COUNT(h.id) as nbEmprunts')
            ->leftJoin('o.historiques', 'h') // Vérifie que la relation dans ton entité Outil s'appelle bien 'historiques'
            ->groupBy('o.id')
            ->orderBy('nbEmprunts', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countOutilsParCategorie(): array
    {
        return $this->createQueryBuilder('o')
            ->select('c.nom AS categorieNom, COUNT(o.id) AS total')
            ->join('o.categorie', 'c') // Supposons que la relation dans Outil s'appelle 'categorie'
            ->groupBy('c.id')
            ->getQuery()
            ->getResult();
    }

    public function findByFilters(?string $recherche, ?int $categorieId, ?string $etat, ?string $ville)
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.proprietaire', 'p'); // 🔗 Jointure avec le propriétaire

        if ($recherche) {
            $qb->andWhere('o.nom LIKE :recherche OR o.description LIKE :recherche')
               ->setParameter('recherche', '%' . $recherche . '%');
        }

        if ($categorieId && $categorieId !== 'all') {
            $qb->andWhere('o.categorie = :categorieId')
               ->setParameter('categorieId', $categorieId);
        }

        if ($etat === 'disponible') {
            $qb->andWhere('o.emprunteur IS NULL');
        } elseif ($etat === 'emprunte') {
            $qb->andWhere('o.emprunteur IS NOT NULL');
        }

        // 📍 Filtre de proximité par la ville du propriétaire
        if ($ville) {
            $qb->andWhere('p.ville LIKE :ville')
               ->setParameter('ville', '%' . $ville . '%');
        }

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return Outil[] Returns an array of Outil objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('o.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Outil
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
