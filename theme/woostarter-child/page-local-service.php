<?php
/**
 * Template Name: Usługa lokalna (SEO)
 *
 * Landing page for a single service in a single city. Editable fields live in
 * the "Usługa lokalna (SEO)" meta box; LocalBusiness and FAQPage schema are
 * emitted from inc/schema.php. Copy the page and change Usługa + Miasto to
 * target a new city.
 *
 * @package WooStarter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$ws_ls   = function_exists( 'woostarter_get_local_service_data' )
	? woostarter_get_local_service_data( get_the_ID() )
	: array();
$ws_title = trim( ( $ws_ls['service'] ?? '' ) . ' ' . ( $ws_ls['city'] ?? '' ) );
$ws_faq   = function_exists( 'woostarter_parse_faq' )
	? woostarter_parse_faq( $ws_ls['faq_raw'] ?? '' )
	: array();
?>
<div id="primary" class="content-area woostarter-content-area">
	<main id="main" class="site-main woostarter-main woostarter-local-service" role="main">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class( 'woostarter-local-service__article' ); ?>>
				<header class="woostarter-local-service__header">
					<h1 class="woostarter-local-service__title">
						<?php echo esc_html( '' !== $ws_title ? $ws_title : get_the_title() ); ?>
					</h1>
					<?php if ( ! empty( $ws_ls['price_range'] ) ) : ?>
						<p class="woostarter-local-service__price">
							<?php
							printf(
								/* translators: %s: price range. */
								esc_html__( 'Cena: %s', 'woostarter' ),
								esc_html( $ws_ls['price_range'] )
							);
							?>
						</p>
					<?php endif; ?>
				</header>

				<section class="woostarter-local-service__body">
					<?php the_content(); ?>
				</section>

				<?php if ( ! empty( $ws_ls['area_served'] ) ) : ?>
					<section class="woostarter-local-service__area">
						<h2><?php echo esc_html__( 'Obszar obsługi', 'woostarter' ); ?></h2>
						<ul>
							<?php foreach ( $ws_ls['area_served'] as $ws_area ) : ?>
								<li><?php echo esc_html( $ws_area ); ?></li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>

				<?php if ( ! empty( $ws_faq ) ) : ?>
					<section class="woostarter-local-service__faq">
						<h2><?php echo esc_html__( 'Najczęstsze pytania', 'woostarter' ); ?></h2>
						<div class="woostarter-faq">
							<?php foreach ( $ws_faq as $ws_pair ) : ?>
								<details class="woostarter-faq__item">
									<summary class="woostarter-faq__question"><?php echo esc_html( $ws_pair['question'] ); ?></summary>
									<div class="woostarter-faq__answer"><?php echo esc_html( $ws_pair['answer'] ); ?></div>
								</details>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>

				<?php if ( ! empty( $ws_ls['cta_url'] ) && ! empty( $ws_ls['cta_text'] ) ) : ?>
					<section class="woostarter-local-service__cta">
						<a class="button woostarter-local-service__cta-button" href="<?php echo esc_url( $ws_ls['cta_url'] ); ?>">
							<?php echo esc_html( $ws_ls['cta_text'] ); ?>
						</a>
					</section>
				<?php elseif ( ! empty( $ws_ls['phone'] ) ) : ?>
					<section class="woostarter-local-service__cta">
						<a class="button woostarter-local-service__cta-button" href="<?php echo esc_attr( 'tel:' . preg_replace( '/\s+/', '', $ws_ls['phone'] ) ); ?>">
							<?php
							printf(
								/* translators: %s: phone number. */
								esc_html__( 'Zadzwoń: %s', 'woostarter' ),
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
