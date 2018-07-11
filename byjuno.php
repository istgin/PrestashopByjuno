<?php
if (!defined('_PS_VERSION_'))
    exit;

if (!defined('_PS_MODULE_INTRUMCOM_API')) {
    require(_PS_MODULE_DIR_ . 'byjuno/api/intrum.php');
    require(_PS_MODULE_DIR_ . 'byjuno/api/library_prestashop.php');
}

class Byjuno extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'byjuno';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'Byjuno.ch (http://www.byjuno.ch/)';
        $this->controllers = array('payment', 'validation', 'errorpayment');
        $this->is_eu_compatible = 1;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Byjuno');
        $this->description = $this->l('Byjuno payment gateway');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.6.99.99');
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active)
            return;

        $state = $params['objOrder']->getCurrentState();
        if (in_array($state, array(Configuration::get('BYJUNO_ORDER_STATE_COMPLETE')))) {
            $this->smarty->assign(array(
                'total_to_pay' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
                'status' => 'ok',
                'id_order' => $params['objOrder']->id
            ));
            if (isset($params['objOrder']->reference) && !empty($params['objOrder']->reference))
                $this->smarty->assign('reference', $params['objOrder']->reference);
        } else {
            $this->smarty->assign('status', 'failed');
        }
        return $this->display(__FILE__, 'payment_return.tpl');
    }

    public function hookPayment($params)
    {
        if (!$this->active)
            return;

        $total = $this->context->cart->getOrderTotal(true, Cart::BOTH);
        if ($total < Configuration::get("BYJUNO_MIN_AMOUNT") || $total > Configuration::get("BYJUNO_MAX_AMOUNT")) {
            returnl;
        }

        $byjuno_invoice = false;
        $byjuno_installment = false;
        if (Configuration::get("single_invoice") == 'enable' || Configuration::get("byjuno_invoice") == 'enable') {
            $byjuno_invoice = true;
        }
        if (Configuration::get("installment_3") == 'enable'
            || Configuration::get("installment_10") == 'enable'
            || Configuration::get("installment_12") == 'enable'
            || Configuration::get("installment_24") == 'enable'
            || Configuration::get("installment_4x12") == 'enable'
        ) {
            $byjuno_installment = true;
        }
        if (($byjuno_invoice || $byjuno_installment) && Configuration::get("BYJUNO_CREDIT_CHECK") == 'enable') {
            $status = 0;
            $invoice_address = new Address($this->context->cart->id_address_invoice);
            $request = CreatePrestaShopRequest($this->context->cart, $this->context->customer, $this->context->currency, "CREDITCHECK");

            $type = "Credit check";
            $b2b = Configuration::get("BYJUNO_B2B") == 'enable';
            $xml = "";
            if ($b2b && !empty($invoice_address->company)) {
                $type = "Credit check B2B";
                $xml = $request->createRequestCompany();
            } else {
                $xml = $request->createRequest();
            }

            $byjunoCommunicator = new ByjunoCommunicator();
            $byjunoCommunicator->setServer(Configuration::get("INTRUM_MODE"));
            $response = $byjunoCommunicator->sendRequest($xml, (int)Configuration::get("BYJUNO_CONN_TIMEOUT"));

            if ($response) {
                $byjunoResponse = new ByjunoResponse();
                $byjunoResponse->setRawResponse($response);
                $byjunoResponse->processResponse();
                $status = $byjunoResponse->getCustomerRequestStatus();
            }
            $byjunoLogger = ByjunoLogger::getInstance();
            $byjunoLogger->log(Array(
                "firstname" => $request->getFirstName(),
                "lastname" => $request->getLastName(),
                "town" => $request->getTown(),
                "postcode" => $request->getPostCode(),
                "street" => trim($request->getFirstLine() . ' ' . $request->getHouseNumber()),
                "country" => $request->getCountryCode(),
                "ip" => $_SERVER["REMOTE_ADDR"],
                "status" => $status,
                "request_id" => $request->getRequestId(),
                "type" => $type,
                "error" => ($status == 0) ? "ERROR" : "",
                "response" => $response,
                "request" => $xml
            ));
            if (!byjunoIsStatusOk($status, "BYJUNO_CDP_ACCEPT")) {
                return;
            }
        }
        $lang = 'de';
        $ln = Context::getContext()->language->iso_code;
        if ($ln == 'de' || $ln == 'en' || $ln == 'it' || $ln == 'fr') {
            $lang = $ln;
        }
        $this->smarty->assign(array(
            'byjuno_invoice' => $byjuno_invoice,
            'byjuno_installment' => $byjuno_installment,
            'lang' => $lang,
            'this_path' => $this->_path,
            'this_path_bw' => $this->_path,
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/'
        ));
        return $this->display(__FILE__, 'payment.tpl');
    }


    public function hookHeader($params)
    {
        $this->context->controller->addCSS($this->_path . 'byjuno.css', 'all');
    }

    /*
    public function hookDisplayPaymentEU($params)
    {
        if (!$this->active)
            return;

        $payment_options = array(
            'cta_text' => $this->l('XXXX'),
            'logo' => Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/byjuno.jpg'),
            'action' => $this->context->link->getModuleLink($this->name, 'validation', array(), true)
        );

        return $payment_options;
    }
    */

    public function addOrderState($name, $color = '#FFF000', $send_mail = false, $paid = false)
    {
        $states = OrderState::getOrderStates((int)Configuration::get('PS_LANG_DEFAULT'));

        $currentStates = array();

        foreach ($states as $state) {
            $state = (object)$state;
            $currentStates[$state->id_order_state] = $state->name;
        }
        if (($state_id = array_search($this->l($name), $currentStates)) === false) {
            $defaultOrderState = new OrderStateCore();
            $defaultOrderState->name = array(Configuration::get('PS_LANG_DEFAULT') => $this->l($name));
            $defaultOrderState->module_name = $this->name;
            $defaultOrderState->send_mail = $send_mail;
            $defaultOrderState->template = '';
            $defaultOrderState->invoice = false;
            $defaultOrderState->color = $color;
            $defaultOrderState->unremovable = false;
            $defaultOrderState->logable = false;
            $defaultOrderState->paid = $paid;
            $defaultOrderState->add();
        } else {
            $defaultOrderState = new stdClass;
            $defaultOrderState->id = $state_id;
        }
        return $defaultOrderState->id;
    }


    public function install()
    {
        if (!parent::install()
            || !$this->registerHook('payment')
            || !$this->registerHook('paymentReturn')
            || !$this->registerHook('displayAfterBodyOpeningTag')
            || !$this->registerHook('displayBeforeShoppingCartBlock')
            || !$this->registerHook('actionOrderStatusPostUpdate')
            || !$this->registerHook('actionOrderSlipAdd')
            || !$this->registerHook('header')
        ) {
            return false;
        }

        Db::getInstance()->Execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'intrum_logs` (
                  `intrum_id` int(10) unsigned NOT NULL auto_increment,
                  `firstname` varchar(250) default NULL,
                  `lastname` varchar(250) default NULL,
                  `town` varchar(250) default NULL,
                  `postcode` varchar(250) default NULL,
                  `street` varchar(250) default NULL,
                  `country` varchar(250) default NULL,
                  `ip` varchar(250) default NULL,
                  `status` varchar(250) default NULL,
                  `request_id` varchar(250) default NULL,
                  `type` varchar(250) default NULL,
                  `error` text default NULL,
                  `response` text default NULL,
                  `request` text default NULL,
                  `creation_date` TIMESTAMP NULL DEFAULT now() ,
                  PRIMARY KEY  (`intrum_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');

        $defaultStateId = $this->addOrderState("Awaiting for Byjuno");
        $receivedPaymentId = $this->addOrderState("Byjuno payment success", "#32CD32", true, true);
        $s4FailId = $this->addOrderState("Byjuno S4 fail", "#FF0000", true, true);
        $s5FailId = $this->addOrderState("Byjuno S5 fail", "#FF0000", true, true);
        Configuration::updateValue('BYJUNO_ORDER_STATE_DEFAULT', $defaultStateId);
        Configuration::updateValue('BYJUNO_ORDER_STATE_COMPLETE', $receivedPaymentId);
        Configuration::updateValue('BYJUNO_ORDER_S4_FAIL', $s4FailId);
        Configuration::updateValue('BYJUNO_ORDER_S5_FAIL', $s5FailId);

        if (!Configuration::get("byjuno_invoice")) {
            Configuration::updateValue('byjuno_invoice', 'disable');
            Configuration::updateValue('single_invoice', 'disable');
            Configuration::updateValue('installment_3', 'disable');
            Configuration::updateValue('installment_10', 'disable');
            Configuration::updateValue('installment_12', 'disable');
            Configuration::updateValue('installment_24', 'disable');
            Configuration::updateValue('installment_4x12', 'disable');
            Configuration::updateValue('BYJUNO_CREDIT_CHECK', 'disable');
            Configuration::updateValue('BYJUNO_CDP_ACCEPT', '2');
            Configuration::updateValue('BYJUNO_S2_IJ_ACCEPT', '2');
            Configuration::updateValue('BYJUNO_S2_MERCHANT_ACCEPT', '');
            Configuration::updateValue('BYJUNO_S3_ACCEPT', '2');
            Configuration::updateValue('BYJUNO_ALLOW_POSTAL', 'false');
            Configuration::updateValue('BYJUNO_CONN_TIMEOUT', '30');
            Configuration::updateValue('BYJUNO_MIN_AMOUNT', '10');
            Configuration::updateValue('BYJUNO_MAX_AMOUNT', '1000');
            Configuration::updateValue('BYJUNO_B2B', 'disable');
            Configuration::updateValue('BYJUNO_S4_ALLOWED', 'enable');
            Configuration::updateValue('BYJUNO_CANCEL_S5_ALLOWED', 'enable');
            Configuration::updateValue('BYJUNO_REFUND_S5_ALLOWED', 'enable');
            Configuration::updateValue('INTRUM_TMXORGID', 'lq866c5i');
            Configuration::updateValue('INTRUM_ENABLETMX', 'true');
            Configuration::updateValue('BYJUNO_GENDER_BIRTHDAY', 'true');


        }
        return true;
    }

    public function hookActionOrderSlipAdd($params)
    {
        if (Configuration::get("BYJUNO_REFUND_S5_ALLOWED") != 'enable') {
            return;
        }
        /* @var $orderCore OrderCore */
        $orderCore = $params["order"];
        $order_module = $orderCore->module;
        if ($order_module == "byjuno") {
            $slips = $orderCore->getOrderSlipsCollection();
            $count = count($slips);
            $i = 1;
            /* @var $curSlip OrderSlipCore */
            $curSlip = null;
            foreach ($slips as $slip) {
                if ($i == $count) {
                    $curSlip = $slip;
                    break;
                }
                $i++;
            }

            $invoices = $orderCore->getInvoicesCollection();
            $curInvoice = null;
            foreach ($invoices as $invoice) {
                /* @var $invoice OrderInvoiceCore */
                $curInvoice = $invoice;
            }
            if ($curInvoice == null)
            {
                return;
            }
            $invoiceNum = $curInvoice->getInvoiceNumberFormatted($id_lang = Context::getContext()->language->id, (int)$orderCore->id_shop);
            $currency = CurrencyCore::getCurrency($orderCore->id_currency);
            $time = strtotime($curSlip->date_add);
            $dt = date("Y-m-d", $time);
            $amount = $curSlip->total_shipping_tax_incl + $curSlip->amount;
            $requestRefund = CreateShopRequestS5Refund($invoiceNum, $amount, $currency["iso_code"], $orderCore->reference, $orderCore->id_customer, $dt);
            $xmlRequestS5 = $requestRefund->createRequest();
            $byjunoCommunicator = new ByjunoCommunicator();
            $byjunoCommunicator->setServer(Configuration::get("INTRUM_MODE"));
            $responseS5 = $byjunoCommunicator->sendS4Request($xmlRequestS5);
            $statusLog = "S5 refund";
            $statusS5 = "ERR";
            if (isset($responseS5)) {
                $byjunoResponseS5 = new ByjunoS4Response();
                $byjunoResponseS5->setRawResponse($responseS5);
                $byjunoResponseS5->processResponse();
                $statusS5 = $byjunoResponseS5->getProcessingInfoClassification();
            }
            $byjunoLogger = ByjunoLogger::getInstance();
            $byjunoLogger->log(Array(
                "firstname" => "-",
                "lastname" => "-",
                "town" => "-",
                "postcode" => "-",
                "street" => "-",
                "country" => "-",
                "ip" => $_SERVER["REMOTE_ADDR"],
                "status" => $statusS5,
                "request_id" => $requestRefund->getRequestId(),
                "type" => $statusLog,
                "error" => $statusS5,
                "response" => $responseS5,
                "request" => $xmlRequestS5
            ));
        }
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
        if (Configuration::get("BYJUNO_S4_ALLOWED") == 'enable') {
            /* @var $orderStatus OrderStateCore */
            $orderStatus = $params["newOrderStatus"];
            if ($orderStatus->id == Configuration::get('PS_OS_PAYMENT')) {
                $orderCore = new OrderCore((int)$params["id_order"]);
                $order_module = $orderCore->module; // will return the payment module eg. ps_checkpayment , ps_wirepayment
                if ($order_module == "byjuno") {
                    $invoices = $orderCore->getInvoicesCollection();
                    foreach ($invoices as $invoice) {
                        /* @var $invoice OrderInvoiceCore */
                        $invoiceNum = $invoice->getInvoiceNumberFormatted($id_lang = Context::getContext()->language->id, (int)$orderCore->id_shop);
                        $currency = CurrencyCore::getCurrency($orderCore->id_currency);
                        $time = strtotime($invoice->date_add);
                        $dt = date("Y-m-d", $time);
                        $requestInvoice = CreateShopRequestS4($invoiceNum, $invoice->total_paid_tax_incl, $invoice->total_products_wt, $currency["iso_code"], $orderCore->reference, $orderCore->id_customer, $dt);
                        $xmlRequestS4 = $requestInvoice->createRequest();
                        $byjunoCommunicator = new ByjunoCommunicator();
                        $byjunoCommunicator->setServer(Configuration::get("INTRUM_MODE"));
                        $responseS4 = $byjunoCommunicator->sendS4Request($xmlRequestS4);
                        $statusLog = "S4 Request";
                        $statusS4 = "ERR";
                        if (isset($responseS4)) {
                            $byjunoResponseS4 = new ByjunoS4Response();
                            $byjunoResponseS4->setRawResponse($responseS4);
                            $byjunoResponseS4->processResponse();
                            $statusS4 = $byjunoResponseS4->getProcessingInfoClassification();
                        }
                        $byjunoLogger = ByjunoLogger::getInstance();
                        $byjunoLogger->log(Array(
                            "firstname" => "-",
                            "lastname" => "-",
                            "town" => "-",
                            "postcode" => "-",
                            "street" => "-",
                            "country" => "-",
                            "ip" => $_SERVER["REMOTE_ADDR"],
                            "status" => $statusS4,
                            "request_id" => $requestInvoice->getRequestId(),
                            "type" => $statusLog,
                            "error" => $statusS4,
                            "response" => $responseS4,
                            "request" => $xmlRequestS4
                        ));
                        if ($statusS4 == "ERR") {
                            $orderCore->setCurrentState(Configuration::get('BYJUNO_ORDER_S4_FAIL'));
                            Tools::redirectAdmin(Context::getContext()->link->getAdminLink("AdminOrders") . "&id_order=" . $orderCore->id . "&vieworder");
                            exit();
                        }
                    }
                }
            }
        }

        if (Configuration::get("BYJUNO_CANCEL_S5_ALLOWED") == 'enable') {
            if ($orderStatus->id == Configuration::get('PS_OS_CANCELED')) {
                $orderCore = new OrderCore((int)$params["id_order"]);
                $order_module = $orderCore->module; // will return the payment module eg. ps_checkpayment , ps_wirepayment
                if ($order_module == "byjuno") {
                    $currency = CurrencyCore::getCurrency($orderCore->id_currency);
                    $dt = date("Y-m-d", time());
                    $requestCancel = CreateShopRequestS5Cancel($orderCore->total_paid_tax_incl, $currency["iso_code"], $orderCore->reference, $orderCore->id_customer, $dt);
                    $xmlRequestS5 = $requestCancel->createRequest();
                    $byjunoCommunicator = new ByjunoCommunicator();
                    $byjunoCommunicator->setServer(Configuration::get("INTRUM_MODE"));
                    $responseS5 = $byjunoCommunicator->sendS4Request($xmlRequestS5);
                    $statusLog = "S5 cancel";
                    $statusS5 = "ERR";
                    if (isset($responseS5)) {
                        $byjunoResponseS5 = new ByjunoS4Response();
                        $byjunoResponseS5->setRawResponse($responseS5);
                        $byjunoResponseS5->processResponse();
                        $statusS5 = $byjunoResponseS5->getProcessingInfoClassification();
                    }
                    $byjunoLogger = ByjunoLogger::getInstance();
                    $byjunoLogger->log(Array(
                        "firstname" => "-",
                        "lastname" => "-",
                        "town" => "-",
                        "postcode" => "-",
                        "street" => "-",
                        "country" => "-",
                        "ip" => $_SERVER["REMOTE_ADDR"],
                        "status" => $statusS5,
                        "request_id" => $requestCancel->getRequestId(),
                        "type" => $statusLog,
                        "error" => $statusS5,
                        "response" => $responseS5,
                        "request" => $xmlRequestS5
                    ));
                    if ($statusS5 == "ERR") {
                        $orderCore->setCurrentState(Configuration::get('BYJUNO_ORDER_S5_FAIL'));
                        Tools::redirectAdmin(Context::getContext()->link->getAdminLink("AdminOrders") . "&id_order=" . $orderCore->id . "&vieworder");
                        exit();
                    }
                }
            }
        }
    }

    public function hookDisplayBeforeShoppingCartBlock($params)
    {
        if (Configuration::get("INTRUM_ENABLETMX") == 'true' && Configuration::get("INTRUM_TMXORGID") != '') {
            global $cookie;
            $cookie->intrumId = Tools::getToken(false);
            echo '
                <script type="text/javascript" src="https://h.online-metrix.net/fp/tags.js?org_id=' . Configuration::get("INTRUM_TMXORGID") . '&session_id=' . $cookie->intrumId . '&pageid=checkout"></script>
            <noscript>
            <iframe style="width: 100px; height: 100px; border: 0; position: absolute; top: -5000px;" src="https://h.online-metrix.net/tags?org_id=' . Configuration::get("INTRUM_TMXORGID") . '&session_id=' . $cookie->intrumId . '&pageid=checkout"></iframe>
            </noscript>
                ';
        }
    }

    public function uninstall()
    {
        // Uninstall module
        if (!parent::uninstall()) {
            return false;
        }
        return true;
    }

    private function _postProcess()
    {
        if (Tools::isSubmit('submitIntrumMethods')) {
            $data = Tools::getValue('data');
            $disabledMethods = Array();
            if (!empty($data) && is_array($data)) {
                foreach ($data as $status => $val) {
                    if (is_array($data[$status])) {
                        if (isset($val[0]) && is_array($val[0])) {
                            foreach ($val[0] as $methodId => $val2) {
                                $disabledMethods[$status][] = $methodId;
                            }
                        }
                    }
                }
                Configuration::updateValue('INTRUM_DISABLED_METHODS', serialize($disabledMethods));

            }
            Configuration::updateValue('INTRUM_SUBMIT_PAYMENTS', 'OK');
        }
        if (Tools::isSubmit('submitIntrumMain')) {
            Configuration::updateValue('INTRUM_SUBMIT_MAIN', 'OK');
            Configuration::updateValue('INTRUM_MODE', trim(Tools::getValue('intrum_mode')));
            Configuration::updateValue('INTRUM_CLIENT_ID', trim(Tools::getValue('intrum_client_id')));
            Configuration::updateValue('INTRUM_USER_ID', trim(Tools::getValue('INTRUM_USER_ID')));
            Configuration::updateValue('INTRUM_PASSWORD', trim(Tools::getValue('intrum_password')));
            Configuration::updateValue('INTRUM_TECH_EMAIL', trim(Tools::getValue('intrum_tech_email')));
            Configuration::updateValue('INTRUM_ENABLETMX', trim(Tools::getValue('INTRUM_ENABLETMX')));
            Configuration::updateValue('INTRUM_TMXORGID', trim(Tools::getValue('INTRUM_TMXORGID')));
            Configuration::updateValue('byjuno_invoice', trim(Tools::getValue('byjuno_invoice')));
            Configuration::updateValue('single_invoice', trim(Tools::getValue('single_invoice')));
            Configuration::updateValue('installment_3', trim(Tools::getValue('installment_3')));
            Configuration::updateValue('installment_10', trim(Tools::getValue('installment_10')));
            Configuration::updateValue('installment_12', trim(Tools::getValue('installment_12')));
            Configuration::updateValue('installment_24', trim(Tools::getValue('installment_24')));
            Configuration::updateValue('installment_4x12', trim(Tools::getValue('installment_4x12')));
            Configuration::updateValue('installment_4x12', trim(Tools::getValue('installment_4x12')));
            Configuration::updateValue('BYJUNO_CREDIT_CHECK', trim(Tools::getValue('BYJUNO_CREDIT_CHECK')));
            Configuration::updateValue('BYJUNO_CDP_ACCEPT', trim(Tools::getValue('BYJUNO_CDP_ACCEPT')));
            Configuration::updateValue('BYJUNO_S2_IJ_ACCEPT', trim(Tools::getValue('BYJUNO_S2_IJ_ACCEPT')));
            Configuration::updateValue('BYJUNO_S2_MERCHANT_ACCEPT', trim(Tools::getValue('BYJUNO_S2_MERCHANT_ACCEPT')));
            Configuration::updateValue('BYJUNO_S3_ACCEPT', trim(Tools::getValue('BYJUNO_S3_ACCEPT')));
            Configuration::updateValue('BYJUNO_ALLOW_POSTAL', trim(Tools::getValue('BYJUNO_ALLOW_POSTAL')));
            Configuration::updateValue('BYJUNO_CONN_TIMEOUT', trim(Tools::getValue('BYJUNO_CONN_TIMEOUT')));
            Configuration::updateValue('BYJUNO_MIN_AMOUNT', trim(Tools::getValue('BYJUNO_MIN_AMOUNT')));
            Configuration::updateValue('BYJUNO_MAX_AMOUNT', trim(Tools::getValue('BYJUNO_MAX_AMOUNT')));
            Configuration::updateValue('BYJUNO_S4_ALLOWED', trim(Tools::getValue('BYJUNO_S4_ALLOWED')));
            Configuration::updateValue('BYJUNO_CANCEL_S5_ALLOWED', trim(Tools::getValue('BYJUNO_CANCEL_S5_ALLOWED')));
            Configuration::updateValue('BYJUNO_REFUND_S5_ALLOWED', trim(Tools::getValue('BYJUNO_REFUND_S5_ALLOWED')));
            Configuration::updateValue('BYJUNO_B2B', trim(Tools::getValue('BYJUNO_B2B')));
            Configuration::updateValue('BYJUNO_GENDER_BIRTHDAY', trim(Tools::getValue('BYJUNO_GENDER_BIRTHDAY')));
        }
        if (Tools::isSubmit('submitLogSearch')) {
            Configuration::updateValue('INTRUM_SHOW_LOG', 'true');
        }
    }

    function getContent()
    {
        Configuration::updateValue('INTRUM_SHOW_LOG', 'false');
        $this->_postProcess();
        $methods = Array();
        $payment_methods = $this->getPayment();
        $disabledMethods = unserialize(Configuration::get("INTRUM_DISABLED_METHODS"));
        $allowed = Array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 27, 28, 29, 30, 50, 51, 52, 53, 54, 55, 56, 57);
        foreach ($allowed as $status_val) {
            $output = '';
            foreach ($payment_methods as $payment) {
                if (file_exists('../modules/' . $payment['name'] . '/logo.png')) {
                    $output .= '<img src="' . __PS_BASE_URI__ . 'modules/' . $payment['name'] . '/logo.png" width="16" title="' . $payment['name'] . '" alt="' . $payment['name'] . '" style="vertical-align:middle" />';
                } else if (file_exists('../modules/' . $payment['name'] . '/logo.gif')) {
                    $output .= '<img src="' . __PS_BASE_URI__ . 'modules/' . $payment['name'] . '/logo.gif" width="16" title="' . $payment['name'] . '" alt="' . $payment['name'] . '" style="vertical-align:middle" />';
                } else {
                    $output .= '' . $payment['name'] . '';
                }
                $checked = false;
                if (!empty($disabledMethods[$status_val]) && is_array($disabledMethods[$status_val]) && in_array($payment['id_module'], $disabledMethods[$status_val])) {
                    $checked = true;
                }
                $output = $output . ' <input type="checkbox" name="data[' . $status_val . '][0][' . $payment['id_module'] . ']" value="1" ' . ($checked ? 'checked="checked"' : '') . ' /> (' . $payment['displayName'] . ')<br />';
            }
            $methods[$status_val]["false"] = $output;
        }

        $values = array(
            'bootstrap' => true,
            'this_path' => $this->_path,
            'intrum_submit_main' => Configuration::get("INTRUM_SUBMIT_MAIN"),
            'intrum_submit_payments' => Configuration::get("INTRUM_SUBMIT_PAYMENTS"),
            'intrum_mode' => Configuration::get("INTRUM_MODE"),
            'intrum_client_id' => Configuration::get("INTRUM_CLIENT_ID"),
            'INTRUM_USER_ID' => Configuration::get("INTRUM_USER_ID"),
            'intrum_password' => Configuration::get("INTRUM_PASSWORD"),
            'intrum_tech_email' => Configuration::get("INTRUM_TECH_EMAIL"),
            'intrum_show_log' => Configuration::get("INTRUM_SHOW_LOG"),
            'INTRUM_ENABLETMX' => Configuration::get("INTRUM_ENABLETMX"),
            'INTRUM_TMXORGID' => Configuration::get("INTRUM_TMXORGID"),
            'byjuno_invoice' => Configuration::get("byjuno_invoice"),
            'single_invoice' => Configuration::get("single_invoice"),
            'installment_3' => Configuration::get("installment_3"),
            'installment_10' => Configuration::get("installment_10"),
            'installment_12' => Configuration::get("installment_12"),
            'installment_24' => Configuration::get("installment_24"),
            'installment_4x12' => Configuration::get("installment_4x12"),
            'BYJUNO_CREDIT_CHECK' => Configuration::get("BYJUNO_CREDIT_CHECK"),
            'BYJUNO_CDP_ACCEPT' => Configuration::get("BYJUNO_CDP_ACCEPT"),
            'BYJUNO_S2_IJ_ACCEPT' => Configuration::get("BYJUNO_S2_IJ_ACCEPT"),
            'BYJUNO_S2_MERCHANT_ACCEPT' => Configuration::get("BYJUNO_S2_MERCHANT_ACCEPT"),
            'BYJUNO_S3_ACCEPT' => Configuration::get("BYJUNO_S3_ACCEPT"),
            'BYJUNO_ALLOW_POSTAL' => Configuration::get("BYJUNO_ALLOW_POSTAL"),
            'BYJUNO_CONN_TIMEOUT' => Configuration::get("BYJUNO_CONN_TIMEOUT"),
            'BYJUNO_MIN_AMOUNT' => Configuration::get("BYJUNO_MIN_AMOUNT"),
            'BYJUNO_MAX_AMOUNT' => Configuration::get("BYJUNO_MAX_AMOUNT"),
            'BYJUNO_S4_ALLOWED' => Configuration::get("BYJUNO_S4_ALLOWED"),
            'BYJUNO_CANCEL_S5_ALLOWED' => Configuration::get("BYJUNO_CANCEL_S5_ALLOWED"),
            'BYJUNO_REFUND_S5_ALLOWED' => Configuration::get("BYJUNO_REFUND_S5_ALLOWED"),
            'BYJUNO_B2B' => Configuration::get("BYJUNO_B2B"),
            'BYJUNO_GENDER_BIRTHDAY' => Configuration::get("BYJUNO_GENDER_BIRTHDAY"),
            'payment_methods' => $methods,
            'intrum_logs' => self::getLogs(),
            'search_in_log' => Tools::getValue('searchInLog'),
            'showlogs' => Tools::getValue('logs') != "" ? 1 : 0,
            'url' => "?controller=AdminModules&token=" . Tools::getValue('token') . "&configure=byjuno&tab_module=payments_gateways&module_name=byjuno",
            'urllogs' => "?controller=AdminModules&token=" . Tools::getValue('token') . "&configure=byjuno&tab_module=payments_gateways&module_name=byjuno&logs=true",
            'intrum_view_xml' => Tools::getValue('viewxml') != "" ? 1 : 0,
            'intrum_single_log' => self::getSingleLog(Tools::getValue('viewxml'))
        );
        $this->context->smarty->assign($values);

        Configuration::updateValue('INTRUM_SUBMIT_MAIN', '');
        Configuration::updateValue('INTRUM_SUBMIT_PAYMENTS', '');
        $output = $this->fetchTemplate('/views/templates/admin/back_office.tpl');

        return $output;
    }

    public function fetchTemplate($name)
    {
        if (version_compare(_PS_VERSION_, '1.4', '<'))
            $this->context->smarty->currentTemplate = $name;
        elseif (version_compare(_PS_VERSION_, '1.5', '<')) {
            $views = 'views/templates/';
            if (@filemtime(dirname(__FILE__) . '/' . $name))
                return $this->display(__FILE__, $name);
            elseif (@filemtime(dirname(__FILE__) . '/' . $views . 'hook/' . $name))
                return $this->display(__FILE__, $views . 'hook/' . $name);
            elseif (@filemtime(dirname(__FILE__) . '/' . $views . 'front/' . $name))
                return $this->display(__FILE__, $views . 'front/' . $name);
            elseif (@filemtime(dirname(__FILE__) . '/' . $views . 'admin/' . $name))
                return $this->display(__FILE__, $views . 'admin/' . $name);
        }

        return $this->display(__FILE__, $name);
    }

    public static function getLogs()
    {

        if (Tools::isSubmit('submitLogSearch') && Tools::getValue('searchInLog') != '') {
            $sql = '
                SELECT *
                FROM `' . _DB_PREFIX_ . 'intrum_logs` as I
                WHERE I.firstname like \'%' . pSQL(Tools::getValue('searchInLog')) . '%\'
                   OR I.lastname like \'%' . pSQL(Tools::getValue('searchInLog')) . '%\'
                   OR I.request_id like \'%' . pSQL(Tools::getValue('searchInLog')) . '%\'
                ORDER BY intrum_id DESC
                ';
            return Db::getInstance()->ExecuteS($sql);

        } else {
            return Db::getInstance()->ExecuteS('
                SELECT *
                FROM `' . _DB_PREFIX_ . 'intrum_logs` as I
                ORDER BY intrum_id DESC
                LIMIT 20 ');
        }

    }

    public static function getSingleLog($id)
    {

        $val = abs(intval($id));
        $return = array();
        if ($val > 0) {
            $sql = '
                SELECT *
                FROM `' . _DB_PREFIX_ . 'intrum_logs` as I
                WHERE I.intrum_id like \'%' . pSQL($val) . '%\'
                ';
            $xml = Db::getInstance()->getRow($sql);


            $domInput = new DOMDocument();
            $domInput->preserveWhiteSpace = FALSE;
            $domInput->loadXML($xml["request"]);
            $elem = $domInput->getElementsByTagName('Request');
            $domInput->formatOutput = TRUE;
            libxml_use_internal_errors(true);
            $testXml = simplexml_load_string($xml["response"]);
            $domOutput = new DOMDocument();
            $domOutput->preserveWhiteSpace = FALSE;
            if ($testXml) {
                $domOutput->loadXML($xml["response"]);
                $domOutput->formatOutput = TRUE;
                $return["input"] = htmlspecialchars($domInput->saveXml());
                $return["output"] = htmlspecialchars($domOutput->saveXml());
            } else {
                $return["input"] = htmlspecialchars($domInput->saveXml());
                $return["output"] = htmlspecialchars("Raw data: " . $xml["response"]);
            }
        }
        return $return;
    }

    public static function searchLogs()
    {
    }


    public static function getPayment()
    {

        $modules_list = Module::getPaymentModules();
        foreach ($modules_list as $k => $paymod) {
            if (file_exists(_PS_MODULE_DIR_ . '/' . $paymod['name'] . '/' . $paymod['name'] . '.php')) {
                require_once(_PS_MODULE_DIR_ . '/' . $paymod['name'] . '/' . $paymod['name'] . '.php');
                $module = get_object_vars(Module::getInstanceByName($paymod['name']));
                $modules_list[$k]['displayName'] = $module['displayName'];
            }
        }
        return $modules_list;
    }

}