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

  private $checkip;
  private $pbx_retour;
  private $pbx_ips;

  public function __construct($checkip, $pbx_retour, $pbx_ips = NULL) {
    $this->checkip = $checkip;
    $this->pbx_retour = $pbx_retour;
    $this->pbx_ip = $pbx_ips;
  }

  function default_logger($log, $syslog_prio = NULL, $source) {
    static $fp;
    syslog($syslog_prio ? : LOG_INFO, "paybox/{$source}: " . $log);
    if(! $fp) $fp = fopen(__DIR__ . '/log.txt','a+');
    fputs($fp, date("Y/m/d H:i:: ") . "{$source}: " . $log . PHP_EOL);
  }

  public function logger($args) {
    if (is_callable([$this, 'custom_logger'])) {
      $this->custom_logger(func_get_args() + ['IPN']);
    }
    else {
      call_user_func_array([$this, 'default_logger'], func_get_args() + [1 => NULL, 2=>'IPN']);
    }
  }

  public function init() {
    if(! has_action('paybox_handle_IPN')) {
      $this->logger("Paybox integration error: no registered custom handler for the IPN. exit");
      exit; // fake IP (or unknown Paybox IP)
    }

    $this->logger("start: " . $_SERVER['REMOTE_ADDR'] . ": " . $_SERVER['QUERY_STRING']);

    if($this->checkip && ! check_pbx_src_ip()) {
      $this->logger("...exit");
      exit; // fake IP (or unknown Paybox IP)
    }

    $this->logger("processing request");

    // recuperation des variables envoyées par Paybox

    // paybox utilise un POST que si nous le sollicitons expressément
    // $vars = array_merge($_GET, $_POST);
    $vars = $_GET;

    if(!isset($vars['pbx_sign']) OR !isset($vars['pbx_error'])) {
      $this->logger("Missing parameters. exit");
      exit;
    }
    if(!isset($vars['pbx_auth'])) {
      $this->logger("Paybox transmited transaction refusal. exit.");
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
      $this->pbx_retour);

    // doit préserver l'ordre des paramètres, tels qu'envoyés (et signés) par Paybox
    $pbx_signed_data = array_intersect_key($_GET, array_flip(array_filter($pbx_signed_fields)));
    $pbx_signed_data_string = http_build_query($pbx_signed_data);

    // verification signature
    if(FALSE && $this->checksig($pbx_signed_data_string, $pbx_sign, __DIR__ . '/paybox.com.pem') !== 1) {
      $this->logger("Unverifiable signature. exit");
      exit;
    }

    // TODO ?
    // XXX: ETAT_PBX fait partie des données signée ? si oui, alors rajouter à WP_Paybox::PBX_RETOUR
    if(isset($_GET['ETAT_PBX']) && $_GET['ETAT_PBX'] == 'PBX_RECONDUCTION_ABT') {
      $this->logger("Reconduction {$vars['ref']}. exit.");
      exit;
    }

    if(!isset($vars['ref'])) {
      $this->logger("No reference number. exit", LOG_ERR);
      exit;
    }

    // load the class attached to this post_id and forward processing.
    $p = get_post($vars['ref']);
    if (! $p) {
      $this->logger("Can't find post {$vars['ref']}. exit", LOG_ERR);
    }

    do_action('paybox_handle_IPN', $p, $vars, [$this, 'logger']);
    $this->logger("generic IPN handler terminate. finish");
    exit;
  }

// cf http://www1.paybox.com/espace-integrateur-documentation/la-solution-paybox-system/urls-dappels-et-adresses-ip/
  protected function check_pbx_src_ip() {
    // TODO: reverse-proxy should forbids this checks
    if(in_array($_SERVER['REMOTE_ADDR'], $this->pbx_ips)) {
      $this->logger("Paybox source IP: authorized");
      return TRUE;
    }
    $this->logger("Paybox source IP {$_SERVER['REMOTE_ADDR']}: unauthorized", LOG_WARNING);
    return FALSE;
  }

  // verification signature Paybox
  private function checksig($data, $sig, $keyfile) {
    $this->logger("Paybox signature check for: $data");
    $key = openssl_pkey_get_public(file_get_contents($keyfile));
    if(!$key) {
      $this->logger("Paybox signature check: can't load $keyfile", LOG_ERR);
      return -1;
    }
    $sig = base64_decode($sig);
    // verification : 1 si valide, 0 si invalide, -1 si erreur
    $ret = openssl_verify($data, $sig, $key);
    $ret_text = ($ret == -1 ? 'error' : $ret == 0 ? 'failure' : 'success');
    $this->logger("Paybox signature verification: {$ret_text} ($ret)", $ret != 1 ? LOG_ERR : NULL);
    return $ret;
  }
}
