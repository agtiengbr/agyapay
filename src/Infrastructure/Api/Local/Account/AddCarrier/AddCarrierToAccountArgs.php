<?php

namespace AGTI\Yapay\Infrastructure\Api\Local\Account\AddCarrier;

use AGTI\Yapay\Entity\AgyapaySellerAccount;
use AGTI\Yapay\Entity\Carrier;

class AddCarrierToAccountArgs
{
    private $account;
    private $carrier;

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
    public function setAccount(AgyapaySellerAccount $account)
    {
        $this->account = $account;

        return $this;
    }

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
    public function setCarrier(Carrier $carrier)
    {
        $this->carrier = $carrier;

        return $this;
    }
}