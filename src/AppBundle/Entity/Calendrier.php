<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use AppBundle\Entity\User;

/**
 * Calendrier
 *
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CalendrierRepository")
 * @ORM\Table(name="Calendrier",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="user_agenda_unique",columns={"user_id","agenda_id"})
 *      })
 * @ORM\HasLifecycleCallbacks
 */
class Calendrier
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="participe", type="boolean")
     */
    protected $participe;

    /**
     * @var boolean
     *
     * @ORM\Column(name="interet", type="boolean")
     */
    protected $interet;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User", inversedBy="calendriers")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Agenda", inversedBy="calendriers")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $agenda;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_date", type="datetime")
     */
    protected $lastDate;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->lastDate = new \DateTime();
        $this->participe = false;
        $this->interet = false;
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload()
    {
        $this->lastDate = new \DateTime;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * Set participe
     *
     * @param boolean $participe
     *
     * @return Calendrier
     */
    public function setParticipe($participe)
    {
        $this->participe = $participe;

        return $this;
    }

    /**
     * Get participe
     *
     * @return boolean
     */
    public function getParticipe()
    {
        return $this->participe;
    }

    /**
     * Set interet
     *
     * @param boolean $interet
     *
     * @return Calendrier
     */
    public function setInteret($interet)
    {
        $this->interet = $interet;

        return $this;
    }

    /**
     * Get interet
     *
     * @return boolean
     */
    public function getInteret()
    {
        return $this->interet;
    }


    /**
     * Set user
     *
     * @param User $user
     *
     * @return Calendrier
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \AppBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set agenda
     *
     * @param Agenda $agenda
     *
     * @return Calendrier
     */
    public function setAgenda(Agenda $agenda)
    {
        $this->agenda = $agenda;

        return $this;
    }

    /**
     * Get agenda
     *
     * @return \AppBundle\Entity\Agenda
     */
    public function getAgenda()
    {
        return $this->agenda;
    }

    /**
     * Set lastDate
     *
     * @param \DateTime $lastDate
     * @return Calendrier
     */
    public function setLastDate($lastDate)
    {
        $this->lastDate = $lastDate;

        return $this;
    }

    /**
     * Get lastDate
     *
     * @return \DateTime
     */
    public function getLastDate()
    {
        return $this->lastDate;
    }

    public function __toString()
    {
        return "#" . $this->id ?: '?';
    }
}
