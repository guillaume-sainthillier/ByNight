<?php

namespace App\Entity;

use App\Geolocalize\GeolocalizeInterface;
use App\Reject\Reject;
use App\Validator\Constraints\EventConstraint;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\Type;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Event.
 *
 * @ORM\Table(name="Agenda", indexes={
 *     @ORM\Index(name="event_theme_manifestation_idx", columns={"theme_manifestation"}),
 *     @ORM\Index(name="event_type_manifestation_idx", columns={"type_manifestation"}),
 *     @ORM\Index(name="event_categorie_manifestation_idx", columns={"categorie_manifestation"}),
 *     @ORM\Index(name="event_search_idx", columns={"place_id", "date_fin", "date_debut"}),
 *     @ORM\Index(name="event_fb_participations", columns={"date_fin", "fb_participations", "fb_interets"}),
 *     @ORM\Index(name="event_external_id_idx", columns={"external_id"})
 * })
 *
 * @ORM\Entity(repositoryClass="App\Repository\EventRepository")
 * @ORM\HasLifecycleCallbacks
 * @ExclusionPolicy("all")
 * @Vich\Uploadable
 * @EventConstraint
 */
class Event implements GeolocalizeInterface
{
    const INDEX_FROM = '-6 months';
    const INDEX_TO = '+2 years';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"list_event"})
     * @Expose
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=127, nullable=true)
     */
    protected $externalId;

    /**
     * @Gedmo\Slug(fields={"nom"})
     * @ORM\Column(length=255, unique=true)
     */
    protected $slug;

    /**
     * @var string
     *
     * @ORM\Column(name="nom", type="string", length=255, nullable=true)
     * @Assert\NotBlank(message="N'oubliez pas de nommer votre événement !")
     * @Groups({"list_event"})
     * @Expose
     */
    protected $nom;

    /**
     * @var string
     *
     * @ORM\Column(name="descriptif", type="text", nullable=true)
     * @Assert\NotBlank(message="N'oubliez pas de décrire votre événement !")
     * @Groups({"list_event"})
     * @Expose
     */
    protected $descriptif;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date_modification", type="datetime", nullable=true)
     */
    protected $dateModification;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="fb_date_modification", type="datetime", nullable=true)
     */
    protected $fbDateModification;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date_debut", type="date", nullable=true)
     * @Assert\NotBlank(message="Vous devez donner une date à votre événement")
     * @Groups({"list_event"})
     * @Expose
     * @Type("DateTime<'Y-m-d'>")
     */
    protected $dateDebut;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date_fin", type="date", nullable=true)
     * @Groups({"list_event"})
     * @Expose
     * @Type("DateTime<'Y-m-d'>")
     */
    protected $dateFin;

    /**
     * @var string
     *
     * @ORM\Column(name="horaires", type="string", length=256, nullable=true)
     */
    protected $horaires;

    /**
     * @var string
     *
     * @ORM\Column(name="modification_derniere_minute", type="string", length=16, nullable=true)
     */
    protected $modificationDerniereMinute;

    /**
     * @var float
     *
     * @ORM\Column(name="latitude", type="float", nullable=true)
     * @Groups({"list_event"})
     * @Expose
     */
    protected $latitude;

    /**
     * @var float
     *
     * @ORM\Column(name="longitude", type="float", nullable=true)
     * @Groups({"list_event"})
     * @Expose
     */
    protected $longitude;

    /**
     * @var string
     *
     * @ORM\Column(name="adresse", type="string", length=255, nullable=true)
     */
    protected $adresse;

    /**
     * @var string
     *
     * @ORM\Column(name="type_manifestation", type="string", length=128, nullable=true)
     * @Groups({"list_event"})
     * @Expose
     */
    protected $typeManifestation;

    /**
     * @var string
     *
     * @ORM\Column(name="categorie_manifestation", type="string", length=128, nullable=true)
     * @Groups({"list_event"})
     * @Expose
     */
    protected $categorieManifestation;

    /**
     * @var string
     *
     * @ORM\Column(name="theme_manifestation", type="string", length=128, nullable=true)
     * @Groups({"list_event"})
     * @Expose
     */
    protected $themeManifestation;

    /**
     * @var string
     *
     * @ORM\Column(name="reservation_telephone", type="string", length=255, nullable=true)
     */
    protected $reservationTelephone;

    /**
     * @var string
     *
     * @ORM\Column(name="reservation_email", type="string", length=255, nullable=true)
     * @Assert\Email
     */
    protected $reservationEmail;

    /**
     * @var string
     *
     * @ORM\Column(name="reservation_internet", type="string", length=512, nullable=true)
     * @Assert\Url
     */
    protected $reservationInternet;

    /**
     * @var string
     *
     * @ORM\Column(name="tarif", type="string", length=255, nullable=true)
     */
    protected $tarif;

    /**
     * @var string
     *
     * @ORM\Column(name="from_data", type="string", length=127, nullable=true)
     */
    protected $fromData;

    /**
     * @Vich\UploadableField(mapping="event_image", fileNameProperty="path")
     * @Assert\Valid
     * @Assert\File(maxSize="6M")
     * @Assert\Image
     */
    protected $file;

    /**
     * @Vich\UploadableField(mapping="event_image", fileNameProperty="systemPath")
     * @Assert\Valid
     * @Assert\File(maxSize="6M")
     * @Assert\Image
     */
    protected $systemFile;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=61, nullable=true)
     */
    protected $path;

    /**
     * @ORM\Column(type="string", name="system_path", length=61, nullable=true)
     */
    protected $systemPath;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $url;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     */
    protected $user;

    /**
     * @var bool
     *
     * @ORM\Column(name="isBrouillon", type="boolean")
     * @Groups({"list_event"})
     * @Expose
     */
    protected $isBrouillon;

    /**
     * @var int
     *
     * @ORM\Column(name="tweet_post_id", type="string", length=127, nullable=true)
     */
    protected $tweetPostId;

    /**
     * @var int
     *
     * @ORM\Column(name="facebook_event_id", type="string", length=127, nullable=true)
     */
    protected $facebookEventId;

    /**
     * @var int
     *
     * @ORM\Column(name="tweet_post_system_id", type="string", length=127, nullable=true)
     */
    protected $tweetPostSystemId;

    /**
     * @var int
     *
     * @ORM\Column(name="fb_post_id", type="string", length=127, nullable=true)
     */
    protected $fbPostId;

    /**
     * @var int
     *
     * @ORM\Column(name="fb_post_system_id", type="string", length=127, nullable=true)
     */
    protected $fbPostSystemId;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Calendrier", mappedBy="event", cascade={"persist", "merge", "remove"}, fetch="EXTRA_LAZY")
     */
    protected $calendriers;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Comment", mappedBy="event", cascade={"persist", "merge", "remove"}, fetch="EXTRA_LAZY")
     * @ORM\OrderBy({"dateCreation": "DESC"})
     */
    protected $commentaires;

    /**
     * @var int
     *
     * @ORM\Column(name="facebook_owner_id", type="string", length=31, nullable=true)
     */
    protected $facebookOwnerId;

    /**
     * @var int
     *
     * @ORM\Column(name="fb_participations", type="integer", nullable=true)
     * @Groups({"list_event"})
     * @Expose
     */
    protected $fbParticipations;

    /**
     * @var int
     *
     * @ORM\Column(name="fb_interets", type="integer", nullable=true)
     */
    protected $fbInterets;

    /**
     * @var int
     *
     * @ORM\Column(name="participations", type="integer", nullable=true)
     */
    protected $participations;

    /**
     * @var int
     *
     * @ORM\Column(name="interets", type="integer", nullable=true)
     */
    protected $interets;

    /**
     * @var int
     *
     * @ORM\Column(name="source", type="string", length=256, nullable=true)
     */
    protected $source;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Place", cascade={"persist", "merge"}, fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"list_event"})
     * @Expose
     * @Assert\Valid
     */
    protected $place;

    /**
     * @var Reject|null
     */
    protected $reject;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_archive", type="boolean")
     */
    protected $isArchive;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="Vous devez indiquer le lieu de votre événement")
     * @Groups({"list_event"})
     * @Expose
     */
    protected $placeName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=127, nullable=true)
     * @Groups({"list_event"})
     * @Expose
     */
    protected $placeStreet;

    /**
     * @ORM\Column(type="string", length=127, nullable=true)
     * @Groups({"list_event"})
     * @Expose
     */
    protected $placeCity;

    /**
     * @ORM\Column(type="string", length=7, nullable=true)
     * @Groups({"list_event"})
     * @Expose
     */
    protected $placePostalCode;

    /**
     * @ORM\Column(type="string", length=127, nullable=true)
     */
    protected $placeExternalId;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $placeFacebookId;

    /**
     * @var Reject|null
     */
    protected $placeReject;

    /**
     * @var string|null
     */
    protected $placeCountryName;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Country")
     * @ORM\JoinColumn(nullable=true)
     *
     * @var Country|null
     */
    protected $placeCountry;

    public function getReject(): ?Reject
    {
        return $this->reject;
    }

    public function setReject(?Reject $reject): Event
    {
        $this->reject = $reject;

        return $this;
    }

    public function getPlaceReject(): ?Reject
    {
        return $this->placeReject;
    }

    public function setPlaceReject(?Reject $placeReject): Event
    {
        $this->placeReject = $placeReject;

        return $this;
    }

    public function isIndexable()
    {
        $from = new DateTime();
        $from->modify(self::INDEX_FROM);

        $to = new DateTime();
        $to->modify(self::INDEX_TO);

        return $this->dateFin >= $from && $this->dateFin <= $to;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the  update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|UploadedFile $image
     *
     * @return Event
     */
    public function setFile(File $image = null)
    {
        $this->file = $image;

        if ($image) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->dateModification = new DateTime();
        }

        return $this;
    }

    public function getFile()
    {
        return $this->file;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the  update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|UploadedFile $image
     *
     * @return Event
     */
    public function setSystemFile(File $image = null)
    {
        $this->systemFile = $image;

        if ($image) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->dateModification = new DateTime();
        }

        return $this;
    }

    public function getSystemFile()
    {
        return $this->systemFile;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function majDateFin()
    {
        if (null === $this->dateFin) {
            $this->dateFin = $this->dateDebut;
        }
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function preDateModification()
    {
        $this->dateModification = new DateTime();
    }

    public function __construct()
    {
        $this->dateDebut = new DateTime();
        $this->calendriers = new ArrayCollection();
        $this->commentaires = new ArrayCollection();
        $this->isBrouillon = false;
        $this->isArchive = false;
    }

    public function getLocationSlug()
    {
        if ($this->getPlace() && $this->getPlace()->getCity()) {
            return $this->getPlace()->getCity()->getSlug();
        }

        if ($this->getPlace() && $this->getPlace()->getCountry()) {
            return $this->getPlace()->getCountry()->getSlug();
        }

        return 'unknown';
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getDistinctTags()
    {
        $tags = $this->categorieManifestation . ',' . $this->typeManifestation . ',' . $this->themeManifestation;

        return \array_unique(\array_map('trim', \array_map('ucfirst', \array_filter(\preg_split('#[,/]#', $tags)))));
    }

    public function getPlaceCountryName(): ?string
    {
        return $this->placeCountryName;
    }

    public function setPlaceCountryName(?string $placeCountryName): self
    {
        $this->placeCountryName = $placeCountryName;

        return $this;
    }

    public function __toString()
    {
        return '#' . $this->id ?: '?';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDescriptif(): ?string
    {
        return $this->descriptif;
    }

    public function setDescriptif(?string $descriptif): self
    {
        $this->descriptif = $descriptif;

        return $this;
    }

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->dateModification;
    }

    public function setDateModification(?\DateTimeInterface $dateModification): self
    {
        $this->dateModification = $dateModification;

        return $this;
    }

    public function getFbDateModification(): ?\DateTimeInterface
    {
        return $this->fbDateModification;
    }

    public function setFbDateModification(?\DateTimeInterface $fbDateModification): self
    {
        $this->fbDateModification = $fbDateModification;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $dateDebut): self
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeInterface $dateFin): self
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getHoraires(): ?string
    {
        return $this->horaires;
    }

    public function setHoraires(?string $horaires): self
    {
        $this->horaires = $horaires;

        return $this;
    }

    public function getModificationDerniereMinute(): ?string
    {
        return $this->modificationDerniereMinute;
    }

    public function setModificationDerniereMinute(?string $modificationDerniereMinute): self
    {
        $this->modificationDerniereMinute = $modificationDerniereMinute;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): self
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getTypeManifestation(): ?string
    {
        return $this->typeManifestation;
    }

    public function setTypeManifestation(?string $typeManifestation): self
    {
        $this->typeManifestation = $typeManifestation;

        return $this;
    }

    public function getCategorieManifestation(): ?string
    {
        return $this->categorieManifestation;
    }

    public function setCategorieManifestation(?string $categorieManifestation): self
    {
        $this->categorieManifestation = $categorieManifestation;

        return $this;
    }

    public function getThemeManifestation(): ?string
    {
        return $this->themeManifestation;
    }

    public function setThemeManifestation(?string $themeManifestation): self
    {
        $this->themeManifestation = $themeManifestation;

        return $this;
    }

    public function getReservationTelephone(): ?string
    {
        return $this->reservationTelephone;
    }

    public function setReservationTelephone(?string $reservationTelephone): self
    {
        $this->reservationTelephone = $reservationTelephone;

        return $this;
    }

    public function getReservationEmail(): ?string
    {
        return $this->reservationEmail;
    }

    public function setReservationEmail(?string $reservationEmail): self
    {
        $this->reservationEmail = $reservationEmail;

        return $this;
    }

    public function getReservationInternet(): ?string
    {
        return $this->reservationInternet;
    }

    public function setReservationInternet(?string $reservationInternet): self
    {
        $this->reservationInternet = $reservationInternet;

        return $this;
    }

    public function getTarif(): ?string
    {
        return $this->tarif;
    }

    public function setTarif(?string $tarif): self
    {
        $this->tarif = $tarif;

        return $this;
    }

    public function getFromData(): ?string
    {
        return $this->fromData;
    }

    public function setFromData(?string $fromData): self
    {
        $this->fromData = $fromData;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getSystemPath(): ?string
    {
        return $this->systemPath;
    }

    public function setSystemPath(?string $systemPath): self
    {
        $this->systemPath = $systemPath;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getIsBrouillon(): ?bool
    {
        return $this->isBrouillon;
    }

    public function setIsBrouillon(?bool $isBrouillon): self
    {
        $this->isBrouillon = $isBrouillon;

        return $this;
    }

    public function getTweetPostId(): ?string
    {
        return $this->tweetPostId;
    }

    public function setTweetPostId(?string $tweetPostId): self
    {
        $this->tweetPostId = $tweetPostId;

        return $this;
    }

    public function getFacebookEventId(): ?string
    {
        return $this->facebookEventId;
    }

    public function setFacebookEventId(?string $facebookEventId): self
    {
        $this->facebookEventId = $facebookEventId;

        return $this;
    }

    public function getTweetPostSystemId(): ?string
    {
        return $this->tweetPostSystemId;
    }

    public function setTweetPostSystemId(?string $tweetPostSystemId): self
    {
        $this->tweetPostSystemId = $tweetPostSystemId;

        return $this;
    }

    public function getFbPostId(): ?string
    {
        return $this->fbPostId;
    }

    public function setFbPostId(?string $fbPostId): self
    {
        $this->fbPostId = $fbPostId;

        return $this;
    }

    public function getFbPostSystemId(): ?string
    {
        return $this->fbPostSystemId;
    }

    public function setFbPostSystemId(?string $fbPostSystemId): self
    {
        $this->fbPostSystemId = $fbPostSystemId;

        return $this;
    }

    public function getFacebookOwnerId(): ?string
    {
        return $this->facebookOwnerId;
    }

    public function setFacebookOwnerId(?string $facebookOwnerId): self
    {
        $this->facebookOwnerId = $facebookOwnerId;

        return $this;
    }

    public function getFbParticipations(): ?int
    {
        return $this->fbParticipations;
    }

    public function setFbParticipations(?int $fbParticipations): self
    {
        $this->fbParticipations = $fbParticipations;

        return $this;
    }

    public function getFbInterets(): ?int
    {
        return $this->fbInterets;
    }

    public function setFbInterets(?int $fbInterets): self
    {
        $this->fbInterets = $fbInterets;

        return $this;
    }

    public function getParticipations(): ?int
    {
        return $this->participations;
    }

    public function setParticipations(?int $participations): self
    {
        $this->participations = $participations;

        return $this;
    }

    public function getInterets(): ?int
    {
        return $this->interets;
    }

    public function setInterets(?int $interets): self
    {
        $this->interets = $interets;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;

        return $this;
    }

    public function getIsArchive(): ?bool
    {
        return $this->isArchive;
    }

    public function setIsArchive(?bool $isArchive): self
    {
        $this->isArchive = $isArchive;

        return $this;
    }

    public function getPlaceName(): ?string
    {
        return $this->placeName;
    }

    public function setPlaceName(string $placeName): self
    {
        $this->placeName = $placeName;

        return $this;
    }

    public function getPlaceStreet(): ?string
    {
        return $this->placeStreet;
    }

    public function setPlaceStreet(?string $placeStreet): self
    {
        $this->placeStreet = $placeStreet;

        return $this;
    }

    public function getPlaceCity(): ?string
    {
        return $this->placeCity;
    }

    public function setPlaceCity(?string $placeCity): self
    {
        $this->placeCity = $placeCity;

        return $this;
    }

    public function getPlacePostalCode(): ?string
    {
        return $this->placePostalCode;
    }

    public function setPlacePostalCode(?string $placePostalCode): self
    {
        $this->placePostalCode = $placePostalCode;

        return $this;
    }

    public function getPlaceExternalId(): ?string
    {
        return $this->placeExternalId;
    }

    public function setPlaceExternalId(?string $placeExternalId): self
    {
        $this->placeExternalId = $placeExternalId;

        return $this;
    }

    public function getPlaceFacebookId(): ?string
    {
        return $this->placeFacebookId;
    }

    public function setPlaceFacebookId(?string $placeFacebookId): self
    {
        $this->placeFacebookId = $placeFacebookId;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection|Calendrier[]
     */
    public function getCalendriers(): Collection
    {
        return $this->calendriers;
    }

    public function addCalendrier(Calendrier $calendrier): self
    {
        if (!$this->calendriers->contains($calendrier)) {
            $this->calendriers[] = $calendrier;
            $calendrier->setEvent($this);
        }

        return $this;
    }

    public function removeCalendrier(Calendrier $calendrier): self
    {
        if ($this->calendriers->contains($calendrier)) {
            $this->calendriers->removeElement($calendrier);
            // set the owning side to null (unless already changed)
            if ($calendrier->getEvent() === $this) {
                $calendrier->setEvent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Comment[]
     */
    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(Comment $commentaire): self
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires[] = $commentaire;
            $commentaire->setEvent($this);
        }

        return $this;
    }

    public function removeCommentaire(Comment $commentaire): self
    {
        if ($this->commentaires->contains($commentaire)) {
            $this->commentaires->removeElement($commentaire);
            // set the owning side to null (unless already changed)
            if ($commentaire->getEvent() === $this) {
                $commentaire->setEvent(null);
            }
        }

        return $this;
    }

    public function getPlace(): ?Place
    {
        return $this->place;
    }

    public function setPlace(?Place $place): self
    {
        $this->place = $place;

        return $this;
    }

    public function getPlaceCountry(): ?Country
    {
        return $this->placeCountry;
    }

    public function setPlaceCountry(?Country $placeCountry): self
    {
        $this->placeCountry = $placeCountry;

        return $this;
    }
}