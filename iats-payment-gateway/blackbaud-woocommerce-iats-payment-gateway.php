<?php
/*
Plugin Name: Blackbaud - WooCommerce - iATS Payment Gateway
Plugin URI: http://www.blackbaud.com/
Description: Adds iATS as a payment gateway for WooCommerce.
Version: 1.0.0
Author: Blackbaud - Bobby Earl
Author URI: http://www.blackbaud.com
*/

/**
* Add the iATS gateway to the WooCommerce options.
*/
add_filter('woocommerce_payment_gateways', function($methods) {
	$methods[] = 'Blackbaud_WooCommerce_iATS_Gateway';
	return $methods;
});

/**
* Add our custom CSS.
*/
add_action('wp_enqueue_scripts', function() {
	wp_register_style('blackbaud-woocommerce-iats-css', plugins_url('blackbaud-woocommerce-iats-payment-gateway/blackbaud-woocommerce-iats-payment-gateway.css'));
	wp_enqueue_style('blackbaud-woocommerce-iats-css');
});

/**
* Include our class after verifying WC_Payment_Gateway class exists.
*/
add_action('plugins_loaded', function() {
	if (class_exists('WC_Payment_Gateway')) {
		include 'blackbaud-woocommerce-iats-payment-gateway.class.php';
	}
});

?>