{if $status == 'ok'}
<p>
	{sprintf($order_status_text, $shop_name)}
	<br /><br />{l s='Amount' mod='byjuno'} <span class="price"><strong>{$total_to_pay}</strong></span>
	<br /><br />{l s='Order reference %s' sprintf=$reference mod='byjuno'}
</p>
{else}
	<p class="warning">
		{l s='We noticed a problem with your order. If you think this is an error, feel free to contact our' mod='byjuno'}
		<a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team' mod='byjuno'}</a>.
	</p>
{/if}
