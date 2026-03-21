<?php
class AgYaPaySellerAccountCarrier extends AgObjectModel
{
    public static $definition = array(
        'table'     => 'agyapay_seller_account_carrier',
        'primary'   => 'id_agyapay_seller_account_carrier',
        'multilang' => false,
        'fields'    => array(
            'id_agyapay_seller_account_carrier' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'id_agyapay_seller_account' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'db_type' => 'int'),
            'id_carrier' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'db_type' => 'int'),
        ),
        'indexes' => [
            [
                'fields' => ['id_carrier'],
                'prefix' => 'unique',
                'name' => 'unicity'
            ]
        ]
    );

    public $id_agyapay_seller_account_carrier;
    public $id_agyapay_seller_account;
    public $id_carrier;

    public static function getAccountTokenFromCarrier($id_carrier, $sandbox)
    {
        $account = self::getAccountByCarrier($id_carrier);

        if ($sandbox) {
            return $account->account_token_sandbox;
        }

        return $account->account_token;
    }

    public static function getAccountByCarrier($id_carrier)
    {
        $sql = new DbQuery;
        $sql->from('agyapay_seller_account_carrier')
            ->select('id_agyapay_seller_account');
        if ($id_carrier == -1 || $id_carrier == 0) {
            $sql->where('id_carrier IS NULL');
        } else {
            $sql->where('id_carrier =' . (int)$id_carrier);
        }

        $account = new AgYaPaySellerAccount(Db::getInstance()->getValue($sql));
        
        return $account;
    }
}