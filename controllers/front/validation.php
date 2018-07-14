<?php
/*
* 2007-2016 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2016 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/**
 * @since 1.5.0
 */
class ByjunoValidationModuleFrontController extends ModuleFrontController
{
	/**
	 * @see FrontController::postProcess()
	 */
	public function postProcess()
	{
		global $cookie;
		$repayment = mapRepayment(Tools::getValue('selected_plan'));
		$toc = Tools::getValue('terms_conditions');
		if (empty($toc) || !$toc || $toc != "terms_conditions")
		{
			if ($repayment == 3 || $repayment == 4) {
				$backLink = $this->context->link->getModuleLink('byjuno', 'payment', Array("paymentmethod" => "invoice", "agree" => "false"));
			} else {
				$backLink = $this->context->link->getModuleLink('byjuno', 'payment', Array("paymentmethod" => "installment", "agree" => "false"));
			}

			$cookie->byjuno_invoice_send = Tools::getValue('invoice_send');
			$cookie->byjuno_selected_plan = Tools::getValue('selected_plan');
			$cookie->byjuno_selected_gender = Tools::getValue('selected_gender');
			$cookie->byjuno_years = Tools::getValue('years');
			$cookie->byjuno_months = Tools::getValue('months');
			$cookie->byjuno_days = Tools::getValue('days');

			Tools::redirect($backLink);
			exit();
		}

		$cookie->byjuno_invoice_send = "";
		$cookie->byjuno_selected_plan = "";
		$cookie->byjuno_selected_gender = "";
		$cookie->byjuno_years = "";
		$cookie->byjuno_months = "";
		$cookie->byjuno_days = "";

		$errorlnk = $this->context->link->getModuleLink('byjuno', 'errorpayment');
		$cart = $this->context->cart;
		if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active)
			Tools::redirect('index.php?controller=order&step=1');

		// Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
		$authorized = false;
		foreach (Module::getPaymentModules() as $module)
			if ($module['name'] == 'byjuno')
			{
				$authorized = true;
				break;
			}
		if (!$authorized)
			die($this->module->l('This payment method is not available.', 'validation'));

		$customer = new Customer($cart->id_customer);
		if (!Validate::isLoadedObject($customer)) {
			Tools::redirect($errorlnk);
			exit();
		}

		$invoiceDelivery = 'email';
		if (Configuration::get('BYJUNO_ALLOW_POSTAL') == 'true') {
			$invoiceDelivery = Tools::getValue('invoice_send');
			if ($invoiceDelivery != 'postal' && $invoiceDelivery != 'email') {
				$invoiceDelivery = 'email';
			}
		}
		$selected_gender = "";
		$selected_birthday = "";
		if (Configuration::get('BYJUNO_GENDER_BIRTHDAY') == 'true') {
			$selected_gender = Tools::getValue('selected_gender');
			$selected_birthday = Tools::getValue('years').'-'.Tools::getValue('months').'-'.Tools::getValue('days');
		}

		$currency = $this->context->currency;
		$total = (float)$cart->getOrderTotal(true, Cart::BOTH);
		$mailVars = null;//array();

		$status = 0;
		if (!defined('_PS_MODULE_INTRUMCOM_API')) {
			require(_PS_MODULE_DIR_.'intrumcom/api/intrum.php');
			require(_PS_MODULE_DIR_.'intrumcom/api/library_prestashop.php');
		}

		$request = CreatePrestaShopRequest($this->context->cart, $this->context->customer, $this->context->currency, "ORDERREQUEST", $selected_gender, $selected_birthday);
		$invoice_address = new Address($this->context->cart->id_address_invoice);

		$type = "S1 Request";
		$b2b = Configuration::get("BYJUNO_B2B") == 'enable';
		if ($b2b && !empty($invoice_address->company)) {
			$type = "S1 Request B2B";
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
			"street" => trim($request->getFirstLine().' '.$request->getHouseNumber()),
			"country" => $request->getCountryCode(),
			"ip" => byjunoGetClientIp(),
			"status" => $status,
			"request_id" => $request->getRequestId(),
			"type" => $type,
			"error" => ($status == 0) ? "ERROR" : "",
			"response" => $response,
			"request" => $xml
		));

		$accept = "";
		if (byjunoIsStatusOk($status, "BYJUNO_S2_MERCHANT_ACCEPT")) {
			$accept = "CLIENT";
		}
		if (byjunoIsStatusOk($status, "BYJUNO_S2_IJ_ACCEPT")) {
			$accept = "IJ";
		}

		if ($accept == "") {
			Tools::redirect($errorlnk);
			exit();
		}

		$this->module->validateOrder($cart->id, Configuration::get('BYJUNO_ORDER_STATE_DEFAULT'), $total, "Byjuno invoice", NULL, $mailVars, (int)$currency->id, false, $customer->secure_key);
		$order = new OrderCore((int)$this->module->currentOrder);

		$requestS3 = CreatePrestaShopRequestAfterPaid($this->context->cart, $order, $this->context->currency, Tools::getValue('selected_plan'), $accept, $invoiceDelivery, $selected_gender, $selected_birthday);
		$typeS3 = "S3 Request";
		$b2b = Configuration::get("BYJUNO_B2B") == 'enable';
		$xml = "";
		if ($b2b && !empty($invoice_address->company)) {
			$typeS3 = "S3 Request B2B";
			$xml = $requestS3->createRequestCompany();
		} else {
			$xml = $requestS3->createRequest();
		}

		$responseS3 = $byjunoCommunicator->sendRequest($xml, (int)Configuration::get("BYJUNO_CONN_TIMEOUT"));
		$statusS3 = 0;
		if ($responseS3) {
			$byjunoResponseS3 = new ByjunoResponse();
			$byjunoResponseS3->setRawResponse($responseS3);
			$byjunoResponseS3->processResponse();
			$statusS3 = $byjunoResponseS3->getCustomerRequestStatus();
		}
		$byjunoLogger->log(Array(
			"firstname" => $requestS3->getFirstName(),
			"lastname" => $requestS3->getLastName(),
			"town" => $requestS3->getTown(),
			"postcode" => $requestS3->getPostCode(),
			"street" => trim($requestS3->getFirstLine().' '.$requestS3->getHouseNumber()),
			"country" => $requestS3->getCountryCode(),
			"ip" => byjunoGetClientIp(),
			"status" => $statusS3,
			"request_id" => $requestS3->getRequestId(),
			"type" => $typeS3,
			"error" => ($statusS3 == 0) ? "ERROR" : "",
			"response" => $responseS3,
			"request" => $xml
		));
		//$history = new OrderHistory();
		//$history->id_order = $this->module->currentOrder;
		//$history->changeIdOrderState(Configuration::get('PS_OS_PAYMENT'), $this->module->currentOrder);
		//$history->addWithemail(true);

		if (byjunoIsStatusOk($statusS3, "BYJUNO_S3_ACCEPT")) {
			$order->setCurrentState(Configuration::get('BYJUNO_ORDER_STATE_COMPLETE'));
			Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
		} else {
			$order->setCurrentState(Configuration::get('PS_OS_CANCELED'));
			Tools::redirect($errorlnk);
		}
	}
}
