<?php

class EasebuzzPaymentPaymentFailedModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $id_order = Tools::getValue('id_order');
        $id_cart = Tools::getValue('id_cart');
        $id_module = Tools::getValue('id_module');
        $key = Tools::getValue('key');

        $order = new Order($id_order);

        $resp_data = [
            'shop_name' => $this->context->shop->name,
            'current_state' => $order->current_state,
            'total_to_pay' => $this->context->getCurrentLocale()->formatPrice(
                $order->getOrdersTotalPaid(),
                (new Currency($order->id_currency))->iso_code
            ),
            'status' => 'ok',
            'id_order' => $order->id,
            'reference' => $order->reference,
            'module_dir' => $this->module->getPathUri()
        ];
        $order_data = Db::getInstance()->getRow('SELECT response_body FROM ' . _DB_PREFIX_ . 'ease_buzz_debug WHERE order_id = ' . (int)$order->id);
        if($order_data && isset($order_data['response_body'])){
            $payment_data = json_decode($order_data['response_body']);
            $resp_data['txnid'] = $payment_data->txnid??"";
            $resp_data['email'] = $payment_data->email??"";
            $resp_data['firstname'] = $payment_data->firstname??"";
            $resp_data['phone'] = $payment_data->phone??"";
            $resp_data['mode'] = $payment_data->mode??"";
            $resp_data['easepayid'] = $payment_data->easepayid??"";
        }
        $this->context->smarty->assign($resp_data);
        $this->setTemplate('module:easebuzzpayment/views/templates/hook/displayPaymentErrorReturn.tpl');
    }
}
