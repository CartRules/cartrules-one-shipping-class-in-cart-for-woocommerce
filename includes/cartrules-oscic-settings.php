<?php

defined( 'ABSPATH' ) || exit;

/**
 * Adds this module's "One Shipping Class in Cart" section to the shared "CartRules" tab.
 */

add_filter( 'woocommerce_get_sections_cartrules', 'cartrules_oscic_add_settings_section' );
add_filter( 'woocommerce_get_settings_cartrules', 'cartrules_oscic_settings_fields', 10, 2 );

function cartrules_oscic_add_settings_section( $sections ) {
	$sections['oscic'] = __( 'One Shipping Class in Cart', 'cartrules-one-shipping-class-in-cart-for-woocommerce' );

	return $sections;
}

function cartrules_oscic_settings_fields( $settings, $section_id ) {
	if ( 'oscic' !== $section_id ) {
		return $settings;
	}

	return array(
		array(
			'title' => __( 'One Shipping Class in Cart', 'cartrules-one-shipping-class-in-cart-for-woocommerce' ),
			'type'  => 'title',
			'desc'  => __( 'Prevent customers from mixing products with different shipping classes in the same cart.', 'cartrules-one-shipping-class-in-cart-for-woocommerce' ),
			'id'    => 'cartrules_oscic_settings_title',
		),
		array(
			'title'   => __( 'Enable restriction', 'cartrules-one-shipping-class-in-cart-for-woocommerce' ),
			'desc'    => __( 'Only allow products from one shipping class in the cart at a time', 'cartrules-one-shipping-class-in-cart-for-woocommerce' ),
			'id'      => 'cartrules_oscic_enabled',
			'default' => 'no',
			'type'    => 'checkbox',
		),
		array(
			'title'   => __( 'When a different shipping class is added', 'cartrules-one-shipping-class-in-cart-for-woocommerce' ),
			'desc'    => __( 'Choose what happens when a customer tries to add a product from a different shipping class', 'cartrules-one-shipping-class-in-cart-for-woocommerce' ),
			'id'      => 'cartrules_oscic_mode',
			'default' => 'deny',
			'type'    => 'select',
			'class'   => 'wc-enhanced-select',
			'options' => array(
				'deny'    => __( 'Block the new product and show an error', 'cartrules-one-shipping-class-in-cart-for-woocommerce' ),
				'replace' => __( 'Empty the cart first, then add the new product', 'cartrules-one-shipping-class-in-cart-for-woocommerce' ),
			),
		),
		array(
			'title'    => __( 'Blocked message', 'cartrules-one-shipping-class-in-cart-for-woocommerce' ),
			'desc_tip' => __( 'Shown when a product is blocked. Use {shipping_class} for the shipping class already in the cart.', 'cartrules-one-shipping-class-in-cart-for-woocommerce' ),
			'id'       => 'cartrules_oscic_deny_message',
			'default'  => __( 'You already have products with the "{shipping_class}" shipping class in your cart. Please remove them first, or complete that order separately.', 'cartrules-one-shipping-class-in-cart-for-woocommerce' ),
			'type'     => 'textarea',
			'css'      => 'width:100%; height: 75px;',
		),
		array(
			'title'    => __( 'Replaced message', 'cartrules-one-shipping-class-in-cart-for-woocommerce' ),
			'desc_tip' => __( 'Shown when the cart is emptied and replaced. Use {shipping_class} for the shipping class that was removed.', 'cartrules-one-shipping-class-in-cart-for-woocommerce' ),
			'id'       => 'cartrules_oscic_replace_message',
			'default'  => __( 'Your cart contained products with the "{shipping_class}" shipping class, so we replaced them with your new selection.', 'cartrules-one-shipping-class-in-cart-for-woocommerce' ),
			'type'     => 'textarea',
			'css'      => 'width:100%; height: 75px;',
		),
		array(
			'type' => 'sectionend',
			'id'   => 'cartrules_oscic_settings_end',
		),
	);
}
