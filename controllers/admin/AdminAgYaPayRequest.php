<?php

class AdminAgYaPayRequestController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap        = true;
        $this->table            = 'agyapay_request';
        $this->className        = 'AgYaPayRequest';
        $this->identifier       = 'id_agyapay_request';
        $this->list_no_link     = true;
        $this->_defaultOrderBy  = 'id_agyapay_request';
        $this->_defaultOrderWay = 'DESC';


        parent::__construct();
		$this->module->prepareNotifications();

        $this->fields_list = [
            'id_agyapay_request' => [
                'title' => 'ID',
                'align' => 'center',
                'type' => 'int',
                'class' => 'fixed-width-xs',
            ],
            'time_spent' => [
                'title' => 'Tempo Gasto',
                'type' => 'text',
                'suffix' => 'ms'
            ],
            'http_code' => [
                'title' => 'Código HTTP',
                'type' => 'int',
                'class' => 'fixed-width-md'
            ],
            'method' => [
                'title' => 'Método',
                'type' => 'text',
                'class' => 'fixed-width-md'
            ],
            'endpoint' => [
                'title' => 'URL',
                'type' => 'text'
            ],
            'endpoint' => [
                'title' => 'URL',
                'type' => 'text'
            ],
            'date_add' => [
                'title' => 'Data',
                'type' => 'datetime'
            ]
        ];

        $this->actions = ['view'];
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

        if (Tools::getIsSet('view' . $this->table)) {
            $request = $this->loadObject();
            $request->response = json_decode($request->response);
            
            $html  = $this->content;

            //contéudo geral da ação VER
            $tpl = $this->context->smarty->createTemplate(_PS_MODULE_DIR_ . $this->module->name.'/views/templates/admin/ag_yapay_request/view.tpl');
            $tpl->assign(['obj' => $request]);
            $html .= $tpl->fetch();

            $this->content = $html;
            $this->context->smarty->assign(['content' => $html]);

            return;
        }
    }
}