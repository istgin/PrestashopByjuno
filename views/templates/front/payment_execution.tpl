{capture name=path}{l s=$paymentname mod='byjuno'}{/capture}

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
                <label for="selected_plan">{l s='Select payment plan' mod='byjuno'}<sup>*</sup></label><br />
				{foreach from=$selected_payments item=s_payment}
					<input type="radio" name="selected_plan" class="form-control" value="{$s_payment.id}" {if $s_payment.selected == 1} checked="checked"{/if}> &nbsp;{l s=$s_payment.name mod='byjuno'}<br />
				{/foreach}
            </div><br />
			{if ($byjuno_allowpostal == 1)}
                <div class="required form-group">
                    <label for="invoice_send">{l s='Select invoice delivery method' mod='byjuno'}<sup>*</sup></label><br />
                    <input type="radio" name="invoice_send" class="form-control" checked="checked" value="email"> &nbsp;{l s="By email" mod='byjuno'}: {$email}<br />
                    <input type="radio" name="invoice_send" class="form-control" value="postal"> &nbsp;{l s="By post" mod='byjuno'}: {$address}<br />
                </div><br />
			{/if}
			{if ($byjuno_gender_birthday == 1)}
				<div class="required form-group">
					<label for="selected_gender">{l s='Gender' mod='byjuno'}<sup>*</sup></label>
					<select name="selected_gender" id="selected_gender" class="form-control">
						<option value="1" {if $sl_gender == 1} selected="selected"{/if}>{l s="Male" mod='byjuno'}</option>
						<option value="2" {if $sl_gender == 2} selected="selected"{/if}>{l s="Female" mod='byjuno'}</option>
					</select>
				</div><br />
				<div class="required form-group">
					<label>{l s='Date of Birth' mod='byjuno'}</label><sup>*</sup>
					<div class="row">
						<div class="col-xs-4" style="max-width: 94px;">
							<select id="days" name="days" class="form-control" style="max-width: 82px;">
								{foreach from=$days item=day}
									<option value="{$day|escape:'html':'UTF-8'}" {if $sl_day == $day} selected="selected"{/if}>{$day|escape:'html':'UTF-8'}&nbsp;&nbsp;</option>
								{/foreach}
							</select>
							{*
                            {l s='January'}
                            {l s='February'}
                            {l s='March'}
                            {l s='April'}
                            {l s='May'}
                            {l s='June'}
                            {l s='July'}
                            {l s='August'}
                            {l s='September'}
                            {l s='October'}
                            {l s='November'}
                            {l s='December'}
                            *}
						</div>
						<div class="col-xs-4" style="max-width: 94px;">
							<select id="months" name="months" class="form-control" style="max-width: 82px;">
								{foreach from=$months key=k item=month}
									<option value="{$k|escape:'html':'UTF-8'}" {if $sl_month == $k} selected="selected"{/if}>{l s=$month}&nbsp;</option>
								{/foreach}
							</select>
						</div>
						<div class="col-xs-4" style="max-width: 94px;">
							<select id="years" name="years" class="form-control" style="max-width: 82px;">
								{foreach from=$years item=year}
									<option value="{$year|escape:'html':'UTF-8'}" {if $sl_year == $year} selected="selected"{/if}>{$year|escape:'html':'UTF-8'}&nbsp;&nbsp;</option>
								{/foreach}
							</select>
						</div>
					</div>
				</div><br />
			{/if}
		</div>
		<p class="cart_navigation clearfix" id="cart_navigation">
			<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" class="button-exclusive btn btn-default">
				<i class="icon-chevron-left"></i>{l s='Other payment methods' mod='byjuno'}
			</a>
			<button type="submit" class="button btn btn-default button-medium">
				<span>{l s='I confirm my order' mod='byjuno'}<i class="icon-chevron-right right"></i></span>
			</button>
		</p>
	</form>
{/if}
