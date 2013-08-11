<?php
/**
 * Template for the WP Manual pages
 *
 * Theme can override this template by adding a manual-page.php
 * template to their theme directory in a subdirectory named wp-manual
 *
 * @package WP Manual
 * @since 1.0
 */
?>
<div id="wpmanual">

	<?php do_action( 'wpmanual_before_page_loop' ); ?>

	<?php if ( wpmanual_has_pages() ) : ?>

		<?php while ( wpmanual_has_pages() ) : wpmanual_the_page(); ?>

			<div id="manual-page-<?php wpmanual_the_id();?>" class="manual-content">
				<?php wpmanual_the_content() ?>
			</div>

			<div class="manual-meta">
				<p><em><?php printf( __( 'Last Update : %s', 'wp-manual' ), wpmanual_get_the_date() );?></em></p>
			</div>

			<?php wpmanual_content_nav();?>

		<?php endwhile; ?>

	<?php else : ?>

		<div class="info alert">
			<p><?php _e( 'Sorry, no manual page matched the query.', 'wp-manual' ); ?></p>
		</div>

	<?php endif; ?>

	<?php do_action( 'wpmanual_after_page_loop' ); ?>

</div>