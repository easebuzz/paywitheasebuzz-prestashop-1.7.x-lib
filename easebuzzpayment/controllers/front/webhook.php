<?php

class EasebuzzPaymentWebhookModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $data = file_get_contents('php://input');
        parse_str(urldecode($data), $jsonData);

        EasebuzzLogger::addLog('webhook', __FUNCTION__, $data, 0, 'WebhookReceived: ');

        if (isset($jsonData['txnid']) && isset($jsonData['status'])) {

            $responcehash = $jsonData['hash'];

            $key = Configuration::get('EASEBUZZ_API_CRED_ID');
            $salt = Configuration::get('EASEBUZZ_API_CRED_SALT');
            $txnId = $jsonData['txnid'];            
            $status = $jsonData['status'];
            $udf1 = $jsonData['udf1'];
            $udf2 = $jsonData['udf2'];
            $udf3 = $jsonData['udf3'];
            $udf4 = $jsonData['udf4'];
            $udf5 = $jsonData['udf5'];
            $email = $jsonData['email'];
            $firstname = $jsonData['firstname'];
            $productinfo = $jsonData['productinfo'];
            $amount = (float) $jsonData['amount'];

            $responce_info = $salt.'|'.$status.'||||||' . $udf5 . '|' . $udf4 . '|' . $udf3 . '|' . $udf2 . '|' . $udf1 . '|' . $email . '|' . $firstname . '|' . $productinfo . '|' . $amount . '|'.$txnId.'|' . $key;
            $generated_hash = hash('SHA512', $responce_info);

            if($responcehash != $generated_hash){
                EasebuzzLogger::addLog('webhook', __FUNCTION__, 'Hash mismatch', $txnId, 'Hash Mismatch: ');
                die('hash mismatches');
            }
            Db::getInstance()->insert('ease_buzz_webhook_log', [
                'txn_id' => pSQL($txnId),
                'status' => pSQL($status),
                'webhook_body' => pSQL($data)
            ]);

            $sql = 'SELECT order_id FROM ' . _DB_PREFIX_ . 'ease_buzz_debug WHERE txn_id = "' . pSQL($txnId) . '"';
            $orderId = Db::getInstance()->getValue($sql);

            if ($orderId) {
                $order = new Order($orderId);

                switch ($status) {
                    case 'success':
                        $order->setCurrentState(Configuration::get('PS_OS_PAYMENT'));
                        break;
                    case 'failure':
                        $order->setCurrentState(Configuration::get('PS_OS_CANCELED'));
                        break;
                    case 'userCancelled':
                        $order->setCurrentState(Configuration::get('PS_OS_CANCELED'));
                        break;
                    default:
                        break;
                }
            }
        }

        header('HTTP/1.1 200 OK');
        die('Webhook received');
    }
}
