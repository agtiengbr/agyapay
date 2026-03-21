<?php
namespace AGTI\Yapay\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class AgyapaySellerAccountCarrier
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id_agyapay_seller_account_carrier", type="integer")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="AgyapaySellerAccount")
     * @ORM\JoinColumn(name="id_agyapay_seller_account", referencedColumnName="id_agyapay_seller_account")
     */
    public $account;

    /**
     * @ORM\OneToOne(targetEntity="Carrier")
     * @ORM\JoinColumn(name="id_carrier", referencedColumnName="id_carrier")
     */
    public $carrier;


    /**
     * Get the value of carrier
     */ 
    public function getCarrier()
    {
        return $this->carrier;
    }

    /**
     * Set the value of carrier
     *
     * @return  self
     */ 
    public function setCarrier($carrier)
    {
        $this->carrier = $carrier;

        return $this;
    }

    /**
     * Get the value of account
     */ 
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * Set the value of account
     *
     * @return  self
     */ 
    public function setAccount($account)
    {
        $this->account = $account;

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
}