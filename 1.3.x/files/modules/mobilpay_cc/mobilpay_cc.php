<?php

class Mobilpay_cc extends PaymentModule
{
  private	$_html = '';
  private $_postErrors = array();

  public function __construct()
  {
    $this->name = 'mobilpay_cc';
    $this->tab = 'Payment';
    $this->version = '1.0';

    $this->currencies = true;
    $this->currencies_mode = 'radio';

    parent::__construct();

    $this->page = basename(__FILE__, '.php');
    $this->displayName = $this->l('Mobilpay - credit card');
    $this->description = $this->l('Accept payments by credit card');
    $this->confirmUninstall = $this->l('Are you sure you want to delete your details ?');
  }

  public function install()
  {
    if (!parent::install()
    OR !Configuration::updateValue('MPCC_SIGNATURE', '')
    OR !Configuration::updateValue('MPCC_CART_DESCRIPTION', '')

    OR !Configuration::updateValue('MPCC_OS_CONFIRMED_PENDING', '')
    OR !Configuration::updateValue('MPCC_OS_CONFIRMED', '')
    OR !Configuration::updateValue('MPCC_OS_PAID_PENDING', '')
    OR !Configuration::updateValue('MPCC_OS_PAID', '')
    OR !Configuration::updateValue('MPCC_OS_CANCELED', '')
    OR !Configuration::updateValue('MPCC_OS_CREDIT', '')

    OR !Configuration::updateValue('MPCC_TESTMODE', 1)
    OR !$this->registerHook('payment')
    OR !$this->registerHook('paymentReturn'))
    return false;
    return true;
  }

  public function uninstall()
  {
    if (!Configuration::deleteByName('MPCC_SIGNATURE')
    OR !Configuration::deleteByName('MPCC_CART_DESCRIPTION')
    OR !Configuration::deleteByName('MPCC_OS_CONFIRMED_PENDING')
    OR !Configuration::deleteByName('MPCC_OS_CONFIRMED')
    OR !Configuration::deleteByName('MPCC_OS_PAID_PENDING')
    OR !Configuration::deleteByName('MPCC_OS_PAID')
    OR !Configuration::deleteByName('MPCC_OS_CANCELED')
    OR !Configuration::deleteByName('MPCC_OS_CREDIT')
    OR !Configuration::deleteByName('MPCC_TESTMODE')
    OR !parent::uninstall())
    return false;
    return true;
  }

  public function getContent()
  {
    $this->_html = '<h2>'.$this->l('MobilPay Credit Card').'</h2>';
    if (isset($_POST['submitMobilpayCC']))
    {
      if (!isset($_POST['MPCC_TESTMODE']))
      $_POST['MPCC_TESTMODE'] = 1;

      if (!sizeof($this->_postErrors))
      {
	    if(!empty($_FILES['MPCC_PRIVATE_KEY']['name']) && $_FILES['MPCC_PRIVATE_KEY']['error']==0) { 	
		  if(!move_uploaded_file($_FILES['MPCC_PRIVATE_KEY']['tmp_name'], dirname(__FILE__).'/Mobilpay/certificates/private.key')) {
			$this->_postErrors[] = 'can not upload private key file please check permissions for '.dirname(__FILE__).'/Mobilpay/certificates/';	
			$this->displayErrors();
			$this->displayPayex();
			$this->displayFormSettings();
			return $this->_html;		
		  }
		}
	    if(!empty($_FILES['MPCC_PUBLIC_KEY']['name']) && $_FILES['MPCC_PUBLIC_KEY']['error']==0) {
		  if(!move_uploaded_file($_FILES['MPCC_PUBLIC_KEY']['tmp_name'], dirname(__FILE__).'/Mobilpay/certificates/public.cer')) {
			$this->_postErrors[] = 'can not upload public key file please check permissions for '.dirname(__FILE__).'/Mobilpay/certificates/';	
			$this->displayErrors();
			$this->displayPayex();
			$this->displayFormSettings();
			return $this->_html;				  
		  }
		}		
        Configuration::updateValue('MPCC_SIGNATURE', strval($_POST['MPCC_SIGNATURE']));
        Configuration::updateValue('MPCC_CART_DESCRIPTION', strval($_POST['MPCC_CART_DESCRIPTION']));

        Configuration::updateValue('MPCC_OS_CONFIRMED_PENDING', intval($_POST['MPCC_OS_CONFIRMED_PENDING']));
        Configuration::updateValue('MPCC_OS_CONFIRMED', intval($_POST['MPCC_OS_CONFIRMED']));
        Configuration::updateValue('MPCC_OS_PAID_PENDING', intval($_POST['MPCC_OS_PAID_PENDING']));
        Configuration::updateValue('MPCC_OS_PAID', intval($_POST['MPCC_OS_PAID']));
        Configuration::updateValue('MPCC_OS_CANCELED', intval($_POST['MPCC_OS_CANCELED']));
        Configuration::updateValue('MPCC_OS_CREDIT', intval($_POST['MPCC_OS_CREDIT']));

        Configuration::updateValue('MPCC_TESTMODE', intval($_POST['MPCC_TESTMODE']));
        $this->displayConf();
      }
      else
      $this->displayErrors();
    }

    $this->displayPayex();
    $this->displayFormSettings();
    return $this->_html;
  }

  public function displayConf()
  {
    $this->_html .= '
		<div class="conf confirm">
			<img src="../img/admin/ok.gif" alt="'.$this->l('Confirmation').'" />
			'.$this->l('Settings updated').'
		</div>';
  }

  public function displayErrors()
  {
    $nbErrors = sizeof($this->_postErrors);
    $this->_html .= '
		<div class="alert error">
			<h3>'.($nbErrors > 1 ? $this->l('There are') : $this->l('There is')).' '.$nbErrors.' '.($nbErrors > 1 ? $this->l('errors') : $this->l('error')).'</h3>
			<ol>';
    foreach ($this->_postErrors AS $error)
    $this->_html .= '<li>'.$error.'</li>';
    $this->_html .= '
			</ol>
		</div>';
  }

  public function displayPayex()
  {
    $this->_html .= '
		<img src="../modules/mobilpay_cc/mobilpay.gif" style="float:left; margin-right:15px;" />
		<b>'.$this->l('This module allows you to accept credit card payments by MobilPay.').'</b>
		<div style="clear:both;">&nbsp;</div>';
  }

  public function displayFormSettings()
  {
    global $cookie;
    $conf = Configuration::getMultiple(array(
    'MPCC_SIGNATURE',
    'MPCC_CART_DESCRIPTION',
    'MPCC_OS_CONFIRMED_PENDING',
    'MPCC_OS_CONFIRMED',
    'MPCC_OS_PAID_PENDING',
    'MPCC_OS_PAID',
    'MPCC_OS_CANCELED',
    'MPCC_OS_CREDIT',
    'MPCC_TESTMODE'
    ));

    $MPCC_SIGNATURE = array_key_exists('MPCC_SIGNATURE', $_POST) ? $_POST['MPCC_SIGNATURE'] : (array_key_exists('MPCC_SIGNATURE', $conf) ? $conf['MPCC_SIGNATURE'] : '');
    $MPCC_CART_DESCRIPTION = array_key_exists('MPCC_CART_DESCRIPTION', $_POST) ? $_POST['MPCC_CART_DESCRIPTION'] : (array_key_exists('MPCC_CART_DESCRIPTION', $conf) ? $conf['MPCC_CART_DESCRIPTION'] : '');
    $MPCC_OS_CONFIRMED_PENDING = array_key_exists('MPCC_OS_CONFIRMED_PENDING', $_POST) ? $_POST['MPCC_OS_CONFIRMED_PENDING'] : (array_key_exists('MPCC_OS_CONFIRMED_PENDING', $conf) ? $conf['MPCC_OS_CONFIRMED_PENDING'] : '');
    $MPCC_OS_CONFIRMED = array_key_exists('MPCC_OS_CONFIRMED', $_POST) ? $_POST['MPCC_OS_CONFIRMED'] : (array_key_exists('MPCC_OS_CONFIRMED', $conf) ? $conf['MPCC_OS_CONFIRMED'] : '');
    $MPCC_OS_PAID_PENDING = array_key_exists('MPCC_OS_PAID_PENDING', $_POST) ? $_POST['MPCC_OS_PAID_PENDING'] : (array_key_exists('MPCC_OS_PAID_PENDING', $conf) ? $conf['MPCC_OS_PAID_PENDING'] : '');
    $MPCC_OS_PAID = array_key_exists('MPCC_OS_PAID', $_POST) ? $_POST['MPCC_OS_PAID'] : (array_key_exists('MPCC_OS_PAID', $conf) ? $conf['MPCC_OS_PAID'] : '');
    $MPCC_OS_CANCELED = array_key_exists('MPCC_OS_CANCELED', $_POST) ? $_POST['MPCC_OS_CANCELED'] : (array_key_exists('MPCC_OS_CANCELED', $conf) ? $conf['MPCC_OS_CANCELED'] : '');
    $MPCC_OS_CREDIT = array_key_exists('MPCC_OS_CREDIT', $_POST) ? $_POST['MPCC_OS_CREDIT'] : (array_key_exists('MPCC_OS_CREDIT', $conf) ? $conf['MPCC_OS_CREDIT'] : '');
    $MPCC_TESTMODE = array_key_exists('MPCC_TESTMODE', $_POST) ? $_POST['MPCC_TESTMODE'] : (array_key_exists('MPCC_TESTMODE', $conf) ? $conf['MPCC_TESTMODE'] : '');

    $this->_html .= '
		<form action="'.$_SERVER['REQUEST_URI'].'" method="post" style="clear: both;" enctype="multipart/form-data">
		<fieldset>
			<legend><img src="../img/admin/contact.gif" />'.$this->l('Settings').'</legend>

      <label>'.$this->l('Account Signature').'</label>
      <div class="margin-form">
        <input type="text" size="33" name="MPCC_SIGNATURE" value="'.htmlentities($MPCC_SIGNATURE, ENT_COMPAT, 'UTF-8').'" />
        <p class="hint clear" style="display: block; width: 501px;">'.$this->l('MobilPay Account Signature').'</p>
      </div><div style="clear:both;">&nbsp;</div>



      <label>'.$this->l('Private Key').'</label>
      <div class="margin-form">
        <input type="file" name="MPCC_PRIVATE_KEY">
        <p class="hint clear" style="display: block; width: 501px;">'.$this->l('MobilPay Private Key').'</p>
      </div><div style="clear:both;">&nbsp;</div>

      <label>'.$this->l('Public Key').'</label>
      <div class="margin-form">
        <input type="file" name="MPCC_PUBLIC_KEY">
        <p class="hint clear" style="display: block; width: 501px;">'.$this->l('MobilPay Public Key').'</p>
      </div><div style="clear:both;">&nbsp;</div>




      <label>'.$this->l('Shopping Cart Description').'</label>
      <div class="margin-form">
        <input type="text" size="33" name="MPCC_CART_DESCRIPTION" value="'.htmlentities($MPCC_CART_DESCRIPTION, ENT_COMPAT, 'UTF-8').'" />
        <p class="hint clear" style="display: block; width: 501px;">'.$this->l('Description appears on mobilpay website').'</p>
      </div><div style="clear:both;">&nbsp;</div>

			<label>'.$this->l('Test Mode').'</label>
			<div class="margin-form">
				<input type="radio" name="MPCC_TESTMODE" value="1" '.($MPCC_TESTMODE ? 'checked="checked"' : '').' /> '.$this->l('Yes').'
				<input type="radio" name="MPCC_TESTMODE" value="0" '.(!$MPCC_TESTMODE ? 'checked="checked"' : '').' /> '.$this->l('No').'
			</div>';

    $states = OrderState::getOrderStates(intval($cookie->id_lang));


    $this->_html .= '
      <label>'.$this->l('Pending verification').'</label>
      <div class="margin-form">';
    $this->_html .=  '				<select name="MPCC_OS_CONFIRMED_PENDING">';
    $currentStateTab = $MPCC_OS_CONFIRMED_PENDING;

    foreach ($states AS $state)
    $this->_html .=  '<option value="'.$state['id_order_state'].'"'.(($state['id_order_state'] == $currentStateTab) ? ' selected="selected"' : '').'>'.stripslashes($state['name']).'</option>';
    $this->_html .=  '
				</select>';
    $this->_html .=  '<p class="hint clear" style="display: block; width: 501px;">'.$this->l('Transaction is pending verification regarding fraud risk. Money are already taken from the client\'s credit card').'</p>
      </div><div style="clear:both;">&nbsp;</div>';


    $this->_html .= '
      <label>'.$this->l('Payed / Confirmed').'</label>
      <div class="margin-form">';
    $this->_html .=  '				<select name="MPCC_OS_CONFIRMED">';
    $currentStateTab = $MPCC_OS_CONFIRMED;

    foreach ($states AS $state)
    $this->_html .=  '<option value="'.$state['id_order_state'].'"'.(($state['id_order_state'] == $currentStateTab) ? ' selected="selected"' : '').'>'.stripslashes($state['name']).'</option>';
    $this->_html .=  '
				</select>';
    $this->_html .=  '</div><div style="clear:both;">&nbsp;</div>';



    $this->_html .= '
      <label>'.$this->l('Pending').'</label>
      <div class="margin-form">';
    $this->_html .=  '				<select name="MPCC_OS_PAID_PENDING">';
    $currentStateTab = $MPCC_OS_PAID_PENDING;

    foreach ($states AS $state)
    $this->_html .=  '<option value="'.$state['id_order_state'].'"'.(($state['id_order_state'] == $currentStateTab) ? ' selected="selected"' : '').'>'.stripslashes($state['name']).'</option>';
    $this->_html .=  '
				</select>';
    $this->_html .=  '</div><div style="clear:both;">&nbsp;</div>';



    $this->_html .= '
      <label>'.$this->l('Open').'</label>
      <div class="margin-form">';
    $this->_html .=  '				<select name="MPCC_OS_PAID">';
    $currentStateTab = $MPCC_OS_PAID;

    foreach ($states AS $state)
    $this->_html .=  '<option value="'.$state['id_order_state'].'"'.(($state['id_order_state'] == $currentStateTab) ? ' selected="selected"' : '').'>'.stripslashes($state['name']).'</option>';
    $this->_html .=  '
				</select>';
    $this->_html .=  '</div><div style="clear:both;">&nbsp;</div>';



    $this->_html .= '
      <label>'.$this->l('Cancelled').'</label>
      <div class="margin-form">';
    $this->_html .=  '				<select name="MPCC_OS_CANCELED">';
    $currentStateTab = $MPCC_OS_CANCELED;

    foreach ($states AS $state)
    $this->_html .=  '<option value="'.$state['id_order_state'].'"'.(($state['id_order_state'] == $currentStateTab) ? ' selected="selected"' : '').'>'.stripslashes($state['name']).'</option>';
    $this->_html .=  '
				</select>';
    $this->_html .=  '</div><div style="clear:both;">&nbsp;</div>';


    $this->_html .= '
      <label>'.$this->l('Credited').'</label>
      <div class="margin-form">';
    $this->_html .=  '				<select name="MPCC_OS_CREDIT">';
    $currentStateTab = $MPCC_OS_CREDIT;

    foreach ($states AS $state)
    $this->_html .=  '<option value="'.$state['id_order_state'].'"'.(($state['id_order_state'] == $currentStateTab) ? ' selected="selected"' : '').'>'.stripslashes($state['name']).'</option>';
    $this->_html .=  '
				</select>';
    $this->_html .=  '</div><div style="clear:both;">&nbsp;</div>';



    $this->_html .=  '
			<br /><center><input type="submit" name="submitMobilpayCC" value="'.$this->l('Update settings').'" class="button" /></center>
		</fieldset>
		</form><br /><br />';
  }

  public function hookPayment($params)
  {
    if (!$this->active)
    return ;

    global $smarty;

    $customer = new Customer(intval($params['cart']->id_customer));
    $currency = new Currency(intval($params['cart']->id_currency));
	$currency_module = $this->getCurrency();
	$currency_default = new Currency(intval(Configuration::get('PS_CURRENCY_DEFAULT')));
	
/*
	if ($currency->id != $currency_module->id)
	{
		$currency = $currency_module;
		$params['cart']->id_currency = $currency_module->id;
		$params['cart']->update();
	}
*/	

    $billing  = new Address(intval($params['cart']->id_address_invoice));
    $delivery = new Address(intval($params['cart']->id_address_delivery));


    //include the main library
    require_once dirname(__FILE__).'/Mobilpay/Payment/Request/Abstract.php';
    require_once dirname(__FILE__).'/Mobilpay/Payment/Request/Card.php';
    require_once dirname(__FILE__).'/Mobilpay/Payment/Invoice.php';
    require_once dirname(__FILE__).'/Mobilpay/Payment/Address.php';

    $paymentUrl = (Configuration::get('MPCC_TESTMODE') == 1) ? 'http://sandboxsecure.mobilpay.ro' : 'https://secure.mobilpay.ro';

    $x509FilePath = dirname(__FILE__).'/Mobilpay/certificates/public.cer';

    try
    {
      $objPmReqCard = new Mobilpay_Payment_Request_Card();

      $objPmReqCard->signature = Configuration::get('MPCC_SIGNATURE');

      $objPmReqCard->orderId    = intval($params['cart']->id);
      $objPmReqCard->returnUrl 	= 'http://'.htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'order-confirmation.php?key='.$customer->secure_key.'&amp;id_cart='.intval($params['cart']->id).'&amp;id_module='.intval($this->id);
      $objPmReqCard->confirmUrl = $this->getPath().'validation.php?key='.$customer->secure_key.'&amp;id_cart='.intval($params['cart']->id).'&amp;id_currency='.intval($currency->id).'&amp;id_module='.intval($this->id);
      $objPmReqCard->cancelUrl 	= 'http://'.htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'index.php';

      $objPmReqCard->invoice = new Mobilpay_Payment_Invoice();

      $objPmReqCard->invoice->currency = $currency_module->iso_code;
	  
	  if($currency_default->iso_code == 'RON' && $currency->iso_code != 'RON') {
		//convert prices manually
		$objPmReqCard->invoice->amount = round($params['cart']->getOrderTotal(true, 3) * 1/$currency->conversion_rate, 2);
	  }
	  else {
		$objPmReqCard->invoice->amount = Tools::convertPrice($params['cart']->getOrderTotal(true, 3), $currency);
	  }
	  
      if(Configuration::get('MPCC_CART_DESCRIPTION')!='') $objPmReqCard->invoice->details = Configuration::get('MPCC_CART_DESCRIPTION');

      $billingAddress = new Mobilpay_Payment_Address();

      if(!empty($billing->company)) {
        $billingAddress->type = 'company';
      }
      else {
        $billingAddress->type = 'person';
      }
      $billingAddress->firstName = $billing->firstname;
      $billingAddress->lastName = $billing->lastname;

      //not supported by this shopping cart $billingAddress->fiscalNumber	= $_POST['billing_fiscal_number'];
      //not supported by this shopping cart $billingAddress->identityNumber	= $_POST['billing_identity_number'];

      $billingAddress->country = $billing->country;

      $billingAddress->city	= $billing->city;
      $billingAddress->zipCode = $billing->postcode;
      $billingAddress->address = $billing->address1. ' ' . $billing->address2;
      $billingAddress->email = $customer->email;
      $billingAddress->mobilePhone = $billing->phone_mobile;

      //not supported by this shopping cart $billingAddress->bank	= $_POST['billing_bank'];
      //not supported by this shopping cart $billingAddress->iban	= $_POST['billing_iban'];

      $objPmReqCard->invoice->setBillingAddress($billingAddress);

      $shippingAddress = new Mobilpay_Payment_Address();
      if(!empty($shipping->company)) {
        $shippingAddress->type = 'company';
      }
      else {
        $shippingAddress->type = 'person';
      }
      $shippingAddress->firstName = $shipping->firstname;
      $shippingAddress->lastName = $shipping->lastname;

      //not supported by this shopping cart $shippingAddress->fiscalNumber	= $_POST['shipping_fiscal_number'];
      //not supported by this shopping cart $shippingAddress->identityNumber	= $_POST['shipping_identity_number'];

      $shippingAddress->country = $shipping->country;

      $shippingAddress->city	= $shipping->city;
      $shippingAddress->zipCode = $shipping->postcode;
      $shippingAddress->address = $shipping->address1. ' ' . $shipping->address2;
      $shippingAddress->email = $customer->email;
      $shippingAddress->mobilePhone = $shipping->phone_mobile;


      $objPmReqCard->invoice->setShippingAddress($shippingAddress);

      $objPmReqCard->encrypt($x509FilePath);
    }

    catch(Exception $e)
    {
      $error = $e->getMessage();
      $errors = explode("\n", $error);//just first line
      $smarty->assign(array('errors' => $errors));
      return ;
      //return $this->display(__FILE__, 'validation.tpl');
    }


    if(!($e instanceof Exception)) {

      $smarty->assign(array(
      'data' => $objPmReqCard->getEncData(),
      'env_key' => $objPmReqCard->getEnvKey(),
      'paymentUrl' => $paymentUrl
      ));
    }
    else {
      $error = $e->getMessage();
      $errors = explode("\n", $error);//just first line
      $smarty->assign(array('errors' => $errors));
      return ;
      //return $this->display(__FILE__, 'validation.tpl');
    }


    return $this->display(__FILE__, 'mobilpay.tpl');
  }

  public function getPath() {
    return (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://').htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__.'modules/'.$this->name.'/';
  }

  public function hookPaymentReturn($params)
  {
    if (!$this->active)
    return ;
    return $this->display(__FILE__, 'confirmation.tpl');
  }

  function validateOrder($id_cart, $id_order_state, $amountPaid, $paymentMethod = 'Unknown', $message = NULL, $extraVars = array(), $currency_special = NULL, $dont_touch_amount = false)
  {
    if (!$this->active)
    return ;

    $currency = $this->getCurrency();
    $cart = new Cart(intval($id_cart));
    $cart->id_currency = $currency->id;
    $cart->save();
	
    parent::validateOrder($id_cart, $id_order_state, $amountPaid, $paymentMethod, $message, $extraVars, $currency_special, true);
  }
}
