<?php

namespace PrestaShop\PrestaShop\Adapter\Product {
    class PriceFormatter
    {
        public function format($value, $currency = null)
        {
            return 'BRL ' . number_format($value, 2);
        }
    }
}

namespace {
    if (PHP_SAPI !== 'cli') {
        http_response_code(404);
        exit;
    }

    define('_PS_MODULE_DIR_', dirname(__DIR__, 2) . '/');
    define('_PS_VERSION_', isset($argv[1]) ? $argv[1] : '9.2.1');

    set_error_handler(function ($severity, $message, $file, $line) {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    });

    class AgObjectModel
    {
        const TYPE_INT = 1, TYPE_STRING = 2, TYPE_DATE = 3, TYPE_FLOAT = 4, TYPE_BOOL = 5;
        public $id;

        public function hydrate($data)
        {
            foreach ($data as $name => $value) {
                $this->$name = $value;
            }
            $this->id = isset($data['id_agyapay_transaction']) ? $data['id_agyapay_transaction'] : null;
        }
    }

    class DbQuery
    {
        public $conditions = [];
        public function select($value) { return $this; }
        public function from($table, $alias = null) { return $this; }
        public function where($value) { $this->conditions[] = $value; return $this; }
        public function orderBy($value) { return $this; }
    }

    class Db
    {
        public static $calls = [];
        public static $row = [];
        public static function getInstance() { return new self(); }
        public function getRow($query)
        {
            self::$calls[] = $query;
            return self::$row;
        }
    }

    function pSQL($value)
    {
        return addslashes($value);
    }

    class Tools
    {
        public static $hash;
        public static function getValue($key) { return self::$hash; }
        public static function displayPrice($value) { return 'BRL ' . number_format($value, 2); }
    }

    class Validate
    {
        public static function isLoadedObject($value) { return !empty($value->id); }
    }

    class ModuleFrontController
    {
        public $module;
        public $context;
        public $template;

        public function __construct()
        {
            $this->module = (object) ['active' => true, 'name' => 'agyapay'];
            $this->context = (object) [
                'currency' => (object) ['iso_code' => 'BRL'],
                'smarty' => new class {
                    public $assigned = [];
                    public function assign($values) { $this->assigned = array_merge($this->assigned, $values); }
                },
            ];
        }

        public function initContent() {}
        public function setTemplate($template) { $this->template = $template; }
    }

    require dirname(__DIR__) . '/classes/AgYapayTransaction.php';
    require dirname(__DIR__) . '/controllers/front/bankslip.php';

    $assertions = 0;
    function expect($condition, $message)
    {
        global $assertions;
        if (!$condition) {
            throw new \RuntimeException($message);
        }
        ++$assertions;
    }

    $invalid = [null, false, 1, [], ['hash'], new \stdClass(), '', str_repeat('a', 39),
        str_repeat('a', 41), str_repeat('g', 40), str_repeat('a', 40) . "'",
        str_repeat('a', 40) . '"', str_repeat('a', 40) . "\n", str_repeat('a', 40) . "\0"];

    foreach ($invalid as $hash) {
        Db::$calls = [];
        $transaction = AgYapayTransaction::getBankSlipFile($hash);
        expect(!Validate::isLoadedObject($transaction), 'Invalid hash must return an unloaded transaction');
        expect(!Db::$calls, 'Invalid hash must not reach the database');

        Tools::$hash = $hash;
        http_response_code(200);
        $controller = new \AgYapaybankslipModuleFrontController();
        $controller->initContent();
        expect(http_response_code() === 400, 'Invalid request must return HTTP 400');
        expect(!Db::$calls, 'Invalid controller request must not reach the database');
    }

    foreach ([sha1('existing-bank-slip'), strtoupper(sha1('existing-bank-slip'))] as $hash) {
        Db::$row = ['id_agyapay_transaction' => 7, 'hash_bank_slip' => $hash,
            'expiration_date' => date('Y-m-d', strtotime('+1 day')), 'value_invoiced' => 98.81];
        Db::$calls = [];
        Tools::$hash = $hash;
        http_response_code(200);
        $controller = new \AgYapaybankslipModuleFrontController();
        $controller->initContent();
        expect(count(Db::$calls) === 1, 'Valid hash must perform one lookup');
        expect(Db::$calls[0]->conditions === ["hash_bank_slip = '" . $hash . "'"], 'Lookup must use the exact quoted hash');
        expect($controller->template === 'module:agyapay/views/templates/front/bank_slip.tpl', 'Valid boleto must keep its template');
        expect($controller->context->smarty->assigned['transaction']->id === 7, 'Valid boleto must preserve its transaction');
        expect($controller->context->smarty->assigned['formattedPrice'] === 'BRL 98.81', 'Valid boleto must preserve its value');
    }

    Db::$row = [];
    $controller = new \AgYapaybankslipModuleFrontController();
    $controller->initContent();
    expect($controller->context->smarty->assigned['error'] === 'Boleto não encontrado', 'Unknown valid hash must keep the existing error');

    Db::$row = ['id_agyapay_transaction' => 7, 'expiration_date' => '2020-01-01'];
    $controller = new \AgYapaybankslipModuleFrontController();
    $controller->initContent();
    expect(strpos($controller->context->smarty->assigned['error'], 'O boleto venceu') === 0, 'Expired boleto must keep its existing error');

    echo $assertions . ' bank-slip security assertions passed on PrestaShop ' . _PS_VERSION_ . PHP_EOL;
}
