<?php
/**
 * Displays the searchform of the theme.
 *
 * @package Type
 * @since Type 1.0
 */
?>

<form role="search" method="get" class="search-form clear" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label>
		<span class="screen-reader-text"><?php _ex( 'Search for:', 'label', 'type' ); ?></span>
		<input type="search" id="s" class="search-field" placeholder="<?php echo esc_attr_x( 'Search &hellip;', 'placeholder', 'type' ); ?>" value="<?php echo get_search_query(); ?>" name="s" />
	</label>
	<button type="submit" class="search-submit">
		<i class="material-icons md-20 md-middle">&#xE8B6;</i> <span class="screen-reader-text">
		<?php _ex( 'Search', 'submit button', 'type' ); ?></span>
	</button>
</form>
