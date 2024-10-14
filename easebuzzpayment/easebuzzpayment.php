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
            || !Configuration::deleteByName('PS_OS_EASEBUZZ_PAYMENT')
        ) {
            return false;
        }

        return true;
    }

    public function installOrderState()
    {
        if (Configuration::get('PS_OS_EASEBUZZ_PAYMENT') < 1) {
            $order_state = new OrderState();
            $order_state->send_email = true;
            $order_state->module_name = $this->name;
            $order_state->invoice = false;
            $order_state->color = '#98c3ff';
            $order_state->logable = true;
            $order_state->shipped = false;
            $order_state->unremovable = false;
            $order_state->delivery = false;
            $order_state->hidden = false;
            $order_state->paid = false;
            $order_state->deleted = false;
            $order_state->name = array((int)Configuration::get('PS_LANG_DEFAULT') => pSQL($this->l('Easebuzz - Awaiting confirmation')));
            $order_state->template = array();
            
            foreach (Language::getLanguages() as $l) {
                $order_state->template[$l['id_lang']] = 'easebuzzpayment';
            }
             
            foreach (Language::getLanguages() as $l) {
                $module_path = dirname(__FILE__) . '/views/templates/mails/' . $l['iso_code'] . '/';
                $application_path = _PS_MAIL_DIR_ . $l['iso_code'] . '/';

                if (!file_exists($module_path . 'easebuzzpayment.txt') || !file_exists($module_path . 'easebuzzpayment.html')) {
                    // Fallback to English if language-specific templates are missing
                    $module_path = dirname(__FILE__) . '/views/templates/mails/en/';
                }
                if (!copy($module_path . 'easebuzzpayment.txt', $application_path . 'easebuzzpayment.txt')
                    || !copy($module_path . 'easebuzzpayment.html', $application_path . 'easebuzzpayment.html')
                ) {
                    return false;
                }
            }

            if ($order_state->add()) {
                Configuration::updateValue('PS_OS_EASEBUZZ_PAYMENT', $order_state->id);

                // Copy the module logo
                copy(dirname(__FILE__) . '/logo.gif', _PS_IMG_DIR_ . 'os/' . $order_state->id . '.gif');
                copy(dirname(__FILE__) . '/logo.gif', _PS_IMG_DIR_ . 'tmp/order_state_mini_' . $order_state->id . '.gif');
            } else {
                return false;
            }
        }

        return true;
    }

    protected function createTables()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "ease_buzz_debug` (
            `debug_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `order_id` INT(20) NOT NULL,
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
        $payment_option->setCallToActionText($this->l('Pay with '))
            ->setAction($this->context->link->getModuleLink($this->name, 'payment', [], true))
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/logo.gif'));
    
        return [$payment_option];
    }

    public function getExternalPaymentOption()
    {
        $externalOption = new \PrestaShop\PrestaShop\Core\Payment\PaymentOption();
        $externalOption->setCallToActionText($this->l('Pay by Eazebuzz'))
            ->setAction($this->context->link->getModuleLink($this->name, 'payment', [], true))
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/logo.gif'));
            

        return $externalOption;
    }

    public function hookDisplayPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }
        EasebuzzLogger::addLog('order', __FUNCTION__, print_r($params['order'], true), $params['order']->id, 'PaymentResponse: ');
        
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
        $order_data = Db::getInstance()->getRow('SELECT response_body FROM ' . _DB_PREFIX_ . 'ease_buzz_debug WHERE order_id = ' . (int)$params['order']->id);
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
            case Configuration::get('PS_OS_PAYMENT'):
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
    
        $paymentAcceptedStatus = Configuration::get('PS_OS_PAYMENT');
    
        if (strpos($order->payment, 'Easebuzz') !== false && $order->current_state != $paymentAcceptedStatus) {
            $paymentStatusUrl = $this->context->link->getModuleLink(
                'easebuzzpayment',
                'paymentstatus',
                ['id_order' => $order->id]
            );
    
            $this->context->smarty->assign([
                'paymentStatusUrl' => $paymentStatusUrl,
            ]);
    
            return $this->display(__FILE__, 'views/templates/front/check_payment_button.tpl');
        }
    
        return '';
    }

}
