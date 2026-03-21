<?php

class AdminAgYapayTransactionController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap  = true;
        $this->table      = 'agyapay_transaction';
        $this->identifier = 'id_agyapay_transaction';
        $this->className  = 'AgYapayTransaction';
        $this->noLink = true;
        $this->list_no_link = true;

        $this->_orderBy = 'id_agyapay_transaction';
        $this->_orderWay = 'DESC';

        parent::__construct();

        $this->_select .= 'CONCAT(c.firstname, " ", c.lastname) customer_name, ';

        $db_prefix = _DB_PREFIX_;
        $this->_join .= "LEFT JOIN {$db_prefix}orders o ON o.id_order = a.id_order ";
        $this->_join .= "LEFT JOIN {$db_prefix}customer c ON c.id_customer = o.id_customer";

        $this->fields_list = [
            'id_agyapay_transaction' => [
                'title' => 'ID',
                'type' => 'int',
                'class' => 'center fixed-width-xs'
            ],
            'id_order' => [
                'title' => 'Pedido',
                'type'  => 'int',
                'class' => 'center fixed-width-xs'
            ],
            'remote_id' => [
                'title' => 'Transação',
                'class' => 'center fixed-width-md',
                'hint' => 'ID Interno da Vindi'
            ],
            'customer_name' => [
                'title' => 'Cliente',
                'havingFilter' => true
            ],
            'type' => [
                'title' => 'Tipo da Transação',
                'class' => 'center fixed-width-xs',
                'type' => 'select',
                'list' => [
                    '0' => 'Boleto Bancário',
                    '1' => 'Cartão de Crédito',
                    '2' => 'Débito em Conta',
                ],
                'filter_key' => 'a!type'
            ],
            'status' => [
                'title' => 'Estado',
            ],
            'deprecated' => array(
                'title' => 'Expirada',
                'type' => 'bool',
                'active' => 'deprecated',
                'class' => 'fixed-width-sm center',
                'hint' => 'Essa transação não será mais monitorada'
            ),
            'expiration_date' => [
                'title' => 'Vencimento',
                'type' => 'date',
                'class' => 'fixed-width-sm center',
                'hint' => 'Apenas para Boleto Bancário'
            ],
            'value_paid' => [
                'title' => 'Valor Total Cobrado',
                'type' => 'price',
                'class' => 'fixed-width-sm',
                'hint' => 'Incluindo juros'
            ],
            'value_invoiced' => [
                'title' => 'Valor',
                'type' => 'price',
                'class' => 'fixed-width-sm'
            ],
            'pix_qrcode_url' => [
                'title' => 'PIX URL',
                'type' => 'text',
            ],
            'pix_qrcode_hash' => [
                'title' => 'PIX Texto',
                'type' => 'text',
            ],
            'url_payment' => [
                'title' => 'Link para Pagamento',
                'class' => 'fixed-width-md'
            ],
            'date_add' => [
                'title' => 'Data da Transação',
                'type' => 'datetime',
                'class' => 'fixed-width-sm'
            ],
            'date_upd' => [
                'title' => 'Atualizada Em',
                'type' => 'datetime',
                'class' => 'fixed-width-sm'
            ]
        ];

        $this->actions = ['refresh', 'cancel'];
        
    }
    
    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();

        $this->page_header_toolbar_btn['cogs'] = [
            'href' => $this->context->link->getAdminLink('AdminModules') . '&configure=agyapay',
            'desc' => 'Configurar'
        ];
    }

    public function initContent()
    {
        parent::initContent();

        if (Tools::getIsSet('deprecated' . $this->table)) {
            $obj = $this->loadObject();

            $obj->deprecated = !$obj->deprecated;
            $obj->update();

            Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token . '&conf=4');
        } elseif (Tools::getIsSet('refresh')) {
            try {
                /** @var AgYapayTransaction */
                $obj = $this->loadObject();
                agyapay::updateLocalTransaction($obj);

                if (!$obj->deprecated) {
                    $obj->updatePsStatus();
                }
                
            } catch (Exception $e) {
                $this->errors[] = $e->getMessage();
            }
        } elseif (Tools::getIsSet('cancel')) {
            try {
                $obj = $this->loadObject();
                $this->module->cancelTransaction($obj->remote_id);
                $obj->status_id = 7;
                
            } catch (Exception $e) {
                $this->errors[] = $e->getMessage();
            }
        }
    }

    public function getList($id_lang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $id_lang_shop = null)
    {

        parent::getList($id_lang, $orderBy, $orderWay, $start, $limit, $this->context->shop->id);
        $nb = count($this->_list);

        for ($i = 0; $i < $nb; $i++) {
            switch ($this->_list[$i]['type']) {
                case 0:
                    $this->_list[$i]['type'] = 'Boleto Bancário';
                    break;
                case 1:
                    $this->_list[$i]['type'] = 'Cartão de Crédito';
                    break;
                case 2:
                    $this->_list[$i]['type'] = 'Débito em Conta';
                    break;
            }
        }
    }

    public function ajaxProcessCreateTicket()
    {
        $id_order = Tools::getValue('id_order');
        $order = new Order($id_order);

        try {
            $ticket_link = $this->module->createTicketForOrder(
                $order,
                [
                    'order_value'     => Tools::getValue('ticket_value'),
                    'expiration_date' => DateTime::createFromFormat('Y-m-d', Tools::getValue('expiration_date'))
                ]
            );

            echo json_encode(['ticket_url' => $ticket_link]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

        exit();
    }

    public function ajaxProcessSendTicket()
    {
        $id_order = Tools::getValue('id_order');
        $order = new Order($id_order);

        try {
            $this->module->sendTicketMailForOrder($order);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

        exit();
    }

    public function ajaxProcessRenderGenerateTicketModal()
    {
        $id_order = Tools::getValue('id_order');

        echo json_encode([
            'success' => true,
            'modal' => $this->module->renderGenerateTicketModal($id_order)
        ]);

        exit();
    }

    //************************************ Ações Individuais **********************/
    public function displayRefreshLink($token, $id)
    {
        $this->context->smarty->assign([
            'url' => self::$currentIndex . '&token=' . $this->token . "&refresh&{$this->identifier}={$id}"
        ]);

        $tpl = $this->createTemplate('helpers/list/refresh.tpl');
        return $tpl->fetch();
    }

    public function displayCancelLink($token, $id)
    {
        $this->context->smarty->assign([
            'url' => self::$currentIndex . '&token=' . $this->token . "&cancel&{$this->identifier}={$id}"
        ]);

        $obj = new AgYapayTransaction($id);
        if ($obj->status_id != 6) {
            return;
        }

        $tpl = $this->createTemplate('helpers/list/cancel.tpl');
        return $tpl->fetch();
    }
}
