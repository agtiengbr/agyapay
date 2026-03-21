<?php

namespace AGTI\Yapay\Infrastructure\Api\Local\Account\Delete;

use AGTI\Yapay\Entity\AgyapaySellerAccount;

class DeleteAccountArgs
{
    private $account;

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
}