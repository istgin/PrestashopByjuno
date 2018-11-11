{capture name=path}{l s=$paymentname mod='byjuno'}{/capture}

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}
{if isset($nbProducts) && $nbProducts <= 0}
	<p class="alert alert-warning">{$l_your_shopping_cart_is_empty}</p>
{else}
	<style>
		div.byjuno_toc .checker,
		.checker+label {
			display: inline-block;
			vertical-align: middle;
		}
	</style>
	<script>
		var msg_order_byjuno = '{$l_you_must_agree_terms_conditions}';
		function acceptCGVByjuno()
		{
			if (typeof msg_order_byjuno != 'undefined' && $('#terms_conditions').length && !$('input#terms_conditions:checked').length)
			{
				if (!!$.prototype.fancybox)
					$.fancybox.open([
								{
									type: 'inline',
									autoScale: true,
									minHeight: 30,
									content: '<p class="fancybox-error">' + msg_order_byjuno + '</p>'
								}],
							{
								padding: 0
							});
				else
					alert(msg_order_byjuno);
			}
			else
				return true;
			return false;
		}
		acceptCGVByjuno();
	</script>
	<form action="{$link->getModuleLink('byjuno', 'validation', [], true)|escape:'html':'UTF-8'}" method="post" onsubmit="return acceptCGVByjuno()">
		<div class="box cheque-box">
			<h3 class="page-subheading">{l s=$paymentname mod='byjuno'}</h3>
			{if (count($selected_payments) > 1)}
            <div class="required form-group">
                <label for="selected_plan">{$l_select_payment_plan}</label><br />
				{foreach from=$selected_payments item=s_payment}
					<input type="radio" name="selected_plan" class="form-control" value="{$s_payment.id}" {if $s_payment.selected == 1} checked="checked"{/if}> &nbsp;{l s=$s_payment.name mod='byjuno'}<br />
				{/foreach}
            </div><br />
			{/if}
			{if (count($selected_payments) == 1)}
				<input type="hidden" name="selected_plan" value="{$selected_payments[0].id}">
			{/if}
			{if ($byjuno_allowpostal == 1)}
                <div class="required form-group">
                    <label for="invoice_send">{$l_select_invoice_delivery_method}</label><br />
                    <input type="radio" name="invoice_send" class="form-control" {if $invoice_send == "email"} checked="checked"{/if} value="email"> &nbsp;{$l_by_email}: {$email}<br />
                    <input type="radio" name="invoice_send" class="form-control" {if $invoice_send == "postal"} checked="checked"{/if} value="postal"> &nbsp;{$l_by_post}: {$address}<br />
                </div><br />
			{/if}
			{if ($byjuno_gender_birthday == 1)}
				<div class="required form-group">
					<label for="selected_gender">{$l_gender}</label>
					<select name="selected_gender" id="selected_gender" class="form-control">
						<option value="1" {if $sl_gender == 1} selected="selected"{/if}>{$l_male}</option>
						<option value="2" {if $sl_gender == 2} selected="selected"{/if}>{$l_female}</option>
					</select>
				</div><br />
				<div class="required form-group">
					<label>{$l_date_of_birth}</label>
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
			{if ($agree_error == 1)}
				<div class="alert alert-danger">
					{$l_you_must_agree_terms_conditions}
				</div>
			{/if}
			<div class="form-group byjuno_toc">
				<input type="checkbox" value="terms_conditions" name="terms_conditions" id="terms_conditions" style="display: inline-block" />
				<a href="{$toc_url}" target="_blank" style="font-weight: bold; text-decoration: underline">{$l_i_agree_with_terms_and_conditions}</a>
			</div>
		</div>
		<p class="cart_navigation clearfix" id="cart_navigation">
			<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" class="button-exclusive btn btn-default">
				<i class="icon-chevron-left"></i>{$l_other_payment_methods}
			</a>
			<button type="submit" class="button btn btn-default button-medium">
				<span>{$l_i_confirm_my_order}<i class="icon-chevron-right right"></i></span>
			</button>
		</p>
	</form>
{/if}
