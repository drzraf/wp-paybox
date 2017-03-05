<?php

/*
 * Copyright (c) 2016, Raphaël Droz <raphael.droz+floss@gmail.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

defined( 'ABSPATH' ) or exit;

class WP_Paybox_Settings {
  private $options;

  public function __construct() {
    add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
    add_action( 'admin_init', array( $this, 'page_init' ) );
  }

  public function add_plugin_page(){
    // This page will be under "Settings"
    add_options_page(
      'Settings Admin', 
      __('Accept payments by Paybox', 'wp-paybox'),
      'manage_options', 
      'wp-paybox-settings', 
      array( $this, 'create_admin_page' )
    );
  }

  public function create_admin_page() {
    // Set class property
    $this->options = get_option( 'wp_paybox_settings' );
    ?>
                   <div class="wrap">
                             <h1>Paybox settings</h1>
                             <form method="post" action="options.php">
<?php
                             // This prints out all hidden setting fields
                             settings_fields( 'my_option_group' );
    do_settings_sections( 'my-setting-admin' );
    submit_button();
    ?>
      </form>
          </div>
<?php
          }

  public function page_init() {        
    register_setting(
      'my_option_group', // Option group
      'my_option_name', // Option name
      array( $this, 'sanitize' ) // Sanitize
    );

    add_settings_section(
      'setting_section_id', // ID
      'My Custom Settings', // Title
      function() { print 'Enter your settings below:'; },
      'my-setting-admin'
    );  

    add_settings_field(
      'id_number', // ID
      'ID Number', // Title 
      array( $this, 'id_number_callback' ), // Callback
      'my-setting-admin', // Page
      'setting_section_id' // Section           
    );      

    add_settings_field(
      'title', 
      'Title', 
      array( $this, 'title_callback' ), 
      'my-setting-admin', 
      'setting_section_id'
    );      

    add_settings_section('site',
                         __('Paybox site value', 'wp-paybox'),
                         function() { printf('<input type="text" size="10" id="site" name="paybox[site]" value="%s" />', isset( $this->options['site'] ) ? esc_attr( $this->options['site']) : ''); },
                         'my-setting-admin', 
                         'setting_section_id');

    add_settings_section('rang',
                         __('Paybox rang value', 'wp-paybox'),
                         function() { printf('<input type="text" size="10" id="rang" name="paybox[rang]" value="%s" />', isset( $this->options['rang'] ) ? esc_attr( $this->options['rang']) : ''); },
                         'my-setting-admin', 
                         'setting_section_id');

    add_settings_section('ident',
                         __('Paybox ident value', 'wp-paybox'),
                         function() { printf('<input type="text" size="10" id="ident" name="paybox[ident]" value="%s" />', isset( $this->options['ident'] ) ? esc_attr( $this->options['ident']) : ''); },
                         'my-setting-admin', 
                         'setting_section_id');

    add_settings_section('hmac',
                         __('Paybox hmac value', 'wp-paybox'),
                         function() { printf('<input type="text" size="33" id="hmac" name="paybox[hmac]" value="%s" />', isset( $this->options['hmac'] ) ? esc_attr( $this->options['hmac']) : ''); },
                         'my-setting-admin', 
                         'setting_section_id');

    add_settings_section('checkip',
                         __("Should IPN endpoint check Paybox source IP?", 'wp-paybox'),
                         function() { printf('<input type="radio" name=""paybox[checkip]" value="1" %1$s /> %2$s <input type="radio" name=""paybox[checkip]" value="0" %2$s /> %4$s', $this->options['checkip'], __('Yes'), ! $this->options['checkip'], __('No')); },
                         'my-setting-admin', 
                         'setting_section_id');

    add_settings_section('testmode',
                         __('Test mode', 'wp-paybox'),
                         function() { printf('<input type="radio" name=""paybox[testmode]" value="1" %1$s /> %2$s <input type="radio" name=""paybox[testmode]" value="0" %2$s /> %4$s', $this->options['testmode'], __('Yes'), ! $this->options['testmode'], __('No')); },
                         'my-setting-admin', 
                         'setting_section_id');
                         
    add_settings_section('3dmin',
                         __('Minimum amount for 3D Secure', 'wp-paybox'),
                         function() { printf('<input type="number" size="5" id="3dmin" name="paybox[3dmin]" value="%s" />', isset( $this->options['3dmin'] ) ? esc_attr( $this->options['3dmin']) : ''); },
                         'my-setting-admin', 
                         'setting_section_id');

    add_settings_section('accept3x',
                         __('Accept 3x payments', 'wp-paybox'),
                         function() { printf('<input type="radio" name=""paybox[accept3x]" value="1" %1$s /> %2$s <input type="radio" name=""paybox[accept3x]" value="0" %2$s /> %4$s', $this->options['accept3x'], __('Yes'), ! $this->options['accept3x'], __('No')); },
                         'my-setting-admin', 
                         'setting_section_id');

    add_settings_section('3xmin',
                         __('The minimum amount to activate the multiple payments', 'wp-paybox'),
                         function() { printf('<input type="number" size="5" id="3xmin" name="paybox[3xmin]" value="%s" />', isset( $this->options['3xmin'] ) ? esc_attr( $this->options['3xmin']) : ''); },
                         'my-setting-admin', 
                         'setting_section_id');

    add_settings_section('nbdays',
                         __('Number of days between multiple payments', 'wp-paybox'),
                         function() { printf('<input type="number" size="5" id="nbdays" name="paybox[nbdays]" value="%s" />', isset( $this->options['nbdays'] ) ? esc_attr( $this->options['nbdays']) : ''); },
                         'my-setting-admin', 
                         'setting_section_id');

    add_settings_section('limit_payment',
                         __('Limit payment types', 'wp-paybox'),
                         function() {
                            printf('<select name="paybox[limit_payment]">');
                            foreach(WP_Paybox::TYPE_PAIEMENT_POSSIBLE as $v) {
                              printf('<option %s value="%s">%s</option>', ($v == $this->options['limit_payment']) ? 'selected="selected"' : '', $v, $v ? : 'No restriction');
                            }
                            printf('</select>');
                         },
                         'my-setting-admin', 
                         'setting_section_id');

    if ($this->options['limit_payment']) {
      add_settings_section('limit_card',
                           __('Restriction sur le type de carte', 'wp-paybox'),
                           function() {
                             __('Submit settings in order to select below card restrictions', 'wp-paybox');
                             __('/!\ Not fully implemented yet!', 'wp-paybox');
                             printf('<select name="paybox[limit_card]">');
                             foreach(WP_Paybox::TYPE_CARTE[$this->options['limit_payment']] as $v) {
                               printf('<option %s value="%s">%s</option>', ($v == $this->options['limit_card']) ? 'selected="selected"' : '', $v, $v == 'CB' ? 'CB (= Any card)' : $v);
                             }
                             printf('</select>');
                           },
                           'my-setting-admin', 
                           'setting_section_id');
    }
  }


  public function sanitize( $input ) {
    $new_input = array();
    if (isset( $input['site'] ))
      $_postErrors[] = __('Paybox site required');
    if (isset( $input['rang'] ))
      $_postErrors[] = __('Paybox rang required');
    if (isset( $input['identifiant'] ))
      $_postErrors[] = __('Paybox identifiant required');
    if (isset( $input['checkip'] ))
      $_POST['pbx_checkip'] = 0;
    if (isset( $input['testmod'] ))
      $_POST['pbx_testmod'] = 0;
    if (! in_array($_POST['pbx_typepaiement'], self::TYPE_PAIEMENT_POSSIBLE))
      $_POST['pbx_typepaiement'] = '';
    if(! isset($_POST['pbx_typecarte'])) // may not have been POSTed
      $_POST['pbx_typecarte'] = '';
    if (! $_POST['pbx_typepaiement'] ||
        ! in_array($_POST['pbx_typecarte'], self::TYPE_CARTE[$_POST['pbx_typepaiement']]))
      $_POST['pbx_typecarte'] = '';

    if( isset( $input['id_number'] ) )
      $new_input['id_number'] = absint( $input['id_number'] );

    if( isset( $input['title'] ) )
      $new_input['title'] = sanitize_text_field( $input['title'] );

    return $new_input;
  }


  public function id_number_callback() {
    printf(
      '<input type="text" id="id_number" name="my_option_name[id_number]" value="%s" />',
      isset( $this->options['id_number'] ) ? esc_attr( $this->options['id_number']) : ''
    );
  }

  public function title_callback() {
    printf(
      '<input type="text" id="title" name="my_option_name[title]" value="%s" />',
      isset( $this->options['title'] ) ? esc_attr( $this->options['title']) : ''
    );
  }

	public function getContent() {
    // 2 steps saving of these two values, not strictly speaking an error
    if($_POST['pbx_typepaiement'] && ! $_POST['pbx_typecarte']) {
      $this->context->smarty->assign('paybox_postWarning', __('Please now select the TYPECARTE restriction'));
    }
    if($settings['pbx_typepaiement'])
      $this->context->smarty->assign('pbx_all_cartes', self::TYPE_CARTE[$settings['pbx_typepaiement']]);
  }
}
