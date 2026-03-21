<?php

class AgYapaySendTrackingNumberModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        AgClienteLogger::createLogger(_PS_MODULE_DIR_ . 'agyapay/logs/sendTrackingNumber.log', 1);

        $sql = new DbQuery;
        $sql->select('oc.id_order, oc.tracking_number, oc.id_order_carrier, oc.id_carrier, oc.date_add, t.remote_id, c.url, c.name AS carrier_name, c.external_module_name')
            ->from('order_carrier', 'oc')
            ->join('INNER JOIN ' . _DB_PREFIX_ . 'agyapay_transaction t ON t.id_order=oc.id_order')
            ->join('INNER JOIN ' . _DB_PREFIX_ . 'carrier c ON c.id_carrier = oc.id_carrier')
            ->where('agyapay_sent_tracking_number=0 OR agyapay_sent_tracking_number IS NULL')
            ->where('tracking_number != ""')
            ->where('t.date_add>="2019-05-01"');

        $db_data = Db::getInstance()->executeS($sql);

        AgClienteLogger::addLog(count($db_data) . ' pedidos encontrados.', 1);
        
        if (Configuration::get('AGYAPAY_ENABLE_SANDBOX')) {
            $url = 'https://api.intermediador.sandbox.yapay.com.br/api/v1/transactions/trace';
        } else {
            $url = 'https://api.intermediador.yapay.com.br/api/v1/transactions/trace';
        }

        foreach (@$db_data as $row) {
            try {
                $token = $this->module->checkAccessToken('', $row['id_carrier']);
            } catch (Exception $e) {
                AgClienteLogger::addLog('agyapay - Erro enviando rastreadores - ' . $e->getMessage(), 3);
                exit();
            }

            
            $carrier_tracking_url = str_replace('@', $row['tracking_number'], $row['url']);

            $yapay_data = [
                "access_token" => $token,
                "order_number" => $row['remote_id'],
                "url" => $carrier_tracking_url,
                "code"         => $row['tracking_number'],
                "date_posting" => date('d-m-Y', strtotime($row['date_add'])),
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout in seconds

            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($yapay_data));

            $return['body'] = curl_exec($ch);
            $return['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            //log da requisição
            $request = new AgYaPayRequest;
            $request->endpoint = $url;
            $request->headers = json_encode(array('Content-Type: application/json'));
            $request->method = 'POST';
            $request->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $request->body = json_encode($yapay_data);
            $request->response = json_encode($return['body']);
            $request->time_spent = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
            $request->add();

            if ($return['http_code'] >= 200 && $return['http_code'] < 300) {
                $response = self::produceXMLObjectTree($return['body']);
                if ((string) @$response->message_response->message  === 'success') {
                    Db::getInstance()->update('order_carrier', ['agyapay_sent_tracking_number' => 1], 'id_order_carrier=' . (int)$row['id_order_carrier']);                 
                } else {
                    AgClienteLogger::addLog('agyapay - Retorno inesperado ao enviar código de rastreio do pedido #' . $row['id_order'] . ': ' . json_encode($return), 4);
                }
            }
        }

        exit();
    }

    protected static function produceXMLObjectTree($raw_XML)
    {
        libxml_use_internal_errors(true);
        try {
            $xmlTree = new SimpleXMLElement($raw_XML);
        } catch (Exception $e) {
            // Something went wrong.
            $error_message = 'SimpleXMLElement threw an exception.';
            foreach (libxml_get_errors() as $error_line) {
                $error_message .= "\t" . $error_line->message;
            }

            trigger_error($error_message);
            return false;
        }
        return $xmlTree;
    }
}