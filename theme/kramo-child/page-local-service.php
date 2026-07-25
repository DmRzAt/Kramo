<?php
/**
 * Template Name: Usługa lokalna (SEO)
 *
 * Landing page for a single service in a single city. Editable fields live in
 * the "Usługa lokalna (SEO)" meta box; LocalBusiness and FAQPage schema are
 * emitted from inc/schema.php. Copy the page and change Usługa + Miasto to
 * target a new city.
 *
 * @package Kramo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$ws_ls   = function_exists( 'kramo_get_local_service_data' )
	? kramo_get_local_service_data( get_the_ID() )
	: array();
$ws_title = trim( ( $ws_ls['service'] ?? '' ) . ' ' . ( $ws_ls['city'] ?? '' ) );
$ws_faq   = function_exists( 'kramo_parse_faq' )
	? kramo_parse_faq( $ws_ls['faq_raw'] ?? '' )
	: array();
?>
<div id="primary" class="content-area kramo-content-area">
	<main id="main" class="site-main kramo-main kramo-local-service" role="main">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class( 'kramo-local-service__article' ); ?>>
				<header class="kramo-local-service__header">
					<h1 class="kramo-local-service__title">
						<?php echo esc_html( '' !== $ws_title ? $ws_title : get_the_title() ); ?>
					</h1>
					<?php if ( ! empty( $ws_ls['price_range'] ) ) : ?>
						<p class="kramo-local-service__price">
							<?php
							printf(
								/* translators: %s: price range. */
								esc_html__( 'Cena: %s', 'kramo' ),
								esc_html( $ws_ls['price_range'] )
							);
							?>
						</p>
					<?php endif; ?>
				</header>

				<section class="kramo-local-service__body">
					<?php the_content(); ?>
				</section>

				<?php if ( ! empty( $ws_ls['area_served'] ) ) : ?>
					<section class="kramo-local-service__area">
						<h2><?php echo esc_html__( 'Obszar obsługi', 'kramo' ); ?></h2>
						<ul>
							<?php foreach ( $ws_ls['area_served'] as $ws_area ) : ?>
								<li><?php echo esc_html( $ws_area ); ?></li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>

				<?php if ( ! empty( $ws_faq ) ) : ?>
					<section class="kramo-local-service__faq">
						<h2><?php echo esc_html__( 'Najczęstsze pytania', 'kramo' ); ?></h2>
						<div class="kramo-faq">
							<?php foreach ( $ws_faq as $ws_pair ) : ?>
								<details class="kramo-faq__item">
									<summary class="kramo-faq__question"><?php echo esc_html( $ws_pair['question'] ); ?></summary>
									<div class="kramo-faq__answer"><?php echo esc_html( $ws_pair['answer'] ); ?></div>
								</details>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>

				<?php if ( ! empty( $ws_ls['cta_url'] ) && ! empty( $ws_ls['cta_text'] ) ) : ?>
					<section class="kramo-local-service__cta">
						<a class="button kramo-local-service__cta-button" href="<?php echo esc_url( $ws_ls['cta_url'] ); ?>">
							<?php echo esc_html( $ws_ls['cta_text'] ); ?>
						</a>
					</section>
				<?php elseif ( ! empty( $ws_ls['phone'] ) ) : ?>
					<section class="kramo-local-service__cta">
						<a class="button kramo-local-service__cta-button" href="<?php echo esc_attr( 'tel:' . preg_replace( '/\s+/', '', $ws_ls['phone'] ) ); ?>">
							<?php
							printf(
								/* translators: %s: phone number. */
								esc_html__( 'Zadzwoń: %s', 'kramo' ),
								esc_html( $ws_ls['phone'] )
							);
							?>
						</a>
					</section>
				<?php endif; ?>
			</article>
			<?php
		endwhile;
		?>
	</main>
</div>
<?php
get_footer();
