<?php

include_once(_PS_MODULE_DIR_ . 'easebuzzpayment/tools/EasebuzzLogger.php');

class EasebuzzPaymentValidationAjaxModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        // Capture the transaction ID
        $txnid = $data['txnid'];
        if (!$txnid) {
            $this->returnJsonError('Invalid transaction ID');
        }

        // Fetch the cart using the transaction ID
        $cart_data = Db::getInstance()->getRow('SELECT cart_id FROM ' . _DB_PREFIX_ . 'ease_buzz_debug WHERE txn_id = "' . pSQL($txnid) . '"');
        if (!$cart_data || !isset($cart_data['cart_id'])) {
            $this->returnJsonError('Cart data not found for txn_id: ' . $txnid);
        }

        $cart_id = (int) $cart_data['cart_id'];
        $cart = new Cart($cart_id);
        if (!Validate::isLoadedObject($cart)) {
            $this->returnJsonError('Cart not found for ID: ' . $cart_id);
        }

        $this->context->cart = $cart;
        $this->context->cookie->id_cart = $cart->id;
        $this->context->cookie->write();

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 ||
            $cart->id_address_invoice == 0 || !$this->module->active) {
            $this->returnJsonError('Invalid cart');
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            $this->returnJsonError('Invalid customer');
        }

        // Log the response
        $where = "`txn_id` = '" . pSQL($txnid) . "'";
        Db::getInstance()->update('ease_buzz_debug', [
            'response_debug_at' => pSQL(date("YmdHis", time())),
            'response_body' => pSQL(json_encode($data)),
        ], $where);

        // Validate the response hash
        $key = Configuration::get('EASEBUZZ_API_CRED_ID');
        $salt = Configuration::get('EASEBUZZ_API_CRED_SALT');
        $status = $data['status'];
        $udf1 = $data['udf1'];
        $udf2 = $data['udf2'];
        $udf3 = $data['udf3'];
        $udf4 = $data['udf4'];
        $udf5 = $data['udf5'];
        $email = $data['email'];
        $firstname = $data['firstname'];
        $productinfo = $data['productinfo'];
        $amount = $data['amount'];
        $response_hash = $data['hash'];

        $response_info = $salt . '|' . $status . '||||||' . $udf5 . '|' . $udf4 . '|' . $udf3 . '|' . $udf2 . '|' . $udf1 . '|' . $email . '|' . $firstname . '|' . $productinfo . '|' . $amount . '|' . $txnid . '|' . $key;

        $generated_hash = hash('SHA512', $response_info);
        EasebuzzLogger::addLog('iframe_payment_process', __FUNCTION__, '$response_info hash', $response_info);

        if ($status === 'success') {
            if ($generated_hash === $response_hash) {
                $success_status = Configuration::get('PS_OS_EASEBUZZ_PAYMENT_RECEIVED');

                EasebuzzLogger::addLog('iframe_payment_process', __FUNCTION__, 'Attempting to Create Order', $cart->id, 'OrderCreateRequest: ');
                $this->module->validateOrder($cart->id, Configuration::get('PS_OS_EASEBUZZ_PAYMENT_PENDING'), $total, $this->module->displayName, NULL, $extra_vars, (int) $currency->id, false, $customer->secure_key);                
                $order_id = $this->module->currentOrder;

                $order = new Order($order_id);
                $order->setCurrentState($success_status);

                $return_url = $this->context->link->getPageLink('order-confirmation', true, null, [
                    'id_cart' => $cart->id,
                    'id_module' => $this->module->id,
                    'id_order' => $order_id,
                    'key' => $customer->secure_key
                ]);

                $this->returnJsonSuccess('Transaction successful.', ['redirect_url' => $return_url]);
            } else {
                $this->returnJsonError('Error: Encryption does not match.');
            }
        } elseif ($status === 'userCancelled') {
            $cancel_status = Configuration::get('PS_OS_CANCELED');
            $this->returnJsonError('Transaction cancelled by user.');
        } elseif ($status === 'failure') {
            $fail_status = Configuration::get('PS_OS_ERROR');

            EasebuzzLogger::addLog('iframe_payment_process', __FUNCTION__, 'Attempting to Create Order', $cart->id, 'OrderCreateRequest: ');
            $this->module->validateOrder($cart->id, Configuration::get('PS_OS_EASEBUZZ_PAYMENT_PENDING'), $total, $this->module->displayName, NULL, $extra_vars, (int) $currency->id, false, $customer->secure_key);                
            $order_id = $this->module->currentOrder;

            $order = new Order($order_id);
            $order->setCurrentState($fail_status);

            $return_url = $this->context->link->getModuleLink('easebuzzpayment', 'paymentFailed', [
                'id_cart' => $cart->id,
                'id_module' => $this->module->id,
                'id_order' => $order_id,
                'key' => $customer->secure_key
            ]);

            $this->returnJsonSuccess('Transaction failed.', ['redirect_url' => $return_url]);
        } else {
            $this->returnJsonError('Unknown transaction status: ' . $status);
        }
    }

    protected function returnJsonError($message)
    {
        header('Content-Type: application/json');
        die(json_encode(['success' => false, 'message' => $message]));
    }

    protected function returnJsonSuccess($message, $data = [])
    {
        header('Content-Type: application/json');
        die(json_encode(['success' => true, 'message' => $message, 'data' => $data]));
    }
}
