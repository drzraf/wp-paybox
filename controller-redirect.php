<?php

/*
 * Copyright (c) 2016, Raphaël Droz <raphael.droz+floss@gmail.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

class WP_Paybox_RedirectController {
  public function init() {
    $this->ensureValidConfiguration();
    if($this::PERSIST_MODE === Payboxable::MODE_PERSIST_BEFORE_REDIRECT) {
      $this->set();
    }
    else {
      if($this->exists()) {
        $this->create();
      }
      else {
        $this->update();
      }
    }

    list($params, $hmac) = $paybox->preparePayboxData($this);
    self::makeHMACForm($params, $hmac);
    return;


  }

  public static function makeHMACForm($params, $hmac) {
    $PBX_PAYBOX     = WP_Paybox::getPayboxURL();
    $pbx_parameters = $params;
    $hmac           = $hmac;
    load_template( __DIR__ . '/templates/hmac-payment.php' );
  }

  public function create() {
    // we are going to output a redirection to Paybox
    // we want to communicate our entity ID as PBX_CMD
    // Thus, here, just before the redirection gets done we create a persistent entity
    mylog("redir", sprintf("save(ref=%s, total=%.2f €)", $this->getUniqId(), $this->getAmount()));
  }

  public function update() {
    $id_order = Order::getOrderByCartId($cart->id);
    $uniqid = Order::getUniqReferenceOf($id_order);
    if (! $uniqid) {
      mylog("redir", "can't find a reference for id_cart={$cart->id} => id_order=$id_order. Exit.", LOG_ERR);
      return displayError($paybox->l("missing order for this cart", 'redirect'));
    }

    $order = new Order($id_order);
    if($order->total_paid_real > 0) { // TODO total_paid_real != total_paid ?
      mylog("redir", "Existing order found: n°$id_order - $uniqid for cart {$cart->id}. But total_paid_real = {$order->total_paid_real} > 0. Exit", LOG_ERR);
      exit; // TODO: redirect: invalid cart/order
    }

    if($order->module != 'paybox') {
      mylog("redir", "Existing order found: n°$id_order - $uniqid for cart {$cart->id}. But order not created using paybox. Exit", LOG_ERR);
      exit; // TODO: redirect: invalid cart/order
    }

    if($order->current_state != Configuration::get('PAYBOX_OS_AUTHORIZATION_PENDING') &&
       $order->current_state != Configuration::get('PS_OS_ERROR')) {
      mylog("redir", sprintf("Existing order found: n°$id_order - $uniqid for cart {$cart->id}. But order state = %d which is different from the expected PAYBOX_OS_AUTHORIZATION_PENDING/%d value. Exit",
                             $order->current_state,
                             Configuration::get('PAYBOX_OS_AUTHORIZATION_PENDING')), LOG_ERR);
      exit; // TODO: redirect: invalid cart/order
    }

    mylog("redir", "Existing order found: n°$id_order - $uniqid for cart {$cart->id}. total_paid_real = 0. Doing paybox redirect. Exit");
  }

  public function ensureValidConfiguration() {
    $is_paybox3x = (int)WP_Paybox::opt('paybox3x');
    $address = new Address((int)($cart->id_address_delivery));
    $customer = new Customer((int)($cart->id_customer));

    if (!Validate::isLoadedObject($address) OR !Validate::isLoadedObject($customer)) {
      Tools::displayError($paybox->l('invalid address or customer', 'redirect'));
      $this->setTemplate('errors.tpl');
      return;
    }
    if (!Validate::isLoadedObject($this->context->shop)) {
      Tools::displayError($paybox->l('invalid shop reference', 'redirect'));
      $this->setTemplate('errors.tpl');
      return;
    }
 }

  // to use with
  // add_action('init', ['WP_Paybox_RedirectController', 'default_endpoints']);
  function default_endpoints() {
    $url_path = trim(parse_url(add_query_arg([]), PHP_URL_PATH), '/');
    if ( $url_path === 'paybox/confirm') {
    }
    if ( $url_path === 'paybox/redirect') {
      // WP_Paybox_RedirectController
    }
    if ( $url_path === 'paybox/confirm') {
    }
    if ( $url_path === 'paybox/error') {
    }
    if ( $url_path === 'paybox/cancel') {
    }
  }
}
