{if ($byjuno_invoice)}
    <div class="row">
        <div class="col-xs-12">
            <p class="payment_module">
                <a class="byjunoinvoice byjunoinvoice_logo_{$lang}"
                   href="{$link->getModuleLink('byjuno', 'payment', ["paymentmethod" => "invoice"], true)|escape:'html':'UTF-8'}"
                   title="{$name_pay_byjuno_invoice}">
                    {$name_byjuno_invoice}
                </a>
            </p>
        </div>
    </div>
{/if}
{if ($byjuno_installment)}
    <div class="row">
        <div class="col-xs-12">
            <p class="payment_module">
                <a class="byjunoinstallment byjunoinstallment_logo_{$lang}"
                   href="{$link->getModuleLink('byjuno', 'payment', ["paymentmethod" => "installment"], true)|escape:'html':'UTF-8'}"
                   title="{$name_pay_byjuno_installemnt}">
                    {$name_byjuno_installemnt}
                </a>
            </p>
        </div>
    </div>
{/if}