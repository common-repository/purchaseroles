<?php
/*
Plugin Name: PurchaseRoles
Author: Tomi Ylä-Soininmäki
Author email: tomi.yla-soininmaki@fimnet.fi
Description: Adds an option to assign a role tu user when a specific product has been bought. Requires Woocommerce.
Version: 0.1
*/

if ( ! defined( 'ABSPATH' ) ) { exit; /*Exit if accessed directly*/}

load_plugin_textdomain('purchaseroles', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	init_purchaseroles();
}

function init_purchaseroles() {
	add_action( 'add_meta_boxes', 'lisaa_purchaseroles_metaboksi' );
	add_action( 'save_post', 'tallenna_purchaseroles_metabox', 1, 2);
	add_action( 'woocommerce_thankyou' , 'purchaseroles_lisaa_rooli' , 9999 );

}

function lisaa_purchaseroles_metaboksi() {
	add_meta_box('purchaseroles', __( 'PurchaseRoles' , 'purchaseroles' ), 'purchaseroles_metabox', 'product', 'side', 'default');
}	

function purchaseroles_metabox() {
	global $post;
	$purchaserole = get_post_meta($post->ID, '_purchaserole', true);
	$purchaserole = ($purchaserole? $purchaserole : NULL);
	echo '<p><b>' . __('Assign a role to user after purchase:' , 'purchaseroles') . '</b></p>';
	echo '<select name="purchaserole">';
	echo '<option value="none">' . __('None' , 'purchaseroles' ) .'</option>';
	wp_dropdown_roles($purchaserole);
	echo '</select>';
}

function tallenna_purchaseroles_metabox($post_id, $post) {
	$arvo = $_POST['purchaserole'];
	$key = '_purchaserole';
	if ($post->post_type == 'revision') return; // Estää jotenkin tuplatallennusta..??
	if (get_post_meta($post->ID, $key, FALSE)) {
		update_post_meta($post->ID, $key, $arvo); // Jos meta löytyy jo niin päivitetään
	} else {
		add_post_meta($post->ID, $key, $arvo); // Jos ei löydy, niin luodaan.
	}
	if(!$arvo || $arvo == 'none') delete_post_meta($post->ID,$key); // Jos lomake tyhjä, niin poistetaan myös meta.
}


function purchaseroles_lisaa_rooli($order_id) {
	$order = new WC_order($order_id);
	
	if ( !$order->has_status( 'failed' ) ) { // Maksun varmistus. Sama koodi varmistamassa woocommercessa
		$tuotteet = $order->get_items();
		$roolit = array();
		
		foreach ($tuotteet as $tuote) {
			$id = $tuote['product_id']; 
			
			$purchaserole = get_post_meta($id, '_purchaserole', true);
			$purchaserole = ($purchaserole? $purchaserole : NULL);
			
			if ($purchaserole) {
				$roolit[] = $purchaserole;
			}
			
		}
		
		if ($roolit && is_user_logged_in()) {
			$user = wp_get_current_user();
			foreach ($roolit as $rooli) {
				$user->add_role($rooli);
			}
		}
		
		
	}
	
}
