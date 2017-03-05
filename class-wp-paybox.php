<?php

/*
 * Copyright (c) 2016, Raphaël Droz <raphael.droz+floss@gmail.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/*
  In order to use this class, just:
  - extend it
  - implements setReturnURLS()
  - optionnally override opt()
  - optionnally implements isTestMode()
  - use your extended class
*/

class WP_Paybox {

  const PBX_URL_TEST = 'https://preprod-tpeweb.paybox.com/cgi/MYchoix_pagepaiement.cgi';
  const PBX_URL      = 'https://tpeweb.paybox.com/cgi/MYchoix_pagepaiement.cgi';
  const PBX_URL_BCK  = 'https://tpeweb1.paybox.com/cgi/MYchoix_pagepaiement.cgi';


  const SOURCE_IPS = ['195.101.99.76', '194.2.122.158', '195.25.7.166'];

  const PBX_RETOUR = ['pbx_amount:M',
                      'ref:R',
                      'pbx_auth:A',
                      'pbx_trans:T',
                      'pbx_error:E',
                      'pbx_client_country:I',
                      'pbx_abonnement:B',
                      'pbx_bank_country:Y',
                      'pbx_sign:K']; // always latest

  const TYPE_PAIEMENT_POSSIBLE = ["", "CARTE", "PAYPAL", "CREDIT", "NETRESERVE", "PREPAYEE",
                                  "FINAREF", "BUYSTER", "LEETCHI", "PAYBUTTONS"];

  const TYPE_CARTE = [
    "CARTE"      => [ "CB", "VISA", "EUROCARD_MASTERCARD", "E_CARD",
                      "MAESTRO", "AMEX", "DINERS", "JCB", "COFINOGA", "SOFINCO",
                      "AURORE", "CDGP", "24H00", "RIVEGAUCHE" ],
    "PAYPAL"     => [ "PAYPAL" ],
    "CREDIT"     => [ "UNEURO", "34ONEY" ],
    "NETRESERVE" => [ "NETCDGP" ],
    "PREPAYEE"   => [ "SVS", "KADEOS", "PSC", "CSHTKT", "LASER", "EMONEO",
                      "IDEAL", "ONEYKDO", "ILLICADO", "WEXPAY", "MAXICHEQUE" ],
    "FINAREF"    => [ "SURCOUF", "KANGOUROU", "FNAC", "CYRILLUS", "PRINTEMPS", "CONFORAMA" ],
    "BUYSTER"    => [ "BUYSTER" ],
    "LEETCHI"    => [ "LEETCHI" ],
    "PAYBUTTONS" => [ "PAYBUTTING" ] ];

  // thx https://github.com/OCA/account-payment/pull/41/files
  const ERROR_CODE = ['00001' => "La connexion au centre d'autorisation a échoué ou une erreur interne est survenue",
                      '001'   => "Paiement refusé par le centre d'autorisation",
                      '00003' => "Erreur Paybox",
                      '00004' => "Numéro de porteur ou cryptogramme visuel invalide",
                      '00006' => "Accès refusé ou site/rang/identifiant incorrect",
                      '00008' => "Date de fin de validité incorrecte",
                      '00009' => "Erreur de création d'un abonnement",
                      '00010' => "Devise inconnue",
                      '00011' => "Montant incorrect",
                      '00015' => "Paiement déjà effectué",
                      '00016' => "Abonné déjà existant",
                      '00021' => "Carte non autorisée",
                      '00029' => "Carte non conforme",
                      '00030' => "Temps d'attente supérieur à 15 minutes par l'acheteur au niveau la page de paiement",
                      '00033' => "Code pays de l'adresse IP du navigateur de l'acheteur non autorisé",
                      '00040' => "Opération sans authentification 3-D Secure, bloquée par le filtre" ];

  const AUTH_CODE = [ '03' => "Commerçant invalide",
                      '05' => "Ne pas honorer",
                      '12' => "Transaction invalide",
                      '13' => "Montant invalide",
                      '14' => "Numéro de porteur invalide",
                      '15' => "Emetteur de carte inconnu",
                      '17' => "Annulation client",
                      '19' => "Répéter la transaction ultérieurement",
                      '20' => "Réponse erronée (erreur dans le domaine serveur)",
                      '24' => "Mise à jour de fichier non supportée",
                      '25' => "Impossible de localiser l'enregistrement dans le fichier",
                      '26' => "Enregistrement dupliqué, ancien enregistrement remplacé",
                      '27' => "Erreur en \"edit\" sur champ de mise à jour fichier",
                      '28' => "Accès interdit au fichier",
                      '29' => "Mise à jour de fichier impossible",
                      '30' => "Erreur de format",
                      '33' => "Carte expirée",
                      '38' => "Nombre d'essais code confidentiel dépassé",
                      '41' => "Carte perdue",
                      '43' => "Carte volée",
                      '51' => "Provision insuffisante ou crédit dépassé",
                      '54' => "Date de validité de la carte dépassée",
                      '55' => "Code confidentiel erroné",
                      '56' => "Carte absente du fichier",
                      '57' => "Transaction non permise à ce porteur",
                      '58' => "Transaction interdite au terminal",
                      '59' => "Suspicion de fraude",
                      '60' => "L'accepteur de carte doit contacter l'acquéreur",
                      '61' => "Dépasse la limite du montant de retrait",
                      '63' => "Règles de sécurité non respectées",
                      '68' => "Réponse non parvenue ou reçue trop tard",
                      '75' => "Nombre d'essais code confidentiel dépassé",
                      '76' => "Porteur déjà en opposition, ancien enregistrement conservé",
                      '89' => "Echec de l'authentification",
                      '90' => "Arrêt momentané du système",
                      '91' => "Emetteur de carte inaccessible",
                      '94' => "Demande dupliquée",
                      '96' => "Mauvais fonctionnement du système",
                      '97' => "Echéance de la temporisation de surveillance globale" ];

  // based upon those supported by Give, cf give_get_currencies()
  const currency_codes = ['USD' => 840,
                          'EUR' => 978,
                          'GBP' => 826,
                          'AUD' => 036,
                          'BRL' => 986,
                          'CAD' => 124,
                          'CZK' => 203,
                          'DKK' => 208,
                          'HKD' => 344,
                          'HUF' => 348,
                          'ILS' => 376,
                          'JPY' => 392,
                          'MYR' => 458,
                          'MAD' => 504,
                          'NZD' => 554,
                          'NOK' => 578,
                          'PHP' => 608,
                          'PLN' => 985,
                          'SGD' => 702,
                          'KRW' => 410,
                          'ZAR' => 710,
                          'SEK' => 752,
                          'CHF' => 756,
                          'TWD' => 901,
                          'THB' => 764,
                          'INR' => 356,
                          'TRY' => 949,
                          'RUB' => 643];

  public function uninstall() {
    delete_option('wp_paybox_settings');
    delete_option('wp_paybox_HMAC_settings');
  }

  public static function isTestMode() {
    return static::opt('testmode') == 1;
  }

  public static function getPayboxURL() {
    if(static::isTestMode()) {
      return self::PBX_URL_TEST;
    } else {
      return self::PBX_URL;
    }
  }
  public static function opt($opt) {
    // wp_paybox_HMAC_settings is it own settings (unserialized) so that prod/preprod/mysqldump
    // are easier to manage
    if ($opt == 'hmac') return  get_option('wp_paybox_HMAC_settings');
    return get_option('wp_paybox_settings')[$opt];
  }


  // No needs to overring these
  public static function preparePayboxCommonData(&$params) {
    $params += array('PBX_SITE' => static::opt('site'),
                     'PBX_RANG' => static::opt('rang'),
                     'PBX_IDENTIFIANT' => static::opt('ident'),
                     'PBX_RETOUR' => implode(';', self::PBX_RETOUR));

    $PBX_TYPEPAIEMENT = static::opt('limit_payment');
    $PBX_TYPECARTE = static::opt('limit_card');
    if($PBX_TYPEPAIEMENT && in_array($PBX_TYPECARTE, self::TYPE_CARTE[$PBX_TYPEPAIEMENT])) {
      $params += array('PBX_TYPEPAIEMENT' => $PBX_TYPEPAIEMENT,
                       'PBX_TYPECARTE' => $PBX_TYPECARTE);
      // XXX: https://github.com/lexik/LexikPayboxBundle/issues/21
      // can't be comma-separated, but PBX_TYPECARTE must be passed multiples times for multiples values!
    }
  }

  public static function preparePaybox3xData(&$params, $total) {
    $m1 = $m2 = intval($total / 3);
    $m3 = intval($total - $m1 - $m2);
    // PBX_TOTAL est remplacé par le montant de la 1ère échéance
    $params = array_replace($params, ['PBX_DATE1'  => date('d/m/Y', time() + (static::opt('nbdays') * 3600 * 24)),
                                      'PBX_DATE2'  => date('d/m/Y', time() + (static::opt('nbdays') * 3600 * 24 * 2)),
                                      'PBX_TOTAL'  => $m1,
                                      'PBX_2MONT1' => $m2,
                                      'PBX_2MONT2' => $m3]);
  }

  public function preparePayboxData(Payboxable $c /* controller override */) {
    if ($c->isPayment3X() && $c->getAmount() < floatval(static::opt('3dmin'))) {
      die(__("You can't pay in 3x for this amount")); // TODO: redirect
    }

		$PBX_TOTAL = number_format($c->getAmount(), 2, '', '');

    $PBX_base_param = array();
    self::preparePayboxCommonData($PBX_base_param);

    $PBX_base_param += ['PBX_TOTAL' => $PBX_TOTAL,
                        'PBX_DEVISE' => $c->getCurrency(),
                        'PBX_CMD' => $c->getUniqId(),
                        // warning xx@localhost.localdomain is rejected by paybox (Erreur de protection)
                        'PBX_PORTEUR' => $c->getEmail(),
                        'PBX_LANGUE' => preg_match('/^fr/', get_locale()) ? 'FRA' : 'GBR',
                        // urlencode(), cf:
                        // http://www1.paybox.com/espace-integrateur-documentation/la-solution-paybox-system/gestion-de-la-reponse/
                        // contrairement à PBX_EFFECTUE, PBX_REFUSE et PBX_ANNULE, PBX_REPONDRE_A est appelée par le robot de Paybox 
                        // et non un <meta refresh>, elle n'est donc pas encodée
                        'PBX_REPONDRE_A' => get_rest_url(NULL, 'wp-paybox/v1/ipn')];

    $this->setReturnURLS($PBX_base_param['PBX_EFFECTUE'], $PBX_base_param['PBX_REFUSE'], $PBX_base_param['PBX_ANNULE']);

    // paiement en 3 fois
    if ($c->isPayment3X()) {
      self::preparePaybox3xData($PBX_base_param, $PBX_TOTAL);
    }

    if($c->getAmount() < floatval(static::opt('3dmin'))) {
      $PBX_base_param['PBX_3DS'] = 'N'; // default, if unset, is True
    }
    $params = $PBX_base_param + ['PBX_TIME' => date("c"), 'PBX_HASH' => 'SHA512'];

    $pbx_query = urldecode(http_build_query($params, NULL, "&", PHP_QUERY_RFC3986));

    // $this->logger("redir", "built query: $pbx_query");
    $hmac = strtoupper(hash_hmac('sha512', $pbx_query, pack("H*", static::opt('hmac'))));

    return [$params, $hmac];
  }
}
