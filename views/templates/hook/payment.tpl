{if ($byjuno_invoice)}
    <div class="row">
        <div class="col-xs-12">
            <p class="payment_module">
                <a class="byjunoinvoice"
                   href="{$link->getModuleLink('byjuno', 'payment', ["paymentmethod" => "invoice"], true)|escape:'html':'UTF-8'}"
                   title="{l s='Pay by byjuno invoice' mod='byjuno'}">
                    {l s='Byjuno invoice' mod='byjuno'}
                </a>
            </p>
        </div>
    </div>
{/if}
{if ($byjuno_installment)}
    <div class="row">
        <div class="col-xs-12">
            <p class="payment_module">
                <a class="byjunoinstallment"
                   href="{$link->getModuleLink('byjuno', 'payment', ["paymentmethod" => "installment"], true)|escape:'html':'UTF-8'}"
                   title="{l s='Pay by byjuno installment' mod='byjuno'}">
                    {l s='Byjuno installment' mod='byjuno'}
                </a>
            </p>
        </div>
    </div>
{/if}