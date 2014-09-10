<?php
/*
Plugin Name: Blackbaud - WooCommerce - BBSP Payment Gateway
Plugin URI: http://www.blackbaud.com/
Description: Adds BBSP as a payment gateway for WooCommerce.
Version: 1.0.0
Author: Blackbaud - Bobby Earl
Author URI: http://www.blackbaud.com
*/

/**
* Add the iATS gateway to the WooCommerce options.
*/
add_filter('woocommerce_payment_gateways', function($methods) {
	$methods[] = 'Blackbaud_WooCommerce_BBSP_Gateway';
	return $methods;
});

/**
* Add our custom CSS.
*/
add_action('wp_enqueue_scripts', function() {
	wp_register_style('blackbaud-woocommerce-bbsp-css', plugins_url('blackbaud-woocommerce-bbsp-payment-gateway/blackbaud-woocommerce-bbsp-payment-gateway.css'));
	wp_enqueue_style('blackbaud-woocommerce-bbsp-css');
});

/**
* Include our class after verifying WC_Payment_Gateway class exists.
*/
add_action('plugins_loaded', function() {
	if (class_exists('WC_Payment_Gateway')) {
		include 'blackbaud-woocommerce-bbsp-payment-gateway.class.php';
	}
});

?>