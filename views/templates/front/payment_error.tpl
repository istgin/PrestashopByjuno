{capture name=path}{l s='Byjuno payments' mod='byjuno'}{/capture}

<h1 class="page-heading">{l s='Payment error' mod='byjuno'}</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}



<form action="{$link->getModuleLink('byjuno', 'validation', [], true)|escape:'html':'UTF-8'}" method="post">
	<p class="warning">
		{l s='Order processing error. Please try again.' mod='byjuno'}
	</p>
	<p class="cart_navigation clearfix" id="cart_navigation">
		<a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" class="button-exclusive btn btn-default">
			<i class="icon-chevron-left"></i>{l s='Other payment methods' mod='byjuno'}
		</a>
	</p>
</form>
