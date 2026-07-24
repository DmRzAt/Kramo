<?php
/**
 * Product personalization from the product form to the order.
 *
 * @package WooStarter
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
function woostarter_get_personalization_product( $product_id ) {
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
function woostarter_get_personalization_settings( $product_id ) {
	$product = woostarter_get_personalization_product( $product_id );

	if ( ! $product instanceof WC_Product ) {
		return array();
	}

	$type = (string) $product->get_meta( '_ws_personalization_type' );
	if ( ! in_array( $type, array( 'text', 'font', 'thread_color' ), true ) ) {
		$type = 'text';
	}

	return array(
		'enabled'    => 'yes' === $product->get_meta( '_ws_personalization_enabled' ),
		'type'       => $type,
		'label'      => (string) ( $product->get_meta( '_ws_personalization_label' ) ?: __( 'Imię do haftu', 'woostarter' ) ),
		'max_length' => max( 1, absint( $product->get_meta( '_ws_personalization_max_length' ) ?: 30 ) ),
		'required'   => 'yes' === $product->get_meta( '_ws_personalization_required' ),
		'surcharge'  => max( 0, (float) wc_format_decimal( $product->get_meta( '_ws_personalization_surcharge' ) ) ),
	);
}

/**
 * Return allowed choices for a personalization type.
 *
 * @param string $type Personalization type.
 * @return array<string,string>
 */
function woostarter_get_personalization_choices( $type ) {
	if ( 'font' === $type ) {
		return array(
			'classic' => __( 'Klasyczny', 'woostarter' ),
			'modern'  => __( 'Nowoczesny', 'woostarter' ),
			'script'  => __( 'Odręczny', 'woostarter' ),
		);
	}

	if ( 'thread_color' === $type ) {
		return array(
			'black' => __( 'Czarny', 'woostarter' ),
			'white' => __( 'Biały', 'woostarter' ),
			'gold'  => __( 'Złoty', 'woostarter' ),
			'red'   => __( 'Czerwony', 'woostarter' ),
			'blue'  => __( 'Niebieski', 'woostarter' ),
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
function woostarter_add_personalization_product_tab( $tabs ) {
	$tabs['woostarter_personalization'] = array(
		'label'    => __( 'Personalizacja', 'woostarter' ),
		'target'   => 'woostarter_personalization_product_data',
		'class'    => array(),
		'priority' => 75,
	);

	return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'woostarter_add_personalization_product_tab' );

/**
 * Render personalization product settings.
 */
function woostarter_personalization_product_panel() {
	?>
	<div id="woostarter_personalization_product_data" class="panel woocommerce_options_panel hidden">
		<div class="options_group">
			<?php
			woocommerce_wp_checkbox(
				array(
					'id'          => '_ws_personalization_enabled',
					'label'       => __( 'Włącz personalizację', 'woostarter' ),
					'description' => __( 'Pokaż pole personalizacji na stronie produktu.', 'woostarter' ),
				)
			);

			woocommerce_wp_select(
				array(
					'id'      => '_ws_personalization_type',
					'label'   => __( 'Typ pola', 'woostarter' ),
					'options' => array(
						'text'         => __( 'Tekst', 'woostarter' ),
						'font'         => __( 'Tekst + wybór kroju pisma', 'woostarter' ),
						'thread_color' => __( 'Tekst + kolor nici', 'woostarter' ),
					),
				)
			);

			woocommerce_wp_text_input(
				array(
					'id'          => '_ws_personalization_label',
					'label'       => __( 'Etykieta pola', 'woostarter' ),
					'placeholder' => __( 'Imię do haftu', 'woostarter' ),
				)
			);

			woocommerce_wp_text_input(
				array(
					'id'                => '_ws_personalization_max_length',
					'label'             => __( 'Maksymalna długość', 'woostarter' ),
					'type'              => 'number',
					'value'             => get_post_meta( get_the_ID(), '_ws_personalization_max_length', true ) ?: 30,
					'custom_attributes' => array(
						'min'  => 1,
						'step' => 1,
					),
				)
			);

			woocommerce_wp_checkbox(
				array(
					'id'          => '_ws_personalization_required',
					'label'       => __( 'Pole obowiązkowe', 'woostarter' ),
					'description' => __( 'Nie pozwalaj dodać produktu bez tekstu.', 'woostarter' ),
				)
			);

			woocommerce_wp_text_input(
				array(
					'id'                => '_ws_personalization_surcharge',
					'label'             => __( 'Dopłata', 'woostarter' ),
					'description'       => __( 'Opcjonalna dopłata za personalizację.', 'woostarter' ),
					'desc_tip'          => true,
					'type'              => 'number',
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
add_action( 'woocommerce_product_data_panels', 'woostarter_personalization_product_panel' );

/**
 * Save personalization product settings.
 *
 * @param WC_Product $product Product being saved.
 */
function woostarter_save_personalization_settings( $product ) {
	$product->update_meta_data(
		'_ws_personalization_enabled',
		isset( $_POST['_ws_personalization_enabled'] ) ? 'yes' : 'no'
	);
	$product->update_meta_data(
		'_ws_personalization_required',
		isset( $_POST['_ws_personalization_required'] ) ? 'yes' : 'no'
	);

	$type = isset( $_POST['_ws_personalization_type'] )
		? sanitize_key( wp_unslash( $_POST['_ws_personalization_type'] ) )
		: 'text';
	$product->update_meta_data(
		'_ws_personalization_type',
		in_array( $type, array( 'text', 'font', 'thread_color' ), true ) ? $type : 'text'
	);

	$label = isset( $_POST['_ws_personalization_label'] )
		? sanitize_text_field( wp_unslash( $_POST['_ws_personalization_label'] ) )
		: '';
	$product->update_meta_data(
		'_ws_personalization_label',
		$label ?: __( 'Imię do haftu', 'woostarter' )
	);

	$max_length = isset( $_POST['_ws_personalization_max_length'] )
		? absint( wp_unslash( $_POST['_ws_personalization_max_length'] ) )
		: 30;
	$product->update_meta_data( '_ws_personalization_max_length', max( 1, $max_length ) );

	$surcharge = isset( $_POST['_ws_personalization_surcharge'] )
		? max( 0, (float) wc_format_decimal( wp_unslash( $_POST['_ws_personalization_surcharge'] ) ) )
		: 0;
	$product->update_meta_data( '_ws_personalization_surcharge', $surcharge );
}
add_action( 'woocommerce_admin_process_product_object', 'woostarter_save_personalization_settings' );

/**
 * Render personalization fields before the add-to-cart button.
 */
function woostarter_render_personalization_fields() {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$settings = woostarter_get_personalization_settings( $product->get_id() );
	if ( empty( $settings['enabled'] ) ) {
		return;
	}

	$choices      = woostarter_get_personalization_choices( $settings['type'] );
	$choice_label = 'font' === $settings['type']
		? __( 'Krój pisma', 'woostarter' )
		: __( 'Kolor nici', 'woostarter' );
	?>
	<fieldset
		class="woostarter-personalization"
		data-max-length="<?php echo esc_attr( $settings['max_length'] ); ?>"
	>
		<legend><?php echo esc_html__( 'Personalizacja', 'woostarter' ); ?></legend>
		<label for="ws-personalization-text">
			<?php echo esc_html( $settings['label'] ); ?>
			<?php if ( $settings['required'] ) : ?>
				<abbr class="required" title="<?php echo esc_attr__( 'wymagane', 'woostarter' ); ?>">*</abbr>
			<?php endif; ?>
		</label>
		<div class="woostarter-personalization__input">
			<input
				id="ws-personalization-text"
				name="ws_personalization_text"
				type="text"
				maxlength="<?php echo esc_attr( $settings['max_length'] ); ?>"
				<?php echo $settings['required'] ? 'required' : ''; ?>
				aria-describedby="ws-personalization-counter"
				autocomplete="off"
			>
			<output
				id="ws-personalization-counter"
				class="woostarter-personalization__counter"
				for="ws-personalization-text"
				aria-live="polite"
			>0 / <?php echo esc_html( $settings['max_length'] ); ?></output>
		</div>

		<?php if ( $choices ) : ?>
			<label for="ws-personalization-choice"><?php echo esc_html( $choice_label ); ?></label>
			<select
				id="ws-personalization-choice"
				name="ws_personalization_choice"
				<?php echo $settings['required'] ? 'required' : ''; ?>
			>
				<option value=""><?php echo esc_html__( 'Wybierz opcję', 'woostarter' ); ?></option>
				<?php foreach ( $choices as $choice_value => $choice_name ) : ?>
					<option value="<?php echo esc_attr( $choice_value ); ?>">
						<?php echo esc_html( $choice_name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		<?php endif; ?>

		<?php if ( $settings['surcharge'] > 0 ) : ?>
			<p class="woostarter-personalization__price">
				<?php
				printf(
					/* translators: %s: formatted personalization surcharge. */
					esc_html__( 'Dopłata za personalizację: %s', 'woostarter' ),
					wp_kses_post( wc_price( $settings['surcharge'] ) )
				);
				?>
			</p>
		<?php endif; ?>
	</fieldset>
	<?php
}
add_action( 'woocommerce_before_add_to_cart_button', 'woostarter_render_personalization_fields', 15 );

/**
 * Sanitize and truncate submitted personalization text.
 *
 * @param mixed $value      Submitted value.
 * @param int   $max_length Maximum number of characters.
 * @return string
 */
function woostarter_sanitize_personalization_text( $value, $max_length ) {
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
function woostarter_get_submitted_personalization( $product_id ) {
	$settings = woostarter_get_personalization_settings( $product_id );
	if ( empty( $settings['enabled'] ) ) {
		return array();
	}

	$text    = isset( $_POST['ws_personalization_text'] )
		? woostarter_sanitize_personalization_text(
			$_POST['ws_personalization_text'],
			$settings['max_length']
		)
		: '';
	$choices = woostarter_get_personalization_choices( $settings['type'] );
	$choice  = isset( $_POST['ws_personalization_choice'] )
		? sanitize_key( wp_unslash( $_POST['ws_personalization_choice'] ) )
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
function woostarter_validate_personalization( $passed, $product_id, $quantity, $variation_id = 0 ) {
	unset( $quantity );

	$submitted = woostarter_get_submitted_personalization( $variation_id ?: $product_id );
	if ( ! $submitted ) {
		return $passed;
	}

	if ( $submitted['settings']['required'] && '' === $submitted['text'] ) {
		wc_add_notice( __( 'Wpisz tekst personalizacji.', 'woostarter' ), 'error' );
		$passed = false;
	}

	if (
		$submitted['choices']
		&& ( $submitted['settings']['required'] || '' !== $submitted['text'] )
		&& '' === $submitted['choice']
	) {
		wc_add_notice( __( 'Wybierz opcję personalizacji.', 'woostarter' ), 'error' );
		$passed = false;
	}

	return $passed;
}
add_filter( 'woocommerce_add_to_cart_validation', 'woostarter_validate_personalization', 10, 4 );

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
function woostarter_add_personalization_to_cart( $cart_item_data, $product_id, $variation_id = 0 ) {
	$submitted = woostarter_get_submitted_personalization( $variation_id ?: $product_id );
	if ( ! $submitted || '' === $submitted['text'] ) {
		return $cart_item_data;
	}

	$settings = $submitted['settings'];
	$cart_item_data['_ws_personalization_text']      = $submitted['text'];
	$cart_item_data['_ws_personalization_label']     = $settings['label'];
	$cart_item_data['_ws_personalization_type']      = $settings['type'];
	$cart_item_data['_ws_personalization_surcharge'] = $settings['surcharge'];

	if ( $submitted['choice'] ) {
		$cart_item_data['_ws_personalization_choice'] = $submitted['choice'];
	}

	$cart_item_data['_ws_personalization_hash'] = wp_hash(
		$submitted['text'] . '|' . $settings['type'] . '|' . $submitted['choice']
	);

	return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item_data', 'woostarter_add_personalization_to_cart', 10, 3 );

/**
 * Add the optional surcharge to personalized cart items.
 *
 * @param WC_Cart $cart Cart instance.
 */
function woostarter_apply_personalization_surcharge( $cart ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}

	foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
		$surcharge = isset( $cart_item['_ws_personalization_surcharge'] )
			? (float) $cart_item['_ws_personalization_surcharge']
			: 0;
		if ( $surcharge <= 0 || empty( $cart_item['data'] ) ) {
			continue;
		}

		if ( ! isset( $cart->cart_contents[ $cart_item_key ]['_ws_personalization_base_price'] ) ) {
			$cart->cart_contents[ $cart_item_key ]['_ws_personalization_base_price'] =
				(float) $cart_item['data']->get_price();
		}

		$base_price = (float) $cart->cart_contents[ $cart_item_key ]['_ws_personalization_base_price'];
		$cart->cart_contents[ $cart_item_key ]['data']->set_price( $base_price + $surcharge );
	}
}
add_action( 'woocommerce_before_calculate_totals', 'woostarter_apply_personalization_surcharge', 20 );

/**
 * Show personalization in cart and checkout.
 *
 * @param array<int,array<string,string>> $item_data Existing display data.
 * @param array<string,mixed>             $cart_item Cart item.
 * @return array<int,array<string,string>>
 */
function woostarter_display_personalization_in_cart( $item_data, $cart_item ) {
	if ( empty( $cart_item['_ws_personalization_text'] ) ) {
		return $item_data;
	}

	$item_data[] = array(
		'key'   => $cart_item['_ws_personalization_label'],
		'value' => $cart_item['_ws_personalization_text'],
	);

	if ( ! empty( $cart_item['_ws_personalization_choice'] ) ) {
		$type    = $cart_item['_ws_personalization_type'];
		$choices = woostarter_get_personalization_choices( $type );
		$item_data[] = array(
			'key'   => 'font' === $type
				? __( 'Krój pisma', 'woostarter' )
				: __( 'Kolor nici', 'woostarter' ),
			'value' => $choices[ $cart_item['_ws_personalization_choice'] ] ?? '',
		);
	}

	if ( ! empty( $cart_item['_ws_personalization_surcharge'] ) ) {
		$item_data[] = array(
			'key'     => __( 'Dopłata za personalizację', 'woostarter' ),
			'value'   => wc_price( $cart_item['_ws_personalization_surcharge'] ),
			'display' => wc_price( $cart_item['_ws_personalization_surcharge'] ),
		);
	}

	return $item_data;
}
add_filter( 'woocommerce_get_item_data', 'woostarter_display_personalization_in_cart', 10, 2 );

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
function woostarter_add_personalization_to_order_item( $item, $cart_item_key, $values, $order ) {
	unset( $cart_item_key, $order );

	if ( empty( $values['_ws_personalization_text'] ) ) {
		return;
	}

	$item->add_meta_data(
		(string) $values['_ws_personalization_label'],
		(string) $values['_ws_personalization_text'],
		true
	);

	if ( ! empty( $values['_ws_personalization_choice'] ) ) {
		$type    = (string) $values['_ws_personalization_type'];
		$choices = woostarter_get_personalization_choices( $type );
		$item->add_meta_data(
			'font' === $type ? __( 'Krój pisma', 'woostarter' ) : __( 'Kolor nici', 'woostarter' ),
			$choices[ $values['_ws_personalization_choice'] ] ?? '',
			true
		);
	}

	if ( ! empty( $values['_ws_personalization_surcharge'] ) ) {
		$item->add_meta_data(
			__( 'Dopłata za personalizację', 'woostarter' ),
			wp_strip_all_tags( wc_price( $values['_ws_personalization_surcharge'] ) ),
			true
		);
	}
}
add_action(
	'woocommerce_checkout_create_order_line_item',
	'woostarter_add_personalization_to_order_item',
	10,
	4
);
