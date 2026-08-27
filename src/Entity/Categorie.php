<?php

namespace App\Entity;

use App\Entity\Outil;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\CategorieRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: CategorieRepository::class)]
class Categorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\OneToMany(mappedBy: 'categorie', targetEntity: Outil::class)]
    private Collection $outils;

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $couleur = null;

    public function __construct()
    {
        $this->outils = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->nom ?? 'Nouvelle catégorie';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    /**
     * @return Collection<int, Outil>
     */
    public function getOutils(): Collection
    {
        return $this->outils;
    }

    public function addOutil(Outil $outil): static
    {
        if (!$this->outils->contains($outil)) {
            $this->outils->add($outil);
            $outil->setCategorie($this);
        }
        return $this;
    }

    public function removeOutil(Outil $outil): static
    {
        if ($this->outils->removeElement($outil)) {
            // set the owning side to null (unless already changed)
            if ($outil->getCategorie() === $this) {
                $outil->setCategorie(null);
            }
        }
        return $this;
    }

    public function getCouleur(): ?string
    {
        return $this->couleur;
    }

    public function setCouleur(?string $couleur): static
    {
        $this->couleur = $couleur;
        return $this;
    }
}
