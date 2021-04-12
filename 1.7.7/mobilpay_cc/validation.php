<?php
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/mobilpay_cc.php');
// require_once dirname(__FILE__).'/controllers/front/cron.php';

require_once dirname(__FILE__).'/Mobilpay/Payment/Request/Abstract.php';
require_once dirname(__FILE__).'/Mobilpay/Payment/Request/Card.php';
require_once dirname(__FILE__).'/Mobilpay/Payment/Request/Notify.php';
require_once dirname(__FILE__).'/Mobilpay/Payment/Invoice.php';
require_once dirname(__FILE__).'/Mobilpay/Payment/Address.php';

$errorCode 		= 0;
$errorType		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_NONE;
$errorMessage	= '';

Mobilpay_cc::setLogObj("Validation.php -> Step #2", null, false);

if (strcasecmp($_SERVER['REQUEST_METHOD'], 'post') == 0)
{
  if(isset($_POST['env_key']) && isset($_POST['data']))
  {
	global $kernel;
    if(!$kernel){ 
      require_once _PS_ROOT_DIR_.'/app/AppKernel.php';
      $kernel = new \AppKernel('prod', false);
      $kernel->boot(); 
    }
	
    #calea catre cheia privata
    #cheia privata este generata de mobilpay, accesibil in Admin -> Conturi de comerciant -> Detalii -> Setari securitate
    $privateKeyFilePath = dirname(__FILE__).'/Mobilpay/certificates/private.key';

    try
    {
      $objPmReq = Mobilpay_Payment_Request_Abstract::factoryFromEncrypted($_POST['env_key'], $_POST['data'], $privateKeyFilePath);

      switch($objPmReq->objPmNotify->action)
        {
          #orice action este insotit de un cod de eroare si de un mesaj de eroare. Acestea pot fi citite folosind $cod_eroare = $objPmReq->objPmNotify->errorCode; respectiv $mesaj_eroare = $objPmReq->objPmNotify->errorMessage;
          #pentru a identifica ID-ul comenzii pentru care primim rezultatul platii folosim $id_comanda = $objPmReq->orderId;
          case 'confirmed':
            #cand action este confirmed avem certitudinea ca banii au plecat din contul posesorului de card si facem update al starii comenzii si livrarea produsului
            $errorMessage = $objPmReq->objPmNotify->getCrc();
            break;
          case 'confirmed_pending':
            #cand action este confirmed_pending inseamna ca tranzactia este in curs de verificare antifrauda. Nu facem livrare/expediere. In urma trecerii de aceasta verificare se va primi o noua notificare pentru o actiune de confirmare sau anulare.
            $errorMessage = $objPmReq->objPmNotify->getCrc();
            break;
          case 'paid_pending':
            #cand action este paid_pending inseamna ca tranzactia este in curs de verificare. Nu facem livrare/expediere. In urma trecerii de aceasta verificare se va primi o noua notificare pentru o actiune de confirmare sau anulare.
            $errorMessage = $objPmReq->objPmNotify->getCrc();
            break;
          case 'paid':
            #cand action este paid inseamna ca tranzactia este in curs de procesare. Nu facem livrare/expediere. In urma trecerii de aceasta procesare se va primi o noua notificare pentru o actiune de confirmare sau anulare.
            $errorMessage = $objPmReq->objPmNotify->getCrc();
            break;
          case 'canceled':
            #cand action este canceled inseamna ca tranzactia este anulata. Nu facem livrare/expediere.
            $errorMessage = $objPmReq->objPmNotify->getCrc();
            break;
          case 'credit':
            #cand action este credit inseamna ca banii sunt returnati posesorului de card. Daca s-a facut deja livrare, aceasta trebuie oprita sau facut un reverse.
            $errorMessage = $objPmReq->objPmNotify->getCrc();
            break;
          default:
            $errorType		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_PERMANENT;
            $errorCode 		= Mobilpay_Payment_Request_Abstract::ERROR_CONFIRM_INVALID_ACTION;
            $errorMessage 	= 'mobilpay_refference_action paramaters is invalid';
            break;
        }
    }
    catch(Exception $e)
    {
      $errorType 		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_TEMPORARY;
      $errorCode		= $e->getCode();
      $errorMessage 	= $e->getMessage();
    }
    Mobilpay_cc::setLogObj( "IPN -> Data Posted to IPN", null,  false);
    Mobilpay_cc::setLogObj( "   - IPN -> Action ".$objPmReq->objPmNotify->action, null,  false);
    // Mobilpay_cc::setLogObj( "   - IPN -> MESSAGE :  ".$errorMessage, null, false);
    // Mobilpay_cc::setLogObj( null , null,  false);
  }
  else
  {
    $errorType 		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_PERMANENT;
    $errorCode		= Mobilpay_Payment_Request_Abstract::ERROR_CONFIRM_INVALID_POST_PARAMETERS;
    $errorMessage 	= 'mobilpay.ro posted invalid parameters';
  }
}
else
{
  $errorType 		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_PERMANENT;
  $errorCode		= Mobilpay_Payment_Request_Abstract::ERROR_CONFIRM_INVALID_POST_METHOD;
  $errorMessage 	= 'invalid request metod for payment confirmation';
}


$Mobilpay_cc = new Mobilpay_cc();

// $Mobilpay_cc->setLogObj( "IPN -> Create NEW OBJ -> Mobilpay_cc ", null,  false);
// $Mobilpay_cc->setLogObj( "IPN -> objPmReq->orderId : ".$objPmReq->orderId, null,  false);
// $Mobilpay_cc->setLogObj( "IPN -> objPmReq->objPmNotify->errorCode ".$objPmReq->objPmNotify->errorCode, null,  false);
// $Mobilpay_cc->setLogObj( "-------------------------------------", null,  false);

if(!empty($objPmReq->orderId) && $objPmReq->objPmNotify->errorCode == 0) {
  
  Mobilpay_cc::setLogObj( "IPN -> objOrderID -> $objPmReq->orderId", null,  false);

	$orderIdParts = explode('#', $objPmReq->orderId);
	$realOrderId = intval($orderIdParts[0]);
  	$cart = new Cart($realOrderId);
    $customer = new Customer((int)$cart->id_customer);

    Mobilpay_cc::setLogObj( "---- IPN -> objPmReq->orderId ", null,  false);
    Mobilpay_cc::setLogObj( "---- IPN -> realOrderId -> ".$realOrderId, null,  false);
    
    // $Mobilpay_cc->setLogObj( $cart->date_upd, "IPN -> CART - date_upd : ",  true);
    // $Mobilpay_cc->setLogObj( $cart->_products, "IPN -> Products Value : ",  true);
    // $Mobilpay_cc->setLogObj( $cart, "IPN -> RealCardID : ".$realOrderId,  true);
    // $Mobilpay_cc->setLogObj( $customer, "IPN -> CUSTOMER " ,  true);

  //real order id
  $order_id = Order::getOrderByCartId($realOrderId);
  Mobilpay_cc::setLogObj( "---- IPN -> getOrderByCartId -> ".$realOrderId, null,  false);

  if(intval($order_id)>0) {
    Mobilpay_cc::setLogObj( "IPN -> Order Id is Not ZERO ->  order_id : ".$order_id, null, false);
    $order = new Order(intval($order_id));

    $history = new OrderHistory();
    $history->id_order = $order_id;
    // $history->changeIdOrderState(intval(Configuration::get('MPCC_OS_'.strtoupper($objPmReq->objPmNotify->action))), intval($order_id));

		$history->id_employee = 1;
		$carrier = new Carrier(intval($order->id_carrier), intval($order->id_lang));
		$templateVars = array('{followup}' => ($history->id_order_state == _PS_OS_SHIPPING_ AND $order->shipping_number) ? str_replace('@', $order->shipping_number, $carrier->url) : '');
		$history->addWithemail(true, $templateVars);
  }
  else {
    Mobilpay_cc::setLogObj( "IPN -> NULL -> Before Order Register . ", null, false);
    /**
     * CALL NEW CONTROLLER HERE
     */
    // $alfaLink = Context::getContext()->link->getModuleLink('mobilpay_cc', 'alfavalidation', array());
    // header("Location: http://navid.ctbhub.com/en/module/mobilpay_cc/alfavalidation/"); // Error 302
    // die();
    /**
     * Another Method - Start
     */
    // $postdata = http_build_query(
    //     array(
    //         'var1' => 'some content',
    //         'var2' => 'doh'
    //     )
    // );
    
    // $opts = array('http' =>
    //     array(
    //         'method'  => 'POST',
    //         'header'  => 'Content-Type: application/x-www-form-urlencoded',
    //         'content' => $postdata
    //     )
    // );
    
    // $context  = stream_context_create($opts);
    // $result = file_get_contents($alfaLink, false, $context);
    /**
     * Another Method - END
     */
    
    // $controller = $Mobilpay_cc->getModuleFrontControllerInstance();
    
    //create the order
    $Mobilpay_cc->validateOrder($objPmReq->orderId, intval(Configuration::get('MPCC_OS_'.strtoupper($objPmReq->objPmNotify->action))), floatval($objPmReq->invoice->amount), $Mobilpay_cc->displayName, NULL, array(), NULL, false, $customer->secure_key);  
    //$Mobilpay_cc->validateOrder($realOrderId, intval(Configuration::get('MPCC_OS_'.strtoupper($objPmReq->objPmNotify->action))), floatval($objPmReq->invoice->amount), $Mobilpay_cc->displayName, NULL, array(), NULL, false, $customer->secure_key);  
     
  }
}

/*
if (headers_sent()) {
  die("Redirect failed. Please click on this link: <a href=...>");
}
else{
  ?>
    <!DOCTYPE html>
    <html>
    <body onload="updateDB();">
    </body>
    <script language="javascript">
      function updateDB() {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "http://navid.ctbhub.com/en/module/mobilpay_cc/alfavalidation", true);
        xhr.send(null);
      }
    </script>
  </html>
  <?php
  die();
}
*/

header('Content-type: application/xml');
echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
if($errorCode == 0)
{
  echo "<crc>{$errorMessage}</crc>";
}
else
{
  echo "<crc error_type=\"{$errorType}\" error_code=\"{$errorCode}\">{$errorMessage}</crc>";
}
