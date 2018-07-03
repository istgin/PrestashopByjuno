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
		}

		$currency = $this->context->currency;
		$total = (float)$cart->getOrderTotal(true, Cart::BOTH);
		$mailVars = null;//array();

		$status = 0;
		if (!defined('_PS_MODULE_INTRUMCOM_API')) {
			require(_PS_MODULE_DIR_.'intrumcom/api/intrum.php');
			require(_PS_MODULE_DIR_.'intrumcom/api/library_prestashop.php');
		}

		$request = CreatePrestaShopRequest($this->context->cart, $this->context->customer, $this->context->currency);
		$xml = $request->createRequest();
		$intrumCommunicator = new IntrumCommunicator();
		$intrumCommunicator->setServer(Configuration::get("INTRUM_MODE"));
		$response = $intrumCommunicator->sendRequest($xml);

		if ($response) {
			$intrumResponse = new IntrumResponse();
			$intrumResponse->setRawResponse($response);
			$intrumResponse->processResponse();
			$status = $intrumResponse->getCustomerRequestStatus();
		}
		$intrumLogger = IntrumLogger::getInstance();
		$intrumLogger->log(Array(
			"firstname" => $request->getFirstName(),
			"lastname" => $request->getLastName(),
			"town" => $request->getTown(),
			"postcode" => $request->getPostCode(),
			"street" => trim($request->getFirstLine().' '.$request->getHouseNumber()),
			"country" => $request->getCountryCode(),
			"ip" => $_SERVER["REMOTE_ADDR"],
			"status" => $status,
			"request_id" => $request->getRequestId(),
			"type" => "Request status",
			"error" => ($status == 0) ? $response : "",
			"response" => $response,
			"request" => $xml
		));

		$this->module->validateOrder($cart->id, Configuration::get('BYJUNO_ORDER_STATE_DEFAULT'), $total, "Byjuno invoice", NULL, $mailVars, (int)$currency->id, false, $customer->secure_key);
		$order = new OrderCore((int)$this->module->currentOrder);
		$request = CreatePrestaShopRequestAfterPaid($this->context->cart, $order, $this->context->currency, "sinlge_invoice", "IJ");
		$xml = $request->createRequest();
		$response = $intrumCommunicator->sendRequest($xml);
		libxml_use_internal_errors(true);
		$xmlResponse = simplexml_load_string($response);
		$intrumLogger = IntrumLogger::getInstance();
		if ($xmlResponse) {
			$intrumLogger->log(Array(
				"firstname" => $request->getFirstName(),
				"lastname" => $request->getLastName(),
				"town" => $request->getTown(),
				"postcode" => $request->getPostCode(),
				"street" => trim($request->getFirstLine().' '.$request->getHouseNumber()),
				"country" => $request->getCountryCode(),
				"ip" => $_SERVER["REMOTE_ADDR"],
				"status" => (isset($xmlResponse->Customer->RequestStatus)) ? 'OK' : '0',
				"request_id" => $request->getRequestId(),
				"type" => "Order confirmation message",
				"error" => (!(isset($xmlResponse->Customer->RequestStatus))) ? $response : "",
				"response" => $response,
				"request" => $xml
			));
		} else {
			$intrumLogger->log(Array(
				"firstname" => $request->getFirstName(),
				"lastname" => $request->getLastName(),
				"town" => $request->getTown(),
				"postcode" => $request->getPostCode(),
				"street" => trim($request->getFirstLine().' '.$request->getHouseNumber()),
				"country" => $request->getCountryCode(),
				"ip" => $_SERVER["REMOTE_ADDR"],
				"status" => '0',
				"request_id" => $request->getRequestId(),
				"type" => "Order confirmation message",
				"error" => "",
				"response" => $response,
				"request" => $xml
			));
		}
		$history = new OrderHistory();
		$history->id_order = $this->module->currentOrder;
		$history->changeIdOrderState(Configuration::get('PS_OS_PAYMENT'), $this->module->currentOrder);
		$history->addWithemail(true);
		$order->setCurrentState(Configuration::get('PS_OS_PAYMENT'));
		Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
	}
}
