<?php

use AGTI\Yapay\Entity\AgyapaySellerAccount;
use AGTI\Yapay\Entity\AgyapaySellerAccountCarrier;
use AGTI\Yapay\Entity\Carrier;
use AGTI\Yapay\Infrastructure\Api\Local\Account\AddCarrier\AddCarrierToAccountArgs;
use AGTI\Yapay\Infrastructure\Api\Local\Account\Delete\DeleteAccountArgs;
use AGTI\Yapay\Infrastructure\Api\Local\Account\Delete\DeleteAccountResponseSuccess;
use AGTI\Yapay\Infrastructure\Api\Local\Account\Get\ListAccountResponseError;
use AGTI\Yapay\Infrastructure\Api\Local\Account\Get\ListAccountResponseSuccess;
use AGTI\Yapay\Infrastructure\Api\Local\Account\AddCarrier\AddCarrierToAccountResponseError;
use AGTI\Yapay\Infrastructure\Api\Local\Account\AddCarrier\AddCarrierToAccountResponseSuccess;
use AGTI\Yapay\Infrastructure\Api\Local\Account\RemoveCarrier\RemoveCarrierFromAccountArgs;
use AGTI\Yapay\Infrastructure\Api\Local\Account\RemoveCarrier\RemoveCarrierFromAccountResponseError;
use AGTI\Yapay\Infrastructure\Api\Local\Account\RemoveCarrier\RemoveCarrierFromAccountResponseSuccess;
use AGTI\Yapay\Infrastructure\Api\Local\Account\Save\SaveAccountArgs;
use AGTI\Yapay\Infrastructure\Api\Local\Account\Save\SaveAccountResponseError;
use AGTI\Yapay\Infrastructure\Api\Local\Account\Save\SaveAccountResponseSuccess;
use AGTI\Yapay\Infrastructure\Api\Local\Carrier\Get\ListCarrierResponseError;
use AGTI\Yapay\Infrastructure\Api\Local\Carrier\Get\ListCarrierResponseSuccess;
use AgYaPaySellerAccountCarrier as GlobalAgYaPaySellerAccountCarrier;
use Doctrine\Common\Collections\Criteria;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Core\Action\ActionsBarButton;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

require_once _PS_MODULE_DIR_ . 'agcliente/lib/AgPaymentModule.php';
require_once _PS_MODULE_DIR_ . 'agyapay/vendor/autoload.php';

class BaseAgYapay extends AgPaymentModule
{
    private const VINDI_CARRIER_MODE_CHECKOUT = 1;
    private const VINDI_CARRIER_MODE_VIRTUAL = 2;
    private const VINDI_CARRIER_MODE_PRESET = 3;

    protected $hooks = [
        'displayHeader',
        'displayBackOfficeHeader',
        'paymentOptions',
        'displayPaymentTop',
        'payment',
        'orderConfirmation',
        'displayOrderDetail',
        'displayAdminOrderContentOrder',
        'actionGetAdminOrderButtons',
        'displayCustomerAccount'
    ];

    //menus do administrativo
    protected $main_tab = 'AdminParentPayment';

    protected $tabs = array(
        array(
            "name"      => "Vindi",
            "className" => "AdminAgYapayConfig",
            "active"    => 1,
        ),
        array(
            "name"      => "vazio",
            "className" => "AdminAgYapayTransactionn",
            "active"    => 0,
            "childs"    => array(
                array(
                    "name"      => "Transações",
                    "className" => "AdminAgYapayTransaction",
                    "active"    => 1,
                    ),
                array(
                    "name"      => "Requisições API",
                    "className" => "AdminAgYaPayRequest",
                    "active"    => 1
                ),
                array(
                    "name"      => "Vindi",
                    "className" => "AdminAgYapayConfig",
                    "active"    => 1
                ),
            )
        )
    );

    protected $workers = [
        //atualiza o estado das transações no bd local caso algum webhook falhe
        [
            'name' => 'updatenotifications',
            'controller' => 'updatetransactions',
            'delay' => 3600
        ],
        //se houver alguma transação com id_order=0, atualiza o id do pedido
        [
            'name' => 'verifytransactionsidzero',
            'controller' => 'verifytransactionsidzero',
            'delay' => 1800
        ],
        //atualiza o id da transação na tabela de pagamentos do PS
        [
            'name' => 'updatepspayments',
            'controller' => 'updatepspayments',
            'delay' => 300
        ],
        //atualiza o estado dos pedidos do PS
        [
            'name' => 'MailPaymentPending',
            'controller' => 'MailPaymentPending',
            'delay' => 86400
        ],
        [
            'name' => 'updateTransactionStatus',
            'controller' => 'updateTransactionStatus',
            'delay' => 86400
        ],
        [
            'name' => 'updateorders',
            'controller' => 'updateorders',
            'delay' => 900
        ],
        [
            'name' => 'clearrequests',
            'controller' => 'clearrequests',
            'delay' => 43200 //12 em 12 horas
        ],
        [
            'name' => 'sendTrackingNumber',
            'controller' => 'sendTrackingNumber',
            'delay' => 900 //15 em 15 minutos
        ]
    ];


    public function __construct()
    {
        $this->name     = 'agyapay';
        $this->tab      = 'payments_gateways';
        $this->version  = '2.7.2';
        $this->author   = 'AGTI';

        $this->bootstrap = true;
        
        parent::__construct();

        $this->displayName = 'Vindi Transparente';
        $this->description = 'Integra a sua loja PrestaShop com o intermediador de pagamentos da Vindi.';

        $this->loadMappings();
    }

    public function install()
    {
        $r = parent::install();

        if (!$r) {
            return false;
        }

        try {
            $db_prefix = _DB_PREFIX_;

            $sql = "ALTER TABLE {$db_prefix}order_carrier ADD COLUMN agyapay_sent_tracking_number boolean";
            Db::getInstance()->execute($sql);
        } catch (Exception $e) {
        }
        
        return true;
    }

    public function resetConfig()
    {
        parent::resetConfig();

        Configuration::updateValue('AGYAPAY_TICKET_TEXT', 'Pagar por Boleto Bancário');
        Configuration::updateValue('AGYAPAY_CREDIT_CARD_TEXT', 'Pagar no Cartão de Crédito');

        Configuration::updateValue('AGYAPAY_TICKET_ACTIVE', 1);
        Configuration::updateValue('AGYAPAY_CREDIT_CARD_ACTIVE', 1);

        if (Configuration::hasKey('AGYAPAY_ENABLE_SANDBOX')) {
            Configuration::updateValue('AGYAPAY_ENABLE_SANDBOX', 1);
        }

        Configuration::updateValue('AGYAPAY_RESELLER_TOKEN', 'a1f7e76cf91c32c');

        Configuration::updateValue('AGYAPAY_MAX_INTALLMENTS', 12);
        Configuration::updateValue('AGYAPAY_SANDBOX_ACCOUNT_TOKEN', '30b70442f14387c');

        Configuration::updateValue('AGYAPAY_STATUS_4', 1);
        Configuration::updateValue('AGYAPAY_STATUS_5', 1);
        Configuration::updateValue('AGYAPAY_STATUS_6', 2);
        Configuration::updateValue('AGYAPAY_STATUS_7', 6);
        Configuration::updateValue('AGYAPAY_STATUS_24', 1);
        Configuration::updateValue('AGYAPAY_STATUS_87', 1);
        Configuration::updateValue('AGYAPAY_STATUS_89', 6);

        Configuration::updateValue('AGYAPAY_VINDI_CARRIER_MODE', self::VINDI_CARRIER_MODE_CHECKOUT);
        Configuration::updateValue('AGYAPAY_VINDI_CARRIER_ID', 0);

        $this->loadMappings();
        if (Module::isInstalled('agcustomers') && Module::isEnabled('agcustomers')) {
            $this->cpf_mapping->mapsTo('cpf');
            $this->cnpj_mapping->mapsTo('cnpj');
            $this->social_name_mapping->mapsTo('company_name');
            $this->address_number_mapping->mapsTo('number');
        } elseif (Module::isInstalled('djtalbrazilianregister') && Module::isEnabled('djtalbrazilianregister')) {
            $this->cpf_mapping->mapsTo('djtalbrazilianregister');
            $this->cnpj_mapping->mapsTo('djtalbrazilianregister');
            $this->social_name_mapping->mapsTo('');
            $this->address_number_mapping->mapsTo('djtalbrazilianregister');
        } elseif (Module::isInstalled('ldbrazilianregister') && Module::isEnabled('ldbrazilianregister')) {
            $this->cpf_mapping->mapsTo('ldbrazilianregister');
            $this->cnpj_mapping->mapsTo('ldbrazilianregister');
            $this->social_name_mapping->mapsTo('');
            $this->address_number_mapping->mapsTo('ldbrazilianregister');
        } elseif (Module::isInstalled('psmodcpf') && Module::isEnabled('psmodcpf')) {
            $this->cpf_mapping->mapsTo('psmodcpf');
            $this->cnpj_mapping->mapsTo('psmodcpf');
            $this->social_name_mapping->mapsTo('');
            $this->address_number_mapping->mapsTo('psmodcpf');
        }
    }

    public function getContent()
    {
        $this->context->controller->addJs('https://cdnjs.cloudflare.com/ajax/libs/axios/1.1.2/axios.min.js');

        if (Tools::getIsSet('api')) {
            $method = 'api' . ucfirst(Tools::getValue('api'));
            if (method_exists($this, $method)) {
                $this->{$method}();
                exit();
            }
            http_response_code(404);
            exit();
        }

        $this->context->controller->addJs($this->_path . 'views/js/configuration.js');
        $this->context->controller->addCss($this->_path . 'views/css/configuration.css');

        $this->context->controller->addJs("https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js");
        $this->context->controller->addJs('https://cdnjs.cloudflare.com/ajax/libs/axios/0.21.1/axios.min.js');

        $this->context->controller->addJs(_MODULE_DIR_ . "agcliente/views/js/component/grid/table.js");
        $this->context->controller->addJs(_MODULE_DIR_ . "agcliente/views/js/component/grid/header.js");
        $this->context->controller->addJs(_MODULE_DIR_ . "agcliente/views/js/component/grid/body.js");
        $this->context->controller->addJs(_MODULE_DIR_ . "agcliente/views/js/component/form/swap.vue.js");
        $this->context->controller->addJs(_MODULE_DIR_ . "agcliente/views/js/component/panel/panel.js");
        $this->context->controller->addJs(_MODULE_DIR_ . "agcliente/views/js/component/dropdown/dropdown.js");
        $this->context->controller->addJs(_MODULE_DIR_ . "agcliente/views/js/component/notification/alert.vue.js");
        $this->context->controller->addJs(_MODULE_DIR_ . "agcliente/views/js/component/loading/loading.vue.js");

        $this->context->controller->addJs($this->_path . 'views/js/multipleaccounts/multipleaccounts.vue.js');
        $this->context->controller->addJs($this->_path . 'views/js/multipleaccounts/app.vue.js');
        $this->context->controller->addJs($this->_path . 'views/js/multipleaccounts/form.vue.js');
        $this->context->controller->addJs($this->_path . 'views/js/multipleaccounts/list.vue.js');
        $this->context->controller->addJs($this->_path . 'views/js/multipleaccounts/rowActions.vue.js');

        $this->context->controller->addCss([_MODULE_DIR_ . "agcliente/views/css/component/grid.css"]);
        $this->context->controller->addCss([$this->_path . "views/css/multipleaccounts.css"]);
        
        return $this->renderConfigForm();
    }

    /* public function renderAuthForm()
    {
        if (Tools::isSubmit('agyapay-config-auth')) {
            Configuration::updateValue('AGYAPAY_SANDBOX_ACCOUNT_TOKEN', Tools::getValue('agyapay_sandbox_account_token'));
            Configuration::updateValue('AGYAPAY_ENABLE_SANDBOX', Tools::getValue('agyapay_enable_sandbox'));
            

            Configuration::updateValue('AGYAPAY_ACCOUNT_TOKEN', Tools::getValue('agyapay_account_token'));
        }

        $helper = $this->generateDefaultHelperForm();
        
        $panels = [];
        $panels[0]['form'] = [
            'legend' => [
                'title' => 'Produção'
            ],
            'input' => [
                [
                    'name' => 'agyapay_account_token',
                    'label' => 'Token da Conta',
                    'col' => 3,
                    'type' => 'text',
                    'desc' => "Não possui cadastro na Vindi? Cadastre-se <a href='https://pagamentos.vindi.com.br/parceria/indique/?parceiro=agti' target='_blank'>aqui</a>."
                ],
            ],
            'submit' => [
                'name' => 'agyapay-config-auth',
                'title' => 'Salvar'
            ]
        ];

        $panels[1]['form'] = [
            'legend' => [
                'title' => 'Modo Testes (Sandbox)'
            ],
            'description' => "Se você ativar o modo testes, as transações não gerarão cobrança no cartão de crédito, e os boletos e chaves PIX geradas não serão aceitas pelos aplicativos dos bancos.",
            'input' => [
                [
                    'name' => 'agyapay_sandbox_account_token',
                    'label' => 'Token da Conta',
                    'col' => 3,
                    'type' => 'text'
                ],
                [
                    'name' => 'agyapay_enable_sandbox',
                    'label' => 'Ativar Sandbox',
                    'type' => 'switch',
                    'values' => array(
                        array(
                            'id'    => 'agyapay_enable_sandbox_on',
                            'value' => 1,
                            'label' => 'Sim',
                        ),
                        array(
                            'id'    => 'agyapay_enable_sandbox_off',
                            'value' => 0,
                            'label' => 'Não',
                        ),
                    ),
                ],
            ],
            'submit' => [
                'name' => 'agyapay-config-auth',
                'title' => 'Salvar'
            ]
        ];

        $helper->fields_value['agyapay_sandbox_account_token'] = Configuration::get('AGYAPAY_SANDBOX_ACCOUNT_TOKEN');
        $helper->fields_value['agyapay_enable_sandbox'] = Configuration::get('AGYAPAY_ENABLE_SANDBOX');

        $helper->fields_value['agyapay_account_token'] = Configuration::get('AGYAPAY_ACCOUNT_TOKEN');

        $existingPanels = $helper->generateForm($panels);

        $multipleaccounts = $this->display($this->_path, 'multipleaccounts.tpl');

        return $multipleaccounts . $existingPanels;
    } */

    public function renderTicketForm()
    {
        if (Tools::isSubmit('agyapay-config-ticket')) {
            Configuration::updateValue('AGYAPAY_TICKET_ACTIVE', Tools::getValue('agyapay_ticket_active'));
            Configuration::updateValue('AGYAPAY_TICKET_TEXT', Tools::getValue('agyapay_ticket_text'));
            Configuration::updateValue('AGYAPAY_TICKET_EXPIRATION_DAYS', Tools::getValue('agyapay_ticket_expiration_days'));

            Configuration::updateValue('AGYAPAY_TICKET_DISCOUNT', Tools::getValue('agyapay_ticket_discount'));
            Configuration::updateValue('AGYAPAY_TICKET_CART_RULE', Tools::getValue('agyapay_ticket_cart_rule'));
            Configuration::updateValue('AGYAPAY_TICKET_MIN_VALUE', Tools::getValue('agyapay_ticket_min_value'));
        }

        $helper = $this->generateDefaultHelperForm();
        $panels[1]['form'] = [
            'legend' => [
                'title' => ''
            ],
            'input' => [
                [
                    'name' => 'agyapay_ticket_active',
                    'label' => 'Ativar Boleto Bancário Vindi',
                    'type' => 'switch',
                    'values' => array(
                        array(
                            'id'    => 'agyapay_ticket_enabled_on',
                            'value' => 1,
                            'label' => 'Sim',
                        ),
                        array(
                            'id'    => 'agyapay_ticket_enabled_off',
                            'value' => 0,
                            'label' => 'Não',
                        ),
                    ),
                ],
                [
                    'name' => 'agyapay_ticket_text',
                    'label' => 'Texto a ser exibido on checkout',
                    'col' => 3,
                    'type' => 'text',
                    'desc' => 'Esse texto será apresentado ao seu cliente no checkout quando as formas de pagamento da sua loja forem exibidas.'
                ],
                [
                    'name' => 'agyapay_ticket_expiration_days',
                    'label' => 'Dias corridos para o vencimento do boleto',
                    'col' => 3,
                    'type' => 'text',
                    'hint' => 'Padrão: 3'
                ],
                [
                    'name' => 'agyapay_ticket_discount',
                    'label' => 'Desconto',
                    'col' => 3,
                    'type' => 'text',
                    'suffix' => '%',
                    'desc' => 'Se você configurar um percentual de desconto, então todas as compras por Boleto Bancário gerarão um cupom de desconto automático com o valor do desconto a ser aplicado em cada pedido.'
                ],
                [
                    'name' => 'agyapay_ticket_cart_rule',
                    'label' => 'ID da Regra de Preço',
                    'desc' => 'Se você especificar uma regra de preço, então o campo de Desconto percentual será ignorado, e o desconto aplicado será o que for configurado na regra de preço escolhida. OBS. Utilize apenas desconto percentual ou com valor fixo, regras mais complexas poderão levar a comportamentos inesperados. Não aplique restrições de transportadoras, clientes ou outras mais a essa regra de preço.',
                    'col' => 3,
                    'type' => 'text',
                ],
                [
                    'name' => 'agyapay_ticket_min_value',
                    'label' => 'Valor Mínimo',
                    'col' => 3,
                    'prefix' => 'R$',
                    'hint' => 'Ex: R$ 50,00',
                    'type' => 'text'
                ],
            ],
            'submit' => [
                'name' => 'agyapay-config-ticket',
                'title' => 'Salvar'
            ]
        ];

        $helper->fields_value['agyapay_ticket_active'] = Configuration::get('AGYAPAY_TICKET_ACTIVE');
        $helper->fields_value['agyapay_ticket_text'] = Configuration::get('AGYAPAY_TICKET_TEXT');
        $helper->fields_value['agyapay_ticket_expiration_days'] = Configuration::get('AGYAPAY_TICKET_EXPIRATION_DAYS');
        $helper->fields_value['agyapay_ticket_discount'] = Configuration::get('AGYAPAY_TICKET_DISCOUNT');
        $helper->fields_value['agyapay_ticket_cart_rule'] = Configuration::get('AGYAPAY_TICKET_CART_RULE');
        $helper->fields_value['agyapay_ticket_min_value'] = Configuration::get('AGYAPAY_TICKET_MIN_VALUE');

        return $helper->generateForm($panels);
    }

    public function renderCreditCardForm()
    {
        if (Tools::isSubmit('agyapay-config-credit-card')) {
            Configuration::updateValue('AGYAPAY_CREDIT_CARD_ACTIVE', Tools::getValue('agyapay_credit_card_active'));
            Configuration::updateValue('AGYAPAY_CREDIT_CARD_TEXT', Tools::getValue('agyapay_credit_card_text'));

            Configuration::updateValue('AGYAPAY_MIN_INSTALLMENT_VALUE', Tools::getValue('agyapay_min_installment_value'));
            Configuration::updateValue('AGYAPAY_MAX_INSTALLMENTS', Tools::getValue('agyapay_max_installments'));
            Configuration::updateValue('AGYAPAY_CREDIT_CARD_INSTALLMENT_METHOD', Tools::getValue('agyapay_credit_card_installment_method'));
            Configuration::updateValue('AGYAPAY_CREDIT_CARD_INSTALLMENTS_NO_INTEREST', Tools::getValue('agyapay_credit_card_installments_no_interest'));

            Configuration::updateValue('AGYAPAY_CREDIT_ENABLE_VAULT', Tools::getValue('agyapay_credit_enable_vault'));
            Configuration::updateValue('AGYAPAY_STATUS_FOR_AUTO_BILL', Tools::getValue('agyapay_status_for_auto_bill'));

            for ($i=1; $i<=12; $i++) {
                Configuration::updateValue("AGYAPAY_CREDIT_CARD_INTEREST_RATE_{$i}", Tools::getValue("agyapay_credit_card_interest_rate_{$i}"));
            }
        }

        $helper = $this->generateDefaultHelperForm();
        $statuses = OrderState::getOrderStates($this->context->language->id);
        
        $statuses = array_merge([['id_order_state' => 0, 'name' => 'Desativar Cobrança Automática']], $statuses);

        
        $panels[1]['form'] = [
            'legend' => [
                'title' => ''
            ],
            'input' => [
                [
                    'name' => 'agyapay_credit_card_active',
                    'label' => 'Ativar Cartão de Crédito Vindi',
                    'type' => 'switch',
                    'values' => array(
                        array(
                            'id'    => 'agyapay_credit_card_active_on',
                            'value' => 1,
                            'label' => 'Sim',
                        ),
                        array(
                            'id'    => 'agyapay_credit_card_active_off',
                            'value' => 0,
                            'label' => 'Não',
                        ),
                    ),
                ],
                [
                    'name' => 'agyapay_credit_card_text',
                    'label' => 'Texto a ser exibido no checkout',
                    'col' => 3,
                    'type' => 'text',
                    'desc' => 'Esse texto será apresentado ao seu cliente no checkout quando as formas de pagamento da sua loja forem exibidas.'
                ],
                [
                    'type'   => 'text',
                    'label'  => 'Número de Vezes sem juros',
                    'name'   => 'agyapay_credit_card_installments_no_interest',
                    'id'     => 'agyapay_credit_card_installments_no_interest',
                    'class'  => 'form-control',
                    'col' => 1
                ],
                [
                    'name' => 'agyapay_max_installments',
                    'label' => 'Máximo de Parcelas',
                    'type' => 'select',
                    'options' => [
                        'id' => 'id',
                        'name' => 'id',
                        'query' => [
                            ['id' => 1],
                            ['id' => 2],
                            ['id' => 3],
                            ['id' => 4],
                            ['id' => 5],
                            ['id' => 6],
                            ['id' => 7],
                            ['id' => 8],
                            ['id' => 9],
                            ['id' => 10],
                            ['id' => 11],
                            ['id' => 12]
                        ]
                    ]
                ],
                [
                    'name' => 'agyapay_min_installment_value',
                    'label' => 'Valor Mínimo da Parcela',
                    'type' => 'text',
                    'prefix' => 'R$',
                    'col' => 2
                ],
                [
                    'name' => 'agyapay_credit_enable_vault',
                    'label' => 'Ativar Cofre de Cartões',
                    'desc' => 'Esse recurso permite que os seus clientes recomprem em sua loja sem a necessidade de preencher novamente os dados do cartão de crédito.',
                    'type' => 'switch',
                    'values' => array(
                        array(
                            'id'    => 'agyapay_credit_enable_vault_active_on',
                            'value' => 1,
                            'label' => 'Sim',
                        ),
                        array(
                            'id'    => 'agyapay_credit_enable_vault_active_off',
                            'value' => 0,
                            'label' => 'Não',
                        ),
                    ),
                ],
                [
                    'label' => 'Estado para Cobrança Automática',
                    'name' => 'agyapay_status_for_auto_bill',
                    'desc' => 'Se o cofre estiver ativado, o módulo tentará realizar automaticamente o pagamento dos pedidos que estiverem nesse estado, uma vez ao dia.',
                    'type' => 'select',
                    'options' => [
                        'id' => 'id_order_state',
                        'name' => 'name',
                        'query' => $statuses
                    ]
                ],
                [
                    'type' => 'radio',
                    'label' => 'Método de Cálculo do Parcelamento',
                    'name' => 'agyapay_credit_card_installment_method',
                    'desc' => 'O cálculo via Webserver Vindi requer que o número do cartão de crédito seja informado antes do parcelamento ser calculado. O cálculo local requer que você especifique manualmente as tarifas de juros a serem utilizadas.<br/> Se selecionado o Calculo Local, configure a sua conta Vindi para parcelar todas as compras SEM JUROS, ou a Vindi aplicará os juros configurados em sua conta após os juros calculados na sua loja.',
                    'values' => [
                        [
                            'id' => 'agyapay_credit_card_installment_method_ws',
                            'value' => 'ws',
                            'label' => 'Webserver Vindi'
                        ],
                        [
                            'id' => 'agyapay_credit_card_installment_method_local',
                            'value' => 'local',
                            'label' => 'Calculo Local'
                        ],
                    ]
                ]
            ],
            'submit' => [
                'name' => 'agyapay-config-credit-card',
                'title' => 'Salvar'
            ]
        ];

        for ($i=1; $i<=12; $i++) {
            $panels[1]['form']['input'][] = [
                'type' => 'text',
                'label' => "Tarifa de juros para {$i}x",
                'name' => "agyapay_credit_card_interest_rate_{$i}",
                'col' => 1,
                'suffix' => '%'
            ];
        }

        $helper->fields_value['agyapay_credit_card_active'] = Configuration::get('AGYAPAY_CREDIT_CARD_ACTIVE');
        $helper->fields_value['agyapay_credit_card_text'] = Configuration::get('AGYAPAY_CREDIT_CARD_TEXT');

        $helper->fields_value['agyapay_min_installment_value'] = Configuration::get('AGYAPAY_MIN_INSTALLMENT_VALUE');
        $helper->fields_value['agyapay_max_installments'] = Configuration::get('AGYAPAY_MAX_INSTALLMENTS');
        $helper->fields_value['agyapay_credit_card_installment_method'] = Configuration::get('AGYAPAY_CREDIT_CARD_INSTALLMENT_METHOD');
        $helper->fields_value['agyapay_credit_card_installments_no_interest'] = Configuration::get('AGYAPAY_CREDIT_CARD_INSTALLMENTS_NO_INTEREST');

        $helper->fields_value['agyapay_credit_enable_vault'] = Configuration::get('AGYAPAY_CREDIT_ENABLE_VAULT');
        $helper->fields_value['agyapay_status_for_auto_bill'] = Configuration::get('AGYAPAY_STATUS_FOR_AUTO_BILL');

         for ($i=1; $i<=12; $i++) {
            $helper->fields_value["agyapay_credit_card_interest_rate_{$i}"] = Configuration::get("AGYAPAY_CREDIT_CARD_INTEREST_RATE_{$i}");
        }

        return $helper->generateForm($panels);
    }


    public function renderPixForm()
    {
        if (Tools::isSubmit('agyapay-config-pix')) {
            Configuration::updateValue('AGYAPAY_PIX_ACTIVE', Tools::getValue('agyapay_pix_active'));
            Configuration::updateValue('AGYAPAY_PIX_TEXT', Tools::getValue('agyapay_pix_text'));

            Configuration::updateValue('AGYAPAY_PIX_DISCOUNT', Tools::getValue('agyapay_pix_discount'));
            Configuration::updateValue('AGYAPAY_PIX_CART_RULE', Tools::getValue('agyapay_pix_cart_rule'));
            Configuration::updateValue('AGYAPAY_PIX_SEND_EMAIL', Tools::getValue('agyapay_pix_send_email'));

            if (Tools::getValue('agyapay_pix_min_value') < 2) {
                $this->context->controller->errors[] = "O valor mínimo deve ser maior ou igual a R$ 2,00.";
            } else {
                Configuration::updateValue('AGYAPAY_PIX_MIN_VALUE', Tools::getValue('agyapay_pix_min_value'));
            }
        }

        $helper = $this->generateDefaultHelperForm();
        $panels[1]['form'] = [
            'legend' => [
                'title' => ''
            ],
            'input' => [
                [
                    'name' => 'agyapay_pix_active',
                    'label' => 'Ativar PIX Vindi',
                    'type' => 'switch',
                    'values' => array(
                        array(
                            'id'    => 'agyapay_pix_enabled_on',
                            'value' => 1,
                            'label' => 'Sim',
                        ),
                        array(
                            'id'    => 'agyapay_pix_enabled_off',
                            'value' => 0,
                            'label' => 'Não',
                        ),
                    ),
                ],
                [
                    'name' => 'agyapay_pix_text',
                    'label' => 'Texto a ser exibido on checkout',
                    'col' => 3,
                    'type' => 'text',
                    'desc' => 'Esse texto será apresentado ao seu cliente no checkout quando as formas de pagamento da sua loja forem exibidas.'
                ],
                [
                    'name' => 'agyapay_pix_discount',
                    'label' => 'Desconto',
                    'col' => 3,
                    'type' => 'text',
                    'suffix' => '%',
                    'desc' => 'Se você configurar um percentual de desconto, então todas as compras por Boleto Bancário gerarão um cupom de desconto automático com o valor do desconto a ser aplicado em cada pedido.'
                ],
                [
                    'name' => 'agyapay_pix_cart_rule',
                    'label' => 'ID da Regra de Preço',
                    'desc' => 'Se você especificar uma regra de preço, então o campo de Desconto percentual será ignorado, e o desconto aplicado será o que for configurado na regra de preço escolhida. OBS. Utilize apenas desconto percentual ou com valor fixo, regras mais complexas poderão levar a comportamentos inesperados. Não aplique restrições de transportadoras, clientes ou outras mais a essa regra de preço.',
                    'col' => 3,
                    'type' => 'text',
                ],
                [
                    'name' => 'agyapay_pix_min_value',
                    'label' => 'Valor Mínimo',
                    'col' => 3,
                    'prefix' => 'R$',
                    'hint' => 'Valor mínimo permitido pela Vindi: R$ 2,00',
                    'type' => 'text'
                ],
                [
                    'name' => 'agyapay_pix_send_email',
                    'label' => 'Enviar dados do PIX por e-mail ao cliente',
                    'type' => 'switch',
                    'values' => array(
                        array(
                            'id'    => 'agyapay_pix_send_email_on',
                            'value' => 1,
                            'label' => 'Sim',
                        ),
                        array(
                            'id'    => 'agyapay_pix_send_email_off',
                            'value' => 0,
                            'label' => 'Não',
                        ),
                    ),
                ],
            ],
            'submit' => [
                'name' => 'agyapay-config-pix',
                'title' => 'Salvar'
            ]
        ];

        $helper->fields_value['agyapay_pix_active'] = Configuration::get('AGYAPAY_PIX_ACTIVE');
        $helper->fields_value['agyapay_pix_text'] = Configuration::get('AGYAPAY_PIX_TEXT');
        $helper->fields_value['agyapay_pix_discount'] = Configuration::get('AGYAPAY_PIX_DISCOUNT');
        $helper->fields_value['agyapay_pix_cart_rule'] = Configuration::get('AGYAPAY_PIX_CART_RULE');
        $helper->fields_value['agyapay_pix_min_value'] = Configuration::get('AGYAPAY_PIX_MIN_VALUE');
        $helper->fields_value['agyapay_pix_send_email'] = Configuration::get('AGYAPAY_PIX_SEND_EMAIL');

        return $helper->generateForm($panels);
    }


    public function renderMappingsTab()
    {
        if (Tools::isSubmit('agyapay-config-mappings')) {
            if (!Module::isEnabled('agcustomers')) {
                $this->getCpfMapping()->mapsTo(Tools::getValue('agyapay_cpf'));
                $this->getCnpjMapping()->mapsTo(Tools::getValue('agyapay_cnpj'));
                $this->getSocialNameMapping()->mapsTo(Tools::getValue('agyapay_social_name'));
                $this->getAddressNumberMapping()->mapsTo(Tools::getValue('agyapay_address_number'));
            }

            Configuration::updateValue('AGYAPAY_STATUS_REFUNDED', Tools::getValue('agyapay_status_refunded'));
            Configuration::updateValue('AGYAPAY_STATUS_4', Tools::getValue('agyapay_status_4'));
            Configuration::updateValue('AGYAPAY_STATUS_5', Tools::getValue('agyapay_status_5'));
            Configuration::updateValue('AGYAPAY_STATUS_6', Tools::getValue('agyapay_status_6'));
            Configuration::updateValue('AGYAPAY_STATUS_7', Tools::getValue('agyapay_status_7'));
            Configuration::updateValue('AGYAPAY_STATUS_24', Tools::getValue('agyapay_status_24'));
            Configuration::updateValue('AGYAPAY_STATUS_87', Tools::getValue('agyapay_status_87'));
            Configuration::updateValue('AGYAPAY_STATUS_89', Tools::getValue('agyapay_status_89'));
        }

        $helper = $this->generateDefaultHelperForm();

        $cpf_columns = $this->getCpfMapping()->getColumnsFromTable();
        $cpf_query = [];
        foreach ($cpf_columns as $key=>$cpf_column) {
            $cpf_query[] = [
                'id' => $key,
                'name' => $cpf_column
            ];
        }

        $cnpj_columns = $this->getCnpjMapping()->getColumnsFromTable();
        $cnpj_query = [];
        foreach ($cnpj_columns as $key=>$cnpj_column) {
            $cnpj_query[] = [
                'id' => $key,
                'name' => $cnpj_column
            ];
        }

        $company_columns = $this->getSocialNameMapping()->getColumnsFromTable();
        $company_query = [];
        foreach ($company_columns as $key=>$company_column) {
            $company_query[] = [
                'id' => $key,
                'name' => $company_column
            ];
        }


        $number_columns = $this->getAddressNumberMapping()->getColumnsFromTable();
        $number_query = [];
        foreach ($number_columns as $key=>$number_column) {
            $number_query[] = [
                'id' => $key,
                'name' => $number_column
            ];
        }

        if (!Module::isEnabled('agcustomers')) {
            $panels[0]['form'] = [
                'legend' => ['title' => 'Campos'],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => 'CPF',
                        'name' => 'agyapay_cpf',
                        'options' => [
                            'id' => 'id',
                            'name' => 'name',
                            'query' => $cpf_query
                        ]
                    ],
                    [
                        'type' => 'select',
                        'label' => 'CNPJ',
                        'name' => 'agyapay_cnpj',
                        'options' => [
                            'id' => 'id',
                            'name' => 'name',
                            'query' => $cnpj_query
                        ]
                    ],
                    [
                        'type' => 'select',
                        'label' => 'Razão Social',
                        'name' => 'agyapay_social_name',
                        'options' => [
                            'id' => 'id',
                            'name' => 'name',
                            'query' => $company_query
                        ]
                    ],
                    [
                        'type' => 'select',
                        'label' => 'Número do Endereço',
                        'name' => 'agyapay_address_number',
                        'options' => [
                            'id' => 'id',
                            'name' => 'name',
                            'query' => $number_query
                        ]
                    ],
                ],
                'submit' => [
                    'name' => 'agyapay-config-mappings',
                    'title' => 'Salvar'
                ]
            ];

            $helper->fields_value['agyapay_cpf'] = $this->getCpfMapping()->getMappedField();
            $helper->fields_value['agyapay_cnpj'] = $this->getCnpjMapping()->getMappedField();
            $helper->fields_value['agyapay_social_name'] = $this->getSocialNameMapping()->getMappedField();
            $helper->fields_value['agyapay_address_number'] = $this->getAddressNumberMapping()->getMappedField();
        }

        $statuses = OrderState::getOrderStates($this->context->language->id);
        array_push($statuses, ['id_order_state' => '0', 'name' => 'Não atualizar o estado do pedido']);

        $panels[1]['form'] = [
            'legend' => ['title' => 'Status'],
            'description' => 'Para cada estado da transação na Vindi, escolha o estado para o qual os seus pedidos devem ser atualizados.',
            'input' => [
                [
                    'label' => 'Aguardando Pagamento',
                    'name' => 'agyapay_status_4',
                    'type' => 'select',
                    'options' => [
                        'id' => 'id_order_state',
                        'name' => 'name',
                        'query' => $statuses
                    ]
                ],
                [
                    'label' => 'Em Processamento',
                    'name' => 'agyapay_status_5',
                    'type' => 'select',
                    'options' => [
                        'id' => 'id_order_state',
                        'name' => 'name',
                        'query' => $statuses
                    ]
                ],
                [
                    'label' => 'Pagamento Aprovado',
                    'name' => 'agyapay_status_6',
                    'type' => 'select',
                    'options' => [
                        'id' => 'id_order_state',
                        'name' => 'name',
                        'query' => $statuses
                    ]
                ],
                [
                    'label' => 'Transação Cancelada após o pagamento',
                    'name' => 'agyapay_status_refunded',
                    'type' => 'select',
                    'options' => [
                        'id' => 'id_order_state',
                        'name' => 'name',
                        'query' => $statuses
                    ]
                ],
                [
                    'label' => 'Transação Cancelada antes do pagamento',
                    'name' => 'agyapay_status_7',
                    'type' => 'select',
                    'options' => [
                        'id' => 'id_order_state',
                        'name' => 'name',
                        'query' => $statuses
                    ]
                ],
                [
                    'label' => 'Pagamento em Contestação',
                    'name' => 'agyapay_status_24',
                    'type' => 'select',
                    'options' => [
                        'id' => 'id_order_state',
                        'name' => 'name',
                        'query' => $statuses
                    ]
                ],
                [
                    'label' => 'Pagamento em Monitoramento',
                    'name' => 'agyapay_status_87',
                    'type' => 'select',
                    'options' => [
                        'id' => 'id_order_state',
                        'name' => 'name',
                        'query' => $statuses
                    ]
                ],
                [
                    'label' => 'Pagamento Recusado',
                    'name' => 'agyapay_status_89',
                    'type' => 'select',
                    'options' => [
                        'id' => 'id_order_state',
                        'name' => 'name',
                        'query' => $statuses
                    ]
                ]
            ],
            'submit' => [
                'name' => 'agyapay-config-mappings',
                'title' => 'Salvar'
            ]
        ];

        

        $helper->fields_value['agyapay_status_refunded'] = Configuration::get('AGYAPAY_STATUS_REFUNDED');
        $helper->fields_value['agyapay_status_4'] = Configuration::get('AGYAPAY_STATUS_4');
        $helper->fields_value['agyapay_status_5'] = Configuration::get('AGYAPAY_STATUS_5');
        $helper->fields_value['agyapay_status_6'] = Configuration::get('AGYAPAY_STATUS_6');
        $helper->fields_value['agyapay_status_7'] = Configuration::get('AGYAPAY_STATUS_7');
        $helper->fields_value['agyapay_status_24'] = Configuration::get('AGYAPAY_STATUS_24');
        $helper->fields_value['agyapay_status_87'] = Configuration::get('AGYAPAY_STATUS_87');
        $helper->fields_value['agyapay_status_89'] = Configuration::get('AGYAPAY_STATUS_89');

        return $helper->generateForm($panels);
    }

    public function renderExtraTab()
    {
        if (Tools::isSubmit('agyapay-config-extra')) {
            Configuration::updateValue('AGYAPAY_ENABLE_SANDBOX', Tools::getValue('AGYAPAY_ENABLE_SANDBOX'));
            Configuration::updateValue('AGYAPAY_PAY_CLOSED_ORDERS', Tools::getValue('agyapay_pay_closed_orders'));
            Configuration::updateValue('AGYAPAY_PAY_EMAIL_ADMINS', Tools::getValue('agyapay_pay_email_admins'));
            Configuration::updateValue('AGYAPAY_VIRTUAL_PRODUCTS', Tools::getValue('agyapay_virtual_products'));
            Configuration::updateValue('AGYAPAY_VINDI_CARRIER_MODE', (int) Tools::getValue('agyapay_vindi_carrier_mode'));
            Configuration::updateValue('AGYAPAY_VINDI_CARRIER_ID', (int) Tools::getValue('agyapay_vindi_carrier_id'));
        }

        $helper = $this->generateDefaultHelperForm();
        $carriers = \Carrier::getCarriers((int) $this->context->language->id, false, false, false, null, \Carrier::ALL_CARRIERS);
        $carrierOptions = [
            [
                'id_carrier' => 0,
                'name' => '-- Selecione --'
            ]
        ];
        foreach ($carriers as $carrier) {
            $carrierOptions[] = [
                'id_carrier' => (int) $carrier['id_carrier'],
                'name' => $carrier['name'],
            ];
        }

        $panels[0]['form'] = [
            'legend' => ['title' => 'Configurações Extras'],
            'input' => [
                [
                    'name' => 'AGYAPAY_ENABLE_SANDBOX',
                    'label' => 'Ativar Sandbox',
                    'desc' => 'Utilize apenas para testes; operações feitas em Sandbox não gerarão nenhuma movimentação financeira.',
                    'type' => 'switch',
                    'values' => array(
                        array(
                            'id'    => 'AGYAPAY_ENABLE_SANDBOX_on',
                            'value' => 1,
                            'label' => 'Sim',
                        ),
                        array(
                            'id'    => 'AGYAPAY_ENABLE_SANDBOX_off',
                            'value' => 0,
                            'label' => 'Não',
                        ),
                    ),
                ],
                [
                    'name' => 'agyapay_pay_closed_orders',
                    'label' => 'Ativar Pagamento de Pedidos Fechados',
                    'desc' => 'Se um pedido for fechado via Boleto Bancário com desconto, o cliente conseguirá pagar o mesmo pedido no cartão, com o mesmo desconto aplicado.',
                    'type' => 'switch',
                    'values' => array(
                        array(
                            'id'    => 'agyapay_pay_closed_orders_on',
                            'value' => 1,
                            'label' => 'Sim',
                        ),
                        array(
                            'id'    => 'agyapay_pay_closed_orders_off',
                            'value' => 0,
                            'label' => 'Não',
                        ),
                    ),
                ],

                [
                    'name' => 'agyapay_pay_email_admins',
                    'label' => 'Enviar informações sobre erros na aprovação de pedidos para:',
                    'desc' => 'Informe um endereço de e-mail por linha',
                    'type' => 'textarea',
                ],


                [
                    'name' => 'agyapay_virtual_products',
                    'label' => 'Permitir pagamento de produtos virtuais',
                    'type' => 'switch',
                    'values' => array(
                        array(
                            'id'    => 'agyapay_virtual_products_on',
                            'value' => 1,
                            'label' => 'Sim',
                        ),
                        array(
                            'id'    => 'agyapay_virtual_products_off',
                            'value' => 0,
                            'label' => 'Não',
                        ),
                    ),
                ],

                [
                    'type' => 'radio',
                    'label' => 'Transportadora a ser utilizada na API Vindi',
                    'name' => 'agyapay_vindi_carrier_mode',
                    'values' => [
                        [
                            'id' => 'agyapay_vindi_carrier_mode_checkout',
                            'value' => self::VINDI_CARRIER_MODE_CHECKOUT,
                            'label' => 'Transportadora selecionada pelo cliente no Checkout'
                        ],
                        [
                            'id' => 'agyapay_vindi_carrier_mode_virtual',
                            'value' => self::VINDI_CARRIER_MODE_VIRTUAL,
                            'label' => 'Assumir produto virtual'
                        ],
                        [
                            'id' => 'agyapay_vindi_carrier_mode_preset',
                            'value' => self::VINDI_CARRIER_MODE_PRESET,
                            'label' => 'Transportadora pré-definida'
                        ],
                    ],
                ],
                [
                    'label' => 'Transportadora pré-definida',
                    'name' => 'agyapay_vindi_carrier_id',
                    'type' => 'select',
                    'desc' => 'Usado quando a opção "Transportadora pré-definida" estiver selecionada.',
                    'form_group_class' => 'form-group-vindi-carrier-preset',
                    'options' => [
                        'id' => 'id_carrier',
                        'name' => 'name',
                        'query' => $carrierOptions,
                    ],
                ],
            ],
            'submit' => [
                'name' => 'agyapay-config-extra',
                'title' => 'Salvar'
            ]
        ];

        $helper->fields_value['AGYAPAY_ENABLE_SANDBOX'] = Configuration::get('AGYAPAY_ENABLE_SANDBOX');
        $helper->fields_value['agyapay_pay_closed_orders'] = Configuration::get('AGYAPAY_PAY_CLOSED_ORDERS');
        $helper->fields_value['agyapay_pay_email_admins'] = Configuration::get('AGYAPAY_PAY_EMAIL_ADMINS');
        $helper->fields_value['agyapay_virtual_products'] = Configuration::get('AGYAPAY_VIRTUAL_PRODUCTS');
        $helper->fields_value['agyapay_vindi_carrier_mode'] = (int) Configuration::get('AGYAPAY_VINDI_CARRIER_MODE') ?: self::VINDI_CARRIER_MODE_CHECKOUT;
        $helper->fields_value['agyapay_vindi_carrier_id'] = (int) Configuration::get('AGYAPAY_VINDI_CARRIER_ID');
        return $helper->generateForm($panels);
    }

    private function resolveVindiCarrierId(int $checkoutCarrierId, bool $checkoutIsVirtual): int
    {
        $mode = (int) Configuration::get('AGYAPAY_VINDI_CARRIER_MODE');
        if (!$mode) {
            $mode = self::VINDI_CARRIER_MODE_CHECKOUT;
        }

        if ($mode === self::VINDI_CARRIER_MODE_VIRTUAL) {
            return -1;
        }

        if ($mode === self::VINDI_CARRIER_MODE_PRESET) {
            $configuredCarrierId = (int) Configuration::get('AGYAPAY_VINDI_CARRIER_ID');
            if ($configuredCarrierId > 0) {
                return $configuredCarrierId;
            }
        }

        return $checkoutIsVirtual ? -1 : $checkoutCarrierId;
    }

    private function getVindiShippingTypeFromCarrierId(int $carrierId): string
    {
        if ($carrierId <= 0) {
            return 'Produto Virtual';
        }

        $carrier = new \Carrier($carrierId);
        return $carrier->name ?: 'Transportadora';
    }

    public function renderMaintanceTab()
    {
        $this->context->controller->addJs(array(
            $this->_path . 'views/js/tab_maintenance.js'
        ));
        
        Media::addJsDef(array(
            'module' => $this->name,
            'token' => Tools::getAdminTokenLite('AdminModules'),
        ));

        $helper = $this->generateDefaultHelperForm();

        $panels = [];

        return $helper->generateForm($panels);
    }

    public function renderConfigForm()
    {
        agcliente::prepareConfigHelpTab($this->name);

        $auth_tab = $this->display($this->_path, 'multipleaccounts.tpl');
        $ticket_tab = $this->renderTicketForm();
        $credit_card_tab = $this->renderCreditCardForm();
        $pix_tab = $this->renderPixForm();
        $maintenance_tab = $this->renderMaintanceTab();
        $mappings_tab = $this->renderMappingsTab();
        $extra_tab = $this->renderExtraTab();

        $is_agshop = Configuration::get('AGSHOP');
        $is_admin = $this->context->employee->id_profile == 1;

        $this->context->smarty->assign([
            'tabs' => [
                'extra' =>  !$is_agshop || $is_admin ?  $extra_tab : '',
                'auth' => $auth_tab,
                'ticket' => $ticket_tab,
                'credit_card' => $credit_card_tab,
                'pix' => $pix_tab,
                'maintenance' => !$is_agshop || $is_admin ? $maintenance_tab : '',
                'mappings' => $mappings_tab
            ],
            'url_transactions' => $this->context->link->getAdminLink('AdminAgYapayTransaction'),
            'url_requests' => $this->context->link->getAdminLink('AdminAgYaPayRequest'),
            'modules_path' => _PS_MODULE_DIR_,
        ]);

        $html = $this->display(_PS_MODULE_DIR_ . $this->name, 'views/templates/admin/configuration.tpl');
        return $html;
    }

    public function checkAccessToken($customer_email='', $id_carrier=0)
    {
        $cache_key = get_called_class() . __FUNCTION__ . $customer_email . '_' . $id_carrier;
        if (!Cache::isStored($cache_key)) {
            if ($customer_email) {
                $account_token = $this->getAccountTokenByEmail($customer_email);
            } else {
                $checkoutCarrierId = (int) $id_carrier;
                $checkoutIsVirtual = false;

                if ($checkoutCarrierId === 0 && isset($this->context->cart) && $this->context->cart) {
                    $checkoutCarrierId = (int) $this->context->cart->id_carrier;
                    $checkoutIsVirtual = (bool) $this->context->cart->isVirtualCart();
                } elseif ($checkoutCarrierId === 0) {
                    $checkoutIsVirtual = true;
                }

                $effectiveCarrierId = $this->resolveVindiCarrierId($checkoutCarrierId, $checkoutIsVirtual);

                $account = \AgYaPaySellerAccountCarrier::getAccountByCarrier($effectiveCarrierId);
                if (Configuration::get('AGYAPAY_ENABLE_SANDBOX')) {
                    $account_token = $account->account_token_sandbox;
                    // $accessToken = $account->access_token_sandbox;
                    // $expiration_date = Configuration::get('AGYAPAY_SANDBOX_ACCESS_TOKEN_EXPIRATION') ? (new DateTime(Configuration::get('AGYAPAY_SANDBOX_ACCESS_TOKEN_EXPIRATION'))) : '';
                } else {
                    $account_token = $account->account_token;
                    // $accessToken = $account->access_token;
                    // $expiration_date = Configuration::get('AGYAPAY_ACCESS_TOKEN_EXPIRATION') ? (new DateTime(Configuration::get('AGYAPAY_ACCESS_TOKEN_EXPIRATION'))) : '';
                }
            }

            // $current_date = new DateTime();

            // if(empty($expiration_date) || ($expiration_date <= $current_date)) {

                $accountCode = $this->refreshAccountCode($account_token, $customer_email == '');
                $accessToken = $this->refreshAccessToken($accountCode, $customer_email == '');
            // }

            Cache::store($cache_key, $accessToken);
        }

        return Cache::retrieve($cache_key);
    }

    public function getAccountTokenByEmail($customer_email)
    {
        if (Configuration::get('AGYAPAY_ENABLE_SANDBOX')) {
            $url ='https://api.intermediador.sandbox.yapay.com.br/api/v1/people/get_by_reseller';
        } else {
            $url ='https://api.intermediador.yapay.com.br/api/v1/people/get_by_reseller';
        }

        $yapay_data = [
            'reseller_token' => $this->getResellerToken(),
            'email' => $customer_email,
            'type_response' => 'J'
        ];
        
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout in seconds

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($yapay_data));

        $return = array();

        $return['body'] = json_decode(curl_exec($ch));
        $return['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        //log da requisição
        $request = new AgYaPayRequest;
        $request->endpoint = $url;
        $request->headers = json_encode([array('Content-Type: application/json')]);
        $request->body = json_encode($yapay_data);
        $request->method = 'POST';
        $request->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $request->response = json_encode($return['body']);
        $request->time_spent = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        $request->add();

        if ($return['http_code'] >= 400) {
            throw new Exception($return['body']);
        }

        return $return['body']->data_response->token_account;
    }

    public function refreshAccountCode($account_token, $save)
    {
        if (Configuration::get('AGYAPAY_ENABLE_SANDBOX')) {
            $url ='https://api.intermediador.sandbox.yapay.com.br/api/v1/reseller/authorizations/create';
        } else {
            $url ='https://api.intermediador.yapay.com.br/api/v1/reseller/authorizations/create';
        }

        if (!$account_token) {
            return false;
        }


        $yapay_data = [
            "consumer_key"    => $this->getConsumerKey(),
            "consumer_secret" => $this->getConsumerSecret(),
            "token_account"   => $account_token,
            "type_response"   => "J",
            'reseller_token'  => $this->getResellerToken($account_token)
        ];

        try {
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout in seconds

            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($yapay_data));

            $return = array();



            $return['body'] = json_decode(curl_exec($ch));
            $return['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            //log da requisição
            $request = new AgYaPayRequest;
            $request->endpoint = $url;
            $request->headers = json_encode([array('Content-Type: application/json')]);
            $request->method = 'POST';
            $request->http_code = $return['http_code'];
            $request->body = json_encode($yapay_data);
            $request->response = json_encode($return['body']);
            $request->time_spent = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
            $request->add();

            if ($return['http_code'] >= 400) {
                throw new Exception($return['body']);
            }

            $code = $return['body']->data_response->authorization->code ?? null;

            if (isset($return['body']->error_response)) {
                $error = $return['body']->error_response->general_errors[0]->message;
                throw new Exception($error);
            }

            if ($save) {
                if (Configuration::get('AGYAPAY_ENABLE_SANDBOX')) {
                    Configuration::updateValue('AGYAPAY_SANDBOX_ACCOUNT_CODE', $code);
                } else {
                    Configuration::updateValue('AGYAPAY_ACCOUNT_CODE', $code);
                }
            }
            return $code;
        } catch (Exception $e) {
            throw $this->formatError($e);
        }
    }

    public function refreshAccessToken($accountCode, $save)
    {
        if (Configuration::get('AGYAPAY_ENABLE_SANDBOX')) {
            $url ='https://api.intermediador.sandbox.yapay.com.br/api/authorizations/access_token';
        } else {
            $url ='https://api.intermediador.yapay.com.br/api/authorizations/access_token';
        }

        if (!$accountCode) {
            return false;
        }


        $yapay_data = [
            "consumer_key"    => $this->getConsumerKey(),
            "consumer_secret" => $this->getConsumerSecret(),
            "code"            => $accountCode,
            "type_response"   => "J"
        ];
        try {
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout in seconds

            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($yapay_data));

            $return = array();

            $return['body'] = json_decode(curl_exec($ch));
            $return['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            //log da requisição
            $request = new AgYaPayRequest;
            $request->endpoint = $url;
            $request->headers = json_encode([array('Content-Type: application/json')]);
            $request->method = 'POST';
            $request->http_code = $return['http_code'];
            $request->body = json_encode($yapay_data);
            $request->response = json_encode($return['body']);
            $request->time_spent = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
            $request->add();

            if ($return['http_code'] >= 400) {
                throw new Exception($return['body']);
            }

            $access_token = $return['body']->data_response->authorization->access_token ?? null;
            $expiration   = $return['body']->data_response->authorization->access_token_expiration ?? null;

            if ($save) {
                if (Configuration::get('AGYAPAY_ENABLE_SANDBOX')) {
                    Configuration::updateValue('AGYAPAY_SANDBOX_ACCESS_TOKEN', $access_token);
                    Configuration::updateValue('AGYAPAY_SANDBOX_ACCESS_TOKEN_EXPIRATION', $expiration);
                } else {
                    Configuration::updateValue('AGYAPAY_ACCESS_TOKEN', $access_token);
                    Configuration::updateValue('AGYAPAY_ACCESS_TOKEN_EXPIRATION', $expiration);
                }
            }

            return $access_token;
        } catch (Exception $e) {
            throw $this->formatError($e);
        }
    }

    /************************ MAPEAMENTOS *************************/
    public function loadMappings()
    {
        $this->cpf_mapping = new AgColumnMapping();
        $this->cpf_mapping->setData(array(
            'table_name' => 'customer',
            'configuration_name' => 'agyapay_cpf'
        ));
        $this->cpf_mapping->addColumn('djtalbrazilianregister', 'Módulo de Cadastro Brasileiro');
        $this->cpf_mapping->addColumn('ldbrazilianregister', 'Módulo de Cadastro LD');
        $this->cpf_mapping->addColumn('psmodcpf', 'Módulo CPF/CNPJ SoliSYS');


        $this->cnpj_mapping = new AgColumnMapping();
        $this->cnpj_mapping->setData(array(
            'table_name' => 'customer',
            'configuration_name' => 'agyapay_cnpj'
        ));
        $this->cnpj_mapping->addColumn('djtalbrazilianregister', 'Módulo de Cadastro Brasileiro');
        $this->cnpj_mapping->addColumn('ldbrazilianregister', 'Módulo de Cadastro LD');
        $this->cnpj_mapping->addColumn('psmodcpf', 'Módulo CPF/CNPJ SoliSYS');

        $this->social_name_mapping = new AgColumnMapping();
        $this->social_name_mapping->setData(array(
            'table_name' => 'customer',
            'configuration_name' => 'agyapay_social_name'
        ));

        $this->address_number_mapping = new AgColumnMapping();
        $this->address_number_mapping->setData(array(
            'table_name' => 'address',
            'configuration_name' => 'agyapay_address_number_mapping'
        ));

        if (Module::isEnabled('agcustomers')) {
            $this->cpf_mapping->mapsTo("cpf");
            $this->cnpj_mapping->mapsTo("cnpj");
            $this->social_name_mapping->mapsTo("company_name");
            $this->address_number_mapping->mapsTo("number");
        }
    }

    public function getCpfMapping()
    {
        return $this->cpf_mapping;
    }

    public function getCnpjMapping()
    {
        return $this->cnpj_mapping;
    }

    public function getSocialNameMapping()
    {
        return $this->social_name_mapping;
    }

    public function getAddressNumberMapping()
    {
        return $this->address_number_mapping;
    }

    public function validatePayment(Cart $cart, Customer $customer)
    {
        $address_invoice = new Address($cart->id_address_invoice);

        $mapping_number = $this->getAddressNumberMapping();
        $address_number = 's/n';
        if ($mapping_number->isMappingEnabled()) {
            $mappedField = $mapping_number->getMappedField();
            $address_number = $address_invoice->{$mappedField};
        }

        $errors = [];
        if (empty($address_number)) {
            $errors[] = 'Número da residência do endereço de entrega não informado.';
        }


        $document = AgColumnMapping::getCustomerDocument(
            $this->getCpfMapping(),
            $this->getCnpjMapping(),
            $this->getSocialNameMapping(),
            $customer
        );

        if (!$document['cpf'] && !$document['cnpj']) {
            $errors[] = 'CPF/CNPJ do comprador não informados.';
        }

        if (!Configuration::get('AGYAPAY_VIRTUAL_PRODUCTS')) {
            foreach ($cart->getProducts() as $product) {
                if ($product['is_virtual']) {
                    $errors[] = 'O pagamento de produtos virtuais está desativado no módulo Vindi.';
                }
            }
        }

        return $errors;
    }

    public function getCustomerData(Customer $customer)
    {
        $document = AgColumnMapping::getCustomerDocument(
            $this->getCpfMapping(),
            $this->getCnpjMapping(),
            $this->getSocialNameMapping(),
            $customer
        );

        return [
            'name' => $document['name'],
            'company_name' => $document['company'],
            'cnpj' => $document['cnpj'],
            'cpf' => $document['cpf']
        ];
    }

    public function generateTicketForm($total=0)
    {
        if (!$this->active) {
            return;
        }

        if (!$total) {
            $total = $this->context->cart->getOrderTotal();
            $total_products = $this->context->cart->getOrderTotal(true, Cart::ONLY_PRODUCTS);
            
            $discount = (float) Configuration::get('AGYAPAY_TICKET_DISCOUNT');

            //verifica se há regra de preço
            $id_cart_rule = (float) Configuration::get('AGYAPAY_TICKET_CART_RULE');
            if ($id_cart_rule) {
                $this->context->cart->removeCartRule($id_cart_rule);
                
                $cartRule = new CartRule($id_cart_rule);

                if ($cartRule->reduction_percent != '0.00') {
                    $discount_value = $total_products * $cartRule->reduction_percent / 100;
                } else {
                    $discount_value = $cartRule->reduction_amount;
                }
            }

            //se não houver regra de preço, usa o desconto do campo de desconto
            if ($discount) {
                if (!$discount_value) {
                    $discount_value = $total_products * $discount / 100;
                }
                $total_with_discount = $total - $discount_value;                 
            } else {
                $total_with_discount = $total;
            }

        } else {
            $total_with_discount = $total;
        }

        if (version_compare(_PS_VERSION_, '9', '<')) {
            $price_formatted = Tools::displayPrice($total_with_discount);
        } else {
            $price_formatted = (new PriceFormatter)->format($total_with_discount);
        }

        $this->context->smarty->assign(array(
            'total_with_discount' => $price_formatted,
            'form_action' => $this->context->link->getModuleLink($this->name, 'validation'),
        ));

        return $this->display($this->_path, 'views/templates/front/ticket.ps17.tpl');
    }


    public function generatePixForm($total=0)
    {
        if (!$this->active) {
            return;
        }

        if (!$total) {
            $total = $this->context->cart->getOrderTotal();
            $total_products = $this->context->cart->getOrderTotal(true, Cart::ONLY_PRODUCTS);
            
            $discount = (float) Configuration::get('AGYAPAY_PIX_DISCOUNT');

            //verifica se há regra de preço
            $id_cart_rule = (float) Configuration::get('AGYAPAY_PIX_CART_RULE');
            if ($id_cart_rule) {
                $this->context->cart->removeCartRule($id_cart_rule);
                
                $cartRule = new CartRule($id_cart_rule);

                if ($cartRule->reduction_percent != '0.00') {
                    $discount_value = $total_products * $cartRule->reduction_percent / 100;
                } else {
                    $discount_value = $cartRule->reduction_amount;
                }
            }

            //se não houver regra de preço, usa o desconto do campo de desconto
            if ($discount) {
                if (!$discount_value) {
                    $discount_value = $total_products * $discount / 100;
                }
                $total_with_discount = $total - $discount_value;
            } else {
                $total_with_discount = $total;
            }
        } else {
            $total_with_discount = $total;
        }

        if (version_compare(_PS_VERSION_, '9', '<')) {
            $total_formatted = Tools::displayPrice($total_with_discount);
        } else {
            $total_formatted = (new PriceFormatter)->format($total_with_discount);
        }

        $this->context->smarty->assign(array(
            'total_with_discount' => $total_formatted,
            'form_action' => $this->context->link->getModuleLink($this->name, 'validation'),
        ));

        return $this->display($this->_path, 'views/templates/front/pix.ps17.tpl');
    }

    /**
     * Gera o boleto bancário para um carrinho de compra.
     *
     * Hoje a função não é compatível com o recurso de marketplace e ela não realiza a aplicação de desconto.
     * Ela assume que o pedido já foi realizado e que os devidos cupons de desconto já foram aplicados.
     *
     * @param Cart $cart carrinho de compras
     * @param array $options array de opções
     *              $options['expiration_date'] data de vencimento do boleto
     */
    public function createTicketForCart(Cart $cart, $options = array())
    {
        try {
            if (empty($options['expiration_date']) || !is_a($options['expiration_date'], 'DateTime')) {
                $expiration_days = Configuration::get('AGYAPAY_TICKET_EXPIRATION_DAYS');
                if (!$expiration_days) {
                    $expiration_days = 3;
                }

                $expiration_date = new DateTime("+$expiration_days days");
            } else {
                $expiration_date = $options['expiration_date'];
            }
            
            $yapay_data = $this->createBasicTransactionForCart($cart);
            $yapay_data['payment'] = [
                'payment_method_id' => '6',
                'billet_date_expiration' => $expiration_date->format('d/m/Y')
            ];
        } catch (Exception $e) {
            throw $this->formatError($e);
        }

        try {
            if (Configuration::get('AGYAPAY_ENABLE_SANDBOX')) {
                $url = 'https://api.intermediador.sandbox.yapay.com.br/api/v3/transactions/payment';
            } else {
                $url = 'https://api.intermediador.yapay.com.br/api/v3/transactions/payment';
            }

            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout in seconds

            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($yapay_data));

            $return = array();

            $return['body'] = curl_exec($ch);
            $return['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            //log da requisição
            $request = new AgYaPayRequest;
            $request->endpoint = $url;
            $request->headers = json_encode([array('Content-Type: application/json')]);
            $request->method = 'POST';
            $request->http_code = $return['http_code'];
            $request->body = json_encode($yapay_data);
            $request->response = $return['body'];
            $request->time_spent = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
            $request->add();
            if ($return['http_code'] >= 400) {
                throw new Exception($return['body']);
            }
        } catch (Exception $e) {
            throw $this->formatError($e);
        }

        $data = json_decode($return['body']);
        $transaction = $data->data_response->transaction;

        return [
            'transaction' => $transaction,
            'expiration_date' => $expiration_date
        ];
    }

    /**
     * Gera o PIX para um carrinho de compra.
     *
     * Hoje a função não é compatível com o recurso de marketplace e ela não realiza a aplicação de desconto.
     * Ela assume que o pedido já foi realizado e que os devidos cupons de desconto já foram aplicados.
     *
     * @param Cart $cart carrinho de compras
     */
    public function createPixForCart(Cart $cart)
    {
        try {
            $yapay_data = $this->createBasicTransactionForCart($cart);
            $yapay_data['payment'] = [
                'payment_method_id' => '27',
            ];
        } catch (Exception $e) {
            throw $this->formatError($e);
        }

        try {
            if (Configuration::get('AGYAPAY_ENABLE_SANDBOX')) {
                $url = 'https://api.intermediador.sandbox.yapay.com.br/api/v3/transactions/payment';
            } else {
                $url = 'https://api.intermediador.yapay.com.br/api/v3/transactions/payment';
            }

            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout in seconds

            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($yapay_data));

            $return = array();

            $return['body'] = curl_exec($ch);
            $return['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            //log da requisição
            $request = new AgYaPayRequest;
            $request->endpoint = $url;
            $request->headers = json_encode([array('Content-Type: application/json')]);
            $request->method = 'POST';
            $request->http_code = $return['http_code'];
            $request->body = json_encode($yapay_data);
            $request->response = $return['body'];
            $request->time_spent = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
            $request->add();
            if ($return['http_code'] >= 400) {
                throw new Exception($return['body']);
            }
        } catch (Exception $e) {
            throw $this->formatError($e);
        }

        $data = json_decode($return['body']);
        $transaction = $data->data_response->transaction;

        return [
            'transaction' => $transaction,
        ];
    }


    /**
     * Gera o boleto bancário para um carrinho de compra.
     *
     * Hoje a função não é compatível com o recurso de marketplace e ela não realiza a aplicação de desconto.
     * Ela assume que o pedido já foi realizado e que os devidos cupons de desconto já foram aplicados.
     *
     * @param Order $ps_order pedido
     * @param array $options array de opções
     *              $options['days_to_expiration_date'] dias para vencimento do boleto
     *              $options['order_value'] valor a ser gerado para o boleto (por padrão é utilizado o valor do pedido)
     */
    public function createTicketForOrder(Order $ps_order, $options = array())
    {
        $ticket_total_cost = 0;

        try {
            $cart = new Cart($ps_order->id_cart);

            if (empty($options['expiration_date']) || !is_a($options['expiration_date'] ?? null, 'DateTime')) {
                $expiration_days = Configuration::get('AGYAPAY_TICKET_EXPIRATION_DAYS');
                if (!$expiration_days) {
                    $expiration_days = 3;
                }

                $expiration_date = new DateTime("+$expiration_days days");
            } else {
                $expiration_date = $options['expiration_date'];
            }

            $address_invoice = new Address($cart->id_address_invoice);
            $payment_mode = Tools::getValue('payment_mode');

            $vindiCarrierId = $this->resolveVindiCarrierId((int) $ps_order->id_carrier, (bool) $ps_order->isVirtual());
            $token = GlobalAgYaPaySellerAccountCarrier::getAccountTokenFromCarrier($vindiCarrierId, Configuration::get('AGYAPAY_ENABLE_SANDBOX'));

            $customer      = new Customer($ps_order->id_customer);
            $customer_data = $this->getCustomerData($customer);
            //adiciona os produtos ao pedido
            $sql = new DbQuery;
            $sql->from('order_detail')
                ->where('id_order=' . (int)$ps_order->id);

            $products = Db::getInstance()->executeS($sql);

            $products_to_yapay = [];
            foreach ($products as $product) {
                $product_object = new Product($product['product_id']);

                if (isset($product['product_quantity_fractional'])) {
                    $quantity = 1;
                    $price_unit = number_format($product['total_price_tax_incl'], 2, '.', '');
                } else {
                    $quantity = (int) $product['product_quantity'];
                    $price_unit = number_format(Tools::convertPrice($product['unit_price_tax_incl'], null, false), 2, '.', '');
                }

                $products_to_yapay[] = [
                    'description' => $product_object->name[$this->context->language->id],
                    'quantity' => $quantity,
                    'price_unit' => $price_unit,
                    'code' => $product_object->id,
                    'sku_code' => $product['product_reference'] ? $product['product_reference'] : $product['product_id']
                ];
            }
            $ticket_total_cost = $ps_order->getTotalProductsWithTaxes() + $ps_order->total_shipping_tax_incl;

            $discounts = Tools::convertPrice($ps_order->total_discounts, $ps_order->id_currency, false);
            $ticket_total_cost -= $discounts;

            if (!empty($options['order_value']) && $options['order_value'] > Tools::ps_round($ticket_total_cost, 2)) {
                throw new Exception('O valor do boleto não pode ser maior que o valor do pedido do PrestaShop (' . $ticket_total_cost . ')');
            }

            if (!empty($options['order_value'])) {
                $discounts += $ticket_total_cost - $options['order_value'];
            }

            //trata números de telefone
            $address = new Address($ps_order->id_address_invoice);

            $contacts = [];
            if ($address->phone) {
                $contacts[] = [
                    'type_contact' => 'H',
                    'number_contact' => preg_replace('/\D/', '', $address->phone ?? '')
                ];
            }

            if ($address->phone_mobile) {
                $contacts[] = [
                    'type_contact' => 'M',
                    'number_contact' => preg_replace('/\D/', '', $address->phone_mobile ?? '')
                ];
            }

            $mapping_number = $this->getAddressNumberMapping();
            $address_number = 's/n';
            if ($mapping_number->isMappingEnabled()) {
                $mappedField = $mapping_number->getMappedField();
                $address_number = $address->{$mappedField};
            }

            $state = new State($address->id_state);

            $shippingType = $this->getVindiShippingTypeFromCarrierId($vindiCarrierId);
            $shippingPrice = $vindiCarrierId === -1
                ? '0.00'
                : number_format(Tools::convertPrice($cart->getTotalShippingCost(), $ps_order->id_currency, false), 2, '.', '');
            $yapay_data = [
                'token_account' => $token,
                'reseller_token' => $this->getResellerToken(),
                'customer' => [
                    'contacts' => $contacts,
                    'addresses' => [
                        [
                            'type_address' => 'B',
                            'postal_code'  => $address->postcode,
                            'street'       => $address->address1,
                            'number'       => $address_number ?? 's/n',
                            'completion'   => $address->other,
                            'neighborhood' => $address->address2,
                            'city'         => $address->city,
                            'state'        => $state->iso_code
                        ]
                    ],
                    'name' => $customer_data['name'],
                    'company_name' => $customer_data['company_name'],
                    'trade_name' => $customer_data['company_name'],
                    'cpf'  => $customer_data['cpf'],
                    'cnpj' => $customer_data['cnpj'] ?? null,
                    'email' => $customer->email
                ],
                'transaction_product' => $products_to_yapay,
                'transaction' => [
                    'available_payment_methods' => '2,3,4,5,6,7,14,15,16,18,19,20,21,22,23,27',
                    'customer_ip'                   => $_SERVER['REMOTE_ADDR'],
                    'shipping_type'                 => $shippingType,
                    'shipping_price'                => $shippingPrice,
                    'price_discount'                => number_format(Tools::convertPrice($discounts, $ps_order->id_currency, false), 2, '.', ''),
                    'url_notification'              => $this->context->link->getModuleLink('agyapay', 'return')
                ],
                'payment' => [
                    'payment_method_id' => '6',
                    'billet_date_expiration' => $expiration_date->format('d/m/Y')
                ]
            ];
        } catch (Exception $e) {
            throw $this->formatError($e);
        }

        try {
            if (Configuration::get('AGYAPAY_ENABLE_SANDBOX')) {
                $url = 'https://api.intermediador.sandbox.yapay.com.br/api/v3/transactions/payment';
            } else {
                $url = 'https://api.intermediador.yapay.com.br/api/v3/transactions/payment';
            }

            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout in seconds

            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($yapay_data));

            $return = array();

            $return['body'] = curl_exec($ch);
            $return['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            AgYapayTransaction::expireOrderTransactions($ps_order);
            //log da requisição
            $request = new AgYaPayRequest;
            $request->endpoint = $url;
            $request->headers = json_encode([array('Content-Type: application/json')]);
            $request->method = 'POST';
            $request->http_code = $return['http_code'];
            $request->body = json_encode($yapay_data);
            $request->response = $return['body'];
            $request->time_spent = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
            $request->add();

            if ($return['http_code'] >= 400) {
                throw new Exception($return['body']);
            }
        } catch (Exception $e) {
            throw $this->formatError($e);
        }

        $data = json_decode($return['body']);
        $transaction = $data->data_response->transaction;

        $hash_bank_slip = sha1($cart->id . strtotime(date('Y-m-d H:i:s')));
        $path = _PS_MODULE_DIR_ . $this->name . '/files/boletos/';
        
        if(!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $params = [
            'hash' => $hash_bank_slip
        ];

        $bank_slip_file = $this->context->link->getModuleLink('agyapay', 'bankslip', $params) ;

        $obj = new AgYapayTransaction;

        $obj->id_order        = $ps_order->id;
        $obj->remote_id       = $transaction->transaction_id;
        $obj->remote_token    = $transaction->token_transaction;
        $obj->type            = 0;
        $obj->expiration_date = $expiration_date->format('Y-m-d H:i:s');
        $obj->url_payment     = $bank_slip_file;
        $obj->bar_code = preg_replace('/[^0-9]/', '', $transaction->payment->linha_digitavel);
        $obj->hash_bank_slip  = $hash_bank_slip;
        $obj->original_url_payment  = $transaction->payment->url_payment;

        $obj->value_paid      = $transaction->payment->price_payment;
        $obj->value_invoiced  = $transaction->payment->price_payment;

        try {
            AgYapayTransaction::expireOrderTransactions($ps_order);
        } catch (Exception $e) {
            throw $this->formatError($e);
        }

        $obj->add();

        return $obj->url_payment;
    }

    /**
     * Gera o PIX para um pedido
     * 
     * Hoje a função não é compatível com o recurso de marketplace e ela não realiza a aplicação de desconto.
     * Ela assume que o pedido já foi realizado e que os devidos cupons de desconto já foram aplicados.
     *
     * @param Order $ps_order pedido
     * @param array $options array de opções
     *              $options['order_value'] valor a ser gerado para o PIX (por padrão é utilizado o valor do pedido)
     * 
     * @return AgYapayTransaction
     */
    public function createPixForOrder(Order $ps_order, $options=[])
    {
        $cart = new Cart($ps_order->id_cart);
        $pix_total_cost = $ps_order->getTotalProductsWithTaxes() + $ps_order->total_shipping_tax_incl;

        $discounts = Tools::convertPrice($ps_order->total_discounts, $ps_order->id_currency, false);
        $pix_total_cost -= $discounts;

        if (!empty($options['order_value']) && $options['order_value'] > Tools::ps_round($pix_total_cost, 2)) {
            throw new Exception('O valor do boleto não pode ser maior que o valor do pedido do PrestaShop (' . $pix_total_cost . ')');
        }

        if (!empty($options['order_value'])) {
            $discounts += $pix_total_cost - $options['order_value'];
        }
        
        try {
            $yapay_data = $this->createBasicTransactionForOrder($ps_order);
            
            $yapay_data['payment'] = [
                'payment_method_id' => '27',
            ];
        } catch (Exception $e) {
            throw $this->formatError($e);
        }

        try {
            if (Configuration::get('AGYAPAY_ENABLE_SANDBOX')) {
                $url = 'https://api.intermediador.sandbox.yapay.com.br/api/v3/transactions/payment';
            } else {
                $url = 'https://api.intermediador.yapay.com.br/api/v3/transactions/payment';
            }

            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout in seconds

            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($yapay_data));

            $return = array();

            $return['body'] = curl_exec($ch);
            $return['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            //log da requisição
            $request = new AgYaPayRequest;
            $request->endpoint = $url;
            $request->headers = json_encode([array('Content-Type: application/json')]);
            $request->method = 'POST';
            $request->http_code = $return['http_code'];
            $request->body = json_encode($yapay_data);
            $request->response = $return['body'];
            $request->time_spent = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
            $request->add();
            if ($return['http_code'] >= 400) {
                throw new Exception($return['body']);
            }
        } catch (Exception $e) {
            throw $this->formatError($e);
        }

        AgYapayTransaction::expireOrderTransactions($ps_order);
        
        $data = json_decode($return['body']);
        $transaction = $data->data_response->transaction;

        $obj = new AgYapayTransaction;

        $obj->id_order        = $ps_order->id;
        $obj->remote_id       = $transaction->transaction_id;
        $obj->remote_token    = $transaction->token_transaction;
        $obj->type = 3;
        $obj->pix_qrcode_hash = $transaction->payment->qrcode_original_path;
        $obj->pix_qrcode_url = $transaction->payment->qrcode_path;
        $obj->pix_expiration_date = str_replace('T', ' ', $transaction->max_days_to_keep_waiting_payment);
        $obj->original_url_payment  = $transaction->payment->url_payment;

        $obj->value_paid      = $transaction->payment->price_payment;
        $obj->value_invoiced  = $transaction->payment->price_payment;
        $obj->add();

        return $obj;
    }

    public function sendTicketMailForOrder(Order $order)
    {
        $ticket = self::getTicketLinkForOrder($order);
        if (!$ticket) {
            return true;
        }

        $customer      = new Customer($order->id_customer);
        $customer_data = $this->getCustomerData($customer);

        $shop = new Shop($order->id_shop);
        $mail_data = array(
            '{shop_name}' => $this->context->shop->name,
            '{customer_name}' => $customer_data['name'],
            '{order_name}' => $order->reference,
            '{print_ticket_link}' => $ticket
        );

        $r = Mail::Send(
            $order->id_lang,
            'ticket_link',
            'Conclua o pagamento de seu pedido',
            $mail_data,
            $customer->email,
            $customer_data['name'],
            null,
            null,
            null,
            null,
            _PS_MODULE_DIR_ . $this->name . '/mails/',
            false,
            $order->id_shop
        );

        return $r;
    }


    public static function getTicketLinkForOrder(Order $order, $include_paid_order=false, $allow_expired_tickets=false)
    {
        if ($order->hasBeenPaid() && !$include_paid_order) {
            return;
        }

        $transaction = AgYapayTransaction::getByOrderId($order->id, false);

        if (!Validate::isLoadedObject($transaction)) {
            return;
        }

        $expiration_date = $transaction->expiration_date;
        $expiration_date_obj = new DateTime($expiration_date);
        $now = new DateTime('today');

        
        //se o boleto estiver vencido, não retorna a URL
        if ($expiration_date_obj < $now && !$allow_expired_tickets) {
            return;
        }

        return $transaction->url_payment;
    }

    public static function getPixTransactionForOrder(Order $order, $include_paid_order=false, $allow_expired_transactions=false)
    {
        if ($order->hasBeenPaid() && !$include_paid_order) {
            return;
        }

        $transaction = AgYapayTransaction::getByOrderId($order->id, false);
        if (!Validate::isLoadedObject($transaction)) {
            return;
        }

        $expiration_date = $transaction->pix_expiration_date;
        $expiration_date_obj = DateTime::createFromFormat('Y-m-d H:i:s', $expiration_date);
        $now = new DateTime('today');
        //se o PIX estiver vencido, não retorna a URL
        if ($expiration_date_obj < $now && !$allow_expired_transactions) {
            return;
        }

        return $transaction;
    }

    public static function userMayCreateTicketForOrder(Customer $customer, $id_order)
    {
        $order = new Order($id_order);

        if (!$id_order || !Validate::isLoadedObject($order)) {
            return 'Erro carregando informações do pedido.';
        }

        if ($order->id_customer != $customer->id) {
            return 'Erro de permissão. Talvez esse pedido tenha sido realizado por outro usuário.';
        }

        if ($order->current_state != Configuration::get('AGYAPAY_STATUS_4')) {
            return 'Pedido em estado inválido.';
        }

        return true;
    }

    /**
     * Gera o boleto bancário para um carrinho de compra.
     *
     * Hoje a função não é compatível com o recurso de marketplace e ela não realiza a aplicação de desconto.
     * Ela assume que o pedido já foi realizado e que os devidos cupons de desconto já foram aplicados.
     *
     * @param Cart $cart carrinho de compras
     * @param array $options array de opções
     *              $options['card_brand']  portador do cartão
     *              $options['card_holder']  portador do cartão
     *              $options['card_number'] número do cartão
     *              $options['cvv'] código de verificação
     *              $options['split'] quantidade de parcelas
     *              $options['expiration_month']
     *              $options['expiration_year']
     */
    public function payCartWithCreditCard(Cart $cart, $options = array())
    {
        try {
            $yapay_data = $this->createBasicTransactionForCart($cart);
            
            if (Configuration::get('AGYAPAY_CREDIT_CARD_INSTALLMENT_METHOD') == 'local') {
                $porcent = Configuration::get("AGYAPAY_CREDIT_CARD_INTEREST_RATE_{$options['split']}")?:0;

                $discount = $yapay_data['transaction']['price_discount'];
                $shiping = $yapay_data['transaction']['shipping_price'];

                $valueProducts=0;
                foreach ($yapay_data['transaction_product'] as $key => $value) {
                    $valueProducts += $value['price_unit'] * $value['quantity'];
                }

                $total=($valueProducts + $shiping) - $discount;
                $juros=($total/100)*(int)$porcent;
               
                $yapay_data['transaction']['price_additional'] = number_format(Tools::convertPrice($juros, null, false), 2, '.', '');
            }

            if (!empty($options['card_token'])) {
                $yapay_data['payment'] = [
                    'card_token' => $options['card_token'],
                    'payment_method_id'  => $options['payment_method_id'],
                    'card_cvv'   => $options['cvv'],
                    'split'      => $options['split']
                ];
            } else {
                $yapay_data['payment'] = [
                    'payment_method_id'  => $options['payment_method_id'],
                    'card_name'          => $options['card_name'],
                    'card_number'        => $options['card_number'],
                    'card_expdate_month' => $options['expiration_month'],
                    'card_expdate_year'  => $options['expiration_year'],
                    'card_cvv'           => $options['cvv'],
                    'split'              => $options['split']
                ];
            }
        } catch (Exception $e) {
            throw $this->formatError($e);
        }

        try {
            if (Configuration::get('AGYAPAY_ENABLE_SANDBOX')) {
                $url = 'https://api.intermediador.sandbox.yapay.com.br/api/v3/transactions/payment';
            } else {
                $url = 'https://api.intermediador.yapay.com.br/api/v3/transactions/payment';
            }

            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout in seconds

            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($yapay_data));
            $return = array();

            $return['body'] = curl_exec($ch);
            $return['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            //log da requisição
            $request = new AgYaPayRequest;
            $request->endpoint = $url;
            $request->headers = json_encode([array('Content-Type: application/json')]);
            $request->method = 'POST';
            $request->http_code = $return['http_code'];

            $yapay_data['payment']['card_cvv'] = 'Ocultado por segurança do usuário.';
            $yapay_data['payment']['card_number'] = 'Ocultado por segurança do usuário.';
            $yapay_data['payment']['card_token'] = 'Ocultado por segurança do usuário.';

            $request->body = json_encode($yapay_data);
            $request->response = $return['body'];
            $request->time_spent = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
            $request->add();

            if ($return['http_code'] >= 400) {
                throw new Exception($return['body']);
            }
        } catch (Exception $e) {
            throw $this->formatError($e);
        }

        $data = json_decode($return['body']);
        $transaction = $data->data_response->transaction;

        return $transaction;
    }


    /**
     * Paga um pedido já existente via cartão de crédito
     *
     * Hoje a função não é compatível com o recurso de marketplace e ela não realiza a aplicação de desconto.
     * Ela assume que o pedido já foi realizado e que os devidos cupons de desconto já foram aplicados.
     *
     * @param Cart $cart carrinho de compras
     * @param array $options array de opções
     *              $options['card_brand']  portador do cartão
     *              $options['card_holder']  portador do cartão
     *              $options['card_number'] número do cartão
     *              $options['cvv'] código de verificação
     *              $options['split'] quantidade de parcelas
     *              $options['expiration_month']
     *              $options['expiration_year']
     */
    public function payOrderWithCreditCard(Order $order, $options = array())
    {
        try {
            $yapay_data = $this->createBasicTransactionForOrder($order);

            if (Configuration::get('AGYAPAY_CREDIT_CARD_INSTALLMENT_METHOD') == 'local') {
                $porcent = Configuration::get("AGYAPAY_CREDIT_CARD_INTEREST_RATE_{$options['split']}")?:0;

                $discount = $yapay_data['transaction']['shipping_price'];
                $shiping = $yapay_data['transaction']['price_discount'];

                $valueProducts=0;
                foreach ($yapay_data['transaction_product'] as $key => $value) {
                    $valueProducts += $value['price_unit'] * $value['quantity'];
                }

                $total=($valueProducts + $shiping) - $discount;
                $juros=($total/100)*(int)$porcent;
               
                $yapay_data['transaction']['price_additional'] = number_format(Tools::convertPrice($juros, null, false), 2, '.', '');
            }
            
            if (!empty($options['card_token'])) {
                $yapay_data['payment'] = [
                    'card_token' => $options['card_token'],
                    'payment_method_id'  => $options['payment_method_id'],
                    'card_cvv'   => $options['cvv'],
                    'split'      => $options['split']
                ];
            } else {
                $yapay_data['payment'] = [
                    'payment_method_id'  => $options['payment_method_id'],
                    'card_name'          => $options['card_name'],
                    'card_number'        => $options['card_number'],
                    'card_expdate_month' => $options['expiration_month'],
                    'card_expdate_year'  => $options['expiration_year'],
                    'card_cvv'           => $options['cvv'],
                    'split'              => $options['split']
                ];
            }
        } catch (Exception $e) {
            throw $this->formatError($e);
        }

        try {
            if (Configuration::get('AGYAPAY_ENABLE_SANDBOX')) {
                $url = 'https://api.intermediador.sandbox.yapay.com.br/api/v3/transactions/payment';
            } else {
                $url = 'https://api.intermediador.yapay.com.br/api/v3/transactions/payment';
            }

            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout in seconds

            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($yapay_data));
            
            $return = array();

            $return['body'] = curl_exec($ch);
            $return['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            //log da requisição
            $request = new AgYaPayRequest;
            $request->endpoint = $url;
            $request->headers = json_encode([array('Content-Type: application/json')]);
            $request->method = 'POST';
            $request->http_code = $return['http_code'];

            $yapay_data['payment']['card_cvv'] = 'Ocultado por segurança do usuário.';
            $yapay_data['payment']['card_number'] = 'Ocultado por segurança do usuário.';
            $yapay_data['payment']['card_token'] = 'Ocultado por segurança do usuário.';

            $request->body = json_encode($yapay_data);
            $request->response = $return['body'];
            $request->time_spent = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
            $request->add();

            if ($return['http_code'] >= 400) {
                throw new Exception($return['body']);
            }
        } catch (Exception $e) {
            throw $this->formatError($e);
        }

        AgYapayTransaction::expireOrderTransactions($order);
        $data = json_decode($return['body']);
        $transaction = $data->data_response->transaction;

        return $transaction;
    }

    protected function createBasicTransactionForCart(Cart $cart)
    {
        $vindiCarrierId = $this->resolveVindiCarrierId((int) $cart->id_carrier, (bool) $cart->isVirtualCart());
        $token = GlobalAgYaPaySellerAccountCarrier::getAccountTokenFromCarrier($vindiCarrierId, Configuration::get('AGYAPAY_ENABLE_SANDBOX'));

        $customer = new Customer($cart->id_customer);
        $customer_data = $this->getCustomerData($customer);

        //adiciona os produtos ao pedido
        $products_to_yapay = [];
        $products = $cart->getProducts();

        foreach ($products as $product) {
            $product_object = new Product($product['id_product']);

            if (isset($product['cart_quantity_fractional'])) {
                $quantity = 1;
                $price_unit = number_format(Tools::convertPrice($product['price_wt'] * $product['cart_quantity_fractional'], null, false), 2, '.', '');
            } else {
                $quantity = (int) $product['cart_quantity'];
                $price_unit = number_format(Tools::convertPrice($product['price_wt'], null, false), 2, '.', '');
            }

            // Nome mascarado do produto
            $masked_name = self::getMaskedProductName($product['id_product'], $product['id_product_attribute'], $product_object->name[$this->context->language->id]);

            $products_to_yapay[] = [
                'description' => $masked_name,
                'quantity' => $quantity,
                'price_unit' => $price_unit,
                'code' => $product_object->id,
                'sku_code' => $product['reference']
            ];
        }

        //trata números de telefone
        $address = new Address($cart->id_address_invoice);

        $contacts = [];
        if ($address->phone) {
            $contacts[] = [
                'type_contact' => 'H',
                'number_contact' => preg_replace('/\D/', '', $address->phone ?? '')
            ];
        }

        if ($address->phone_mobile) {
            $contacts[] = [
                'type_contact' => 'M',
                'number_contact' => preg_replace('/\D/', '', $address->phone_mobile ?? '')
            ];
        }

        $mapping_number = $this->getAddressNumberMapping();
        $address_number = 's/n';
        if ($mapping_number->isMappingEnabled()) {
            $mappedField = $mapping_number->getMappedField();
            $address_number = $address->{$mappedField};
        }

        $state = new State($address->id_state);

        $shippingType = $this->getVindiShippingTypeFromCarrierId($vindiCarrierId);
        $discount = $cart->getOrderTotal(true, Cart::ONLY_DISCOUNTS);

        $account = new AgYaPaySellerAccount($token);
        $notification_url = $account->notification_url ?: $this->context->link->getModuleLink('agyapay', 'return');

        $shippingPrice = $vindiCarrierId === -1
            ? '0.00'
            : number_format(Tools::convertPrice($cart->getTotalShippingCost(), null, false), 2, '.', '');

        $yapay_data = [
            'token_account' => $token,
            'reseller_token' => $this->getResellerToken(),
            'customer' => [
                'contacts' => $contacts,
                'addresses' => [
                    [
                        'type_address' => 'B',
                        'postal_code'  => $address->postcode,
                        'street'       => $address->address1,
                        'number'       => $address_number ?? 's/n',
                        'completion'   => $address->other,
                        'neighborhood' => $address->address2,
                        'city'         => $address->city,
                        'state'        => $state->iso_code
                    ]
                ],
                'name' => $customer_data['name'],
                'company_name' => $customer_data['company_name'],
                'trade_name' => $customer_data['company_name'],
                'cpf'  => $customer_data['cpf'],
                'cnpj' => $customer_data['cnpj'] ?? null,
                'email' => $customer->email
            ],
            'transaction_product' => $products_to_yapay,
            'transaction' => [
                'available_payment_methods' => '2,3,4,5,6,7,14,15,16,18,19,20,21,22,23,27',
                'customer_ip'                   => $_SERVER['REMOTE_ADDR'],
                'shipping_type'                 => $shippingType,
                'shipping_price'                => $shippingPrice,
                'price_discount'                => number_format(Tools::convertPrice($discount, null, false), 2, '.', ''),
                'url_notification'              => $notification_url
            ]
        ];

        $this->generateAffiliatesArray($cart, $yapay_data);
        return $yapay_data;
    }


    protected function createBasicTransactionForOrder(Order $order)
    {
        $vindiCarrierId = $this->resolveVindiCarrierId((int) $order->id_carrier, (bool) $order->isVirtual());
        $token = GlobalAgYaPaySellerAccountCarrier::getAccountTokenFromCarrier($vindiCarrierId, Configuration::get('AGYAPAY_ENABLE_SANDBOX'));

        $customer      = new Customer($order->id_customer);
        $customer_data = $this->getCustomerData($customer);

        //adiciona os produtos ao pedido
        $products_to_yapay = [];
        $products = $order->getProducts();

        foreach ($products as $product) {
            $product_object = new Product($product['id_product']);

            if (isset($product['cart_quantity_fractional'])) {
                $quantity = 1;
                $price_unit = number_format(Tools::convertPrice($product['product_price_wt'] * $product['cart_quantity_fractional'], null, false), 2, '.', '');
            } else {
                $quantity = (int) $product['product_quantity'];
                $price_unit = number_format(Tools::convertPrice($product['product_price_wt'], null, false), 2, '.', '');
            }

            // Nome mascarado do produto
            $masked_name = self::getMaskedProductName($product['id_product'], $product['id_product_attribute'], $product_object->name[$this->context->language->id]);

            $products_to_yapay[] = [
                'description' => $masked_name,
                'quantity' => $quantity,
                'price_unit' => $price_unit,
                'code' => $product_object->id,
                'sku_code' => $product['product_reference']
            ];
        }


        //trata números de telefone
        $address = new Address($order->id_address_invoice);

        $contacts = [];
        if ($address->phone) {
            $contacts[] = [
                'type_contact' => 'H',
                'number_contact' => preg_replace('/\D/', '', $address->phone ?? '')
            ];
        }

        if ($address->phone_mobile) {
            $contacts[] = [
                'type_contact' => 'M',
                'number_contact' => preg_replace('/\D/', '', $address->phone_mobile ?? '')
            ];
        }

        $mapping_number = $this->getAddressNumberMapping();
        $address_number = 's/n';
        if ($mapping_number->isMappingEnabled()) {
            $mappedField = $mapping_number->getMappedField();
            $address_number = $address->{$mappedField};
        }

        $state = new State($address->id_state);

        $shippingType = $this->getVindiShippingTypeFromCarrierId($vindiCarrierId);

        $discount = $order->total_discounts_tax_incl;

        $account = new AgYaPaySellerAccount($token);
        $notification_url = $account->notification_url ?: $this->context->link->getModuleLink('agyapay', 'return');

        $yapay_data = [
            'token_account' => $token,
            'reseller_token' => $this->getResellerToken(),
            'customer' => [
                'contacts' => $contacts,
                'addresses' => [
                    [
                        'type_address' => 'B',
                        'postal_code'  => $address->postcode,
                        'street'       => $address->address1,
                        'number'       => $address_number ?? 's/n',
                        'completion'   => $address->other,
                        'neighborhood' => $address->address2,
                        'city'         => $address->city,
                        'state'        => $state->iso_code
                    ]
                ],
                'name' => $customer_data['name'],
                'company_name' => $customer_data['company_name'],
                'trade_name' => $customer_data['company_name'],
                'cpf'  => $customer_data['cpf'],
                'cnpj' => $customer_data['cnpj'] ?? null,
                'email' => $customer->email
            ],
            'transaction_product' => $products_to_yapay,
            'transaction' => [
                'available_payment_methods' => '2,3,4,5,6,7,14,15,16,18,19,20,21,22,23,27',
                'customer_ip'                   => $_SERVER['REMOTE_ADDR'],
                'shipping_type'                 => $shippingType,
                'shipping_price'                => $vindiCarrierId === -1 ? '0.00' : number_format(Tools::convertPrice($order->total_shipping_tax_incl, null, false), 2, '.', ''),
                'price_discount'                => number_format(Tools::convertPrice($discount, null, false), 2, '.', ''),
                'url_notification'              => $notification_url
            ]
        ];

        return $yapay_data;
    }

    public static function updateLocalTransaction(AgYapayTransaction $obj)
    {
        $module = new agyapay;
        AgClienteLogger::addLog("agyapay - sincronizando transação {$obj->id} (token {$obj->remote_token}).", 1, null, null, null, true);

        $transaction = $module->getTransaction($obj->remote_token);

        $obj->status    = $transaction->status_name;
        $obj->status_id = $transaction->status_id;

        $obj->save();

        AgClienteLogger::addLog("agyapay - transação {$obj->id} atualizada para status_id {$obj->status_id} ({$obj->status}).", 1, null, null, null, true);
    }

    public function getTransaction($token_transaction)
    {
        $transaction = AgYapayTransaction::getByRemoteToken($token_transaction);
        $account = new \AgYapaySellerAccount($transaction->id_agyapay_seller_account);
        try {
            if (Configuration::get('AGYAPAY_ENABLE_SANDBOX')) {
                $token = $account->account_token_sandbox;
                $url = "https://api.intermediador.sandbox.yapay.com.br/api/v3/transactions/get_by_token?token_account={$token}&token_transaction={$token_transaction}";
            } else {
                $token = $account->account_token;
                $url = "https://api.intermediador.yapay.com.br/api/v3/transactions/get_by_token?token_account={$token}&token_transaction={$token_transaction}";
            }
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout in seconds

            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

            $return = array();

            $return['body'] = curl_exec($ch);
            $return['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            //log da requisição
            $request = new AgYaPayRequest;
            $request->endpoint = $url;
            $request->headers = json_encode([array('Content-Type: application/json')]);
            $request->method = 'GET';
            $request->http_code = $return['http_code'];
            $request->response = $return['body'];
            $request->time_spent = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
            $request->add();

            if ($return['http_code'] >= 400) {
                throw new Exception($return['body']);
            }
        } catch (Exception $e) {
            throw $this->formatError($e);
        }

        $data = json_decode($return['body']);
        $transaction = $data->data_response->transaction;

        return $transaction;
    }

    public function getStatus($status_id, $order)
    {
        //tratamento do estado de cancelado/reembolsado
        if ($status_id != 7) {
            return Configuration::get('AGYAPAY_STATUS_' . $status_id);
        }

        //se o pedido já foi pago, então retorna o estado de reembolsado
        if ($order->hasBeenPaid()) {
            return Configuration::get('AGYAPAY_STATUS_REFUNDED');
        }
        return Configuration::get('AGYAPAY_STATUS_7');

    }

    public function renderGenerateTicketModal($id_order)
    {
        $order = new Order($id_order);
        $remaining_value = $order->total_paid_tax_incl - $order->getTotalPaid();

        $expiration_days = Configuration::get('AGYAPAY_TICKET_EXPIRATION_DAYS') ?: 3;
        $expiration_date = date('Y-m-d', strtotime('+' . $expiration_days . ' days'));

        $this->context->smarty->assign([
            'remaining_value' => $remaining_value,
            'default_expiration_date' => $expiration_date
        ]);
        
        if (version_compare(_PS_VERSION_, '1.7.7', '>=')) {
            return $this->display(_PS_MODULE_DIR_ . $this->name, 'admin_create_ticket_modal.1.7.7.tpl');
        } else {
            return $this->display(_PS_MODULE_DIR_ . $this->name, 'admin_create_ticket_modal.tpl');
        }
    }

    public function cancelTransaction($transaction_id)
    {
        $yapay_data =[
            'access_token' => $this->checkAccessToken(),
            'transaction_id' => $transaction_id
        ];

        if (Configuration::get('AGYAPAY_ENABLE_SANDBOX')) {
            $url = "https://api.intermediador.sandbox.yapay.com.br/api/v3/transactions/cancel";
        } else {
            $url = "https://api.intermediador.yapay.com.br/api/v3/transactions/cancel";
        }

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout in seconds

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($yapay_data));

        $r = curl_exec($ch);
        $c = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        //log da requisição
        $request = new AgYaPayRequest;
        $request->endpoint = $url;
        $request->headers = json_encode([array('Content-Type: application/json')]);
        $request->method = 'PATCH';
        $request->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $request->response = $r;
        $request->time_spent = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        $request->add();

        if ($c != 200) {
            throw new Exception(json_encode($r));
        }
    }

    /************************ HOOKS *******************************/
    public function hookDisplayCustomerAccount()
    {
        if (!Configuration::get('AGYAPAY_CREDIT_ENABLE_VAULT')) {
            return;
        }

        $cards = AgYapayCreditCard::findByCustomer($this->context->customer);

        $this->context->smarty->assign(['yapay_link' => $this->context->link->getModuleLink('agyapay', 'managecards')]);
        
        return $this->display(_PS_MODULE_DIR_ . $this->name, 'customer_account.tpl');
    }

    public function hookDisplayBackOfficeHeader()
    {
        $return = "<script type='text/javascript'>";
        $return .= "var agyapay_transaction_token='" . Tools::getAdminTokenLite('AdminAgYapayTransaction') . "';";
        $return .= "var agyapay_transaction_url='" . $this->context->link->getAdminLink('AdminAgYapayTransaction') . "';"; // Adicionado

        $controllerName = $this->context->controller->controller_name ?? '';
        if ($controllerName === 'AdminModules' && Tools::getValue('configure') === $this->name) {
            $this->context->controller->addJs([
                _PS_MODULE_DIR_ . $this->name . '/views/js/admin_config_carrier.js',
            ]);
        }

        if ($controllerName == 'AdminOrders' && Tools::getIsSet('vieworder')) {
            $this->context->controller->addJs([
                $this->_path . 'views/js/loadingOverlay.js',
                _PS_MODULE_DIR_ . $this->name . '/views/js/admin_orders.js'
            ]);

            $this->context->controller->addCss([
                $this->_path . 'views/css/loadingOverlay.css'
            ]);
        } elseif (isset($this->context->controller->php_self) && $this->context->controller->php_self == 'AdminOrders') {
            $this->context->controller->addJs([
                $this->_path . 'views/js/loadingOverlay.js',
                _PS_MODULE_DIR_ . $this->name . '/views/js/admin_orders.1.7.7.js'
            ]);
            
            $this->context->controller->addCss([
                $this->_path . 'views/css/loadingOverlay.css'
            ]);
        }

        $return .= "</script>";
        return $return;
    }

    public function hookDisplayAdminOrderContentOrder($params)
    {
        if (!$this->active) {
            return;
        }

        $order = $params['order'];
        $ticket_link = $this->getTicketLinkForOrder($order, true);
        
        $expiration_days = Configuration::get('AGYAPAY_TICKET_EXPIRATION_DAYS') ?: 3;
        $expiration_date = date('Y-m-d', strtotime('+' . $expiration_days . ' days'));

        // $total_transactions = AgYapayTransaction::sumTransactionsForOrder($order->id);
        $remaining_value = $order->total_paid_tax_incl - $order->getTotalPaid();

        $this->context->smarty->assign(array(
            'print_ticket_link'       => $ticket_link,
            'token'                   => Tools::getAdminTokenLite('AdminAgYapayTransaction'),
            'default_expiration_date' => $expiration_date,
            'remaining_value'         => $remaining_value
        ));

        return $this->display(_PS_MODULE_DIR_ . $this->name, 'admin_content_order.tpl') . $this->display(_PS_MODULE_DIR_ . $this->name, 'admin_create_ticket_modal.tpl') . $this->display(_PS_MODULE_DIR_ . $this->name, 'admin_send_email.tpl');
    }

    public function hookActionGetAdminOrderButtons($params)
    {
        if (!$this->active) {
            return;
        }

        $bar = $params['actions_bar_buttons_collection'];
        if (version_compare(_PS_VERSION_, '9', '<')) {
            $class = "\PrestaShopBundle\Controller\Admin\Sell\Order\ActionsBarButton";
        } else {
            $class = ActionsBarButton::class;
        }

        $order = new Order($params['id_order']);
        $ticket_link = $this->getTicketLinkForOrder($order, true);

        if ($ticket_link) {
            $bar->add(
                new $class(
                    'agyapay_print_ticket btn-action',
                    ['href' => $ticket_link, 'target' => '_blank'],
                    'Imprimir Boleto'
                )
            );

            $bar->add(
                new $class(
                    'agyapay_send_mail btn-action',
                    [],
                    'Enviar Boleto por E-mail'
                )
            );
        }

        $remaining_value = $order->total_paid_tax_incl - $order->getTotalPaid();
        if ($remaining_value) {
            $bar->add(
                new $class(
                    'agyapay_generate_ticket btn-action',
                    [],
                    'Gerar Boleto'
                )
            );
        }
    }


    public function hookDisplayOrderDetail($params)
    {
        if (!$this->active) {
            return;
        }

        $order = $params['order'];

        if($order->payment == "Yapay - PIX") {
            $this->context->controller->addCSS(_PS_MODULE_DIR_ . $this->name . '/views/css/order_detail_pix.css');
            $this->context->controller->addJs([_PS_MODULE_DIR_ . $this->name . '/views/js/pix.js',]);

            $transaction = AgYapayTransaction::getByOrderId($order->id);

            $current_date = new DateTime();
            $expiration_date = new DateTime($transaction->pix_expiration_date);

            if ($current_date < $expiration_date) {
                $this->context->smarty->assign(array(
                    'transaction' => $transaction
                ));

                return $this->display(_PS_MODULE_DIR_ . $this->name, 'order_detail_pix.tpl');
            }
        } else {
            $url = $this->getTicketLinkForOrder($order);
            
            if (!$url) {
                return;
            }

            $this->context->smarty->assign(array(
                'print_ticket_link' => $url
            ));

            return $this->display(_PS_MODULE_DIR_ . $this->name, 'order_detail.tpl');
        }
    }


    public function hookDisplayOrderConfirmation($params)
    {
        if (!$this->active) {
            return;
        }

        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            $order = $params['objOrder'];
        } else {
            $order = $params['order'];
        }

        if ($order->module !== $this->name) {
            return;
        }

        $transaction = AgYapayTransaction::getByOrderId($order->id);
        if (!Validate::isLoadedObject($transaction)) {
            return;
        }

        try {
            $remote_transaction = $this->getTransaction($transaction->remote_token);
            $this->context->smarty->assign(array(
                'transaction'        => $transaction,
                'remote_transaction' => $remote_transaction,
                'order'              => $order
            ));
        } catch (Exception $e) {
            if (isset($e->public_message)) {
                $error = $e->public_message;
            } else {
                $error = $e->getMessage();
            }

            $this->context->smarty->assign(array(
                $error => $error
            ));
        }
        if (!$order->hasBeenPaid()) {
            $this->context->controller->addJs([
                $this->_path . 'views/js/confirmation.js',
            ]);
        }
        
        return $this->display(_PS_MODULE_DIR_ . $this->name, 'success.tpl');
    }

    public function hookDisplayHeader()
    {
        if (!Module::isEnabled($this->name)) {
            return;
        }

        //tratamento de erros
        if (Tools::getValue('agyapay_error')) {
            $this->context->controller->errors[] = 'Ocorreu um erro processando o pagamento do seu pedido. Se achar necessário, <a href="{$this->context->controller->getPageLink(contact)}" target="_blank">entre em contato</a> com nossa equipe de atendimento ao cliente';
        }

        //script para criação do fingerprint
        //@todo adicionar apenas no checkout
        if (
            $this->ps16
            || $this->context->controller instanceof OrderController
        ) {
            $this->context->controller->addJs([
                $this->_path . 'views/js/lib/fingerprint.js',
                $this->_path . 'views/js/lib/creditcard.min.js',
                $this->_path . 'views/js/loadingOverlay.js',
                $this->_path . 'views/js/agyapay.js',
                $this->_path . 'views/js/agyapay_credit_card.ps17.js',
                $this->_path . 'views/js/agyapay_ticket.ps17.js'
            ]);
        }

        $waiting_payment_status = new OrderState(Configuration::get('AGYAPAY_STATUS_4'), $this->context->language->id);


        Media::addJsDef([
            'agyapay' => [
                'calc_method' => Configuration::get('AGYAPAY_CREDIT_CARD_INSTALLMENT_METHOD'),
                'sandbox' => Configuration::get('AGYAPAY_ENABLE_SANDBOX') ? true : false,
                'get_installments_url' => $this->context->link->getModuleLink('agyapay', 'creditCard', ['action' => 'simulateSplitting']),
                'credit_card_active' => Configuration::get('AGYAPAY_CREDIT_CARD_ACTIVE'),
                'ticket_active' => Configuration::get('AGYAPAY_TICKET_ACTIVE'),
                'pix_active' => Configuration::get('AGYAPAY_PIX_ACTIVE'),
                'base_uri' => $this->context->shop->getBaseURL(true),
                'links' => [
                    'validation'           => $this->context->link->getModuleLink($this->name, 'validation'),
                    'payorder'           => $this->context->link->getModuleLink($this->name, 'payorder'),
                    'form_closed_order_cc' => $this->context->link->getModuleLink($this->name, 'creditCard'),
                    'manage_cards'         => $this->context->link->getModuleLink($this->name, 'managecards'),
                    'sse' => $this->context->link->getModuleLink($this->name, 'pixSSE'),
                ],
                'status_pay_closed_order' => Configuration::get('agyapay_pay_closed_orders') ? $waiting_payment_status->name: '',
                'max_installments' => Configuration::get('AGYAPAY_MAX_INSTALLMENTS'),
                'min_installment_value' => Configuration::get('AGYAPAY_MIN_INSTALLMENT_VALUE'),
                'installments_method' => Configuration::get('AGYAPAY_CREDIT_CARD_INSTALLMENT_METHOD')
            ]
        ]);

        $this->context->controller->addCSS(_PS_MODULE_DIR_ . $this->name . '/views/css/credit_card.css');

        if (($this->context->controller->module->name ?? '') == 'agcheckout') {
            $this->context->controller->registerJavascript(
                'yapay-fingerprint',
                'modules/agyapay/views/js/lib/fingerprint.js'
            );

            $this->context->controller->registerJavascript(
                'yapay-lib',
                'modules/agyapay/views/js/agyapay.js'
            );

            $this->context->controller->registerJavascript(
                'yapay-agcheckout',
                'modules/agyapay/views/js/front/agcheckout.js'
            );
        } else {
            if (version_compare(_PS_VERSION_, '1.7', '<')) {
                $this->context->controller->addCSS($this->_path . '/views/css/front.css');
            } else {
                $this->context->controller->addCSS($this->_path . '/views/css/front.ps17.css');
            }
        }

        if ($this->context->controller->php_self === 'history') {
            $this->context->controller->addCSS(_PS_MODULE_DIR_ . 'agcliente/views/css/agmodal.css');

            if ($this->ps17 || ($this->ps8 ?? false)) {
                $this->context->controller->addJs([
                    $this->_path . 'views/js/order_history.js',
                ]);
            } elseif ($this->ps16) {
                $this->context->controller->addJs([
                    _PS_MODULE_DIR_ . 'agcliente/views/js/agmodal.js',
                    $this->_path . 'views/js/order_history.ps16.js',
                ]);
            }
        }
    }

    public function hookPaymentTop()
    {
        if (!$this->active) {
            return;
        }

        // $errors = $this->validatePayment($this->context->cart, $this->context->customer);

        // $this->context->smarty->assign([
        //     'errors' => $errors,
        // ]);

        return $this->display(_PS_MODULE_DIR_ . $this->name, 'views/templates/front/fingerprint.tpl');
    }


    public function hookPaymentOptions()
    {
        if (!$this->active) {
            return;
        }

        $vindiCarrierId = $this->resolveVindiCarrierId((int) $this->context->cart->id_carrier, (bool) $this->context->cart->isVirtualCart());
        $token = GlobalAgYaPaySellerAccountCarrier::getAccountTokenFromCarrier($vindiCarrierId, Configuration::get('AGYAPAY_ENABLE_SANDBOX'));
        if (!$token) {
            return;
        }

        $errors = $this->validatePayment($this->context->cart, $this->context->customer);
        
        if (count($errors)) {
            return;
        }


        //verifica se o desconto do boleto já foi aplicado ao carrinho
        $has_discount = false;
        $id_cart_rule = 0;

        $rules = $this->context->cart->getCartRules();
        foreach ($rules as $rule) {
            if ($rule['description'] === 'Desconto boleto') {
                $has_discount = true;
                $id_cart_rule = $rule['id_cart_rule'];
            }
        }

        if ($id_cart_rule) {
            $this->context->cart->removeCartRule($id_cart_rule);
        }
        $options = [];
        if (Configuration::get('AGYAPAY_TICKET_ACTIVE') && $this->context->currency->iso_code === 'BRL' && $this->context->cart->getOrderTotal() >= (float) Configuration::get('AGYAPAY_TICKET_MIN_VALUE')) {
            $newOption = new PaymentOption();
            $newOption->setCallToActionText(Configuration::get('AGYAPAY_TICKET_TEXT'))
                ->setForm($this->generateTicketForm());

            $options[] = $newOption;
        }

        if (Configuration::get('AGYAPAY_CREDIT_CARD_ACTIVE')) {
            $newOption = new PaymentOption();
            $newOption->setCallToActionText(Configuration::get('AGYAPAY_CREDIT_CARD_TEXT'))
                ->setForm($this->generateCreditCardForm());
            // ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/credit_card.png'));

            $options[] = $newOption;
        }

        if (Configuration::get('AGYAPAY_PIX_ACTIVE') && $this->context->cart->getOrderTotal() >= (float) Configuration::get("AGYAPAY_PIX_MIN_VALUE")) {

            $newOption = new PaymentOption();
            $newOption->setCallToActionText(Configuration::get('AGYAPAY_PIX_TEXT'))
                ->setForm($this->generatePixForm());

            $options[] = $newOption;
        }

        return $options;
    }

    /*
    *  @param $options[installment_value_min] - Valor mínimo da parcela
    *  @param $options[value] - Valor do pagamento à vista
    *  @param $options[interest_rate] - Taxa de juros
    */
    public function calcInstallments($options)
    {
        $return = [];

        $max_installments = Configuration::get('AGYAPAY_MAX_INSTALLMENTS');
        
        for ($i=0; $i<$max_installments; $i++) {
            $total_value = (100 + $options['interest_rate'][$i])/100 * $options['value'];
            $installment_value = $total_value / ($i+1);

            if (Tools::convertPrice($installment_value, null, false) < $options['installment_value_min'] && $i) {
                break;
            }

            $return[] = array(
                'total' => $total_value,
                'installment_value' => $installment_value
            );
        }

        return $return;
    }

    public function generateCreditCardForm($total=0)
    {
        if (!$this->active) {
            return;
        }

        if (Configuration::get('AGYAPAY_CREDIT_ENABLE_VAULT')) {
            $cards = AgYaPayCreditCard::findByCustomer($this->context->customer);
        }
        if (!$total) {
            $total = $this->context->cart->getOrderTotal();
        }

        $interest_rate = [];

        for ($i=0; $i<12; $i++) {
            $interest_rate[] = Configuration::get("AGYAPAY_CREDIT_CARD_INTEREST_RATE_" . ($i+1));
        }

        $options = array(
            'value'                 => $total,
            'installment_value_min' => Configuration::get('AGYAPAY_CREDIT_CARD_MIN_INSTALLMENT_VALUE'),
            'interest_rate'         => $interest_rate
        );

        $installments = [];

        if (Configuration::get('AGYAPAY_CREDIT_CARD_INSTALLMENT_METHOD') == 'local') {
            $installments = $this->calcInstallments($options);
            
            foreach ($installments as $i => $installment) {
                if (version_compare(_PS_VERSION_, '9', '<')) {
                    $installments[$i]['installment_value'] = Tools::displayPrice($installments[$i]['installment_value']);
                    $installments[$i]['total'] = Tools::displayPrice($installments[$i]['total']);
                } else {
                    $installments[$i]['installment_value'] = (new PriceFormatter)->format($installments[$i]['installment_value']);
                    $installments[$i]['total'] = (new PriceFormatter)->format($installments[$i]['total']);
                }
            }
        } else {
            $splits = $this->simulateSplitting($total);
            $installments = [];
            
            foreach ($splits as $i=>$payment_mode) {
                foreach ($payment_mode['splits'] as $j=>$split) {
                    if ($j > Configuration::get('AGYAPAY_MAX_INSTALLMENTS') - 1) {
                        break;
                    }
                    $installments[$j]['installment_value'] = $split['value_split'];
                    if (version_compare(_PS_VERSION_, '9', '<')) {
                        $installments[$j]['total'] = Tools::displayPrice($split['value_transaction']);
                    } else {
                        $installments[$j]['total'] = (new PriceFormatter)->format($split['value_transaction']);
                    }
                }
                break;
            }
        }

        if (version_compare(_PS_VERSION_, '9', '<')) {
            $total_formatted = Tools::displayPrice($total);
        } else {
            $total_formatted = (new PriceFormatter)->format($total);
        }
        $this->context->smarty->assign(array(
            'total' => $total_formatted,
            'installments' => $installments,
            'form_action' => $this->context->link->getModuleLink($this->name, 'validation'),
            'credit_cards' => $cards ?? []
            // 'credit_cards' => []
        ));

        if ($this->context->controller->module && $this->context->controller->module->name == 'agcheckout') {
            return $this->display($this->_path, 'views/templates/front/credit_card.agcheckout.tpl');
        } else {
            return $this->display($this->_path, 'views/templates/front/credit_card.ps17.tpl');
        }
    }
    
    public function simulateSplitting($price)
    {
        $token = GlobalAgYaPaySellerAccountCarrier::getAccountTokenFromCarrier(
            $this->resolveVindiCarrierId((int) $this->context->cart->id_carrier, (bool) $this->context->cart->isVirtualCart()),
            Configuration::get('AGYAPAY_ENABLE_SANDBOX')
        );
    
        $yapay_data = [
            'price' => number_format(Tools::convertPrice($price, null, false), 2, '.', ''),
            'token_account' => $token
        ];

        if (Configuration::get('AGYAPAY_ENABLE_SANDBOX')) {
            $url = 'https://api.intermediador.sandbox.yapay.com.br/v1/transactions/simulate_splitting';
        } else {
            $url = 'https://api.intermediador.yapay.com.br/v1/transactions/simulate_splitting';
        }

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout in seconds

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($yapay_data));

        $return = array();

        $r = curl_exec($ch);
        $c = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        //log da requisição
        $request = new AgYaPayRequest;
        $request->endpoint = $url;
        $request->headers = json_encode([array('Content-Type: application/json')]);
        $request->method = 'POST';
        $request->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $request->body = json_encode($yapay_data);
        $request->response = $r;
        $request->time_spent = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        $request->add();

        if ($c >= 400) {
            throw new Exception($return['body']);
        }

        $split_data = [];
        
        libxml_use_internal_errors(true);
        $xmlTree = new SimpleXMLElement($r);

        if ($xmlTree->message_response->message->__toString() === 'error') {
            throw new Exception($xmlTree->error_response->errors->error->code->__toString() . ' - ' . $xmlTree->error_response->errors->error->message->__toString());
        }

        foreach ($xmlTree->data_response->payment_methods->payment_method as $payment_method) {
            $row = [
                'payment_method_id' => $payment_method->payment_method_id->__toString(),
                'splits' => []
            ];

            foreach ($payment_method->splittings->splitting as $split) {
                $row['splits'][] = [
                    'split' => $split->split->__toString(),
                    'value_split' => $split->value_split->__toString(),
                    'value_transaction' => $split->value_transaction->__toString()
                ];
            }

            $split_data[] = $row;
        }
        
        return $split_data;
    }

    public function getResellerToken($accountToken='')
    {
        if (Configuration::get('AGYAPAY_ENABLE_SANDBOX')) {
            return '6a081b8c3718eb6';
        }

        $resellerToken = 'a1f7e76cf91c32c';
        if ($resellerToken == $accountToken) {
            return '';
        }
        
        return $resellerToken;
    }



    public function getConsumerKey()
    {
        if (Configuration::get('AGYAPAY_ENABLE_SANDBOX')) {
            return 'd75a6693c37c6562c6182767f927a2f7';
        }

        return '55491a1c2bbbf3ac8f3bb65817392d41';
    }

    public function getConsumerSecret()
    {
        if (Configuration::get('AGYAPAY_ENABLE_SANDBOX')) {
            return 'f3baf57a7a1628232c7ccc2b5331a10e';
        }
        
        return 'b9377a026650dcf82e0b1c77c30a07dd';
    }


    public function generateAffiliatesArray(Cart $cart, &$transaction_data)
    {
        if (!Module::isEnabled('agmarketplace')) {
            return;
        }

        //agrupa os produtos por vendedor
        $products_per_seller = [];
        $products = $cart->getProducts();
        foreach ($products as $product) {
            $sql = new DbQuery;
            $sql->from('agmarketplace_product', 'ap')
                ->innerJoin('agmarketplace', 'a', 'a.id_agmarketplace = ap.id_agmarketplace')
                ->select('id_customer, a.email')
                ->where('ap.id_product=' . (int)$product['id_product']);

            $db_data =  Db::getInstance()->getRow($sql);
            $id_customer = $db_data['id_customer'];
            if (!$id_customer) {
                continue;
            }

            $affiliate = AgYapayAffiliate::findByIdCustomer($id_customer);
            if (!$affiliate->email) {
                throw new Exception("Vendedor {$db_data['email']} não informou o seu e-mail de cadastro na Vindi.");
            }

            $products_per_seller[$affiliate->id][] = $product;
        }

        $total_per_seller = [];
        foreach ($products_per_seller as $affiliate_id=>$products) {
            $obj = new AgYapayAffiliate($affiliate_id);
            $total_per_seller[$affiliate_id] = 0;
            

            foreach ($products as $product) {
                $total_per_seller[$affiliate_id] += $product['total_wt'] * (100 - $obj->comission) / 100;
            }

            $address_delivery = new Address($cart->id_address_delivery);
            $r = AgClienteShippingSimulation::simulateCarriers([
                'products' => $products,
                'type' => 'products',
                'postcode' => $address_delivery->postcode
            ]);
            
            $carrier_check = false;
            foreach ($r as $carrier) {
                if ($carrier['carrier']->id == $cart->id_carrier) {
                    $carrier_check = true;
                    $total_per_seller[$affiliate_id] += $carrier['numeric_price'];
                }
            }

            if (!$carrier_check) {
                throw new Exception('Alguns produtos não podem ser entregues pela transportadora escolhida.');
            }
        }

        //TODO: Adicionar o valor do frete para cada vendedor
        $transaction_data['affiliates'] = [];
        foreach ($total_per_seller as $affiliate_id => $total) {
            $obj = new AgYapayAffiliate($affiliate_id);

            $transaction_data['affiliates'][] = [
                'account_email' => $obj->email,
                'commission_amount' => Tools::ps_round($total, 2, PS_ROUND_UP)
            ];
        }
    }

    /**
     * Envia um cartão de crédito para o cofre da Vindi. Note que, apesar de o CVV não ficar salvo, ele é necessário para que
     * o cartão seja armazenado corretamente. Essa funçao fará a renovação do access_toke do lojista se o mesmo estiver expirado.
     */
    public function saveCreditCard(
        $card_number,
        $cvv,
        $card_holder,
        $payment_method_id,
        $exp_month,
        $exp_year,
        $customer_email = null
    ) {
        if (!$card_number) {
            throw new Exception("O número do cartão é obrigatório.");
        }

        if (!$cvv) {
            throw new Exception("O código de verificação do cartão é obrigatório.");
        }

        if (!$card_holder) {
            throw new Exception("O nome do proprietário do cartão é obrigatório.");
        }

        if (!$payment_method_id) {
            throw new Exception("A bandeira do cartão é inválida.");
        }

        if ($exp_month < 1) {
            throw new Exception("O mês de vencimento do cartão é inválido.");
        }

        if ($exp_year < date('Y')) {
            throw new Exception("O ano de vencimento do cartão é inválido.");
        }

        if (!$customer_email) {
            $customer_email = $this->context->customer->email;
        }

        $access_token = $this->checkAccessToken($customer_email);
        if (Configuration::get('AGYAPAY_ENABLE_SANDBOX')) {
            $url ='https://api.intermediador.sandbox.yapay.com.br/api/v1/person_cards/create';
        } else {
            $url ='https://api.intermediador.yapay.com.br/api/v1/person_cards/create';
        }

        $yapay_data = [
            "access_token" => $access_token,
            "payment_method_id" => $payment_method_id,
            "card_number" => $card_number,
            "card_name" => $card_holder,
            "card_cvv" => $cvv,
            "card_expdate_month" => $exp_month,
            "card_expdate_year" => $exp_year,
            'return' => 'J'
        ];

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout in seconds

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($yapay_data));

        $curl_data = curl_exec($ch);
    
        $return = [];
        $return['body'] = json_decode($curl_data);
        $return['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        dump($return);exit();
        //log da requisição
        $request = new AgYaPayRequest;
        $request->endpoint = $url;
        $request->headers = json_encode([array('Content-Type: application/json')]);
        $request->method = 'PATCH';
        $request->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $request->response = json_encode($return['body']);
        $request->time_spent = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        $request->body =$yapay_data;
        $request->add();

        if ($return['http_code'] >= 400) {
            throw new Exception($return['body']);
        }

        if (isset($return['body']->error_response)) {
            $error_response = $return['body']->error_response;
            if (isset($error_response->general_errors)) {
                if (is_array($return['body']->error_response->general_errors)) {
                    $error = $return['body']->error_response->general_errors[0]->message;
                } else {
                    $error = $return['body']->error_response->general_errors->general_error->message;
                }
            } else {
                $error = $return['body']->error_response->errors->error->message;
            }
            throw new Exception($error);
        }

        $obj = new AgYapayCreditCard;

        $obj->id_customer = Context::getContext()->customer->id;
        $obj->cardnumber = $return['body']->data_response->card_number;
        $obj->cvv = $cvv;
        $obj->expmonth = $return['body']->data_response->card_expdate_month;
        $obj->expyear = $return['body']->data_response->card_expdate_year;
        $obj->card_token = $return['body']->data_response->card_token;
        $obj->active = 1;
        $obj->payment_method_id = $payment_method_id;

        $obj->save();
    }

    public function formatError(Exception $e){
        if(isset(json_decode($e->getMessage())->error_response->validation_errors[0]->message_complete)){
            $this->errors[] = json_decode($e->getMessage())->error_response->validation_errors[0]->message_complete;
            $private_message = json_decode($e->getMessage())->error_response->validation_errors[0]->message_complete;
            
        }elseif(isset(json_decode($e->getMessage())->error_response->general_errors[0]->message)){
            $this->errors[] = json_decode($e->getMessage())->error_response->general_errors[0]->message;
            $private_message = json_decode($e->getMessage())->error_response->general_errors[0]->message;

        }else{
            $this->errors[] = $e->getMessage();
            $private_message = $e->getMessage();

        }

        $e = new Exception($private_message);
        $e->public_message = $private_message;

        return $e;
    }

    

    public function sendMailPixForOrder($id_order)
    {
        $transaction = AgYapayTransaction::getByOrderId($id_order);
        if($transaction->type != 3 || !Configuration::get('AGYAPAY_PIX_SEND_EMAIL')){
            return;
        }else{
            $transaction->sendMailPix();
        }
    }


    private function apiListCarriers()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $serializer = $this->get('agti.yapay.infrastructure.serializer.serializer');
        try {
            $carriers = $em->getRepository(Carrier::class)->findAll();
            $r = new ListCarrierResponseSuccess;
            $r->setSuccess(true)
                ->setCarriers($carriers);
            echo $serializer->serialize($r,'json');
        } catch (Exception $e) {
            $r = new ListCarrierResponseError;
            $r->setSuccess(false)
                ->setError($e->getMessage());
            echo $serializer->serialize($r,'json');
        }
    }


    private function apiListAccounts()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $serializer = $this->get('agti.yapay.infrastructure.serializer.serializer');
        try {
            $accounts = $em->getRepository(AgyapaySellerAccount::class)->findAll();
            $r = new ListAccountResponseSuccess;
            $r->setSuccess(true)
                ->setAccounts($accounts);
            echo $serializer->serialize($r,'json');
        } catch (Exception $e) {
            $r = new ListAccountResponseError;
            $r->setSuccess(false)
                ->setError($e->getMessage());
            echo $serializer->serialize($r,'json');
        }
    }

    private function apiDeleteAccount()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $serializer = $this->get('agti.yapay.infrastructure.serializer.serializer');
        try {
            $args = $serializer->deserialize(file_get_contents('php://input'), DeleteAccountArgs::class, 'json');

            $dbEtt = $em->getRepository(AgyapaySellerAccount::class)->findOneBy(['id' => $args->getAccount()->getId()]);
            if (is_null($dbEtt)) {
                throw new Exception("Conta de ID {$args->getAccount()->getId()} não encontrada.");
            }

            $em->remove($dbEtt);
            $em->flush();

            $r = new DeleteAccountResponseSuccess;
            $r->setSuccess(true);
            echo $serializer->serialize($r,'json');
        } catch (Exception $e) {
            $r = new ListAccountResponseError;
            $r->setSuccess(false)
                ->setError($e->getMessage());
            echo $serializer->serialize($r,'json');
        }
    }

    private function apiSaveAccount()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $serializer = $this->get('agti.yapay.infrastructure.serializer.serializer');
        try {
            $args = $serializer->deserialize(file_get_contents('php://input'), SaveAccountArgs::class, 'json');

            $dbEtt = $em->getRepository(AgyapaySellerAccount::class)->findOneBy(['id' => $args->getAccount()->getId()]);
            if (is_null($dbEtt)) {
                if ($args->getAccount()->getId()) {
                    throw new Exception("Conta de ID {$args->getAccount()->getId()} não encontrada.");
                }

                $dbEtt = new AgyapaySellerAccount;
                $em->persist($dbEtt);
            }

            $dbEtt->copyFromEntity($args->getAccount());
            $em->flush();
            
            $r = new SaveAccountResponseSuccess;
            $r->setSuccess(true)
                ->setAccount($dbEtt);

            echo $serializer->serialize($r,'json');
        } catch (Exception $e) {
            $r = new SaveAccountResponseError;
            $r->setSuccess(false)
                ->setError($e->getMessage());
            echo $serializer->serialize($r,'json');
        }
    }    

    private function apiAddCarrierToAccount()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $serializer = $this->get('agti.yapay.infrastructure.serializer.serializer');
        try {
            $args = $serializer->deserialize(file_get_contents('php://input'), AddCarrierToAccountArgs::class, 'json');

            $accountEtt = $em->getRepository(AgyapaySellerAccount::class)->findOneBy(['id' => $args->getAccount()->getId()]);
            $carrierEtt = $em->getRepository(Carrier::class)->findOneBy(['id' => $args->getCarrier()->getId()]);

            if (is_null($accountEtt)) {
                throw new Exception("Conta de ID {$args->getAccount()->getId()} não encontrada.");
            }

            if (is_null($carrierEtt) && $args->getCarrier()->getId() !== null) {
                throw new Exception("Transportadora de ID {$args->getCarrier()->getId()} não encontrada.");
            }


            $criteria = Criteria::create()->where(
                Criteria::expr()->eq('carrier', $carrierEtt)
            );
            
            if (count($accountEtt->getCarriers()->matching($criteria))) {
                if (!is_null($carrierEtt)) {
                    throw new Exception("A transportadora {$carrierEtt->getId()} já está associada à conta {$accountEtt->getId()}.");
                } else {
                    throw new Exception("O envio de produtos virtuais já está associado à conta {$accountEtt->getId()}.");
                }
            }
            
            $asso = new AgyapaySellerAccountCarrier;
            $asso->setAccount($accountEtt)->setCarrier($carrierEtt);
            $accountEtt->getCarriers()->add($asso);
            $em->flush();
            
            $r = new AddCarrierToAccountResponseSuccess;
            $r->setSuccess(true);
            echo $serializer->serialize($r,'json');
        } catch (Exception $e) {
            $r = new AddCarrierToAccountResponseError;
            $r->setSuccess(false)
                ->setError($e->getMessage());

            echo $serializer->serialize($r,'json');
        }
    }

    private function apiRemoveCarrierFromAccount()
    {
        $em = $this->get('doctrine.orm.entity_manager');
        $serializer = $this->get('agti.yapay.infrastructure.serializer.serializer');
        try {
            $args = $serializer->deserialize(file_get_contents('php://input'), RemoveCarrierFromAccountArgs::class, 'json');

            $accountEtt = $em->getRepository(AgyapaySellerAccount::class)->findOneBy(['id' => $args->getAccount()->getId()]);
            $carrierEtt = $em->getRepository(Carrier::class)->findOneBy(['id' => $args->getCarrier()->getId()]);

            if (is_null($accountEtt)) {
                throw new Exception("Conta de ID {$args->getAccount()->getId()} não encontrada.");
            }

            if (is_null($carrierEtt) && $args->getCarrier()->getId() !== null) {
                throw new Exception("Transportadora de ID {$args->getCarrier()->getId()} não encontrada.");
            }


            $criteria = Criteria::create()->where(
                Criteria::expr()->eq('carrier', $carrierEtt)
            );
            
            $asso = $accountEtt->getCarriers()->matching($criteria);
            if (!count($asso)) {
                if (is_null($carrierEtt)) {
                    throw new Exception("A conta {$accountEtt->getId()} não está configurada para o envio de produtos virtuais.");
                } else {
                    throw new Exception("A transportadora {$carrierEtt->getId()} não está associada à conta {$accountEtt->getId()}.");
                }
            }
            
            $em->remove($asso[0]);
            $em->flush();

            
            
            $r = new RemoveCarrierFromAccountResponseSuccess;
            $r->setSuccess(true);
            echo $serializer->serialize($r,'json');
        } catch (Exception $e) {
            $r = new RemoveCarrierFromAccountResponseError;
            $r->setSuccess(false)
                ->setError($e->getMessage());

            echo $serializer->serialize($r,'json');
        }
    }

    /**
     * Retorna o nome mascarado do produto, usando a feature MASKED_NAME se existir,
     * senão retorna o nome do produto do PrestaShop, já removendo caracteres especiais.
     */
    public static function getMaskedProductName($id_product, $id_product_attribute = 0, $fallbackName = '')
    {
        $finalName = '';
        if (class_exists('Feature') && class_exists('Product')) {
            $id_lang = (int)Configuration::get('PS_LANG_DEFAULT');
            // Busca a feature MASKED_NAME
            $sql = new DbQuery();
            $sql->select('id_feature');
            $sql->from('feature_lang');
            $sql->where('name = "MASKED_NAME"');
            $sql->where('id_lang = ' . (int)$id_lang);
            $id_feature = Db::getInstance()->getValue($sql);
            if ($id_feature) {
                $sql = new DbQuery();
                $sql->select('id_feature_value');
                $sql->from('feature_product');
                $sql->where('id_product = ' . (int)$id_product);
                $sql->where('id_feature = ' . (int)$id_feature);
                $id_feature_value = Db::getInstance()->getValue($sql);
                if ($id_feature_value) {
                    $feature_value = new FeatureValue($id_feature_value, $id_lang);
                    if (!empty($feature_value->value)) {
                        $finalName = $feature_value->value;
                    }
                }
            }
            if (!$finalName) {
                $finalName = Product::getProductName($id_product, $id_product_attribute, $id_lang);
            }
        }
        if (!$finalName) {
            $finalName = $fallbackName;
        }
        // Remove caracteres especiais conforme padrão do agbling/agmelhorenvio
        $finalName = str_replace(['^','<','>',';','=','#','{','}'], '', $finalName);
        return $finalName;
    }
}
