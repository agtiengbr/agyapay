<?php

use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;

class AgYapayTransaction extends AgObjectModel
{
    public static $definition = array(
        'table'     => 'agyapay_transaction',
        'primary'   => 'id_agyapay_transaction',
        'multilang' => false,
        'fields'    => array(
            'id_agyapay_transaction' => array('type' => self::TYPE_INT, ' idate' => 'isInt'),
            'id_order'               => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'db_type' => 'int'),
            'id_cart'                => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'db_type' => 'int'),
            'remote_token'           => array('type' => self::TYPE_STRING, 'db_type' => 'varchar(256)'),
            'remote_id'              => array('type' => self::TYPE_STRING, 'db_type' => 'varchar(100)'),
            'type'                   => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'db_type' => 'int'),
            'expiration_date'        => array('type' => self::TYPE_DATE, 'db_type' => 'date'),
            'url_payment'            => array('type' => self::TYPE_STRING, 'db_type' => 'varchar(255)'),
            'original_url_payment'   => array('type' => self::TYPE_STRING, 'db_type' => 'varchar(255)'),
            'bar_code'               => array('type' => self::TYPE_STRING, 'db_type' => 'varchar(255)'),
            'hash_bank_slip'         => array('type' => self::TYPE_STRING, 'db_type' => 'varchar(255)'),
            'date_add'               => array('type' => self::TYPE_DATE, 'db_type' => 'datetime'),
            'date_upd'               => array('type' => self::TYPE_DATE, 'db_type' => 'datetime'),
            'status'                 => array('type' => self::TYPE_STRING, 'db_type' => 'varchar(64)'),
            'status_id'              => array('type' => self::TYPE_INT, 'db_type' => 'int'),
            'qty_installments'       => array('type' => self::TYPE_INT, 'db_type' => 'int'),
            'installment_value'       => array('type' => self::TYPE_FLOAT, 'db_type' => 'float'),

            'payment_processed' => ['type' => self::TYPE_BOOL,'validate' => 'isBool','db_type' => 'boolean','default' => '0'],

            'deprecated'                   => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'db_type' => 'int'),

            'value_paid' => array('type' => self::TYPE_FLOAT, 'db_type' => 'float'),
            'value_invoiced' => array('type' => self::TYPE_FLOAT, 'db_type' => 'float'),

            'pix_qrcode_url' => array('type' => self::TYPE_STRING, 'db_type' => 'varchar(255)'),
            'pix_qrcode_hash' => array('type' => self::TYPE_STRING, 'db_type' => 'varchar(255)'),
            'pix_expiration_date' => array('type' => self::TYPE_STRING, 'db_type' => 'datetime'),
            'id_agyapay_seller_account' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'db_type' => 'int')
        ),
        'indexes' => array(
            array(
                'name' => 'unique_token',
                'prefix' => 'unique',
                'fields' => array('remote_token')
            ),
            array(
                'name' => 'unique_remote_id',
                'prefix' => 'unique',
                'fields' => array('remote_id')
            )
        )
    );

    public $id_agyapay_transaction;
    public $id_order;
    public $id_cart;
    public $remote_token;
    public $remote_id;

    //0: ticket; 1: cartão de crédito;
    public $type;
    public $expiration_date;
    public $url_payment;
    public $original_url_payment;
    public $bar_code;
    public $hash_bank_slip;
    public $date_add;
    public $date_upd;
    public $payment_processed;
    public $status;
    public $status_id;

    public $deprecated;

    //valor efetivamente pago pelo cliente somadas as taxas de parcelamento
    public $value_paid;

    //valor a ser somado na tabela de "pagamento" do PS
    public $value_invoiced;

    public $qty_installments;
    public $installment_value;

    public $pix_qrcode_url;
    public $pix_qrcode_hash;
    public $pix_expiration_date;
    public $id_agyapay_seller_account;

    public static function getByOrderId($id_order, $use_cache = true)
    {
        $cache_key = get_called_class() . __FUNCTION__ . $id_order;

        if (!Cache::isStored($cache_key) || !$use_cache) {
            $order = new Order($id_order);

            $sql = new DbQuery();
            $sql->select('t.*');
            $sql->from('agyapay_transaction', 't');
            $sql->where('id_order="' . $id_order . '"');
            $sql->where('deprecated != 1');

            $sql->orderBy('id_agyapay_transaction DESC');

            $db_data = Db::getInstance()->getRow($sql);
            if (!$db_data) {
                $db_data = array();
            }

            $return = new AgYapayTransaction();
            $return->hydrate($db_data);

            Cache::store($cache_key, $return);
        }


        return Cache::retrieve($cache_key);
    }

    public static function getBankSlipFile($hash_bank_slip)
    {
        $sql = new DbQuery();
        $sql->select('t.*');
        $sql->from('agyapay_transaction', 't');
        $sql->where('hash_bank_slip="' . $hash_bank_slip . '"');

        $sql->orderBy('id_agyapay_transaction DESC');

        $db_data = Db::getInstance()->getRow($sql);
        if (!$db_data) {
            $db_data = array();
        }

        $obj = new AgYapayTransaction();
        $obj->hydrate($db_data);

        return $obj;
    }

    /**
     * @return AgYapayTransaction
     */
    public static function getByRemoteToken($remote_token)
    {
        $cache_key = get_called_class() . __FUNCTION__ . $remote_token;

        if (!Cache::isStored($cache_key)) {
            $sql = new DbQuery();
            $sql->select('t.*');
            $sql->from('agyapay_transaction', 't');
            $sql->where('remote_token="' . pSQL($remote_token) . '"');

            $db_data = Db::getInstance()->getRow($sql);
            if (!$db_data) {
                $db_data = array();
            }

            $return = new AgYapayTransaction();
            $return->hydrate($db_data);

            Cache::store($cache_key, $return);
        }


        return Cache::retrieve($cache_key);
    }
    
    public static function getAllTransactionsWithoutOrder()
    {
        $sql = new DbQuery();
        $sql->select('t.*');
        $sql->from('agyapay_transaction', 't');
        $sql->where('(id_order=0 OR id_order IS NULL) AND id_cart IS NOT NULL');
        $sql->where('deprecated != 1');

        $sql->orderBy('id_agyapay_transaction DESC');

        $db_data = Db::getInstance()->ExecuteS($sql);
        if (!$db_data) {
            $db_data = array();
        }

        return ObjectModel::hydrateCollection('AgYapayTransaction', $db_data);
    }

    //soma o total das transações que já foram geradas para um pedido
    public static function sumTransactionsForOrder($id_order)
    {
        $cache_key = get_called_class() . __FUNCTION__ . $id_order;

        if (!Cache::isStored($cache_key)) {
            $sql = new DbQuery;
            $sql->from('agyapay_transaction')
                ->select('SUM(value_paid)')
                ->where('deprecated=0')
                ->where('id_order=' . (int)$id_order);

            $total = (int) Db::getInstance()->getValue($sql);

            Cache::store($cache_key, $total);
        }

        return Cache::retrieve($cache_key);
    }

    public function updatePsStatus()
    {
        $ps_order = new Order($this->id_order);
        if (!Validate::isLoadedObject($ps_order)) {
            AgClienteLogger::addLog("agyapay - Pedido não localizado.", 2);

            return;
        }

        //nao cancela pedidos que não sejam do próprio módulo.
        if ($ps_order->module != 'agyapay' && $this->status_id != 6) {
            AgClienteLogger::addLog("agyapay - Pedido não gerado pelo módulo.", 2);
            return;
        }

        //evita a repetição de estados dos pedidos
        $semId = ftok(__FILE__, "s");
        $sem = sem_get($semId, 1);
        sem_acquire($sem);

        $module = new agyapay;
        $ps_status = $module->getStatus($this->status_id, $ps_order);
        AgClienteLogger::addLog("agyapay - Transação {$this->id} / estado $this->status_id / Estado PS: {$ps_status}");
        
        // Adiciona o pagamento no pedido do PS
        if ($ps_status != $ps_order->current_state) {
            $history = $ps_order->getHistory(Context::getContext()->language->id, $ps_status);
            if (!$history) {
                AgClienteLogger::addLog("agyapay - Atualizando estado do pedido {$ps_order->id} para {$ps_status}.", 1, null, null, null, true);
                try {
                    $this->setStatusForOrder($ps_order, $ps_status);
                    
                    // Atualiza o valor da propriedade "payment" do pedido
                    if ($ps_status == Configuration::get('PS_OS_PAYMENT')) {
                        if ($this->type == 0) {
                            $ps_order->payment = 'Vindi - Boleto Bancário';
                        } elseif ($this->type == 3) {
                            $ps_order->payment = 'Vindi - PIX';
                        } elseif ($this->type == 1) {
                            $ps_order->payment = 'Vindi - Cartão ' . $this->qty_installments . 'x';
                        }
                        $ps_order->update();
                    }
                } catch (Exception $e) {
                    AgClienteLogger::addLog("agyapay - ERRO: {$e->getMessage()}.", 3, null, null, null, true);
                }
            } else {
                AgClienteLogger::addLog("agyapay - Estado {$ps_status} já processado no pedido {$ps_order->id}.", 1, null, null, null, true);
            }
        } else {
            AgClienteLogger::addLog("agyapay - Estado do pedido não alterado. Pedido {$ps_order->id} está no estado {$ps_order->current_state}; estado vindi: {$ps_status}", 1);
        }

        sem_release($sem);
    }

    protected function setStatusForOrder(Order $order, $id_order_state)
    {
        $order->setCurrentState($id_order_state);
    }

    public static function expireOrderTransactions(Order $order)
    {
        Db::getInstance()->update('agyapay_transaction', ['deprecated' => 1], 'id_order=' . (int)$order->id);
    }


    public static function getAllTransactionsWithoutStatus($status)
    {
        $sql = new DbQuery();
        $sql->select('t.*');
        $sql->from('agyapay_transaction', 't');
        $sql->innerJoin('orders', 'o', 'o.id_order = t.id_order');
        $sql->where('o.current_state = '.$status);
        $sql->orderBy('id_agyapay_transaction DESC');

        $db_data = Db::getInstance()->ExecuteS($sql);
        if (!$db_data) {
            $db_data = array();
        }

        return ObjectModel::hydrateCollection('AgYapayTransaction', $db_data);
    }

    public function sendMailPix(){

        $order      = new Order($this->id_order);
        $customer   = new Customer($order->id_customer);
        $module     = new agyapay;
        
        $mail_data = [
            '{pix_expiration_date}' => Tools::displayDate($this->pix_expiration_date, null, true),
            '{customer_name}' => $customer->firstname." ".$customer->lastname,
            '{firstname}' => $customer->firstname,
            '{pix_qrcode_url}' => $this->pix_qrcode_url,
            '{pix_qrcode_hash}' => $this->pix_qrcode_hash,
            '{token}' => Context::getContext()->link->getModuleLink($module->name, 'pix',['hash' => $this->remote_token]),
            '{value_invoiced}' => version_compare(_PS_VERSION_, '9', '<') ? 
                Tools::displayPrice($this->value_invoiced) :
                (new PriceFormatter)->format($this->value_invoiced),
            '{order_id}' => $order->id,
            '{order_reference}' => $order->reference,
            '{order_date}' => Tools::displayDate($order->date_add),
            '{order_value}' => version_compare(_PS_VERSION_, '9', '<') ?
                Tools::displayPrice($order->total_paid) :
                (new PriceFormatter)->format($order->total_paid)
        ];
        $module = new agyapay;

        $r = Mail::Send(
            $order->id_lang,
            'create_pix',
            'Pagamento via PIX',
            $mail_data,
            $customer->email,
            null,
            null,
            null,
            null,
            null,
            _PS_MODULE_DIR_ . $module->name . '/mails/',
            false,
            $order->id_shop
        );

        return $r;
    }

}
