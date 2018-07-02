{*
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
*}

{capture name=path}{l s='Byjuno Invoice' mod='byjuno'}{/capture}

<h1 class="page-heading">{l s='Select payment' mod='byjuno'}</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if isset($nbProducts) && $nbProducts <= 0}
	<p class="alert alert-warning">{l s='Your shopping cart is empty.' mod='byjuno'}</p>
{else}

	<form action="{$link->getModuleLink('byjuno', 'validation', [], true)|escape:'html':'UTF-8'}" method="post">
		<div class="box cheque-box">
			<h3 class="page-subheading">{l s='Byjuno Invoice' mod='byjuno'}</h3>
			<p class="cheque-indent">
				<strong class="dark">
					{l s='You have chosen to pay by byjuno invoice.' mod='byjuno'}
				</strong>
			</p>

		</div>
		<p class="cart_navigation clearfix" id="cart_navigation">
			<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" class="button-exclusive btn btn-default">
				<i class="icon-chevron-left"></i>{l s='Other payment methods' mod='cheque'}
			</a>
			<button type="submit" class="button btn btn-default button-medium">
				<span>{l s='I confirm my order' mod='cheque'}<i class="icon-chevron-right right"></i></span>
			</button>
		</p>
	</form>
{/if}
