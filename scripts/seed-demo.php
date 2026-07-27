<?php

if (!defined('ABSPATH') || !class_exists('WooCommerce')) {
    WP_CLI::error('WooCommerce is not loaded.');
}

/**
 * Build a valid solid-color RGB PNG without an image-library dependency.
 */
function kramo_demo_png(int $width, int $height, string $hex): string
{
    $hex = ltrim($hex, '#');
    $rgb = hex2bin($hex);
    $scanline = "\x00" . str_repeat($rgb, $width);
    $raw = str_repeat($scanline, $height);

    $chunk = static function (string $type, string $data): string {
        return pack('N', strlen($data))
            . $type
            . $data
            . hash('crc32b', $type . $data, true);
    };

    return "\x89PNG\r\n\x1a\n"
        . $chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0))
        . $chunk('IDAT', gzcompress($raw, 9))
        . $chunk('IEND', '');
}

function kramo_demo_asset(int $index): ?string
{
    if ($index > 100) {
        $product_number = $index - 100;
        $candidates = [
            sprintf('product-%02d-alt.jpg', $product_number),
            sprintf('product-%02d.jpg', $product_number),
        ];
    } else {
        $candidates = [sprintf('product-%02d.jpg', $index)];
    }

    foreach ($candidates as $candidate) {
        $file = sprintf('%s/demo-assets/%s', __DIR__, $candidate);
        if (is_readable($file)) {
            return $file;
        }
    }

    return null;
}

/**
 * Create or return a deterministic 1200x1500 demo attachment.
 */
function kramo_demo_image(int $index, string $product_name, string $hex): int
{
    $key = sprintf('photo-%02d', $index);
    $existing = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_key' => '_kramo_demo_image_key',
        'meta_value' => $key,
    ]);

    if ($existing) {
        return (int) $existing[0];
    }

    $asset = kramo_demo_asset($index);
    if (null !== $asset) {
        $mime = 'image/jpeg';
        $filename = sprintf('kramo-%02d.jpg', $index);
        $upload = wp_upload_bits($filename, null, file_get_contents($asset));
    } else {
        $mime = 'image/png';
        $filename = sprintf('kramo-%02d.png', $index);
        $upload = wp_upload_bits($filename, null, kramo_demo_png(1200, 1500, $hex));
    }

    if (!empty($upload['error'])) {
        WP_CLI::error($upload['error']);
    }

    $attachment_id = wp_insert_attachment([
        'post_mime_type' => $mime,
        'post_title' => $product_name,
        'post_status' => 'inherit',
    ], $upload['file']);

    if (is_wp_error($attachment_id)) {
        WP_CLI::error($attachment_id->get_error_message());
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    $metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
    if (!is_wp_error($metadata) && $metadata) {
        wp_update_attachment_metadata($attachment_id, $metadata);
    }

    update_post_meta($attachment_id, '_wp_attachment_image_alt', $product_name);
    update_post_meta($attachment_id, '_kramo_demo_image_key', $key);

    return (int) $attachment_id;
}

/**
 * Return an existing term ID or create the term.
 */
function kramo_demo_category(string $name, string $slug): int
{
    $term = term_exists($slug, 'product_cat');
    if ($term) {
        return (int) (is_array($term) ? $term['term_id'] : $term);
    }

    $created = wp_insert_term($name, 'product_cat', ['slug' => $slug]);
    if (is_wp_error($created)) {
        WP_CLI::error($created->get_error_message());
    }

    return (int) $created['term_id'];
}

/**
 * Create a global product attribute and its terms for filterable demo data.
 *
 * @return array{id:int,taxonomy:string,terms:array<string,array{id:int,slug:string}>}
 */
function kramo_demo_attribute(string $label, string $slug, array $options): array
{
    $attribute_id = wc_attribute_taxonomy_id_by_name($slug);

    if (!$attribute_id) {
        $attribute_id = wc_create_attribute([
            'name' => $label,
            'slug' => $slug,
            'type' => 'select',
            'order_by' => 'menu_order',
            'has_archives' => false,
        ]);

        if (is_wp_error($attribute_id)) {
            WP_CLI::error($attribute_id->get_error_message());
        }

        delete_transient('wc_attribute_taxonomies');
        wp_cache_delete('woocommerce-attributes', 'woocommerce');
    }

    $taxonomy = wc_attribute_taxonomy_name($slug);
    if (!taxonomy_exists($taxonomy)) {
        register_taxonomy($taxonomy, ['product'], [
            'hierarchical' => false,
            'show_ui' => false,
            'query_var' => true,
            'rewrite' => false,
        ]);
    }

    $terms = [];
    foreach ($options as $option) {
        $term = term_exists($option, $taxonomy);
        if (!$term) {
            $term = wp_insert_term($option, $taxonomy);
        }

        if (is_wp_error($term)) {
            WP_CLI::error($term->get_error_message());
        }

        $term_id = (int) (is_array($term) ? $term['term_id'] : $term);
        $term_object = get_term($term_id, $taxonomy);
        $terms[$option] = [
            'id' => $term_id,
            'slug' => $term_object instanceof WP_Term ? $term_object->slug : sanitize_title($option),
        ];
    }

    return [
        'id' => (int) $attribute_id,
        'taxonomy' => $taxonomy,
        'terms' => $terms,
    ];
}

function kramo_demo_category_image(int $term_id, string $slug): void
{
    if (get_term_meta($term_id, 'thumbnail_id', true)) {
        return;
    }

    $file = sprintf('%s/demo-assets/category-%s.jpg', __DIR__, $slug);
    if (!is_readable($file)) {
        return;
    }

    $upload = wp_upload_bits(sprintf('kramo-cat-%s.jpg', $slug), null, file_get_contents($file));
    if (!empty($upload['error'])) {
        return;
    }

    $attachment_id = wp_insert_attachment([
        'post_mime_type' => 'image/jpeg',
        'post_title' => sprintf('Kategoria %s', $slug),
        'post_status' => 'inherit',
    ], $upload['file']);

    if (is_wp_error($attachment_id)) {
        return;
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    $metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
    if (!is_wp_error($metadata) && $metadata) {
        wp_update_attachment_metadata($attachment_id, $metadata);
    }

    update_term_meta($term_id, 'thumbnail_id', $attachment_id);
}

$category_definitions = [
    ['Odzież', 'odziez'],
    ['Akcesoria', 'akcesoria'],
    ['Dom', 'dom'],
];

$categories = [];
foreach ($category_definitions as [$category_name, $category_slug]) {
    $category_id = kramo_demo_category($category_name, $category_slug);
    kramo_demo_category_image($category_id, $category_slug);
    $categories[] = $category_id;
}

// Each row: name, base price, primary hex, gallery hex, weight in kg.
// Weights are required for the weight-based shipping rules in inc/shipping.php.
$products = [
    ['Lniana koszula', 159, '#D8C3A5', '#4B5563', 0.30],
    ['Klasyczny T-shirt', 89, '#A8B5A2', '#374151', 0.20],
    ['Bluza z kapturem', 219, '#8D99AE', '#475569', 0.65],
    ['Sweter merino', 279, '#C9ADA7', '#5B4B58', 0.50],
    ['Spodnie chino', 199, '#B7A99A', '#3F4A4A', 0.55],
    ['Lekka kurtka', 349, '#6B7D7D', '#263238', 0.80],
    ['Torba miejska', 189, '#A98467', '#3D342F', 0.60],
    ['Czapka bawełniana', 69, '#CB997E', '#4B3D38', 0.15],
    ['Szalik z wełny', 119, '#9A8C98', '#403845', 0.25],
    ['Poszewka dekoracyjna', 79, '#DDBEA9', '#65534A', 0.20],
    ['Koc bawełniany', 239, '#B7B7A4', '#46463F', 1.20],
    ['Fartuch kuchenny', 109, '#A5A58D', '#393A32', 0.30],
];

$colors = ['Beżowy', 'Czarny', 'Niebieski'];
$sizes = ['S', 'M', 'L'];
$color_definition = kramo_demo_attribute('Kolor', 'kolor', $colors);
$size_definition = kramo_demo_attribute('Rozmiar', 'rozmiar', $sizes);
$product_ids = [];

foreach ($products as $index => [$name, $base_price, $hex, $gallery_hex, $weight]) {
    $number = $index + 1;
    $product_colors = 0 === $index % 2 ? ['Beżowy', 'Czarny'] : ['Niebieski', 'Czarny'];
    $product_sizes = 0 === $index % 2 ? ['S', 'M'] : ['M', 'L'];
    $sku = sprintf('DEMO-%02d', $number);
    $product_id = wc_get_product_id_by_sku($sku);
    $product = $product_id ? wc_get_product($product_id) : new WC_Product_Variable();

    if (!$product instanceof WC_Product_Variable) {
        WP_CLI::warning(sprintf('Skipping %s because SKU %s belongs to another product type.', $name, $sku));
        continue;
    }

    $color_attribute = new WC_Product_Attribute();
    $color_attribute->set_id($color_definition['id']);
    $color_attribute->set_name($color_definition['taxonomy']);
    $color_attribute->set_options(array_map(
        static fn (string $color): int => $color_definition['terms'][$color]['id'],
        $product_colors
    ));
    $color_attribute->set_position(0);
    $color_attribute->set_visible(true);
    $color_attribute->set_variation(true);

    $size_attribute = new WC_Product_Attribute();
    $size_attribute->set_id($size_definition['id']);
    $size_attribute->set_name($size_definition['taxonomy']);
    $size_attribute->set_options(array_map(
        static fn (string $size): int => $size_definition['terms'][$size]['id'],
        $product_sizes
    ));
    $size_attribute->set_position(1);
    $size_attribute->set_visible(true);
    $size_attribute->set_variation(true);

    $product->set_name($name);
    $product->set_slug(sanitize_title($name));
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $product->set_sku($sku);
    $product->set_description('Starannie wykonany produkt do codziennego użytku. Naturalne materiały i wygodny krój.');
    $product->set_short_description('Polska jakość, prosty krój i wygoda na co dzień.');
    $product->set_category_ids([$categories[$index % count($categories)]]);
    $product->set_weight((string) $weight);
    $product->set_attributes([$color_attribute, $size_attribute]);
    $primary_image_id = kramo_demo_image($number, $name, $hex);
    $gallery_image_id = kramo_demo_image($number + 100, $name . ' — wariant', $gallery_hex);
    $product->set_image_id($primary_image_id);
    $product->set_gallery_image_ids([$gallery_image_id]);

    if (0 === $index) {
        $product->update_meta_data('_ws_personalization_enabled', 'yes');
        $product->update_meta_data('_ws_personalization_type', 'font');
        $product->update_meta_data('_ws_personalization_label', 'Imię do haftu');
        $product->update_meta_data('_ws_personalization_max_length', 20);
        $product->update_meta_data('_ws_personalization_required', 'yes');
        $product->update_meta_data('_ws_personalization_surcharge', 20);
    }

    if (in_array($index, [0, 1, 2, 3, 4, 5], true)) {
        $product->update_meta_data(
            '_ws_size_guide',
            "Rozmiar :: Obwód klatki (cm) :: Długość (cm)\n"
                . "S :: 92-96 :: 68\n"
                . "M :: 98-102 :: 70\n"
                . 'L :: 104-108 :: 72'
        );
    }

    if (7 === $index) {
        // Demonstrates the FAQ field feeding FAQPage schema on the product page.
        $product->update_meta_data(
            '_ws_faq',
            "Czy czapka jest ciepła? :: Tak, gruba bawełna z domieszką elastanu.\n"
                . 'Jak prać? :: Ręcznie lub w pralce w 30 stopniach.'
        );
    }

    $product_id = $product->save();
    $product_ids[] = $product_id;

    $desired_variation_ids = [];
    foreach ($product_colors as $color_index => $color) {
        foreach ($product_sizes as $size_index => $size) {
            $variation_sku = sprintf(
                '%s-%s-%s',
                $sku,
                strtoupper(sanitize_title($color)),
                strtoupper($size)
            );
            $variation_id = wc_get_product_id_by_sku($variation_sku);
            $variation = $variation_id
                ? new WC_Product_Variation($variation_id)
                : new WC_Product_Variation();

            $variation->set_parent_id($product_id);
            $variation->set_sku($variation_sku);
            $variation->set_attributes([
                $color_definition['taxonomy'] => $color_definition['terms'][$color]['slug'],
                $size_definition['taxonomy'] => $size_definition['terms'][$size]['slug'],
            ]);
            $regular_price = $base_price + ($size_index * 10);
            $variation->set_regular_price((string) $regular_price);
            $variation->set_sale_price(0 === $index % 4 ? (string) ($regular_price - 20) : '');
            $variation->set_image_id(0 === $color_index ? $primary_image_id : $gallery_image_id);
            $variation->set_weight((string) $weight);
            $is_unavailable = 0 === $index && 1 === $color_index && 1 === $size_index;
            $variation->set_manage_stock(true);
            $variation->set_stock_quantity($is_unavailable ? 0 : 8 + $color_index + $size_index);
            $variation->set_stock_status($is_unavailable ? 'outofstock' : 'instock');
            $variation->set_status('publish');
            $desired_variation_ids[] = $variation->save();
        }
    }

    foreach ($product->get_children() as $child_id) {
        if (!in_array($child_id, $desired_variation_ids, true)) {
            $obsolete = wc_get_product($child_id);
            if ($obsolete instanceof WC_Product_Variation) {
                $obsolete->delete(true);
            }
        }
    }

    WC_Product_Variable::sync($product_id);
    wc_delete_product_transients($product_id);
}

$legacy_images = get_posts([
    'post_type' => 'attachment',
    'post_status' => 'inherit',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'meta_key' => '_kramo_demo_image_key',
    'meta_value' => 'product-',
    'meta_compare' => 'LIKE',
]);

foreach ($legacy_images as $legacy_id) {
    wp_delete_attachment((int) $legacy_id, true);
}

$reviews = [
    [0, 'Anna Kowalska', 'Świetna jakość i bardzo szybka wysyłka.', 5],
    [2, 'Marek Nowak', 'Produkt zgodny z opisem, rozmiar pasuje idealnie.', 5],
    [5, 'Katarzyna Wiśniewska', 'Dobre wykonanie i staranne pakowanie.', 4],
    [8, 'Piotr Zieliński', 'Polecam, na pewno wrócę po kolejne zakupy.', 5],
];

foreach ($reviews as $review_index => [$product_index, $author, $content, $rating]) {
    if (empty($product_ids[$product_index])) {
        continue;
    }

    $review_key = 'review-' . ($review_index + 1);
    $existing = get_comments([
        'post_id' => $product_ids[$product_index],
        'count' => true,
        'meta_key' => '_kramo_demo_review',
        'meta_value' => $review_key,
    ]);

    if ($existing) {
        continue;
    }

    $comment_id = wp_insert_comment([
        'comment_post_ID' => $product_ids[$product_index],
        'comment_author' => $author,
        'comment_author_email' => sprintf('demo%d@example.test', $review_index + 1),
        'comment_content' => $content,
        'comment_type' => 'review',
        'comment_approved' => 1,
        'comment_meta' => [
            'rating' => $rating,
            'verified' => 1,
            '_kramo_demo_review' => $review_key,
        ],
    ]);

    if (!$comment_id) {
        WP_CLI::warning(sprintf('Could not create review %s.', $review_key));
    }
}

$coupon_id = wc_get_coupon_id_by_code('START10');
$coupon = $coupon_id ? new WC_Coupon($coupon_id) : new WC_Coupon();
$coupon->set_code('START10');
$coupon->set_description('10% rabatu na pierwsze zamówienie');
$coupon->set_discount_type('percent');
$coupon->set_amount(10);
$coupon->set_individual_use(false);
$coupon->set_usage_limit(100);
$coupon->save();

// Example local-service landing page (LocalBusiness + FAQPage schema).
// Duplicate it and change service + city fields to target another city.
$local_slug = 'mycie-kostki-brukowej-katowice';
$local_existing = get_page_by_path($local_slug);
$local_id = $local_existing
    ? $local_existing->ID
    : wp_insert_post([
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_name' => $local_slug,
        'post_title' => 'Mycie kostki brukowej Katowice',
        'post_content' => 'Profesjonalne mycie i impregnacja kostki brukowej. Dojazd na terenie całej aglomeracji, wycena tego samego dnia.',
    ]);

if (!is_wp_error($local_id) && $local_id) {
    update_post_meta($local_id, '_wp_page_template', 'page-local-service.php');
    update_post_meta($local_id, '_ws_ls_service', 'Mycie kostki brukowej');
    update_post_meta($local_id, '_ws_ls_city', 'Katowice');
    update_post_meta($local_id, '_ws_ls_area', 'Katowice, Sosnowiec, Gliwice, Chorzów');
    update_post_meta($local_id, '_ws_ls_price', '20–40 zł/m²');
    update_post_meta($local_id, '_ws_ls_phone', '+48 500 600 700');
    update_post_meta($local_id, '_ws_ls_cta_text', 'Zamów wycenę');
    update_post_meta($local_id, '_ws_ls_cta_url', 'tel:+48500600700');
    update_post_meta(
        $local_id,
        '_ws_ls_faq',
        "Ile trwa usługa? :: Zwykle jeden dzień dla typowego podjazdu.\n"
            . 'Czy impregnacja jest w cenie? :: Tak, w pakiecie podstawowym.'
    );
}


// Storefront home page: hero, featured products and categories, all in native
// blocks so the client can edit them.
$home_slug = 'strona-glowna';
$home_content = <<<'HTML'
<!-- wp:group {"tagName":"section","className":"kramo-hero","layout":{"type":"constrained"}} -->
<section class="wp-block-group kramo-hero"><!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Polskie rzemiosło na co dzień</h1>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Naturalne materiały, prosty krój i wysyłka w 24 godziny. Personalizacja z imieniem lub własnym napisem dostępna przy każdym produkcie.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/sklep/">Zobacz sklep</a></div>
<!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="%%KRAMO_HOME_CATEGORY%%">Dom i wnętrze</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></section>
<!-- /wp:group -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Nowości w sklepie</h2>
<!-- /wp:heading -->
<!-- wp:shortcode -->
[products limit="4" columns="4" orderby="date"]
<!-- /wp:shortcode -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Kategorie</h2>
<!-- /wp:heading -->
<!-- wp:shortcode -->
[product_categories number="3" columns="3"]
<!-- /wp:shortcode -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Na prezent</h2>
<!-- /wp:heading -->
<!-- wp:shortcode -->
[products limit="4" columns="4" on_sale="true"]
<!-- /wp:shortcode -->
HTML;

$home_category_term = get_term_by('slug', 'dom', 'product_cat');
$home_category_link = $home_category_term instanceof WP_Term
    ? get_term_link($home_category_term)
    : '';
$home_content = str_replace(
    '%%KRAMO_HOME_CATEGORY%%',
    is_string($home_category_link) && '' !== $home_category_link
        ? esc_url($home_category_link)
        : wc_get_page_permalink('shop'),
    $home_content
);

$home = get_page_by_path($home_slug);
$home_id = $home
    ? $home->ID
    : wp_insert_post([
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_name' => $home_slug,
        'post_title' => 'Strona główna',
        'post_content' => $home_content,
    ]);

if (!is_wp_error($home_id) && $home_id) {
    wp_update_post(['ID' => $home_id, 'post_content' => $home_content]);
    update_option('show_on_front', 'page');
    update_option('page_on_front', $home_id);
}

// The storefront reads better full width; GeneratePress defaults to a sidebar.
$generate_settings = get_option('generate_settings', []);
$generate_settings = is_array($generate_settings) ? $generate_settings : [];
$generate_settings['layout_setting'] = 'no-sidebar';
$generate_settings['single_layout_setting'] = 'no-sidebar';
$generate_settings['post_content'] = 'excerpt';
update_option('generate_settings', $generate_settings);

update_option('woocommerce_coming_soon', 'no');
update_option('woocommerce_store_pages_only', 'no');

WP_CLI::success(sprintf(
    'Demo content ready: %d variable products, %d categories, %d reviews and coupon START10.',
    count($product_ids),
    count($categories),
    count($reviews)
));
