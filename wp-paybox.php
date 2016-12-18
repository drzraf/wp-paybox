<?php

/**
 * Plugin Name: wp-paybox
 * License: General Public Licence v3 or later
 * Description: Accept Payment by credit card with Paybox
 * Author: Raphaël Droz <raphael.droz+floss@gmail.com>
 * Author URI: https://drzraf.me
 * Version: 0.1
 * Text Domain: wp-paybox
 * Domain Path: /languages
 * GitHub Plugin URI: https://gitlab.com/drzraf/wp-paybox
 */

/**
   TODO:
   - TYPECARTE: select multiple
   - Manage abonnements
   - payment-retry in case of paybox failure
   http://www1.paybox.com/espace-integrateur-documentation/la-solution-paybox-system/gestion-de-la-reponse/
   - PBX_ATTENTE ?
   - PBX_REFUSE and PBX_ANNULE must NOT allow reusing of the initial post->id
   since it's used as PBX_CMD which is *UNIQUE* from Paybox POV and will *NOT* be submitted again (payment being modified or not)
*/

defined( 'ABSPATH' ) or exit;

require_once __DIR__ . '/class-wp-paybox.php';
require_once __DIR__ . '/class-admin-settings.php';
require_once(__DIR__ . '/controller-ipn.php');
require_once(__DIR__ . '/controller-redirect.php');

load_plugin_textdomain( 'wp-paybox', false, __DIR__ );

add_action( 'rest_api_init', function () {
  register_rest_route('wp-paybox' , '/ipn', ['methods' => 'GET', 'callback' => ['WP_Paybox_IPN', 'init']]);
});

if (is_admin()) {
  $my_settings_page = new WP_Paybox_Settings();
}

function mylog($source = 'IPN', $log, $syslog_prio = NULL) {
  static $fp;
  syslog($syslog_prio ? : LOG_INFO, "paybox/{$source}: " . $log);
  if(! $fp) $fp = fopen(__DIR__ . '/log.txt','a+');
  fputs($fp, date("Y/m/d H:i:: ") . "{$source}: " . $log . PHP_EOL);
}
