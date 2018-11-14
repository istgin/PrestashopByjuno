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
class ByjunoPaymentModuleFrontController extends ModuleFrontController
{
	public $ssl = true;
	public $display_column_left = false;
	public $display_column_right = false;
	/**
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		global $cookie;
		$this->display_column_left = false;
		parent::initContent();
		$cart = $this->context->cart;
		/* @var $customer CustomerCore */
		$customer = $this->context->customer;
		$payment = 'invoice';
		$paymentName = $this->module->l('Byjuno invoice');
		$pp = Tools::getValue('paymentmethod');
		if ($pp ==  'invoice' || $pp == 'installment') {
			$payment = $pp;
		}
		$selected_payments = Array();
		$tocUrl = Configuration::get('BYJUNO_TOC_INVOICE_EN');
		$lng = Context::getContext()->language->iso_code;
		$langtoc = "DE";
		if ($lng == "en" || $lng == "de" || $lng == "fr" || $lng == "it") {
			$langtoc = strtoupper($lng);
		}
		if ($payment == 'invoice')
		{
			$paymentName = $this->module->l('Byjuno invoice');
			if (Configuration::get("byjuno_invoice") == 'enable')
			{
				$selected_payments[] = Array('name' => 'Byjuno Invoice (with partial payment option)', 'id' => 'byjuno_invoice', "selected" => 0);
			}
			if (Configuration::get("single_invoice") == 'enable')
			{
				$selected_payments[] = Array('name' => 'Byjuno Single Invoice', 'id' => 'single_invoice', "selected" => 0);
			}
			$tocUrl = Configuration::get('BYJUNO_TOC_INVOICE_'.$langtoc);
		}
		if ($payment == 'installment')
		{
			$paymentName = $this->module->l('Byjuno installment');
			if (Configuration::get("installment_3") == 'enable')
			{
				$selected_payments[] = Array('name' => '3 installments', 'id' => 'installment_3', "selected" => 0);
			}
			if (Configuration::get("installment_10") == 'enable')
			{
				$selected_payments[] = Array('name' => '10 installments', 'id' => 'installment_10', "selected" => 0);
			}
			if (Configuration::get("installment_12") == 'enable')
			{
				$selected_payments[] = Array('name' => '12 installments', 'id' => 'installment_12', "selected" => 0);
			}
			if (Configuration::get("installment_24") == 'enable')
			{
				$selected_payments[] = Array('name' => '24 installments', 'id' => 'installment_24', "selected" => 0);
			}
			if (Configuration::get("installment_4x12") == 'enable')
			{
				$selected_payments[] = Array('name' => '4 installments in 12 months', 'id' => 'installment_4x12', "selected" => 0);
			}
			$tocUrl = Configuration::get('BYJUNO_TOC_INSTALLMENT_'.$langtoc);
		}
		$selected_payments[0]["selected"] = 1;

		$tm = strtotime($customer->birthday);
		$years = Tools::dateYears();
		$months = Tools::dateMonths();
		$days = Tools::dateDays();

		$invoice_send = "email";
		if (!empty($cookie->byjuno_invoice_send)) {
			$invoice_send = $cookie->byjuno_invoice_send;
		}
		if (!empty($cookie->byjuno_selected_plan)) {
			$selected_plan = $cookie->byjuno_selected_plan;
			$isSelected = false;
			foreach($selected_payments as $key => $val)
			{
				if ($selected_payments[$key]["id"] == $selected_plan) {
					$selected_payments[$key]["selected"] = 1;
					$isSelected = true;
				} else {
					$selected_payments[$key]["selected"] = 0;
				}
			}
			if (!$isSelected) {
				$selected_payments[0]["selected"] = 1;
			}
		}
		$selected_gender = $customer->id_gender;
		if (!empty($cookie->byjuno_selected_gender)) {
			$selected_gender = $cookie->byjuno_selected_gender;
		}

		$byjuno_years = date("Y", $tm);
		$byjuno_months = date("m", $tm);
		$byjuno_days = date("d", $tm);

		if (!empty($cookie->byjuno_years) && !empty($cookie->byjuno_months) && !empty($cookie->byjuno_days)) {
			$byjuno_years = $cookie->byjuno_years;
			$byjuno_months = $cookie->byjuno_months;
			$byjuno_days = $cookie->byjuno_days;
		}

		$invoice_address = new Address($cart->id_address_invoice);
		$values = array(
			'payment' => $payment,
			'paymentname' => $paymentName,
			'selected_payments' => $selected_payments,
			'invoice_send' => $invoice_send,
			'byjuno_allowpostal' => (Configuration::get('BYJUNO_ALLOW_POSTAL') == 'true') ? 1 : 0,
			'byjuno_gender_birthday' => (Configuration::get('BYJUNO_GENDER_BIRTHDAY') == 'true') ? 1 : 0,
			'email' => $this->context->customer->email,
			'address' => trim($invoice_address->address1.' '.$invoice_address->address2).', '.$invoice_address->postcode.' '.$invoice_address->city,
			'years' => $years,
			'sl_year' => $byjuno_years,
			'months' => $months,
			'sl_month' => $byjuno_months,
			'days' => $days,
			'sl_day' => $byjuno_days,
			'sl_gender' => $selected_gender,
			'toc_url' => $tocUrl,
			'l_select_payment_plan' => $this->module->l("Select payment plan"),
			'l_select_invoice_delivery_method' => $this->module->l("Select invoice delivery method"),
			'l_gender' => $this->module->l("Gender"),
			'l_male' => $this->module->l("Male"),
			'l_female' => $this->module->l("Female"),
			'l_date_of_birth' => $this->module->l("Date of Birth"),
			'l_you_must_agree_terms_conditions' => $this->module->l("You must agree terms conditions"),
			'l_i_agree_with_terms_and_conditions' => $this->module->l("I agree with terms and conditions"),
			'l_other_payment_methods' => $this->module->l("Other payment methods"),
			'l_i_confirm_my_order' => $this->module->l("I confirm my order"),
			'l_your_shopping_cart_is_empty' => $this->module->l("Your shopping cart is empty."),
			'l_by_email' => $this->module->l("By email"),
			'l_by_post' => $this->module->l("By post"),
			//Select invoice delivery method
			//Gender
			//Male
			//Female
			//Date of Birth
			//You must agree terms conditions
			//I agree with terms and conditions
			//Other payment methods
			//I confirm my order
			//Your shopping cart is empty.
			//By email
			//By post
			'agree_error' => (!empty(Tools::getValue('agree'))) ? 1 : 0
		);
		$this->context->smarty->assign($values);
		$this->setTemplate('payment_execution.tpl');
	}
}
