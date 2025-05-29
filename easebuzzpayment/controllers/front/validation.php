<?php

include_once(_PS_MODULE_DIR_ . 'easebuzzpayment/tools/EasebuzzLogger.php');
class EasebuzzPaymentValidationModuleFrontController extends ModuleFrontController
{
    public function postProcess() {
        $txnid = Tools::getValue('txnid');
        if (!$txnid) {
            $this->returnError('Invalid transaction ID');
        }
        
        $cart_data = Db::getInstance()->getRow('SELECT cart_id FROM ' . _DB_PREFIX_ . 'ease_buzz_debug WHERE txn_id = "' . pSQL($txnid) . '"');
        if (!$cart_data || !isset($cart_data['cart_id'])) {
            $this->returnError('Order not found for txn_id: ' . $txnid);
        }

        $cart_id = (int) $cart_data['cart_id'];

        $cart = new Cart($cart_id);
        if (!Validate::isLoadedObject($cart)) {
            $this->returnError('Cart not found for ID: ' . $cart_id);
        }

        $this->context->cart = $cart;
        $this->context->cookie->id_cart = $cart->id;
        $this->context->cookie->write();

        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 ||
            $cart->id_address_invoice == 0 || !$this->module->active) {
            $this->returnError('Invalid cart');
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)){
            $this->returnError('Invalid customer');
        } else {
            $this->context->updateCustomer($customer);
        }
        $where = "`txn_id` = '" . pSQL($txnid) . "'";
        Db::getInstance()->update('ease_buzz_debug', array(
            'response_debug_at' => pSQL(date("YmdHis", time())),
            'response_body' => pSQL(json_encode($_POST)),
                ), $where);

        $key = Configuration::get('EASEBUZZ_API_CRED_ID');
        $salt = Configuration::get('EASEBUZZ_API_CRED_SALT');
        $status = Tools::getValue('status');
        $udf1 = Tools::getValue('udf1');
        $udf2 = Tools::getValue('udf2');
        $udf3 = Tools::getValue('udf3');
        $udf4 = Tools::getValue('udf4');
        $udf5 = Tools::getValue('udf5');
        $email = Tools::getValue('email');
        $firstname = Tools::getValue('firstname');
        $productinfo = Tools::getValue('productinfo');
        $amount = Tools::getValue('amount');
        $responcehase = Tools::getValue('hash');

        $responce_info = $salt.'|'.$status.'||||||' . $udf5 . '|' . $udf4 . '|' . $udf3 . '|' . $udf2 . '|' . $udf1 . '|' . $email . '|' . $firstname . '|' . $productinfo . '|' . $amount . '|'.$txnid.'|' . $key;

        $hase = hash('SHA512', $responce_info);
        EasebuzzLogger::addLog('payment_process', __FUNCTION__, '$responce_info hash', $responce_info);
        
        if (Tools::getValue('status') == 'success') {
            if ($hase == $responcehase) {
                $successtatus = Configuration::get('PS_OS_EASEBUZZ_PAYMENT_RECEIVED');

                EasebuzzLogger::addLog('payment_process', __FUNCTION__, 'Attempting to Create Order', $cart->id, 'OrderCreateRequest: ');
                $this->module->validateOrder($cart->id, Configuration::get('PS_OS_EASEBUZZ_PAYMENT_PENDING'), $total, $this->module->displayName, NULL, $extra_vars, (int) $currency->id, false, $customer->secure_key);                
                $order_id = $this->module->currentOrder;

                $order = new Order($order_id);
                $order->setCurrentState($successtatus);

                $return_url = $this->context->link->getPageLink('order-confirmation', true, null, array(
                    'id_cart'    => $cart->id,
                    'id_module'  => $this->module->id,
                    'id_order'   => $order_id,
                    'key'        => $customer->secure_key
                ));
                
                Tools::redirect($return_url);
            } else {
                $this->returnError('Error : Encryption does not match!!');
            }
        } elseif (Tools::getValue('status') == 'userCancelled') {
            // User cancelled
            $cancel_status = Configuration::get('PS_OS_CANCELED');
        
            $this->errors[] = $this->module->l('Payment was cancelled by the user. Please try again.', 'validation');
            // Redirect back to checkout
            Tools::redirect('index.php?controller=order&step=1');            
        }elseif (Tools::getValue('status') == 'failure') {
            //failure

            $failstatus = Configuration::get('PS_OS_ERROR');

            EasebuzzLogger::addLog('payment_process', __FUNCTION__, 'Attempting to Create Order', $cart->id, 'OrderCreateRequest: ');
            $this->module->validateOrder($cart->id, Configuration::get('PS_OS_EASEBUZZ_PAYMENT_PENDING'), $total, $this->module->displayName, NULL, $extra_vars, (int) $currency->id, false, $customer->secure_key);                
            $order_id = $this->module->currentOrder;

            $order = new Order($order_id);
            $order->setCurrentState($failstatus);

            $return_url = $this->context->link->getModuleLink('easebuzzpayment', 'paymentFailed', array(
                'id_cart'    => $cart->id,
                'id_module'  => $this->module->id,
                'id_order'   => $order_id,
                'key'        => $customer->secure_key
            ));
            
            Tools::redirect($return_url);
        }
    }

    protected function returnError($message){
        $this->context->smarty->assign(['error_msg' => $message]);
        $this->setTemplate('module:easebuzzpayment/views/templates/front/error.tpl');
    }
}
