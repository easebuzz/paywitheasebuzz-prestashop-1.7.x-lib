<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once(_PS_MODULE_DIR_ . 'easebuzzpayment/tools/EasebuzzLogger.php');

class EasebuzzPayment extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'easebuzzpayment';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'Adri IT Solutions';
        $this->bootstrap = true;
        $this->controllers = ['payment', 'validation'];
        $this->is_eu_compatible = 1;
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->l('Easebuzz Payment');
        $this->description = $this->l('Easebuzz Payment Gateway enables secure, seamless online payments with multiple payment options, easy integration, and real-time transaction tracking.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    public function install()
    {
        if (!parent::install()
            || !$this->registerHook('paymentOptions')
            || !$this->registerHook('displayPaymentReturn')
            || !$this->registerHook('displayOrderDetail')
            || !$this->installOrderState()
            || !$this->createTables()
        ) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall()
            || !Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ease_buzz_debug`')
            || !Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'ease_buzz_webhook_log`')
            || !Configuration::deleteByName('PS_OS_EASEBUZZ_PAYMENT_COMPLETED')
            || !Configuration::deleteByName('PS_OS_EASEBUZZ_PAYMENT_RECEIVED')
            || !Configuration::deleteByName('PS_OS_EASEBUZZ_PAYMENT_FAILED')
            || !Configuration::deleteByName('PS_OS_EASEBUZZ_PAYMENT_PENDING')
        ) {
            return false;
        }

        return true;
    }

    public function installOrderState()
    {
        $statuses = [
            [
                'name' => 'Payment Completed',
                'color' => '#28a745',
                'logable' => true,
                'paid' => true,
                'send_mail' => false
            ],
            [
                'name' => 'Payment Received',
                'color' => '#17a2b8',
                'logable' => true,
                'paid' => true,
                'send_mail' => false
            ],
            [
                'name' => 'Payment Failed',
                'color' => '#dc3545',
                'logable' => false,
                'paid' => false,                
                'send_mail' => false
            ],
            [
                'name' => 'Payment Pending',
                'color' => '#ffc107',
                'logable' => false,
                'paid' => false,
                'send_mail' => false
            ]
        ];

        foreach ($statuses as $status) {
            if (!Configuration::get('PS_OS_EASEBUZZ_' . strtoupper(str_replace(' ', '_', $status['name'])))) {
                $order_state = new OrderState();
                $order_state->send_email = $status['send_mail'];
                $order_state->module_name = $this->name;
                $order_state->invoice = true;
                $order_state->color = $status['color'];
                $order_state->logable = $status['logable'];
                $order_state->shipped = false;
                $order_state->unremovable = false;
                $order_state->delivery = false;
                $order_state->hidden = false;
                $order_state->paid = $status['paid'];
                $order_state->deleted = false;
                $order_state->name = array((int)Configuration::get('PS_LANG_DEFAULT') => pSQL($this->l($status['name'])));
                $order_state->template = array();
    
                foreach (Language::getLanguages() as $l) {
                    $order_state->template[$l['id_lang']] = 'easebuzzpayment';
                }
    
                // foreach (Language::getLanguages() as $l) {
                //     $module_path = dirname(__FILE__) . '/views/templates/mails/' . $l['iso_code'] . '/';
                //     $application_path = _PS_MAIL_DIR_ . $l['iso_code'] . '/';
    
                //     if (!file_exists($module_path . 'easebuzzpayment.txt') || !file_exists($module_path . 'easebuzzpayment.html')) {
                //         // Fallback to English if language-specific templates are missing
                //         $module_path = dirname(__FILE__) . '/views/templates/mails/en/';
                //     }
                //     if (!copy($module_path . 'easebuzzpayment.txt', $application_path . 'easebuzzpayment.txt')
                //         || !copy($module_path . 'easebuzzpayment.html', $application_path . 'easebuzzpayment.html')
                //     ) {
                //         return false;
                //     }
                // }
    
                if ($order_state->add()) {
                    Configuration::updateValue('PS_OS_EASEBUZZ_' . strtoupper(str_replace(' ', '_', $status['name'])), $order_state->id);
    
                    // Copy the module logo
                    copy(dirname(__FILE__) . '/logo.gif', _PS_IMG_DIR_ . 'os/' . $order_state->id . '.gif');
                    copy(dirname(__FILE__) . '/logo.gif', _PS_IMG_DIR_ . 'tmp/order_state_mini_' . $order_state->id . '.gif');
                } else {
                    return false;
                }
            }
        }

        return true;
    }

    protected function createTables()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "ease_buzz_debug` (
            `debug_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `cart_id` INT(20) NOT NULL,
            `txn_id` VARCHAR(64) NOT NULL,
            `request_debug_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `response_debug_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `request_body` TEXT NULL,
            `response_body` TEXT NULL,
            PRIMARY KEY (`debug_id`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";
    
        $webhook_log_sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "ease_buzz_webhook_log` (
            `log_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `txn_id` VARCHAR(64) NOT NULL,
            `status` VARCHAR(32) NOT NULL,
            `webhook_received_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `webhook_body` TEXT NULL,
            PRIMARY KEY (`log_id`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";

        return Db::getInstance()->execute($sql) && Db::getInstance()->execute($webhook_log_sql);
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return [];
        }
        
        $payment_option = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();

        if(Configuration::get('EASEBUZZ_CHECKOUT_OPTIONS') == 'hosted'){
            $payment_option->setCallToActionText($this->l('Pay with '))
                        ->setAction($this->context->link->getModuleLink($this->name, 'payment', [], true))
                        ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/logo.gif'));
        }else{
            $app_env = Configuration::get('EASEBUZZ_ENVIRONMENT') == 'sandbox' ? 'test' : 'prod';
            $easebuzz_checkout_key = Configuration::get('EASEBUZZ_API_CRED_ID');

            // Assign variables to Smarty
            $this->context->smarty->assign([
                'easebuzz_checkout_key' => $easebuzz_checkout_key,
                'app_env' => $app_env,
                'module_link' => $this->context->link->getModuleLink($this->name, 'paymentAjax', ['ajax' => 1], true),
                'validation_link' => $this->context->link->getModuleLink($this->name, 'validationAjax', ['ajax' => 1], true)
            ]);

            $payment_option->setCallToActionText($this->l('Pay with '))
                        ->setAction($this->context->link->getModuleLink($this->name, 'payment', ['ajax' => 1], true))
                        ->setAdditionalInformation($this->fetch('module:easebuzzpayment/views/templates/front/payment_script.tpl'))
                        ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/logo.gif'));
        }
        
    
        return [$payment_option];
    }

    public function hookDisplayPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }
        EasebuzzLogger::addLog('payment_process', __FUNCTION__, print_r($params['order'], true), $params['order']->id, 'PaymentResponse: ');
        
        $resp_data = [
            'shop_name' => $this->context->shop->name,
            'current_state' => $params['order']->current_state,
            'total_to_pay' => $this->context->getCurrentLocale()->formatPrice(
                $params['order']->getOrdersTotalPaid(),
                (new Currency($params['order']->id_currency))->iso_code
            ),
            'status' => 'ok',
            'id_order' => $params['order']->id,
            'reference' => $params['order']->reference,
            'module_dir' => $this->getPathUri()
        ];
        $order_data = Db::getInstance()->getRow('SELECT response_body FROM ' . _DB_PREFIX_ . 'ease_buzz_debug WHERE cart_id = ' . (int)$params['order']->id_cart);
        if($order_data && isset($order_data['response_body'])){
            $payment_data = json_decode($order_data['response_body']);
            $resp_data['txnid'] = $payment_data->txnid??"";
            $resp_data['email'] = $payment_data->email??"";
            $resp_data['firstname'] = $payment_data->firstname??"";
            $resp_data['phone'] = $payment_data->phone??"";
            $resp_data['mode'] = $payment_data->mode??"";
            $resp_data['easepayid'] = $payment_data->easepayid??"";
        }
        $this->smarty->assign($resp_data);
        $template = '';
        switch ($params['order']->current_state) {
            case Configuration::get('PS_OS_EASEBUZZ_PAYMENT_RECEIVED'):
                $template = 'displayPaymentReturn.tpl';
                break;
            case Configuration::get('PS_OS_CANCELED'):
                $template = 'displayPaymentCancelReturn.tpl';
                break;
            default:
                $template = 'displayPaymentErrorReturn.tpl';
                break;
        }
        return $this->fetch('module:easebuzzpayment/views/templates/hook/'.$template);
    }

    public function getContent()
    {
        require_once dirname(__FILE__) . '/controllers/hook/getContent.php';
        
        $controller = new EasebuzzPaymentGetContentController($this, __FILE__, $this->_path);
        return $controller->run();
    }
    
    public function hookDisplayOrderDetail($params) {
        $order = $params['order'];
    
        $paymentAcceptedStatus = Configuration::get('PS_OS_EASEBUZZ_PAYMENT_RECEIVED');
    
        if (strpos($order->payment, 'Easebuzz') !== false && $order->current_state != $paymentAcceptedStatus) {
            $paymentStatusUrl = $this->context->link->getModuleLink(
                'easebuzzpayment',
                'paymentstatus',
                ['id_cart' => $order->id_cart, 'id_order' => $order->id]
            );
    
            $this->context->smarty->assign([
                'paymentStatusUrl' => $paymentStatusUrl,
            ]);
    
            return $this->display(__FILE__, 'views/templates/front/check_payment_button.tpl');
        }
    
        return '';
    }

}
