<?php
namespace AGTI\Yapay\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class AgyapaySellerAccount
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id_agyapay_seller_account", type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @ORM\Column(type="string")
     */
    private $accountToken;

    /**
     * @ORM\Column(type="string")
     */
    private $accountTokenSandbox;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateAdd;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateUpd;

    /**
     * @ORM\Column(type="string")
     */
    private $accessToken;

    /**
     * @ORM\Column(type="string")
     */
    private $accessTokenSandbox;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $notificationUrl;

    /**
     * @ORM\OneToMany(targetEntity="AgyapaySellerAccountCarrier", mappedBy="account", cascade={"persist"})
     */
    public $carriers;


    public function __construct()
    {
        $this->carriers = new ArrayCollection;
    }

    /**
     * Get the value of accessTokenSandbox
     */ 
    public function getAccessTokenSandbox()
    {
        return $this->accessTokenSandbox;
    }

    /**
     * Set the value of accessTokenSandbox
     *
     * @return  self
     */ 
    public function setAccessTokenSandbox($accessTokenSandbox)
    {
        $this->accessTokenSandbox = $accessTokenSandbox;

        return $this;
    }

    /**
     * Get the value of accessToken
     */ 
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Set the value of accessToken
     *
     * @return  self
     */ 
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    /**
     * Get the value of dateUpd
     */ 
    public function getDateUpd()
    {
        return $this->dateUpd;
    }

    /**
     * Set the value of dateUpd
     *
     * @return  self
     */ 
    public function setDateUpd($dateUpd)
    {
        $this->dateUpd = $dateUpd;

        return $this;
    }

    /**
     * Get the value of dateAdd
     */ 
    public function getDateAdd()
    {
        return $this->dateAdd;
    }

    /**
     * Set the value of dateAdd
     *
     * @return  self
     */ 
    public function setDateAdd($dateAdd)
    {
        $this->dateAdd = $dateAdd;

        return $this;
    }

    /**
     * Get the value of accountTokenSandbox
     */ 
    public function getAccountTokenSandbox()
    {
        return $this->accountTokenSandbox;
    }

    /**
     * Set the value of accountTokenSandbox
     *
     * @return  self
     */ 
    public function setAccountTokenSandbox($accountTokenSandbox)
    {
        $this->accountTokenSandbox = $accountTokenSandbox;

        return $this;
    }

    /**
     * Get the value of accountToken
     */ 
    public function getAccountToken()
    {
        return $this->accountToken;
    }

    /**
     * Set the value of accountToken
     *
     * @return  self
     */ 
    public function setAccountToken($accountToken)
    {
        $this->accountToken = $accountToken;

        return $this;
    }

    /**
     * Get the value of name
     */ 
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @return  self
     */ 
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of id
     */ 
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the value of id
     *
     * @return  self
     */ 
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of carriers
     */ 
    public function getCarriers()
    {
        return $this->carriers;
    }

    /**
     * Set the value of carriers
     *
     * @return  self
     */ 
    public function setCarriers($carriers)
    {
        $this->carriers = $carriers;

        return $this;
    }

    /**
     * Get the value of notificationUrl
     */ 
    public function getNotificationUrl()
    {
        return $this->notificationUrl;
    }

    /**
     * Set the value of notificationUrl
     *
     * @return  self
     */ 
    public function setNotificationUrl($notificationUrl)
    {
        $this->notificationUrl = $notificationUrl;

        return $this;
    }

    public function copyFromEntity(AgyapaySellerAccount $ett)
    {
        $this->name = $ett->getName();
        $this->accountToken = $ett->getAccountToken();
        $this->accountTokenSandbox = $ett->getAccountTokenSandbox();
        $this->dateAdd = $ett->getDateAdd();
        $this->dateUpd = $ett->getDateUpd();
        $this->accessToken = $ett->getAccessToken();
        $this->accessTokenSandbox = $ett->getAccessTokenSandbox();
        $this->notificationUrl = $ett->getNotificationUrl();
    }
}