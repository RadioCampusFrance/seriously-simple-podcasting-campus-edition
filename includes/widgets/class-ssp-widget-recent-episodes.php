<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seriously Simple Podcasting Recent Podcast Episodes Widget
 *
 * @author 		Hugh Lashbrooke
 * @package 	SeriouslySimplePodcasting
 * @category 	SeriouslySimplePodcasting/Widgets
 * @since 		1.8.0
 */
class SSP_Widget_Recent_Episodes extends WP_Widget {
	protected $widget_cssclass;
	protected $widget_description;
	protected $widget_idbase;
	protected $widget_title;

	/**
	 * Constructor function.
	 * @since  1.8.0
	 */
	public function __construct() {
		/* Widget variable settings. */
		$this->widget_cssclass = 'widget_recent_entries widget_recent_episodes';
		$this->widget_description = __( 'Display a list of your most recent podcast episodes.', 'seriously-simple-podcasting' );
		$this->widget_idbase = 'ss_podcast';
		$this->widget_title = __( 'Podcast: Recent Episodes', 'seriously-simple-podcasting' );

		/* Widget settings. */
		$widget_ops = array( 'classname' => $this->widget_cssclass, 'description' => $this->widget_description );

		parent::__construct('recent-podcast-episodes', $this->widget_title, $widget_ops);

		$this->alt_option_name = 'widget_recent_episodes';

		add_action( 'save_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'deleted_post', array( $this, 'flush_widget_cache' ) );
		add_action( 'switch_theme', array( $this, 'flush_widget_cache' ) );

	} // End __construct()

	public function widget($args, $instance) {
		if (get_post_type() == 'podcast') {
			$this->showSubscribeToThis();
		}


		$cache = array();
		if ( ! $this->is_preview() ) {
			$cache = wp_cache_get( 'widget_recent_episodes', 'widget' );
		}

		if ( ! is_array( $cache ) ) {
			$cache = array();
		}

		if ( ! isset( $args['widget_id'] ) ) {
			$args['widget_id'] = $this->id;
		}

		if ( isset( $cache[ $args['widget_id'] ] ) ) {
			echo $cache[ $args['widget_id'] ];
			return;
		}

		$title = ( ! empty( $instance['title'] ) ) ? $instance['title'] : __( 'Recent Episodes', 'seriously-simple-podcasting' );

		/** This filter is documented in wp-includes/default-widgets.php */
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$number = ( ! empty( $instance['number'] ) ) ? absint( $instance['number'] ) : 5;
		if ( ! $number ) {
			$number = 5;
		}

		$show_date = isset( $instance['show_date'] ) ? $instance['show_date'] : false;

		$query_args = ssp_episodes( $number, '', true, 'widget' );

		$qry = new WP_Query( apply_filters( 'ssp_widget_recent_episodes_args', $query_args ) );

		ob_start();

		if ($qry->have_posts()) :
?>
		<?php echo $args['before_widget']; ?>
		<?php if ( $title ) {
			$urlarchive = get_post_type_archive_link( 'podcast' );
			echo $args['before_title'] . "<a href='" . $urlarchive . "' title='Podcasts'>" . $title . "</a>" . $args['after_title'];
		} ?>
		<ul>
		<?php while ( $qry->have_posts() ) : $qry->the_post(); ?>
			<li>
				<a href="<?php the_permalink(); ?>"><?php get_the_title() ? the_title() : the_ID(); ?></a>
			<?php if ( $show_date ) : ?>
				<span class="post-date"><?php echo get_the_date(); ?></span>
			<?php endif; ?>
			</li>
		<?php endwhile; ?>
		</ul>
		<?php echo $args['after_widget']; ?>
<?php
		// Reset the global $the_post as this query will have stomped on it
		wp_reset_postdata();

		endif;

		if ( ! $this->is_preview() ) {
			$cache[ $args['widget_id'] ] = ob_get_flush();
			wp_cache_set( 'widget_recent_episodes', $cache, 'widget' );
		} else {
			ob_end_flush();
		}
	}

	private function showSubscribeToThis() {
		$terms = wp_get_post_terms( get_the_ID() , 'series' );
		if ($terms) {
			$term = $terms[0];
			$series_slug = $term->slug;
			$series_prefix = $term->taxonomy;
		} else {
			return;
		}

		$title = __( 'Subscribe to this series' , 'seriously-simple-podcasting' );
		$rss_msg = __('RSS Feed:' , 'seriously-simple-podcasting' );
		$itunes_msg = __('iTunes:' , 'seriously-simple-podcasting' );
		$episodes_msg = __('Listen to other episodes from this series' , 'seriously-simple-podcasting' );

		$rss = '/?feed=podcast&podcast_series='.$series_slug;
		$itunes = 'itpc://'.$_SERVER['HTTP_HOST'].$rss;
		$episodes = '/'.$series_prefix.'/'.$series_slug.'/';

		$assets_url = esc_url( trailingslashit( plugins_url( '/assets/', dirname( __FILE__ ).'/../../seriously-simple-podcasting.php' ) ) );

		$rss_img = $assets_url.'img/rss.png';
		$itunes_img = $assets_url.'img/itunes.png';

		echo <<<END
<div class="widget">
	<div class="widget-content">
		<h3 class="widget-title">$title</h3>
		<ul>
			<li>
				$rss_msg
				<a href='$rss' title='RSS'>
					<img src='$rss_img' alt='RSS'/>
				</a>
			</li>
			<li>
				$itunes_msg
				<a href='$itunes' title='iTunes'>
					<img src='$itunes_img' alt='iTunes'/>
				</a>
			</li>
			<li>
				<a href="$episodes">$episodes_msg</a>
			</li>
		</ul>
	</div>
	<div class="clear"></div>
</div>
END;
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = (int) $new_instance['number'];
		$instance['show_date'] = isset( $new_instance['show_date'] ) ? (bool) $new_instance['show_date'] : false;
		$this->flush_widget_cache();

		return $instance;
	}

	public function flush_widget_cache() {
		wp_cache_delete('widget_recent_episodes', 'widget');
	}

	public function form( $instance ) {
		$title     = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$number    = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
		$show_date = isset( $instance['show_date'] ) ? (bool) $instance['show_date'] : false;
?>
		<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'seriously-simple-podcasting' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id( 'number' ); ?>"><?php _e( 'Number of episodes to show:', 'seriously-simple-podcasting' ); ?></label>
		<input id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>

		<p><input class="checkbox" type="checkbox" <?php checked( $show_date ); ?> id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" />
		<label for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php _e( 'Display episode date?', 'seriously-simple-podcasting' ); ?></label></p>
<?php
	}
} // End Class

?>
