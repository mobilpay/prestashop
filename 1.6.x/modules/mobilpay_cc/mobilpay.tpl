<p class="payment_module">
	<a href="javascript:$('#mobilpay_cc_form').submit();" title="{l s='Pay with MobilPay Credit Card' mod='mobilpay_cc'}">
		<img src="{$module_template_dir}mobilpay.gif" alt="{l s='Pay with credit card' mod='mobilpay_cc'}" />
		{l s='Pay with credit card' mod='mobilpay_cc'}
	</a>
</p>


<form action="{$paymentUrl}" method="post" id="mobilpay_cc_form" class="hidden">
	<input type="hidden" name="data" value="{$data}" />
	<input type="hidden" name="env_key" value="{$env_key}" />
</form>