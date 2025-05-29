<?php

include_once(_PS_MODULE_DIR_ . 'easebuzzpayment/tools/EasebuzzLogger.php');

class EasebuzzPaymentPaymentAjaxModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
    
        parent::initContent();

        $cart = $this->context->cart;
        EasebuzzLogger::addLog('iframe_payment_process', __FUNCTION__, 'Initiate payment with Cart Id: ', $cart->id);

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            EasebuzzLogger::addLog('iframe_payment_process', __FUNCTION__, 'Cart Data Not Found: ');
            $message = 'Error on initiating payment : Cart Data Not Found';
            $ajax_response = [
                'success' => false,
                'message' => $message
            ];
            die(json_encode($ajax_response));
        }
        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            EasebuzzLogger::addLog('iframe_payment_process', __FUNCTION__, 'Customer Data Not Found: ');
            $message = 'Error on initiating payment : Customer Data Not Found';
            $ajax_response = [
                'success' => false,
                'message' => $message
            ];
            die(json_encode($ajax_response));
        } else {
            EasebuzzLogger::addLog('iframe_payment_process', __FUNCTION__, 'Customer Loaded Successfully', $cart->id);
            $this->context->updateCustomer($customer);
        }
        $currency = $this->context->currency;
        $total = (float) $cart->getOrderTotal(true, Cart::BOTH);
        $extra_vars = array(
            '{total_to_pay}' => Tools::displayPrice($total),
        );

        // EasebuzzLogger::addLog('iframe_payment_process', __FUNCTION__, 'Attempting to Create Order', $cart->id, 'OrderCreateRequest: ');
        // $this->module->validateOrder($cart->id, Configuration::get('PS_OS_EASEBUZZ_PAYMENT_PENDING'), $total, $this->module->displayName, NULL, $extra_vars, (int) $currency->id, false, $customer->secure_key);

        $cart_id = $cart->id;

        $salt = Configuration::get('EASEBUZZ_API_CRED_SALT');
        $base_url = (Configuration::get('EASEBUZZ_ENVIRONMENT') == 'sandbox')?'https://testpay.easebuzz.in/':'https://pay.easebuzz.in/';
        $api_url = $base_url.'payment/initiateLink';
        $key = Configuration::get('EASEBUZZ_API_CRED_ID');
        $amount = (float) $this->context->cart->getOrderTotal(true, Cart::BOTH);
        $productInfo = 'product name'; // Modify as needed
        $firstname = trim($this->context->customer->firstname);
        $address = new Address($this->context->cart->id_address_delivery);
        $phone = !empty($address->phone) ? $address->phone : $address->phone_mobile;
        $email = $this->context->customer->email;
        $surl = $this->context->link->getModuleLink('easebuzzpayment', 'validation');
        $furl = $this->context->link->getModuleLink('easebuzzpayment', 'validation');
        $udf1 = $cart_id;
        $udf2 = '';
        $udf3 = '';
        $udf4 = '';
        $udf5 = '';
        $address1 = preg_replace('/[^A-Za-z0-9\.\,\ ]/', ' ', $address->address1);
        $address2 = preg_replace('/[^A-Za-z0-9\.\,\ ]/', ' ', $address->address2);
        $city = $address->city;
        $state = State::getNameById($address->id_state);
        $country = $address->country;

        $txnid = 'TXN-' . $cart_id .'-'. time();  // Generate a unique transaction ID
        EasebuzzLogger::addLog('iframe_payment_process', __FUNCTION__, 'Generated txn_id: ' . $txnid, $cart->id, 'TxnIdGeneration: ');
    
        $request_Info = $key . '|' . $txnid . '|' . $amount . '|' . $productInfo . '|' . $firstname . '|' . $email . '|' . $udf1 . '|' . $udf2 . '|' . $udf3 . '|' . $udf4 . '|' . $udf5 . '||||||'.$salt;
        $hash = hash('SHA512', $request_Info);
        EasebuzzLogger::addLog('iframe_payment_process', __FUNCTION__, '$request_Info hash', $request_Info);
        $request_data = array(
            'api_url' => $api_url,
            'key' => $key,
            'txnid' => $txnid,
            'amount' => $amount,
            'productinfo' => $productInfo,
            'firstname' => $firstname,
            'phone' => $phone,
            'email' => $email,
            'surl' => $surl,
            'furl' => $furl,
            'hash' => $hash,
            'udf1' => $udf1,
            'udf2' => $udf2,
            'udf3' => $udf3,
            'udf4' => $udf4,
            'udf5' => $udf5,
            'address1' => $address1,
            'address2' => $address2,
            'city' => $city,
            'state' => $state,
            'country' => $country,
        );

        // Insert Log
        Db::getInstance()->insert('ease_buzz_debug', array(
            'cart_id' => (int) $cart_id,
            'txn_id' => pSQL($txnid),
            'request_debug_at' => pSQL(date("Y-m-d H:i:s")),
            'response_debug_at' => pSQL('2018-06-03 00:00:00'),
            'request_body' => pSQL(json_encode($request_data)),
            'response_body' => pSQL('response_info'),
        ));

        EasebuzzLogger::addLog('iframe_payment_process', __FUNCTION__, print_r($request_data, true), $txnid, 'PHP Version: ' . phpversion() .'Payment Request Data: ');
        // Initiate payment request
        $response = $this->initiatePayment($api_url, $request_data);
        EasebuzzLogger::addLog('iframe_payment_process', __FUNCTION__, print_r($response, true), $txnid, 'Payment Response: ');

        if ($response && isset($response['data'])) {
            $ajax_response = [
                'success' => true,
                'message' => 'Payment initialized successfully',
                'access_token' => $response['data']
            ];            
            EasebuzzLogger::addLog('iframe_payment_process', __FUNCTION__, 'Return Token for iframe checkout: ' . $response['data'], $txnid);
        } else {
            $message = 'Error on initiating payment request';
            $ajax_response = [
                'success' => false,
                'message' => $message
            ];
            EasebuzzLogger::addLog('iframe_payment_process', __FUNCTION__, 'Error Initiating access token', $txnid);
        }
        die(json_encode($ajax_response));
    }

    private function initiatePayment($url, $data){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }
}
