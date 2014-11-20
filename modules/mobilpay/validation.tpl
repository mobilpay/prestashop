<script type="text/javascript">
<!--
	var baseDir = '{$base_dir_ssl}';
-->
</script>

{capture name=path}{l s='Your payment method'}{/capture}
{include file=$tpl_dir./breadcrumb.tpl}

<h2>{l s='Choose your payment method'}</h2>

{assign var='current_step' value='payment'}
{include file=$tpl_dir./order-steps.tpl}

{include file=$tpl_dir./errors.tpl}


<p class="cart_navigation"><a href="{$base_dir_ssl}order.php?step=3" title="{l s='Previous'}" class="button">&laquo; {l s='Previous'}</a></p>
