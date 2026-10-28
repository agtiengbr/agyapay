<?php
namespace PrestaShop\PrestaShop\Adapter\Product {
    class PriceFormatter
    {
        public function format($value) { return 'BRL ' . number_format($value, 2); }
    }
}
namespace {
    if (PHP_SAPI !== 'cli') {
        http_response_code(404);
        exit;
    }
    // Dependency doubles isolate module behavior from provider calls and real mail.
    define('_PS_VERSION_', isset($argv[1]) ? $argv[1] : '9.2.0');
    $fixtures = sys_get_temp_dir() . '/agyapay-regression-' . getmypid() . '/';
    mkdir($fixtures . 'agcliente/lib', 0777, true);
    mkdir($fixtures . 'agyapay/vendor', 0777, true);
    file_put_contents($fixtures . 'agcliente/lib/AgPaymentModule.php', "<?php\n");
    file_put_contents($fixtures . 'agyapay/vendor/autoload.php', "<?php\n");
    define('_PS_MODULE_DIR_', $fixtures);
    register_shutdown_function(function () use ($fixtures) {
        unlink($fixtures . 'agcliente/lib/AgPaymentModule.php');
        unlink($fixtures . 'agyapay/vendor/autoload.php');
        foreach (['agcliente/lib', 'agcliente', 'agyapay/vendor', 'agyapay', ''] as $dir) {
            rmdir($fixtures . $dir);
        }
    });
    set_error_handler(function ($severity, $message, $file, $line) {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    });
    class Configuration
    {
        public static $values = [];
        public static function get($key, ...$scope) { return isset(self::$values[$key]) ? self::$values[$key] : false; }
    }
    class Validate
    {
        public static function isEmail($email) { return (bool) filter_var($email, FILTER_VALIDATE_EMAIL); }
        public static function isLoadedObject($object) { return !empty($object->id); }
    }
    class SharedTools
    {
        public static function getValue($key) { return isset($_GET[$key]) ? $_GET[$key] : false; }
        public static function displayPrice($value) { return 'BRL ' . number_format($value, 2); }
        public static function redirect($url) { throw new \RuntimeException('Unexpected redirect: ' . $url); }
    }
    if (version_compare(_PS_VERSION_, '8.0.0', '<')) {
        class Tools extends SharedTools
        {
            public static function displayDate($date, $language = null, $full = false, $separator = null)
            { return $full ? $date : substr($date, 0, 10); }
        }
    } else {
        class Tools extends SharedTools
        {
            public static function displayDate($date, bool $full = false)
            { return $full ? $date : substr($date, 0, 10); }
        }
    }
    class Context
    {
        private static $instance;
        public $shop;
        public $customer;
        public $smarty;
        public $link;
        public static function getContext()
        {
            if (!self::$instance) {
                self::$instance = new self();
                self::$instance->shop = (object) ['name' => 'Regression store'];
                self::$instance->customer = (object) ['id' => 22];
                self::$instance->smarty = new class {
                    public $assigned = [];
                    public function assign($values) { $this->assigned = array_merge($this->assigned, $values); }
                };
                self::$instance->link = new class {
                    public function getModuleLink($module, $controller, $args = []) { return 'https://example.test/' . $controller; }
                    public function getPageLink($controller) { return 'https://example.test/' . $controller; }
                };
            }
            return self::$instance;
        }
    }
    class AgPaymentModule
    {
        public $context;
        public $name;
        public $tab;
        public $version;
        public $author;
        public $bootstrap;
        public $displayName;
        public $description;
        public function __construct() { $this->context = Context::getContext(); }
    }
    class Module { public static function isEnabled($name) { return false; } }
    class AgColumnMapping
    {
        public function setData($data) { return $this; }
        public function addColumn(...$args) { return $this; }
        public function mapsTo($column) { return $this; }
    }
    class AgObjectModel
    {
        const TYPE_INT = 1, TYPE_STRING = 2, TYPE_DATE = 3, TYPE_FLOAT = 4, TYPE_BOOL = 5;
        public $id;
        public static $saved = [];
        public function save() { self::$saved[] = clone $this; return true; }
    }
    class Order
    {
        public $id;
        public $id_customer = 22;
        public $id_shop = 1;
        public $id_lang = 1;
        public $current_state = 1;
        public $reference = 'CORRECT-ORDER-57';
        public $date_add = '2026-10-20 09:30:00';
        public $total_paid = 100.50;
        public $total_paid_tax_incl = '100.50';
        public $total_products_wt = 90.50;
        public $total_shipping_tax_incl = 10.00;
        public function __construct($id) { $this->id = (int) $id; }
        public function getProducts() { return []; }
    }
    class Customer
    {
        public $firstname = 'Test';
        public $lastname = 'Customer';
        public $email = 'customer@example.test';
        public function __construct($id) {}
    }
    class Mail
    {
        public static $results = [];
        public static $calls = [];
        public static function Send(...$args)
        {
            self::$calls[] = $args;
            return self::$results ? array_shift(self::$results) : true;
        }
    }
    class ModuleFrontController
    {
        public $context;
        public $module;
        public $template;
        public function __construct() { $this->context = Context::getContext(); }
        public function initContent() {}
        public function setTemplate($template) { $this->template = $template; }
    }
    require dirname(__DIR__) . '/base.php';
    require dirname(__DIR__) . '/classes/AgYapayTransaction.php';
    require dirname(__DIR__) . '/classes/AgYapayOrderEmails.php';
    require dirname(__DIR__) . '/controllers/front/payorder.php';
    class AgYapay extends BaseAgYapay
    {
        public $formTotals = [];
        public function generateTicketForm($total = 0) { $this->formTotals['ticket'] = $total; return 'ticket'; }
        public function generateCreditCardForm($total = 0) { $this->formTotals['card'] = $total; return 'card'; }
        public function generatePixForm($total = 0) { $this->formTotals['pix'] = $total; return 'pix'; }
    }
    $count = 0;
    function expect($condition, $message)
    {
        global $count;
        if (!$condition) { throw new \RuntimeException($message); }
        ++$count;
    }
    $module = new AgYapay();
    foreach (['cpf_mapping', 'cnpj_mapping', 'social_name_mapping', 'address_number_mapping'] as $property) {
        expect((new \ReflectionClass('BaseAgYapay'))->hasProperty($property), 'Mapping must be declared: ' . $property);
        expect($module->$property instanceof AgColumnMapping, 'Mapping must initialize: ' . $property);
    }
    Configuration::$values = [
        'AGYAPAY_PAY_CLOSED_ORDERS' => 1, 'AGYAPAY_STATUS_4' => 1,
        'AGYAPAY_TICKET_ACTIVE' => 1, 'AGYAPAY_CREDIT_CARD_ACTIVE' => 1, 'AGYAPAY_PIX_ACTIVE' => 1,
        'PS_MAIL_METHOD' => 2, 'AGYAPAY_PAY_EMAIL_ADMINS' => 'admin@example.test',
    ];
    $_GET['id_order'] = 57;
    $controller = new agyapaypayorderModuleFrontController();
    $controller->module = $module;
    $controller->initContent();
    foreach (['ticket', 'card', 'pix'] as $method) {
        expect($module->formTotals[$method] === 100.50, 'Existing order must pass numeric total to ' . $method);
    }
    $transaction = new AgYapayTransaction();
    $transaction->id_order = 57;
    $transaction->pix_expiration_date = '2026-10-30 12:34:56';
    $transaction->pix_qrcode_url = 'https://example.test/qr';
    $transaction->pix_qrcode_hash = 'PIX-LOCAL-ONLY';
    $transaction->remote_token = 'TEST-TOKEN';
    $transaction->value_invoiced = 100.50;
    expect($transaction->sendMailPix() === true, 'PIX email must reach Mail::Send');
    $pixMail = end(Mail::$calls);
    expect($pixMail[1] === 'create_pix', 'Correct PIX template');
    expect($pixMail[3]['{pix_expiration_date}'] === '2026-10-30 12:34:56', 'Expiration date must include time on every supported API');
    expect($pixMail[3]['{order_id}'] === 57, 'PIX email must identify the intended order');
    $order = new Order(57);
    AgObjectModel::$saved = [];
    Mail::$calls = [];
    Configuration::$values['PS_MAIL_METHOD'] = 3;
    expect($module->sendPendingPaymentReminder($order) === false, 'Disabled mail must not mark reminders sent');
    expect(!Mail::$calls && !AgObjectModel::$saved, 'Disabled mail must not send or persist');
    Configuration::$values['PS_MAIL_METHOD'] = 2;
    Configuration::$values['AGYAPAY_PAY_EMAIL_ADMINS'] = "\n invalid \n";
    expect($module->sendPendingPaymentReminder($order) === false, 'Empty/invalid recipients must not mark reminders sent');
    expect(!Mail::$calls && !AgObjectModel::$saved, 'Invalid recipients must not send or persist');
    Configuration::$values['AGYAPAY_PAY_EMAIL_ADMINS'] = " admin@example.test \r\n\nadmin@example.test\ninvalid";
    Mail::$results = [false];
    expect($module->sendPendingPaymentReminder($order) === false, 'Failed mail must remain retryable');
    expect(count(Mail::$calls) === 1 && !AgObjectModel::$saved, 'Failed mail must not write a sent record');
    Mail::$calls = [];
    Mail::$results = [true];
    expect($module->sendPendingPaymentReminder($order) === true, 'Successful retry must be recorded');
    expect(count(Mail::$calls) === 1, 'Recipients must be trimmed, validated and deduplicated');
    expect(count(AgObjectModel::$saved) === 1 && AgObjectModel::$saved[0]->id_order === 57, 'Sent record must belong to the intended order');
    Mail::$calls = [];
    AgObjectModel::$saved = [];
    Configuration::$values['AGYAPAY_PAY_EMAIL_ADMINS'] = "one@example.test\ntwo@example.test";
    Mail::$results = [true, false];
    expect($module->sendPendingPaymentReminder($order) === false, 'Partial failure must not mark the complete reminder sent');
    expect(count(Mail::$calls) === 2 && !AgObjectModel::$saved, 'Every required recipient must succeed before persisting');
    foreach (['create_pix', 'ticket_link', 'pending_payment'] as $template) {
        foreach (['html', 'txt'] as $extension) {
            $placeholders = [];
            foreach (['br', 'en'] as $iso) {
                $file = dirname(__DIR__) . '/mails/' . $iso . '/' . $template . '.' . $extension;
                expect(is_file($file), 'Template must exist: ' . $file);
                preg_match_all('/\{[a-z_]+\}/', file_get_contents($file), $matches);
                $keys = array_values(array_unique($matches[0])); sort($keys); $placeholders[$iso] = $keys;
            }
            expect($placeholders['br'] === $placeholders['en'], 'Fallback must retain every template variable: ' . $template . '.' . $extension);
        }
    }
    echo json_encode(['prestashop_api' => _PS_VERSION_, 'php' => PHP_VERSION, 'assertions' => $count, 'status' => 'passed']) . PHP_EOL;
}
