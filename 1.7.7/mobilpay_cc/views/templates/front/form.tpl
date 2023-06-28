<form action="{$paymentUrl}" method="post" id="mobilpay_cc_form" class="hidden">
	<input type="hidden" name="data" value="{$data}" />
	<input type="hidden" name="env_key" value="{$env_key}" />
	<input type="hidden" name="cipher" value="{$cipher}" />
	<input type="hidden" name="iv" value="{$iv}" />
</form>