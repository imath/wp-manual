<?php
/**
 * Template for the WP Manual search page
 *
 * Theme can override this template by adding a manual-search.php
 * template to their theme directory in a subdirectory named wp-manual
 *
 * @package WP Manual
 * @since 1.0
 */
?>
<div id="wpmanual">

	<?php do_action( 'wpmanual_before_page_loop' ); ?>

	<?php if ( wpmanual_has_pages() ) : ?>

		<ul class="manual-list">

			<?php while ( wpmanual_has_pages() ) : wpmanual_the_page(); ?>

				
				<li id="manual-page-<?php wpmanual_the_id();?>">
					<h4><a href="<?php wpmanual_the_permalink() ?>"><?php wpmanual_the_title() ?></a></h4>
					<div class="manual-excerpt">
						<?php wpmanual_the_excerpt();?>
					</div>
					<div class="manual-meta">
						<p><em><?php printf( __( 'Last Update : %s', 'wp-manual' ), wpmanual_get_the_date() );?></em></p>
					</div>
				</li>

			<?php endwhile; ?>

		</ul>

		<div class="pagination">

			<div class="pagination-links">

				<?php wpmanual_pagination_links(); ?>

			</div>

		</div>

	<?php else : ?>

		<div class="info alert">
			<p><?php _e( 'Sorry, no manual page matched the query.', 'wp-manual' ); ?></p>
		</div>

	<?php endif; ?>

	<?php do_action( 'wpmanual_after_page_loop' ); ?>

</div>