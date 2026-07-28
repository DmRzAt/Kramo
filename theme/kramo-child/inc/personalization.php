<?php
/**
 * Product personalization from the product form to the order.
 *
 * @package Kramo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the product that owns personalization settings.
 *
 * Variations inherit the settings configured on their parent product.
 *
 * @param int $product_id Product or variation ID.
 * @return WC_Product|false
 */
function kramo_get_personalization_product( $product_id ) {
	$product = wc_get_product( $product_id );

	if ( $product instanceof WC_Product_Variation ) {
		$product = wc_get_product( $product->get_parent_id() );
	}

	return $product;
}

/**
 * Return normalized personalization settings.
 *
 * @param int $product_id Product or variation ID.
 * @return array<string,mixed>
 */
function kramo_get_personalization_settings( $product_id ) {
	$product = kramo_get_personalization_product( $product_id );

	if ( ! $product instanceof WC_Product ) {
		return array();
	}

	$type = (string) kramo_product_meta( $product, '_kramo_personalization_type' );
	if ( ! in_array( $type, array( 'text', 'font', 'thread_color' ), true ) ) {
		$type = 'text';
	}

	return array(
		'enabled'    => 'yes' === kramo_product_meta( $product, '_kramo_personalization_enabled' ),
		'type'       => $type,
		'label'      => (string) ( kramo_product_meta( $product, '_kramo_personalization_label' ) ?: __( 'Imię do haftu', 'kramo' ) ),
		'max_length' => max( 1, absint( kramo_product_meta( $product, '_kramo_personalization_max_length' ) ?: 30 ) ),
		'required'   => 'yes' === kramo_product_meta( $product, '_kramo_personalization_required' ),
		'surcharge'  => max( 0, (float) wc_format_decimal( kramo_product_meta( $product, '_kramo_personalization_surcharge' ) ) ),
	);
}

/**
 * Return allowed choices for a personalization type.
 *
 * @param string $type Personalization type.
 * @return array<string,string>
 */
function kramo_get_personalization_choices( $type ) {
	if ( 'font' === $type ) {
		return array(
			'classic' => __( 'Klasyczny', 'kramo' ),
			'modern'  => __( 'Nowoczesny', 'kramo' ),
			'script'  => __( 'Odręczny', 'kramo' ),
		);
	}

	if ( 'thread_color' === $type ) {
		return array(
			'black' => __( 'Czarny', 'kramo' ),
			'white' => __( 'Biały', 'kramo' ),
			'gold'  => __( 'Złoty', 'kramo' ),
			'red'   => __( 'Czerwony', 'kramo' ),
			'blue'  => __( 'Niebieski', 'kramo' ),
		);
	}

	return array();
}

/**
 * Add the Personalizacja tab to product data.
 *
 * @param array<string,array<string,mixed>> $tabs Existing tabs.
 * @return array<string,array<string,mixed>>
 */
function kramo_add_personalization_product_tab( $tabs ) {
	$tabs['kramo_personalization'] = array(
		'label'    => __( 'Personalizacja', 'kramo' ),
		'target'   => 'kramo_personalization_product_data',
		'class'    => array(),
		'priority' => 75,
	);

	return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'kramo_add_personalization_product_tab' );

/**
 * Render personalization product settings.
 */
function kramo_personalization_product_panel() {
	?>
	<div id="kramo_personalization_product_data" class="panel woocommerce_options_panel hidden">
		<div class="options_group">
			<?php
			woocommerce_wp_checkbox(
				array(
					'id'          => '_kramo_personalization_enabled',
					'label'       => __( 'Włącz personalizację', 'kramo' ),
					'description' => __( 'Pokaż pole personalizacji na stronie produktu.', 'kramo' ),
					'value'       => kramo_post_meta_value( get_the_ID(), '_kramo_personalization_enabled' ),
				)
			);

			woocommerce_wp_select(
				array(
					'id'      => '_kramo_personalization_type',
					'label'   => __( 'Typ pola', 'kramo' ),
					'value'   => kramo_post_meta_value( get_the_ID(), '_kramo_personalization_type' ) ?: 'text',
					'options' => array(
						'text'         => __( 'Tekst', 'kramo' ),
						'font'         => __( 'Tekst + wybór kroju pisma', 'kramo' ),
						'thread_color' => __( 'Tekst + kolor nici', 'kramo' ),
					),
				)
			);

			woocommerce_wp_text_input(
				array(
					'id'          => '_kramo_personalization_label',
					'label'       => __( 'Etykieta pola', 'kramo' ),
					'placeholder' => __( 'Imię do haftu', 'kramo' ),
					'value'       => kramo_post_meta_value( get_the_ID(), '_kramo_personalization_label' ),
				)
			);

			woocommerce_wp_text_input(
				array(
					'id'                => '_kramo_personalization_max_length',
					'label'             => __( 'Maksymalna długość', 'kramo' ),
					'type'              => 'number',
					'value'             => kramo_post_meta_value( get_the_ID(), '_kramo_personalization_max_length' ) ?: 30,
					'custom_attributes' => array(
						'min'  => 1,
						'step' => 1,
					),
				)
			);

			woocommerce_wp_checkbox(
				array(
					'id'          => '_kramo_personalization_required',
					'label'       => __( 'Pole obowiązkowe', 'kramo' ),
					'description' => __( 'Nie pozwalaj dodać produktu bez tekstu.', 'kramo' ),
					'value'       => kramo_post_meta_value( get_the_ID(), '_kramo_personalization_required' ),
				)
			);

			woocommerce_wp_text_input(
				array(
					'id'                => '_kramo_personalization_surcharge',
					'label'             => __( 'Dopłata', 'kramo' ),
					'description'       => __( 'Opcjonalna dopłata za personalizację.', 'kramo' ),
					'desc_tip'          => true,
					'type'              => 'number',
					'value'             => kramo_post_meta_value( get_the_ID(), '_kramo_personalization_surcharge' ),
					'custom_attributes' => array(
						'min'  => 0,
						'step' => '0.01',
					),
				)
			);
			?>
		</div>
	</div>
	<?php
}
add_action( 'woocommerce_product_data_panels', 'kramo_personalization_product_panel' );

/**
 * Save personalization product settings.
 *
 * @param WC_Product $product Product being saved.
 */
function kramo_save_personalization_settings( $product ) {
	$product->update_meta_data(
		'_kramo_personalization_enabled',
		isset( $_POST['_kramo_personalization_enabled'] ) ? 'yes' : 'no'
	);
	$product->update_meta_data(
		'_kramo_personalization_required',
		isset( $_POST['_kramo_personalization_required'] ) ? 'yes' : 'no'
	);

	$type = isset( $_POST['_kramo_personalization_type'] )
		? sanitize_key( wp_unslash( $_POST['_kramo_personalization_type'] ) )
		: 'text';
	$product->update_meta_data(
		'_kramo_personalization_type',
		in_array( $type, array( 'text', 'font', 'thread_color' ), true ) ? $type : 'text'
	);

	$label = isset( $_POST['_kramo_personalization_label'] )
		? sanitize_text_field( wp_unslash( $_POST['_kramo_personalization_label'] ) )
		: '';
	$product->update_meta_data(
		'_kramo_personalization_label',
		$label ?: __( 'Imię do haftu', 'kramo' )
	);

	$max_length = isset( $_POST['_kramo_personalization_max_length'] )
		? absint( wp_unslash( $_POST['_kramo_personalization_max_length'] ) )
		: 30;
	$product->update_meta_data( '_kramo_personalization_max_length', max( 1, $max_length ) );

	$surcharge = isset( $_POST['_kramo_personalization_surcharge'] )
		? max( 0, (float) wc_format_decimal( wp_unslash( $_POST['_kramo_personalization_surcharge'] ) ) )
		: 0;
	$product->update_meta_data( '_kramo_personalization_surcharge', $surcharge );
}
add_action( 'woocommerce_admin_process_product_object', 'kramo_save_personalization_settings' );

/**
 * Render personalization fields before the add-to-cart button.
 */
function kramo_render_personalization_fields() {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$settings = kramo_get_personalization_settings( $product->get_id() );
	if ( empty( $settings['enabled'] ) ) {
		return;
	}

	$choices      = kramo_get_personalization_choices( $settings['type'] );
	$choice_label = 'font' === $settings['type']
		? __( 'Krój pisma', 'kramo' )
		: __( 'Kolor nici', 'kramo' );
	?>
	<fieldset
		class="kramo-personalization"
		data-max-length="<?php echo esc_attr( $settings['max_length'] ); ?>"
	>
		<legend><?php echo esc_html__( 'Personalizacja', 'kramo' ); ?></legend>
		<label for="ws-personalization-text">
			<?php echo esc_html( $settings['label'] ); ?>
			<?php if ( $settings['required'] ) : ?>
				<abbr class="required" title="<?php echo esc_attr__( 'wymagane', 'kramo' ); ?>">*</abbr>
			<?php endif; ?>
		</label>
		<div class="kramo-personalization__input">
			<input
				id="ws-personalization-text"
				name="kramo_personalization_text"
				type="text"
				maxlength="<?php echo esc_attr( $settings['max_length'] ); ?>"
				<?php echo $settings['required'] ? 'required' : ''; ?>
				aria-describedby="ws-personalization-counter"
				autocomplete="off"
			>
			<output
				id="ws-personalization-counter"
				class="kramo-personalization__counter"
				for="ws-personalization-text"
				aria-live="polite"
			>0 / <?php echo esc_html( $settings['max_length'] ); ?></output>
		</div>

		<?php if ( $choices ) : ?>
			<label for="ws-personalization-choice"><?php echo esc_html( $choice_label ); ?></label>
			<select
				id="ws-personalization-choice"
				name="kramo_personalization_choice"
				<?php echo $settings['required'] ? 'required' : ''; ?>
			>
				<option value=""><?php echo esc_html__( 'Wybierz opcję', 'kramo' ); ?></option>
				<?php foreach ( $choices as $choice_value => $choice_name ) : ?>
					<option value="<?php echo esc_attr( $choice_value ); ?>">
						<?php echo esc_html( $choice_name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		<?php endif; ?>

		<?php if ( $settings['surcharge'] > 0 ) : ?>
			<p class="kramo-personalization__price">
				<?php
				printf(
					/* translators: %s: formatted personalization surcharge. */
					esc_html__( 'Dopłata za personalizację: %s', 'kramo' ),
					wp_kses_post( wc_price( $settings['surcharge'] ) )
				);
				?>
			</p>
		<?php endif; ?>
	</fieldset>
	<?php
}
add_action( 'woocommerce_before_add_to_cart_button', 'kramo_render_personalization_fields', 15 );

/**
 * Sanitize and truncate submitted personalization text.
 *
 * @param mixed $value      Submitted value.
 * @param int   $max_length Maximum number of characters.
 * @return string
 */
function kramo_sanitize_personalization_text( $value, $max_length ) {
	$text = sanitize_text_field( wp_unslash( (string) $value ) );

	if ( function_exists( 'mb_substr' ) ) {
		return mb_substr( $text, 0, $max_length );
	}

	return substr( $text, 0, $max_length );
}

/**
 * Return sanitized submitted personalization data.
 *
 * @param int $product_id Product ID.
 * @return array<string,mixed>
 */
function kramo_get_submitted_personalization( $product_id ) {
	$settings = kramo_get_personalization_settings( $product_id );
	if ( empty( $settings['enabled'] ) ) {
		return array();
	}

	$text_raw = kramo_source_value( $_POST, 'kramo_personalization_text' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$text     = null !== $text_raw
		? kramo_sanitize_personalization_text( $text_raw, $settings['max_length'] )
		: '';
	$choices    = kramo_get_personalization_choices( $settings['type'] );
	$choice_raw = kramo_source_value( $_POST, 'kramo_personalization_choice' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$choice     = null !== $choice_raw
		? sanitize_key( wp_unslash( $choice_raw ) )
		: '';

	if ( ! isset( $choices[ $choice ] ) ) {
		$choice = '';
	}

	return array(
		'settings' => $settings,
		'text'     => $text,
		'choice'   => $choice,
		'choices'  => $choices,
	);
}

/**
 * Validate personalization before adding a product to the cart.
 *
 * @param bool $passed       Existing validation result.
 * @param int  $product_id   Product ID.
 * @param int  $quantity     Quantity.
 * @param int  $variation_id Variation ID.
 * @return bool
 */
function kramo_validate_personalization( $passed, $product_id, $quantity, $variation_id = 0 ) {
	unset( $quantity );

	$submitted = kramo_get_submitted_personalization( $variation_id ?: $product_id );
	if ( ! $submitted ) {
		return $passed;
	}

	if ( $submitted['settings']['required'] && '' === $submitted['text'] ) {
		wc_add_notice( __( 'Wpisz tekst personalizacji.', 'kramo' ), 'error' );
		$passed = false;
	}

	if (
		$submitted['choices']
		&& ( $submitted['settings']['required'] || '' !== $submitted['text'] )
		&& '' === $submitted['choice']
	) {
		wc_add_notice( __( 'Wybierz opcję personalizacji.', 'kramo' ), 'error' );
		$passed = false;
	}

	return $passed;
}
add_filter( 'woocommerce_add_to_cart_validation', 'kramo_validate_personalization', 10, 4 );

/**
 * Add personalization to cart item data.
 *
 * The prefixed internal keys also make differently personalized products unique.
 *
 * @param array<string,mixed> $cart_item_data Existing cart item data.
 * @param int                 $product_id      Product ID.
 * @param int                 $variation_id    Variation ID.
 * @return array<string,mixed>
 */
function kramo_add_personalization_to_cart( $cart_item_data, $product_id, $variation_id = 0 ) {
	$submitted = kramo_get_submitted_personalization( $variation_id ?: $product_id );
	if ( ! $submitted || '' === $submitted['text'] ) {
		return $cart_item_data;
	}

	$settings = $submitted['settings'];
	$cart_item_data['_kramo_personalization_text']      = $submitted['text'];
	$cart_item_data['_kramo_personalization_label']     = $settings['label'];
	$cart_item_data['_kramo_personalization_type']      = $settings['type'];
	$cart_item_data['_kramo_personalization_surcharge'] = $settings['surcharge'];

	if ( $submitted['choice'] ) {
		$cart_item_data['_kramo_personalization_choice'] = $submitted['choice'];
	}

	$cart_item_data['_kramo_personalization_hash'] = wp_hash(
		$submitted['text'] . '|' . $settings['type'] . '|' . $submitted['choice']
	);

	return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item_data', 'kramo_add_personalization_to_cart', 10, 3 );

/**
 * Add the optional surcharge to personalized cart items.
 *
 * @param WC_Cart $cart Cart instance.
 */
function kramo_apply_personalization_surcharge( $cart ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}

	foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
		$surcharge_raw = kramo_cart_item_value( $cart_item, '_kramo_personalization_surcharge' );
		$surcharge     = null !== $surcharge_raw ? (float) $surcharge_raw : 0;
		if ( $surcharge <= 0 || empty( $cart_item['data'] ) ) {
			continue;
		}

		$base_raw = kramo_cart_item_value( $cart_item, '_kramo_personalization_base_price' );
		if ( null === $base_raw ) {
			$cart->cart_contents[ $cart_item_key ]['_kramo_personalization_base_price'] =
				(float) $cart_item['data']->get_price();
			$base_raw = $cart->cart_contents[ $cart_item_key ]['_kramo_personalization_base_price'];
		}

		$cart->cart_contents[ $cart_item_key ]['data']->set_price( (float) $base_raw + $surcharge );
	}
}
add_action( 'woocommerce_before_calculate_totals', 'kramo_apply_personalization_surcharge', 20 );

/**
 * Show personalization in cart and checkout.
 *
 * @param array<int,array<string,string>> $item_data Existing display data.
 * @param array<string,mixed>             $cart_item Cart item.
 * @return array<int,array<string,string>>
 */
function kramo_display_personalization_in_cart( $item_data, $cart_item ) {
	$text = kramo_cart_item_value( $cart_item, '_kramo_personalization_text' );
	if ( empty( $text ) ) {
		return $item_data;
	}

	$item_data[] = array(
		'key'   => kramo_cart_item_value( $cart_item, '_kramo_personalization_label' ),
		'value' => $text,
	);

	$choice = kramo_cart_item_value( $cart_item, '_kramo_personalization_choice' );
	if ( ! empty( $choice ) ) {
		$type    = kramo_cart_item_value( $cart_item, '_kramo_personalization_type' );
		$choices = kramo_get_personalization_choices( $type );
		$item_data[] = array(
			'key'   => 'font' === $type
				? __( 'Krój pisma', 'kramo' )
				: __( 'Kolor nici', 'kramo' ),
			'value' => $choices[ $choice ] ?? '',
		);
	}

	$surcharge = kramo_cart_item_value( $cart_item, '_kramo_personalization_surcharge' );
	if ( ! empty( $surcharge ) ) {
		$item_data[] = array(
			'key'     => __( 'Dopłata za personalizację', 'kramo' ),
			'value'   => wc_price( $surcharge ),
			'display' => wc_price( $surcharge ),
		);
	}

	return $item_data;
}
add_filter( 'woocommerce_get_item_data', 'kramo_display_personalization_in_cart', 10, 2 );

/**
 * Save visible personalization meta on an order item.
 *
 * Visible order item meta is automatically included by WooCommerce in order
 * details, customer/admin emails and exporters that include line-item metadata.
 *
 * @param WC_Order_Item_Product $item          Order item.
 * @param string                $cart_item_key Cart item key.
 * @param array<string,mixed>   $values        Cart item values.
 * @param WC_Order              $order         Order object.
 */
function kramo_add_personalization_to_order_item( $item, $cart_item_key, $values, $order ) {
	unset( $cart_item_key, $order );

	$text = kramo_cart_item_value( $values, '_kramo_personalization_text' );
	if ( empty( $text ) ) {
		return;
	}

	$item->add_meta_data(
		(string) kramo_cart_item_value( $values, '_kramo_personalization_label' ),
		(string) $text,
		true
	);

	$choice = kramo_cart_item_value( $values, '_kramo_personalization_choice' );
	if ( ! empty( $choice ) ) {
		$type    = (string) kramo_cart_item_value( $values, '_kramo_personalization_type' );
		$choices = kramo_get_personalization_choices( $type );
		$item->add_meta_data(
			'font' === $type ? __( 'Krój pisma', 'kramo' ) : __( 'Kolor nici', 'kramo' ),
			$choices[ $choice ] ?? '',
			true
		);
	}

	$surcharge = kramo_cart_item_value( $values, '_kramo_personalization_surcharge' );
	if ( ! empty( $surcharge ) ) {
		$item->add_meta_data(
			__( 'Dopłata za personalizację', 'kramo' ),
			wp_strip_all_tags( wc_price( $surcharge ) ),
			true
		);
	}
}
add_action(
	'woocommerce_checkout_create_order_line_item',
	'kramo_add_personalization_to_order_item',
	10,
	4
);
