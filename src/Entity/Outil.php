<?php

namespace App\Entity;

use App\Entity\User;
use App\Entity\Photo;
use App\Entity\Categorie;
use App\Entity\Historique;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use App\Repository\OutilRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: OutilRepository::class)]
class Outil
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    // 🌟 RELATION MODIFIÉE : ManyToOne vers l'entité Categorie
    #[ORM\ManyToOne(targetEntity: Categorie::class, inversedBy: 'outils')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Categorie $categorie = null;

    #[ORM\Column(length: 255)]
    private ?string $etat = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\ManyToOne(inversedBy: 'outils')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $proprietaire = null;

    #[ORM\ManyToOne(inversedBy: 'emprunts')]
    private ?User $emprunteur = null;

    /**
     * @var Collection<int, Historique>
     */
    #[ORM\OneToMany(targetEntity: Historique::class, mappedBy: 'outil')]
    private Collection $historiques;

    #[ORM\Column(nullable: true)]
    private ?array $caracteristiques = null;

    /**
     * @var Collection<int, Photo>
     */
    #[ORM\OneToMany(targetEntity: Photo::class, mappedBy: 'outil', orphanRemoval: true)]
    private Collection $photos;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'outil')]
    private Collection $messages;

    public function __construct()
    {
        $this->historiques = new ArrayCollection();
        $this->photos = new ArrayCollection();
        $this->messages = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    // 🌟 GETTER MODIFIÉ : Retourne désormais un objet Categorie ou null
    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    // 🌟 SETTER MODIFIÉ : Accepte désormais un objet Categorie ou null
    public function setCategorie(?Categorie $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): static
    {
        $this->etat = $etat;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getProprietaire(): ?User
    {
        return $this->proprietaire;
    }

    public function setProprietaire(?User $proprietaire): static
    {
        $this->proprietaire = $proprietaire;

        return $this;
    }

    public function getEmprunteur(): ?User
    {
        return $this->emprunteur;
    }

    public function setEmprunteur(?User $emprunteur): static
    {
        $this->emprunteur = $emprunteur;

        return $this;
    }

    /**
     * @return Collection<int, Historique>
     */
    public function getHistoriques(): Collection
    {
        return $this->historiques;
    }

    public function addHistorique(Historique $historique): static
    {
        if (!$this->historiques->contains($historique)) {
            $this->historiques->add($historique);
            $historique->setOutil($this);
        }

        return $this;
    }

    public function removeHistorique(Historique $historique): static
    {
        if ($this->historiques->removeElement($historique)) {
            // set the owning side to null (unless already changed)
            if ($historique->getOutil() === $this) {
                $historique->setOutil(null);
            }
        }

        return $this;
    }

    /**
     * Calcule la note moyenne de cet outil à partir de son historique
     */
    public function getNoteMoyenne(): ?float
    {
        $historiques = $this->getHistoriques(); // Récupère tout l'historique de l'outil

        if ($historiques->isEmpty()) {
            return null; // Pas encore de note pour cet outil
        }

        $somme = 0;
        $compteurNotes = 0;

        foreach ($historiques as $historique) {
            // On ne prend en compte que les historiques qui ont une note
            if ($historique->getNote() !== null) {
                $somme += $historique->getNote();
                $compteurNotes++;
            }
        }

        // Si l'outil a été emprunté mais que personne n'a encore laissé de note
        if ($compteurNotes === 0) {
            return null;
        }

        // On calcule la moyenne et on l'arrondit à 1 chiffre après la virgule (ex: 4.3)
        return round($somme / $compteurNotes, 1);
    }

    public function getCaracteristiques(): ?array
    {
        return $this->caracteristiques;
    }

    public function setCaracteristiques(?array $caracteristiques): static
    {
        $this->caracteristiques = $caracteristiques;

        return $this;
    }

    /**
     * @return Collection<int, Photo>
     */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function addPhoto(Photo $photo): static
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->setOutil($this);
        }

        return $this;
    }

    public function removePhoto(Photo $photo): static
    {
        if ($this->photos->removeElement($photo)) {
            // set the owning side to null (unless already changed)
            if ($photo->getOutil() === $this) {
                $photo->setOutil(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setOutil($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getOutil() === $this) {
                $message->setOutil(null);
            }
        }

        return $this;
    }
}
