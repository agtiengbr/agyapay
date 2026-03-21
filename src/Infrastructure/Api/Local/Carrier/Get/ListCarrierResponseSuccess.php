<?php

namespace AGTI\Yapay\Infrastructure\Api\Local\Carrier\Get;

class ListCarrierResponseSuccess
{
    private $success;
    private $carriers;

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