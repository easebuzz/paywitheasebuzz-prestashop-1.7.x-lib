<?php

class EasebuzzPaymentPaymentStatusModuleFrontController extends ModuleFrontController
{
    
    public function initContent()
    {
        parent::initContent();
        
        $this->checkPaymentStatusForOrderID();
        return;
    }
    
    private function checkPaymentStatusForOrderID()
    {
        $id_order = (int) Tools::getValue('id_order');
        $base_url = (Configuration::get('EASEBUZZ_ENVIRONMENT') == 'sandbox')?'https://testdashboard.easebuzz.in/':'https://dashboard.easebuzz.in/';
        $api_url = $base_url.'transaction/v2.1/retrieve';
        $salt = Configuration::get('EASEBUZZ_API_CRED_SALT');
        $key = Configuration::get('EASEBUZZ_API_CRED_ID');
        
        $order_data = Db::getInstance()->getRow('SELECT txn_id FROM ' . _DB_PREFIX_ . 'ease_buzz_debug WHERE order_id = ' . $id_order);
        if (!$order_data || !isset($order_data['txn_id'])) {
            echo json_encode(['success' => false, 'message' => 'Order not found for order_id: ' . $id_order]);
            die();
        }

        $txn_id = $order_data['txn_id'] ?? "";
        $hash = hash('SHA512', $key . '|' . $txn_id . '|' . $salt);
        $post_data = [
            'txnid' => $txn_id,
            'key' => $key,
            'hash' => $hash
        ];
        
        $response = $this->checkPaymentStatus($api_url, $post_data);
        
        $status = $response['msg'][0]['status'] ?? "";
        $message = '';

        if ($status === 'success') {
            $successtatus = Configuration::get('PS_OS_PAYMENT');
            $order = new Order($id_order);
            $order->setCurrentState($successtatus);
            $message = 'Payment status updated to success.';
        } elseif ($status === 'userCancelled') {
            $cancel_status = Configuration::get('PS_OS_CANCELED');
            $order = new Order($id_order);
            $order->setCurrentState($cancel_status);
            $message = 'Payment was cancelled by the user.';
        } else {
            $failstatus = Configuration::get('PS_OS_ERROR');
            if ($failstatus) {
                $order = new Order($id_order);
                if (!Validate::isLoadedObject($order)) {
                    echo json_encode(['success' => false, 'message' => 'Order not found for order ID: ' . $id_order]);
                    die();
                }
                try {
                    $order->setCurrentState($failstatus);
                    $message = 'Payment status updated to error state.';
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => 'Failed to update order status: ' . $e->getMessage()]);
                    die();
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid error status.']);
                die();
            }
        }

        echo json_encode(['success' => true, 'message' => $message]);
        die();
    }

    private function checkPaymentStatus($url, $data){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }
}
