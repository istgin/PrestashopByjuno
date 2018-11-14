{if $status == 'ok'}
<p>
	{sprintf($order_status_text, $shop_name)}
	<br /><br />{sprintf($order_status_text2, $total_to_pay)}
	<br /><br />{sprintf($order_status_text3, $reference)}
</p>
{else}
	<p class="warning">
		{l s='We noticed a problem with your order. If you think this is an error, feel free to contact our' mod='byjuno'}
		<a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='expert customer support team' mod='byjuno'}</a>.
	</p>
{/if}
