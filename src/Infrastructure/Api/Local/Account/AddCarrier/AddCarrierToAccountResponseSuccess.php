<?php

namespace AGTI\Yapay\Infrastructure\Api\Local\Account\AddCarrier;

class AddCarrierToAccountResponseSuccess
{
    private $success;

    /**
     * Get the value of success
     */ 
    public function getSuccess()
    {
        return $this->success;
    }

    /**
     * Set the value of success
     *
     * @return  self
     */ 
    public function setSuccess($success)
    {
        $this->success = $success;

        return $this;
    }
}