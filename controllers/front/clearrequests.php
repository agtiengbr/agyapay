<?php
class agyapayclearrequestsModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        Db::getInstance()->delete('agyapay_request', 'date_add < "' . date('Y-m-d H:i:s', strtotime("-7 days")) . '"');
        exit();
    }
}