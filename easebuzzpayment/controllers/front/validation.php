<?php

include_once(_PS_MODULE_DIR_ . 'easebuzzpayment/tools/EasebuzzLogger.php');
class EasebuzzPaymentValidationModuleFrontController extends ModuleFrontController
{
    public function postProcess() {
        $txnid = Tools::getValue('txnid');
        if (!$txnid) {
            $this->returnError('Invalid transaction ID');
        }
        
        $order_data = Db::getInstance()->getRow('SELECT order_id FROM ' . _DB_PREFIX_ . 'ease_buzz_debug WHERE txn_id = "' . pSQL($txnid) . '"');
        if (!$order_data || !isset($order_data['order_id'])) {
            $this->returnError('Order not found for txn_id: ' . $txnid);
        }

        $order_id = (int) $order_data['order_id'];
        $cart = new Cart((int) Cart::getCartIdByOrderId($order_id));

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
        $amount = (float) Tools::getValue('amount');
        $responcehase = Tools::getValue('hash');

        $responce_info = $salt.'|'.$status.'||||||' . $udf5 . '|' . $udf4 . '|' . $udf3 . '|' . $udf2 . '|' . $udf1 . '|' . $email . '|' . $firstname . '|' . $productinfo . '|' . $amount . '|'.$txnid.'|' . $key;

        $hase = hash('SHA512', $responce_info);
        EasebuzzLogger::addLog('order', __FUNCTION__, '$responce_info hash', $responce_info);
        
        $customData = [
            'txnid' => $txnid,
            'amount' => $amount,
            'customField' => 'YourCustomValue', // Add your custom data
        ];
        
        if (Tools::getValue('status') == 'success') {
            if ($hase == $responcehase) {
                $successtatus = Configuration::get('PS_OS_PAYMENT');
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
        
            $order = new Order($order_id);
            $order->setCurrentState($cancel_status);
        
            $return_url = $this->context->link->getModuleLink('easebuzzpayment', 'orderCancelled', array(
                'id_cart'    => $cart->id,
                'id_module'  => $this->module->id,
                'id_order'   => $order_id,
                'key'        => $customer->secure_key
            ));
        
            Tools::redirect($return_url);
        }elseif (Tools::getValue('status') == 'failure') {
            //failure

            $failstatus = Configuration::get('PS_OS_ERROR');

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
