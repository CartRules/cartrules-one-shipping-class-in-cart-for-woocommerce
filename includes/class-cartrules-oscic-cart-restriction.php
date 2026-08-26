<?php

defined( 'ABSPATH' ) || exit;

/**
 * Blocks or replaces cart contents when a product from a different shipping class is added.
 */
class CartRules_OSCIC_Cart_Restriction {

	/**
	 * Cart item keys staged for removal in "replace" mode, keyed by the product id that
	 * triggered the replacement. Populated during validation, consumed by handle_replace().
	 *
	 * @var array<int, array{cart_item_keys: string[], shipping_class_name: string}>
	 */
	private $pending_replacements = array();

	public function __construct() {
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_add_to_cart' ), 10, 2 );
		add_action( 'woocommerce_add_to_cart', array( $this, 'handle_replace' ), 10, 2 );
	}

	/**
	 * Other plugins may also hook woocommerce_add_to_cart_validation. Removing cart items here
	 * instead of in handle_replace() would mutate the cart mid-filter-chain, so a plugin
	 * validating later would see an already-emptied cart and skip its own check.
	 *
	 * @param bool $passed
	 * @param int  $product_id
	 * @return bool
	 */
	public function validate_add_to_cart( $passed, $product_id ) {
		if ( ! $passed || 'yes' !== get_option( 'cartrules_oscic_enabled', 'no' ) || WC()->cart->is_empty() ) {
			return $passed;
		}

		$new_classes = $this->get_product_shipping_class_ids( $product_id );

		if ( empty( $new_classes ) ) {
			return $passed;
		}

		$cart_classes = array();

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$cart_classes = array_merge( $cart_classes, $this->get_product_shipping_class_ids( $cart_item['product_id'] ) );
		}

		$cart_classes = array_values( array_unique( $cart_classes ) );

		if ( empty( $cart_classes ) || array_intersect( $new_classes, $cart_classes ) ) {
			return $passed;
		}

		$existing_class_name = $this->get_shipping_class_name( $cart_classes[0] );

		if ( 'replace' === get_option( 'cartrules_oscic_mode', 'deny' ) ) {
			$this->pending_replacements[ $product_id ] = array(
				'cart_item_keys'      => array_keys( WC()->cart->get_cart() ),
				'shipping_class_name' => $existing_class_name,
			);

			return true;
		}

		wc_add_notice( $this->build_deny_message( $existing_class_name ), 'error' );

		return false;
	}

	/**
	 * Runs once WooCommerce has actually added the new item to the cart, so every plugin
	 * hooked into the validation filter has already had a chance to evaluate the original cart.
	 *
	 * @param string $cart_item_key
	 * @param int    $product_id
	 */
	public function handle_replace( $cart_item_key, $product_id ) {
		if ( ! isset( $this->pending_replacements[ $product_id ] ) ) {
			return;
		}

		$replacement = $this->pending_replacements[ $product_id ];
		unset( $this->pending_replacements[ $product_id ] );

		foreach ( $replacement['cart_item_keys'] as $conflicting_item_key ) {
			WC()->cart->remove_cart_item( $conflicting_item_key );
		}

		wc_add_notice( $this->build_replace_message( $replacement['shipping_class_name'] ), 'notice' );
	}

	/**
	 * The message options are only ever written to the database when the settings screen is
	 * saved, so get_option() needs the same fallback the settings screen shows in the
	 * textarea by default -- otherwise enabling this via wp_cli/wp option update, or a site
	 * migration that drops the option row, silently produces a blank notice.
	 */
	private function build_deny_message( $shipping_class_name ) {
		$default = __( 'You already have products with the "{shipping_class}" shipping class in your cart. Please remove them first, or complete that order separately.', 'cartrules-one-shipping-class-in-cart-for-woocommerce' );

		return str_replace( '{shipping_class}', $shipping_class_name, get_option( 'cartrules_oscic_deny_message', $default ) );
	}

	private function build_replace_message( $shipping_class_name ) {
		$default = __( 'Your cart contained products with the "{shipping_class}" shipping class, so we replaced them with your new selection.', 'cartrules-one-shipping-class-in-cart-for-woocommerce' );

		return str_replace( '{shipping_class}', $shipping_class_name, get_option( 'cartrules_oscic_replace_message', $default ) );
	}

	/**
	 * A product has at most one shipping class, unlike categories. Products with no shipping
	 * class assigned (id 0, WooCommerce's own default) are treated the same way OCIC treats
	 * uncategorized products: they carry no group and so never trigger the restriction.
	 */
	private function get_product_shipping_class_ids( $product_id ) {
		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return array();
		}

		$shipping_class_id = $product->get_shipping_class_id();

		return $shipping_class_id ? array( $shipping_class_id ) : array();
	}

	private function get_shipping_class_name( $term_id ) {
		$term = get_term( $term_id, 'product_shipping_class' );

		return $term && ! is_wp_error( $term ) ? $term->name : '';
	}
}
