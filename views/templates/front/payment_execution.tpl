{capture name=path}{l s='Byjuno Invoice' mod='byjuno'}{/capture}

<h1 class="page-heading">{l s='Select payment' mod='byjuno'}</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if isset($nbProducts) && $nbProducts <= 0}
	<p class="alert alert-warning">{l s='Your shopping cart is empty.' mod='byjuno'}</p>
{else}

	<form action="{$link->getModuleLink('byjuno', 'validation', [], true)|escape:'html':'UTF-8'}" method="post">
		<div class="box cheque-box">
			<h3 class="page-subheading">{l s=$paymentname mod='byjuno'}</h3>
            <div class="required form-group">
                <label for="selected_plan">{l s='Select payment plan' mod='byjuno'}<sup>*</sup></label>
                <select name="selected_plan" id="selected_plan" class="form-control">
                    {foreach from=$selected_payments item=s_payment}
                        <option value="{$s_payment.id}">{l s=$s_payment.name mod='byjuno'}</option>
                    {/foreach}
                </select>
            </div><br />
			{if ($byjuno_allowpostal == 1)}
                <div class="required form-group">
                    <label for="selected_plan">{l s='Select invoice delivery method' mod='byjuno'}<sup>*</sup></label><br />
                    <input type="radio" name="invoice_send" class="form-control" checked="checked" value="email"> &nbsp;{l s="By email" mod='byjuno'}: {$email}<br />
                    <input type="radio" name="invoice_send" class="form-control" value="postal"> &nbsp;{l s="By post" mod='byjuno'}: {$address}<br />
                </div>
			{/if}
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
