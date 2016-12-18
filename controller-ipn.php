<?php
/*
 * Copyright (c) 2016, Raphaël Droz <raphael.droz+floss@gmail.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This manages reactions to hits to IPN URL as set in Paybox as PBX_REPONDRE_A,
 * cf http://www1.paybox.com/espace-integrateur-documentation/la-solution-paybox-system/gestion-de-la-reponse/
 * Only requested data, as set in PBX_RETOUR are signed.
 * "The only limitation is that this script must not do redirect and must generate an empty HTML page."
 */

class WP_Paybox_IPN {
  public function init() {
    mylog("IPN", "start: " . $_SERVER['REMOTE_ADDR'] . ": " . $_SERVER['QUERY_STRING']);


    if(WP_Paybox::opt('CHECKIP') && ! check_pbx_src_ip()) {
      mylog("IPN", "...exit");
      exit; // fake IP (or unknown Paybox IP)
    }

    mylog("IPN", "processing request");

    // recuperation des variables envoyées par Paybox

    // paybox utilise un POST que si nous le sollicitons expressément
    // $vars = array_merge($_GET, $_POST);
    $vars = $_GET;

    if(!isset($vars['pbx_sign']) OR !isset($vars['pbx_error'])) {
      mylog("IPN", "Missing parameters: exit.");
      exit;
    }
    if(!isset($vars['pbx_auth'])) {
      mylog("IPN", "Paybox transmited transaction refusal. exit.");
      exit;
    }

    /* How to fail "kindly" in the above cases?
       $paybox->validateOrder(intval($vars['ref']), _PS_OS_ERROR_, 0, $paybox->displayName, $erreurPayment.'<br />');
       auquel cas, après vérification de la signature */
    $pbx_sign = $vars['pbx_sign'];

    // Note: ref:R au retour == PBX_CMD à l'envoi == $post->id
    // (default class implementation that may be overriden)
    $pbx_signed_fields = array_map(
      function($e) {
        if(preg_match('/pbx_sign/', $e)) return NULL;
        return preg_replace('/:.*/', '', $e);
      },
      WP_Paybox::PBX_RETOUR);

    // doit préserver l'ordre des paramètres, tels qu'envoyés (et signés) par Paybox
    $pbx_signed_data = array_intersect_key($_GET, array_flip(array_filter($pbx_signed_fields)));
    $pbx_signed_data_string = http_build_query($pbx_signed_data);

    // verification signature
    if(checksig($pbx_signed_data_string, $pbx_sign, __DIR__ . 'paybox.com.pem') !== 1) {
      mylog("IPN", "exit.");
      exit;
    }

    // TODO ?
    // XXX: ETAT_PBX fait partie des données signée ? si oui, alors rajouter à WP_Paybox::PBX_RETOUR
    if(isset($_GET['ETAT_PBX']) && $_GET['ETAT_PBX'] == 'PBX_RECONDUCTION_ABT') {
      mylog("IPN", "Reconduction {$vars['ref']}. exit.");
      exit;
    }

    if(!isset($vars['ref'])) {
      mylog("IPN", "No reference number: exit.", LOG_ERR);
      exit;
    }

    // load the class attached to this post_id and forward processing.
    $p = get_posts($vars['ref']);

    if (class_implements($p)['Payboxable']) {
      if ($p->handleIPN(mylog, $vars)) {
        mylog("IPN", "handler success. exit");
      } else {
        mylog("IPN", "handler failure. exit");
      }
    }
    else {
      // TODO: default handler ?
      mylog("IPN", "no handler! exit");
    }
    exit;
  }

// cf http://www1.paybox.com/espace-integrateur-documentation/la-solution-paybox-system/urls-dappels-et-adresses-ip/
  protected function check_pbx_src_ip() {
    // TODO: reverse-proxy should forbids this checks
    if(in_array($_SERVER['REMOTE_ADDR'], WP_Paybox::SOURCE_IPS)) {
      mylog("IPN", "Paybox source IP: authorized");
      return TRUE;
    }
    mylog("IPN", "Paybox source IP {$_SERVER['REMOTE_ADDR']}: unauthorized", LOG_WARNING);
    return FALSE;
  }

  // verification signature Paybox
  private function checksig($data, $sig, $keyfile) {
    mylog("IPN", "Paybox signature check for: $data");
    $key = openssl_pkey_get_public(file_get_contents($keyfile));
    if(!$key) {
      mylog("IPN", "Paybox signature check: can't load $keyfile", LOG_ERR);
      return -1;
    }
    $sig = base64_decode($sig);
    // verification : 1 si valide, 0 si invalide, -1 si erreur
    $ret = openssl_verify($data, $sig, $key);
    $ret_text = ($ret == -1 ? 'error' : $ret == 0 ? 'failure' : 'success');
    mylog("IPN", "Paybox signature verification: {$ret_text} ($ret)", $ret != 1 ? LOG_ERR : NULL);
    return $ret;
  }
}
