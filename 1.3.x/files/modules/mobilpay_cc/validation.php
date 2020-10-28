<?php
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/mobilpay_cc.php');

require_once dirname(__FILE__).'/Mobilpay/Payment/Request/Abstract.php';
require_once dirname(__FILE__).'/Mobilpay/Payment/Request/Card.php';
require_once dirname(__FILE__).'/Mobilpay/Payment/Request/Notify.php';
require_once dirname(__FILE__).'/Mobilpay/Payment/Invoice.php';
require_once dirname(__FILE__).'/Mobilpay/Payment/Address.php';

$errorCode 		= 0;
$errorType		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_NONE;
$errorMessage	= '';

if (strcasecmp($_SERVER['REQUEST_METHOD'], 'post') == 0)
{
  if(isset($_POST['env_key']) && isset($_POST['data']))
  {
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
		  $errorCode = $objPmReq->objPmNotify->errorCode;
          $errorMessage = $objPmReq->objPmNotify->getCrc();
          break;
        case 'confirmed_pending':
          #cand action este confirmed_pending inseamna ca tranzactia este in curs de verificare antifrauda. Nu facem livrare/expediere. In urma trecerii de aceasta verificare se va primi o noua notificare pentru o actiune de confirmare sau anulare.
		  $errorCode = $objPmReq->objPmNotify->errorCode;
          $errorMessage = $objPmReq->objPmNotify->getCrc();
          break;
        case 'paid_pending':
          #cand action este paid_pending inseamna ca tranzactia este in curs de verificare. Nu facem livrare/expediere. In urma trecerii de aceasta verificare se va primi o noua notificare pentru o actiune de confirmare sau anulare.
		  $errorCode = $objPmReq->objPmNotify->errorCode;
          $errorMessage = $objPmReq->objPmNotify->getCrc();
          break;
        case 'paid':
          #cand action este paid inseamna ca tranzactia este in curs de procesare. Nu facem livrare/expediere. In urma trecerii de aceasta procesare se va primi o noua notificare pentru o actiune de confirmare sau anulare.
		  $errorCode = $objPmReq->objPmNotify->errorCode;
          $errorMessage = $objPmReq->objPmNotify->getCrc();
          break;
        case 'canceled':
          #cand action este canceled inseamna ca tranzactia este anulata. Nu facem livrare/expediere.
		  $errorCode = $objPmReq->objPmNotify->errorCode;
          $errorMessage = $objPmReq->objPmNotify->getCrc();
          break;
        case 'credit':
          #cand action este credit inseamna ca banii sunt returnati posesorului de card. Daca s-a facut deja livrare, aceasta trebuie oprita sau facut un reverse.
		  $errorCode = $objPmReq->objPmNotify->errorCode;
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

if(!empty($objPmReq->orderId) && $errorCode==0) {
  $cart = new Cart(intval($objPmReq->orderId));

  //real order id
  $order_id = Order::getOrderByCartId($objPmReq->orderId);

  if(intval($order_id)>0) {    
    $order = new Order(intval($order_id));

    $history = new OrderHistory();
    $history->id_order = $order_id;
    $history->changeIdOrderState(intval(Configuration::get('MPCC_OS_'.strtoupper($objPmReq->objPmNotify->action))), intval($order_id));

		$history->id_employee = 1;
		$carrier = new Carrier(intval($order->id_carrier), intval($order->id_lang));
		$templateVars = array('{followup}' => ($history->id_order_state == _PS_OS_SHIPPING_ AND $order->shipping_number) ? str_replace('@', $order->shipping_number, $carrier->url) : '');
		$history->addWithemail(true, $templateVars);
  }
  else {
    //create the order
    $Mobilpay_cc->validateOrder($objPmReq->orderId, intval(Configuration::get('MPCC_OS_'.strtoupper($objPmReq->objPmNotify->action))), floatval($objPmReq->objPmNotify->originalAmount), $Mobilpay_cc->displayName);
  }
}

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