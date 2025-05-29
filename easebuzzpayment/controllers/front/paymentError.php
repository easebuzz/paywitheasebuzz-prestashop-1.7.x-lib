<?php

class EasebuzzPaymentOrderErrorModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $resp_data = [
            'message' => Tools::getValue('message')??"",
            'id_order' => Tools::getValue('id_order')??""
        ];
        $this->context->smarty->assign($resp_data);
        $this->setTemplate('module:easebuzzpayment/views/templates/hook/displayOrderError.tpl');
    }
}
