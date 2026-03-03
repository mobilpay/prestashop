<?php
/**
 * Payment return controller: "back to merchant" URL for the user.
 * - Order exists (payment accepted) → redirect to order-confirmation.
 * - No order (failed/cancelled) → redirect to cart (avoids 404).
 * The platform also receives confirmUrl (betavalidation) for IPN; both URLs are sent in the request.
 */

class Mobilpay_CcReturnModuleFrontController extends ModuleFrontController
{
    public function init()
    {
        parent::init();

        $id_cart = (int) Tools::getValue('id_cart', 0);
        $key = Tools::getValue('key', '');

        if ($id_cart < 1 || $key === '') {
            Tools::redirect($this->context->link->getPageLink('cart', true, (int) $this->context->language->id, ['action' => 'show']));
            return;
        }

        $cart = new Cart($id_cart);
        if (!Validate::isLoadedObject($cart) || $cart->secure_key !== $key) {
            Tools::redirect($this->context->link->getPageLink('cart', true, (int) $this->context->language->id, ['action' => 'show']));
            return;
        }

        $id_order = Order::getIdByCartId($id_cart);
        if ($id_order !== false && (int) $id_order > 0) {
            $params = [
                'id_cart' => $id_cart,
                'id_module' => (int) $this->module->id,
                'id_order' => (int) $id_order,
                'key' => $key,
            ];
            Tools::redirect($this->context->link->getPageLink('order-confirmation', true, (int) $this->context->language->id, $params));
        } else {
            Tools::redirect($this->context->link->getPageLink('cart', true, (int) $this->context->language->id, ['action' => 'show']));
        }
    }
}
