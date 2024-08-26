<?php
/**
 * betavalidation is relocated for validation on root
 * And is the actuial IPN for mobilpay_cc
 */
 
require_once dirname(__FILE__).'/../../Mobilpay/Payment/Request/Abstract.php';
require_once dirname(__FILE__).'/../../Mobilpay/Payment/Request/Card.php';
require_once dirname(__FILE__).'/../../Mobilpay/Payment/Request/Notify.php';
require_once dirname(__FILE__).'/../../Mobilpay/Payment/Invoice.php';
require_once dirname(__FILE__).'/../../Mobilpay/Payment/Address.php';

class Mobilpay_CcBetavalidationModuleFrontController extends ModuleFrontController
{
	public $errorCode 		= 0;
	public $errorType		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_NONE;
	public $errorMessage	= '';
	public $beta = 0;
	public $cipher     = 'rc4';
	public $iv         = null;

	/**
	 * Default value for Samedays module
	 * If exist the Samedays module 
	 */
	public $samedaysLockerId = 0;
	public $samedaysLockerName = null;
	public $samedaysLockerAddress = null;

	public function initContent() {
		parent::initContent();
		$this->setTemplate('module:mobilpay_cc/views/templates/front/betavalidation.tpl');
	}

	
	public function postProcess()
	{
		if(array_key_exists('cipher', $_POST))
		{
		    $cipher = $_POST['cipher'];
		    if(array_key_exists('iv', $_POST))
		    {
			$iv = $_POST['iv'];
		    }
		}
	    if (strcasecmp($_SERVER['REQUEST_METHOD'], 'POST') == 0)
			{

			if(isset($_POST['env_key']) && isset($_POST['data']))
			{

				#calea catre cheia privata
				#cheia privata este generata de mobilpay, accesibil in Admin -> Conturi de comerciant -> Detalii -> Setari securitate
				$privateKeyFilePath = dirname(__FILE__).'/../../Mobilpay/certificates/private.key';
				try{
					$objPmReq = Mobilpay_Payment_Request_Abstract::factoryFromEncrypted($_POST['env_key'], $_POST['data'], $privateKeyFilePath, null, $cipher, $iv);

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
				} catch (Exception $e) {
					$this->errorType 		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_TEMPORARY;
					$this->errorCode		= $e->getCode();
					$this->errorMessage 	= $e->getMessage();
				}
		
			}	else {
				$this->errorType 		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_PERMANENT;
				$this->errorCode		= Mobilpay_Payment_Request_Abstract::ERROR_CONFIRM_INVALID_POST_PARAMETERS;
				$this->errorMessage 	= 'mobilpay.ro posted invalid parameters';
			}
		} else {
			$this->errorType 		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_PERMANENT;
			$this->errorCode		= Mobilpay_Payment_Request_Abstract::ERROR_CONFIRM_INVALID_POST_METHOD;
			$this->errorMessage 	= 'invalid request metod for payment confirmation';
		}

		
		if(!empty($objPmReq->orderId) && $objPmReq->objPmNotify->errorCode == 0) {
			/**
			 * Check params 
			 */

			if(!empty($objPmReq->params)) {
				foreach ($objPmReq->params as $pkey=>$pval) {
					switch ($pkey) {
						case "samedaysLockerId":
							$this->samedaysLockerId = $pval;
							break; 
						case "samedaysLockerName":
							$this->samedaysLockerName = $pval;
							break; 
						case "samedaysLockerAddress":
							$this->samedaysLockerAddress = $pval;
							break; 
					}
				}
			}

			$IpnOrderIdParts = explode('#', $objPmReq->orderId);
			$realOrderId = intval($IpnOrderIdParts[0]);
			$cart = new Cart($realOrderId);
			$customer = new Customer((int)$cart->id_customer);

			//real order id
			$order_id = Order::getOrderByCartId($realOrderId);
			

			if(intval($order_id)>0) {
				$order = new Order(intval($order_id));

				$history = new OrderHistory();
				$history->id_order = $order_id;

				$history->id_employee = 1;
				$carrier = new Carrier(intval($order->id_carrier), intval($order->id_lang));
				$templateVars = array('{followup}' => ($history->id_order_state == _PS_OS_SHIPPING_ AND $order->shipping_number) ? str_replace('@', $order->shipping_number, $carrier->url) : '');
				$history->addWithemail(true, $templateVars);

			}else{
				/**
				 * Add Order
				 */	
				 
				$result = $this->module->validateOrder(
					(int) $cart->id,
					(int) Configuration::get('MPCC_OS_'.strtoupper($objPmReq->objPmNotify->action)),
					// $total,
					floatval($objPmReq->invoice->amount),
					$this->module->displayName,
					null,  // Message , saved in ps_message TB
					array('transaction_id'=> $objPmReq->orderId), // Extera Vars
					// (int) $currency->id,
					null,
					false,
					$customer->secure_key
				);

				//real order id
				$order_id = Order::getOrderByCartId($realOrderId);
				/**
				 * Check if for sameday there is no record
				 * So add new record for Sameday
				 */
			
				if($order_id > 0) {
					if(($this->samedaysLockerId > 0) && !is_null($this->samedaysLockerName) && !is_null($this->samedaysLockerAddress)) {
						$sql = "INSERT INTO "._DB_PREFIX_."sameday_order_locker ( id_order, id_locker, 	address_locker, name_locker ) values( '$order_id', '$this->samedaysLockerId', '$this->samedaysLockerAddress', '$this->samedaysLockerName' )";
						Db::getInstance()->execute($sql);
					}
				}
			}
			  

		}

		$this->context->smarty->assign([
            'errorType' => $this->errorType,
            'errorCode' => $this->errorCode,
            'errorMessage' => $this->errorMessage
        ]);
	}
}
