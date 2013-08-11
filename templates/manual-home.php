<?php
/**
 * Template for the WP Manual "Home" page
 *
 * Theme can override this template by adding a manual-home.php
 * template to their theme directory in a subdirectory named wp-manual
 *
 * @package WP Manual
 * @since 1.0
 */
?>
<div id="wpmanual">

	<?php do_action( 'wpmanual_before_page_loop' ); ?>

	<?php if ( wpmanual_has_pages( array( 'orderby' => 'menu_order', 'order' => 'ASC' ) ) ) : ?>

		<ul class="manual-list">

			<?php while ( wpmanual_has_pages() ) : wpmanual_the_page(); ?>

				
				<li id="manual-page-<?php wpmanual_the_id();?>">
					<h4><a href="<?php wpmanual_the_permalink() ?>"><?php wpmanual_the_title() ?></a></h4>
					<div class="manual-toc">
						<?php wpmanual_the_toc();?>
					</div>
				</li>

			<?php endwhile; ?>

		</ul>

	<?php else : ?>

		<div class="info alert">
			<p><?php _e( 'Sorry, no manual page matched the query.', 'wp-manual' ); ?></p>
		</div>

	<?php endif; ?>

	<?php do_action( 'wpmanual_after_page_loop' ); ?>

</div>