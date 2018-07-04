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
		$this->display_column_left = false;
		parent::initContent();
		$cart = $this->context->cart;
		$payment = 'invoice';
		$paymentName = 'Byjuno Invoice';
		$pp = Tools::getValue('paymentmethod');
		if ($pp ==  'invoice' || $pp == 'installment') {
			$payment = $pp;
		}
		$selected_payments = Array();
		if ($payment == 'invoice')
		{
			$paymentName = 'Byjuno Invoice';
			if (Configuration::get("byjuno_invoice") == 'enable')
			{
				$selected_payments[] = Array('name' => 'Byjuno Invoice (with partial payment option)', 'id' => '');
			}
			if (Configuration::get("single_invoice") == 'enable')
			{
				$selected_payments[] = Array('name' => 'Byjuno Single Invoice', 'id' => '');
			}
		}
		if ($payment == 'installment')
		{
			$paymentName = 'Byjuno Installment';
			if (Configuration::get("installment_3") == 'enable')
			{
				$selected_payments[] = Array('name' => '3 installments', 'id' => '');
			}
			if (Configuration::get("installment_10") == 'enable')
			{
				$selected_payments[] = Array('name' => '10 installments', 'id' => '');
			}
			if (Configuration::get("installment_12") == 'enable')
			{
				$selected_payments[] = Array('name' => '12 installments', 'id' => '');
			}
			if (Configuration::get("installment_24") == 'enable')
			{
				$selected_payments[] = Array('name' => '24 installments', 'id' => '');
			}
			if (Configuration::get("installment_4x12") == 'enable')
			{
				$selected_payments[] = Array('name' => '4 installments in 12 months', 'id' => '');
			}
		}
		$values = array(
			'payment' => $payment,
			'paymentname' => $paymentName,
			'selected_payments' => $selected_payments
		);
		$this->context->smarty->assign($values);
		$this->setTemplate('payment_execution.tpl');
	}
}
