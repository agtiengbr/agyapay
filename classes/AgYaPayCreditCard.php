<?php

class AgYapayCreditCard extends AgObjectModel
{
    public static $definition = array(
        'table'     => 'agyapay_credit_card',
        'primary'   => 'id_agyapay_credit_card',
        'multilang' => false,
        'fields'    => array(
            'id_agyapay_credit_card' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'id_customer' => ['type' => self::TYPE_INT, 'db_type' => 'int unsigned'],
            'payment_method_id' => ['type' => self::TYPE_INT, 'db_type' => 'int unsigned'],
            'cardnumber' => ['type' => self::TYPE_STRING, 'db_type' => 'varchar(32)'],
            'cvv' => ['type' => self::TYPE_STRING, 'db_type' => 'varchar(8)'],
            'expmonth' => ['type' => self::TYPE_INT, 'db_type' => 'int unsigned'],
            'expyear' => ['type' => self::TYPE_INT, 'db_type' => 'int unsigned'],
            'card_token' => ['type' => self::TYPE_STRING, 'db_type' => 'varchar(64)'],
            'active' => ['type' => self::TYPE_BOOL, 'db_type' => 'boolean'],
            'date_add' => ['type' => self::TYPE_DATE,'db_type' => 'datetime'],
            'date_upd' => ['type' => self::TYPE_DATE,'db_type' => 'datetime']
        )
    );

    public $id_agyapay_credit_card;
    public $id_customer;
    public $cardnumber;
    public $cvv;
    public $expmonth;
    public $expyear;
    public $card_token;
    public $active;
    public $date_add;
    public $date_upd;
    public $payment_method_id;

    /**
     * @return AgYaPayCreditCard[]
     */
    public static function findByCustomer(Customer $customer)
    {
        $sql = new DbQuery;
        $sql->from('agyapay_credit_card')
            ->where('id_customer=' . $customer->id);

        $db_data = Db::getInstance()->executeS($sql);
        if (!$db_data) {
            return [];
        }

        return ObjectModel::hydrateCollection('AgYaPayCreditCard', $db_data);
    }

    public function getCardBanner()
    {
        switch($this->payment_method_id) {
            case 3: return 'visa';
            case 4: return 'mastercard';
            case 5: return 'amex';
            case 14: return 'peela';
            case 15: return 'discover';
            case 16: return 'elo';
            case 18: return 'aura';
            case 19: return 'jcb';
            case 20: return 'hipercard';
            case 25: return 'hiper';
        }
    }
}