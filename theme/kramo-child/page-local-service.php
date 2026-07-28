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

$kramo_ls   = function_exists( 'kramo_get_local_service_data' )
	? kramo_get_local_service_data( get_the_ID() )
	: array();
$kramo_title = trim( ( $kramo_ls['service'] ?? '' ) . ' ' . ( $kramo_ls['city'] ?? '' ) );
$kramo_faq   = function_exists( 'kramo_parse_faq' )
	? kramo_parse_faq( $kramo_ls['faq_raw'] ?? '' )
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
						<?php echo esc_html( '' !== $kramo_title ? $kramo_title : get_the_title() ); ?>
					</h1>
					<?php if ( ! empty( $kramo_ls['price_range'] ) ) : ?>
						<p class="kramo-local-service__price">
							<?php
							printf(
								/* translators: %s: price range. */
								esc_html__( 'Cena: %s', 'kramo' ),
								esc_html( $kramo_ls['price_range'] )
							);
							?>
						</p>
					<?php endif; ?>
				</header>

				<section class="kramo-local-service__body">
					<?php the_content(); ?>
				</section>

				<?php if ( ! empty( $kramo_ls['area_served'] ) ) : ?>
					<section class="kramo-local-service__area">
						<h2><?php echo esc_html__( 'Obszar obsługi', 'kramo' ); ?></h2>
						<ul>
							<?php foreach ( $kramo_ls['area_served'] as $kramo_area ) : ?>
								<li><?php echo esc_html( $kramo_area ); ?></li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>

				<?php if ( ! empty( $kramo_faq ) ) : ?>
					<section class="kramo-local-service__faq">
						<h2><?php echo esc_html__( 'Najczęstsze pytania', 'kramo' ); ?></h2>
						<div class="kramo-faq">
							<?php foreach ( $kramo_faq as $kramo_pair ) : ?>
								<details class="kramo-faq__item">
									<summary class="kramo-faq__question"><?php echo esc_html( $kramo_pair['question'] ); ?></summary>
									<div class="kramo-faq__answer"><?php echo esc_html( $kramo_pair['answer'] ); ?></div>
								</details>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>

				<?php if ( ! empty( $kramo_ls['cta_url'] ) && ! empty( $kramo_ls['cta_text'] ) ) : ?>
					<section class="kramo-local-service__cta">
						<a class="button kramo-local-service__cta-button" href="<?php echo esc_url( $kramo_ls['cta_url'] ); ?>">
							<?php echo esc_html( $kramo_ls['cta_text'] ); ?>
						</a>
					</section>
				<?php elseif ( ! empty( $kramo_ls['phone'] ) ) : ?>
					<section class="kramo-local-service__cta">
						<a class="button kramo-local-service__cta-button" href="<?php echo esc_attr( 'tel:' . preg_replace( '/\s+/', '', $kramo_ls['phone'] ) ); ?>">
							<?php
							printf(
								/* translators: %s: phone number. */
								esc_html__( 'Zadzwoń: %s', 'kramo' ),
								esc_html( $kramo_ls['phone'] )
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
