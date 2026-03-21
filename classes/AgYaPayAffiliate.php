<?php

class AgYapayAffiliate extends AgObjectModel
{
    public static $definition = array(
        'table'     => 'agyapay_affiliate',
        'primary'   => 'id_agyapay_affiliate',
        'multilang' => false,
        'fields'    => array(
            'id_agyapay_affiliate' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'id_customer' => array('type' => self::TYPE_INT, 'db_type' => 'int'),
            'comission' => array('type' => self::TYPE_FLOAT, 'db_type' => 'float'),
            'email' => ['type' => self::TYPE_STRING, 'db_type' => 'varchar(255)'],
            'date_add' => ['type' => self::TYPE_DATE,'db_type' => 'datetime'],
            'date_upd' => ['type' => self::TYPE_DATE,'db_type' => 'datetime']
        )
    );


    public $id_agyapay_affiliate;
    public $id_customer;
    public $comission;
    public $email;
    public $date_add;
    public $date_upd;

    public static function createFromCustomer(Customer $customer)
    {
        $current_obj = self::findByIdCustomer($customer->id);
        if (Validate::isLoadedObject($current_obj)) {
            throw new Exception("O cliente {$customer->email} já é um afiliado cadastrado.");
        }
        $obj = new AgYapayAffiliate;
        $obj->id_customer = $customer->id;
        $obj->add();
    }

    /**
     * @return AgYapayAffiliate
     */
    public static function findByIdCustomer($id_customer)
    {
        $sql = new DbQuery;
        $sql->from('agyapay_affiliate')
            ->where('id_customer=' . $id_customer);

        $db_data = Db::getInstance()->getRow($sql);
        if (is_array($db_data)) {
            $return = new AgYapayAffiliate($db_data['id_agyapay_affiliate']);
        } else {
            return new AgYapayAffiliate;
        }

        return $return;
    }
}