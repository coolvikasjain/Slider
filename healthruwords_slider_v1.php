<?php
/*
Plugin Name: Healthruwords Slider
Plugin URI: http://healthruwords.com/
Version: 1.0
Description: Healthruwords Slider Widget is a responsive slider widget that shows 20 latest Quotes from Healthruwords API.
Author: Arnaud Saint-Paul
Author URI: http://healthruwords.com/
License: GPLv2 or later
*/

require_once plugin_dir_path( __FILE__ ).'config.php';
require_once 'Curl.php';

use \Curl\Curl;

/**
 * On widgets Init register Widget
 */
add_action( 'widgets_init', array( 'HealthruwordsSlider', 'register_widget' ) );

/**
 * HealthruwordsSlider Class
 */
class HealthruwordsSlider extends WP_Widget {
	
	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @var     string
	 */
	const VERSION = '1.0';	
	
	/**
	 * Initialize the plugin by registering widget and loading public scripts
	 *
	 */
	public function __construct() {
		
		// Widget ID and Class Setup
		parent::__construct( 'jr_insta_slider', __( 'Healthruwords Slider', 'healthruwordsslider' ), array(
				'classname' => 'jr-insta-slider',
				'description' => __( 'A widget that displays a slider with healthruwords images ', 'healthruwordsslider' ) 
			) 
		);

		// Shortcode				
		add_shortcode( 'healthruwords', array( $this, 'shortcode' ) );
		
		// Healthruwords Action to display images
		add_action( 'healthruwords', array( $this, 'healthruwords_images' ) );

		// Enqueue Plugin Styles and scripts
		add_action( 'wp_enqueue_scripts', array( $this,	'healthruwords_enqueue' ) );
		
		// Enqueue Plugin Styles and scripts for admin pages
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ) );
		

	}

	/**
	 * Register widget on widgets init
	 */
	public static function register_widget() {
		register_widget( __CLASS__ );
		register_sidebar( array(
	        'name' => __( 'Healthruwords Slider - Shortcode Generator', 'healthruwordsslider' ),
	        'id' => 'healthruwords-shortcodes',
	        'description' => __( "1. Drag Healthruwords Slider widget here. 2. Fill in the fields and hit save. 3. Copy the shortocde generated at the bottom of the widget form and use it on posts or pages.", 'healthruwordsslider' )
	    	) 
	    );
	}
	
	/**
	 * Enqueue public-facing Scripts and style sheet.
	 */
	public function healthruwords_enqueue() {
		
		wp_enqueue_style( 'htw-slider', plugins_url( 'assets/css/htw-slider.css', __FILE__ ), array(), self::VERSION );
		
		wp_enqueue_script( 'jquery-pllexi-slider', plugins_url( 'assets/js/jquery.flexslider-min.js', __FILE__ ), array( 'jquery' ), '2.2', false );
	}
	
	/**
	 * Enqueue admin side scripts and styles
	 * 
	 * @param  string $hook
	 */
	public function admin_enqueue( $hook ) {
		
		if ( 'widgets.php' != $hook ) {
			return;
		}
		
		wp_enqueue_style( 'healthruwords-admin-styles', plugins_url( 'assets/css/jr-htw-admin.css', __FILE__ ), array(), self::VERSION );

		wp_enqueue_script( 'healthruwords_admin-script', plugins_url( 'assets/js/jr-htw-admin.js', __FILE__ ), array( 'jquery' ), self::VERSION, true );
				
	}
	
	/**
	 * The Public view of the Widget  
	 *
	 * @return mixed
	 */
	public function widget( $args, $instance ) {
		
		extract( $args );
		
		//Our variables from the widget settings.
		$title = apply_filters( 'widget_title', $instance['title'] );
		
		echo $before_widget;
		
		// Display the widget title 
		if ( $title ) {
			echo $before_title . $title . $after_title;
		}
		
		do_action( 'healthruwords', $instance );
		
		echo $after_widget;
	}
	
	/**
	 * Update the widget settings 
	 *
	 * @param    array    $new_instance    New instance values
	 * @param    array    $old_instance    Old instance values	 
	 *
	 * @return array
	 */
	public function update( $new_instance, $instance ) {
		
		$instance['title']            = strip_tags( $new_instance['title'] );
		$instance['username']         = $new_instance['username'];
		$instance['source']           = $new_instance['source'];
		$instance['attachment']       = $new_instance['attachment'];
		$instance['template']         = $new_instance['template'];
		$instance['images_link']      = $new_instance['images_link'];
		$instance['custom_url']       = $new_instance['custom_url'];
		$instance['orderby']          = $new_instance['orderby'];
		$instance['images_number']    = $new_instance['images_number'];
		$instance['columns']          = $new_instance['columns'];
		$instance['image_size']       = $new_instance['image_size'];
		$instance['topic_list']       = $new_instance['topic_list'];
		$instance['image_link_class'] = $new_instance['image_link_class'];
		$instance['controls']         = $new_instance['controls'];
		$instance['animation']        = $new_instance['animation'];
		$instance['slider_speed']	  = $new_instance['slider_speed'];
		$opt_name  = 'healthru_' . md5($this->id);
		$args_save_opt='heal_args_'. md5($this->id);
		
		delete_transient($opt_name);
		update_option($args_save_opt, $instance);
		return $instance;
	}
	
	
	/**
	 * Widget Settings Form
	 *
	 * @return mixed
	 */
	public function form( $instance ) {

		$defaults = array(
			'title'            => __('Healthruwords Slider', 'healthruwordsslider'),
			'username'         => '',
			'source'           => 'healthruwords',
			'attachment' 	   => 1,
			'template'         => 'slider',
			'images_link'      => 'image_url',
			'custom_url'       => '',
			'orderby'          => 'rand',
			'images_number'    => 5,
			'columns'          => 4,
			'refresh_hour'     => 5,
			'image_size'       => 'full',
			'image_link_rel'   => '',
			'image_link_class' => '',
			'controls'		   => 'prev_next',
			'animation'        => 'slide',
			'description'      => array( 'username', 'time','caption' )
		);
		
		$instance = wp_parse_args( (array) $instance, $defaults );
		// Fetch Topics
		$ch = curl_init();
		$url="http://healthruwords.com/api/v1/topics/";
		//set the url, number of POST vars, POST data
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		
		//execute post
		$result = curl_exec($ch);
		$topics_api=json_decode("$result");
		?>
		<div class="jr-container">
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'healthruwordsslider'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" />
			</p>
			<!-- <p>
				<label for="<?php echo $this->get_field_id( 'username' ); ?>"><?php _e('Healthruwords Username:', 'healthruwordsslider'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'username' ); ?>" name="<?php echo $this->get_field_name( 'username' ); ?>" value="<?php echo $instance['username']; ?>" />
			</p>-->
			<p style="display:none;">
				<?php _e( 'Source:', 'healthruwordsslider' ); ?><br>
				
				<label class="jr-radio"><input type="radio" id="<?php echo $this->get_field_id( 'source' ); ?>" name="<?php echo $this->get_field_name( 'source' ); ?>" value="media_library" <?php echo 'checked';//checked( 'media_library', $instance['source'] ); ?> /> <?php _e( 'WP Media Library', 'healthruwordsslider' ); ?></label>
				<br><span class="jr-description"><?php _e( 'WP Media Library option will display previously saved healthruwords images for the user in the field above!', 'healthruwordsslider') ?></span>
			</p>
	        <p class="hidden">
	            <label for="<?php echo $this->get_field_id( 'attachment' ); ?>"><?php _e( 'Insert images into Media Library:', 'healthruwordsslider' ); ?></label>
	            <input class="widefat" id="<?php echo $this->get_field_id( 'attachment' ); ?>" name="<?php echo $this->get_field_name( 'attachment' ); ?>" type="checkbox" value="1" <?php checked( '1', $instance['attachment'] ); ?> />
	        	<br><span class="jr-description"><?php _e( 'It is recommended you check the field above because images displayed directly from Healthruwords server will affect your site loading time!', 'healthruwordsslider') ?></span>
	        </p>
			<?php 		
				$user_opt = get_option( 'jr_insta_'. md5( $instance['username'] ) );
				if ( isset( $user_opt['deleted_images'] ) && ( !empty( $user_opt['deleted_images'] ) && ( $instance['source'] == 'healthruwords' ) && ( $instance['attachment'] ) ) ) {
					$deleted_count = count( $user_opt['deleted_images'] );
					echo '<div class="blocked-wrap">';
					wp_nonce_field( 'jr_unblock_instagram_image', 'unblock_images_nonce' );
					echo "<strong>{$instance['username']}</strong> has <strong class='blocked-count-nr'>{$deleted_count}</strong> blocked images! ";
					echo "<a href='#' class='blocked-images-toggle'>[ + Open ]</a>";
					echo '<div class="blocked-images hidden">';
						echo '<ul>';
							foreach ( $user_opt['deleted_images'] as $id => $image ) {
								echo "<li class='blocked-column' data-id='{$id}'><span class='blocked-imgcontainer'><span class='jr-allow-yes dashicons dashicons-yes'></span><img src='{$image}'></span></li>";
							}
						echo '</ul>';
					echo '</div>';
					echo "<span class='jr-description'>You can unblock healthruwords images by clicking the ones you want to have on the media library.</span>";
					echo '</div>';
				} 
			?>
			<!-- <p>
				<label for="<?php echo $this->get_field_id( 'template' ); ?>"><?php _e( 'Template', 'healthruwordsslider' ); ?>
					<select class="widefat" name="<?php echo $this->get_field_name( 'template' ); ?>" id="<?php echo $this->get_field_id( 'template' ); ?>">
						<option value="slider" <?php echo ($instance['template'] == 'slider') ? ' selected="selected"' : ''; ?>><?php _e( 'Slider - Normal', 'healthruwordsslider' ); ?></option>
						<option value="slider-overlay" <?php echo ($instance['template'] == 'slider-overlay') ? ' selected="selected"' : ''; ?>><?php _e( 'Slider - Overlay Text', 'healthruwordsslider' ); ?></option>
						<option value="thumbs" <?php echo ($instance['template'] == 'thumbs') ? ' selected="selected"' : ''; ?>><?php _e( 'Thumbnails', 'healthruwordsslider' ); ?></option>
					</select>  
				</label>
			</p> -->
			<!--<p>
				<?php
				$image_sizes = array( 'thumbnail', 'medium', 'large' );
				/* 
				$image_size_options = get_intermediate_image_sizes();
				if ( is_array( $image_size_options ) && !empty( $image_size_options ) && !$instance['attachment'] ) {
					$image_sizes = $image_size_options;
				}
				*/
				?>			
				<label for="<?php echo $this->get_field_id( 'image_size' ); ?>"><?php _e( 'Image size', 'healthruwordsslider' ); ?></label>
				<select class="widefat" id="<?php echo $this->get_field_id( 'image_size' ); ?>" name="<?php echo $this->get_field_name( 'image_size' ); ?>">
					<option value=""><?php _e('Select Image Size', 'healthruwordsslider') ?></option>
					<?php
					foreach ( $image_sizes as $image_size_option ) {
						printf( 
							'<option value="%1$s" %2$s>%3$s</option>',
						    esc_attr( $image_size_option ),
						    selected( $image_size_option, $instance['image_size'], false ),
						    ucfirst( $image_size_option )					    
						);
					}
					?>
				</select>
			</p>-->	        					
			<!--<p>
				<label for="<?php echo $this->get_field_id( 'orderby' ); ?>"><?php _e( 'Order by', 'healthruwordsslider' ); ?>
					<select class="widefat" name="<?php echo $this->get_field_name( 'orderby' ); ?>" id="<?php echo $this->get_field_id( 'orderby' ); ?>">
						<option value="date-ASC" <?php selected( $instance['orderby'], 'date-ASC', true); ?>><?php _e( 'Date - Ascending', 'healthruwordsslider' ); ?></option>
						<option value="date-DESC" <?php selected( $instance['orderby'], 'date-DESC', true); ?>><?php _e( 'Date - Descending', 'healthruwordsslider' ); ?></option>
						<option value="popular-ASC" <?php selected( $instance['orderby'], 'popular-ASC', true); ?>><?php _e( 'Popularity - Ascending', 'healthruwordsslider' ); ?></option>
						<option value="popular-DESC" <?php selected( $instance['orderby'], 'popular-DESC', true); ?>><?php _e( 'Popularity - Descending', 'healthruwordsslider' ); ?></option>
						<option value="rand" <?php selected( $instance['orderby'], 'rand', true); ?>><?php _e( 'Random', 'healthruwordsslider' ); ?></option>
					</select>  
				</label>
			</p>-->
			<!--<p>
				<label for="<?php echo $this->get_field_id( 'images_link' ); ?>"><?php _e( 'Link to', 'healthruwordsslider' ); ?>
					<select class="widefat" name="<?php echo $this->get_field_name( 'images_link' ); ?>" id="<?php echo $this->get_field_id( 'images_link' ); ?>">
						<option value="image_url" <?php selected( $instance['images_link'], 'image_url', true); ?>><?php _e( 'healthruwords Image', 'healthruwordsslider' ); ?></option>
						<option value="user_url" <?php selected( $instance['images_link'], 'user_url', true); ?>><?php _e( 'healthruwords Profile', 'healthruwordsslider' ); ?></option>
						<?php if ( $instance['attachment'] ) : ?>
						<option value="local_image_url" <?php selected( $instance['images_link'], 'local_image_url', true); ?>><?php _e( 'Locally Saved Image', 'healthruwordsslider' ); ?></option>
						<option value="attachment" <?php selected( $instance['images_link'], 'attachment', true); ?>><?php _e( 'Attachment Page', 'healthruwordsslider' ); ?></option>
						<?php endif; ?>
						<option value="custom_url" <?php selected( $instance['images_link'], 'custom_url', true ); ?>><?php _e( 'Custom Link', 'healthruwordsslider' ); ?></option>
						<option value="none" <?php selected( $instance['images_link'], 'none', true); ?>><?php _e( 'None', 'healthruwordsslider' ); ?></option>
					</select>  
				</label>
			</p>	-->		
			<p class="<?php if ( 'custom_url' != $instance['images_link'] ) echo 'hidden'; ?>">
				<label for="<?php echo $this->get_field_id( 'custom_url' ); ?>"><?php _e( 'Custom link:', 'healthruwordsslider'); ?></label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'custom_url' ); ?>" name="<?php echo $this->get_field_name( 'custom_url' ); ?>" value="<?php echo $instance['custom_url']; ?>" />
				<span><?php _e('* use this field only if the above option is set to <strong>Custom Link</strong>', 'healthruwordsslider'); ?></span>
			</p>
			<p>
				<label  for="<?php echo $this->get_field_id( 'images_number' ); ?>"><?php _e( 'Number of images to show:', 'healthruwordsslider' ); ?>
					<input  class="small-text" id="<?php echo $this->get_field_id( 'images_number' ); ?>" name="<?php echo $this->get_field_name( 'images_number' ); ?>" value="<?php echo $instance['images_number']; ?>" />
					<span><?php _e( '<strong>limit is 20</strong> ', 'healthruwordsslider' ); ?></span>
				</label>
			</p>
			<p>
					<label for="<?php echo $this->get_field_id( 'slider_speed' ); ?>">Slider speed in ms :</label>
					<input class="widefat" id="<?php echo $this->get_field_id( 'slider_speed' ); ?>" name="<?php echo $this->get_field_name( 'slider_speed' ); ?>" value="<?php echo $instance['slider_speed']; ?>">
					<span class="jr-description">(default: 7000ms)</span>
					</p>
			<p class="<?php if ( 'thumbs' != $instance['template'] ) echo 'hidden'; ?>">
				<label  for="<?php echo $this->get_field_id( 'columns' ); ?>"><?php _e( 'Number of Columns:', 'healthruwordsslider' ); ?>
					<input class="small-text" id="<?php echo $this->get_field_id( 'columns' ); ?>" name="<?php echo $this->get_field_name( 'columns' ); ?>" value="<?php echo $instance['columns']; ?>" />
					<span><?php _e('max is 10 ( only for thumbnails template )', 'healthruwordsslider'); ?></span>
				</label>
			</p>			
			
			<p>
				<strong>Advanced Options</strong> 
				<?php 
				$advanced_class = '';
				$advanced_text = '[ - Close ]';		
				if ( '' == trim( $instance['image_link_rel'] ) && '' == trim( $instance['image_link_class'] ) && '' == trim( $instance['image_size'] ) )  { 
					$advanced_class = 'hidden';
					$advanced_text = '[ + Open ]';
				}
				?>
				<a href="#" class="jr-advanced"><?php echo $advanced_text;  ?></a>
			</p>
			<div class="jr-advanced-input <?php echo $advanced_class; ?>">
				<div class="jr-image-options">
					<h4 class="jr-advanced-title"><?php _e( 'Advanced Image Options', 'healthruwordsslider'); ?></h4>
					<!--<p>
						<label for="<?php echo $this->get_field_id( 'image_link_rel' ); ?>"><?php _e( 'Image Link rel attribute', 'healthruwordsslider' ); ?>:</label>
						<input class="widefat" id="<?php echo $this->get_field_id( 'image_link_rel' ); ?>" name="<?php echo $this->get_field_name( 'image_link_rel' ); ?>" value="<?php echo $instance['image_link_rel']; ?>" />
						<span class="jr-description"><?php _e( 'Specifies the relationship between the current page and the linked website', 'healthruwordsslider' ); ?></span>
					</p>-->
					<p>
						<label for="<?php echo $this->get_field_id( 'image_link_class' ); ?>"><?php _e( 'Image Link class', 'healthruwordsslider' ); ?>:</label>
						<input class="widefat" id="<?php echo $this->get_field_id( 'image_link_class' ); ?>" name="<?php echo $this->get_field_name( 'image_link_class' ); ?>" value="<?php echo $instance['image_link_class']; ?>" />
						<span class="jr-description"><?php _e( 'Usefull if you are using jQuery lightbox plugins to open links', 'healthruwordsslider' ); ?></span>

					</p>
				</div>
				<div class="jr-slider-options <?php if ( 'thumbs' == $instance['template'] ) echo 'hidden'; ?>">
					<h4 class="jr-advanced-title"><?php _e( 'Advanced Slider Options', 'healthruwordsslider'); ?></h4>
					<p>
						<?php _e( 'Slider Navigation Controls:', 'healthruwordsslider' ); ?><br>
						<label class="jr-radio"><input type="radio" id="<?php echo $this->get_field_id( 'controls' ); ?>" name="<?php echo $this->get_field_name( 'controls' ); ?>" value="prev_next" <?php checked( 'prev_next', $instance['controls'] ); ?> /> <?php _e( 'Prev & Next', 'healthruwordsslider' ); ?></label>  
						<label class="jr-radio"><input type="radio" id="<?php echo $this->get_field_id( 'controls' ); ?>" name="<?php echo $this->get_field_name( 'controls' ); ?>" value="numberless" <?php checked( 'numberless', $instance['controls'] ); ?> /> <?php _e( 'Numberless', 'healthruwordsslider' ); ?></label>
						<label class="jr-radio"><input type="radio" id="<?php echo $this->get_field_id( 'controls' ); ?>" name="<?php echo $this->get_field_name( 'controls' ); ?>" value="none" <?php checked( 'none', $instance['controls'] ); ?> /> <?php _e( 'No Navigation', 'healthruwordsslider' ); ?></label>
					</p>
					<p>
						<?php _e( 'Slider Animation:', 'healthruwordsslider' ); ?><br>
						<label class="jr-radio"><input type="radio" id="<?php echo $this->get_field_id( 'animation' ); ?>" name="<?php echo $this->get_field_name( 'animation' ); ?>" value="slide" <?php checked( 'slide', $instance['animation'] ); ?> /> <?php _e( 'Slide', 'healthruwordsslider' ); ?></label>  
						<label class="jr-radio"><input type="radio" id="<?php echo $this->get_field_id( 'animation' ); ?>" name="<?php echo $this->get_field_name( 'animation' ); ?>" value="fade" <?php checked( 'fade', $instance['animation'] ); ?> /> <?php _e( 'Fade', 'healthruwordsslider' ); ?></label>
					</p>
					<p>
						<label for="<?php echo $this->get_field_id('topic_list'); ?>"><?php _e( 'Slider Topic list:', 'healthruwordsslider' ); ?></label>
						
						<?php $selected="";
						
						if(!isset($instance['topic_list']) || empty($instance['topic_list'])){
								$selected="selected";
						}?>
						<select size=3 class='widefat' id="<?php echo $this->get_field_id('topic_list'); ?>" name="<?php echo $this->get_field_name('topic_list'); ?>[]" multiple="multiple">
						<?php foreach($topics_api as $topic){
							
							?>
							<option value="<?php echo $topic->topic;?>" <?php if($selected==''){$this->selected( $instance['topic_list'], $topic->topic );}else{echo $selected;} ?>><?php _e( $topic->topic, 'healthruwordsslider'); ?></option>
						<?php 	
						}?>
						</select>
						<span class="jr-description"><?php _e( 'Hold ctrl and click the fields you want to show/hide on your slider.  Default all selected.', 'healthruwordsslider') ?></span>
					</p>					
				</div>
			</div>
			<?php $widget_id = preg_replace( '/[^0-9]/', '', $this->id ); if ( $widget_id != '' ) : ?>
			<p>
				<label for="jr_insta_shortcode"><?php _e('Shortcode of this Widget:', 'healthruwordsslider'); ?></label>
				<input id="jr_insta_shortcode" onclick="this.setSelectionRange(0, this.value.length)" type="text" class="widefat" value="[healthruwords id=&quot;<?php echo $widget_id ?>&quot;]" readonly="readonly" style="border:none; color:black; font-family:monospace;">
				<span class="jr-description"><?php _e( 'Use this shortcode in any page or post to display images with this widget configuration!', 'healthruwordsslider') ?></span>
			</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Selected array function echoes selected if in array
	 * 
	 * @param  array $haystack The array to search in
	 * @param  string $current  The string value to search in array;
	 * 
	 * @return string
	 */
	public function selected( $haystack, $current ) {
		
		if( is_array( $haystack ) && in_array( $current, $haystack ) ) {
			selected( 1, 1, true );
		}
	}	

	/**
	 * Add shorcode function
	 * @param  array $atts shortcode attributes
	 * @return mixed
	 */
	public function shortcode( $atts ) {
		
		
		$atts = shortcode_atts( array( 'id' => '' ), $atts, 'healthruwords' );
		$opt='heal_args_'.md5('jr_insta_slider-'.$atts['id']);
		$args = get_option($opt);
		
		//var_dump($args);
		if ( isset($args ) ) {
			return $this->display_images( $args ,true);
		}
	}

	/**
	 * Echoes the Display healthruwords Images method
	 * 
	 * @param  array $args
	 * 
	 * @return void
	 */
	public function healthruwords_images( $args ) {
		echo $this->display_images( $args );
	}

	/**
	 * Runs the query for images and returns the html
	 * 
	 * @param  array  $args 
	 * 
	 * @return string       
	 */
	private function display_images( $args, $is_shortcode=FALSE) {
		
		
		$username         = isset( $args['username'] ) && !empty( $args['username'] ) ? $args['username'] : false;
		$widget_name      = 'healthruwords';
		$source           = isset( $args['source'] ) && !empty( $args['source'] ) ? $args['source'] : 'instagram';
		$attachment       = isset( $args['attachment'] ) ? true : false;
		$template         = isset( $args['template'] ) ? $args['template'] : 'slider-overlay';
		$args['template']=$template;
		$orderby          = isset( $args['orderby'] ) ? $args['orderby'] : 'rand';
		$images_link      = isset( $args['images_link'] ) ? $args['images_link'] : 'local_image_url';
		$custom_url       = isset( $args['custom_url'] ) ? $args['custom_url'] : '';
		$images_number    = isset( $args['images_number'] ) ? absint( $args['images_number'] ) : 5;
		$columns          = isset( $args['columns'] ) ? absint( $args['columns'] ) : 4;
		$image_size       = isset( $args['image_size'] ) ? $args['image_size'] : 'full';
		$image_link_rel   = isset( $args['image_link_rel'] ) ? $args['image_link_rel'] : '';
		$image_link_class = isset( $args['image_link_class'] ) ? $args['image_link_class'] : '';
		$controls         = isset( $args['controls'] ) ? $args['controls'] : 'prev_next';
		$animation        = isset( $args['animation'] ) ? $args['animation'] : 'slide';
		$description      = isset( $args['description'] ) ? $args['description'] : array();
		$slider_speed     = isset( $args['slider_speed'] )&& $args['slider_speed']!='' ? absint( $args['slider_speed'] ) : 7000;
		$topic_list     = (isset( $args['topic_list']) && !empty($args['topic_list'])) ? $args['topic_list'] : array();
		
		/*if ( false == $username ) {
			return false;
		}*/

		if ( !empty( $description ) && !is_array( $description ) ) {
			$description = explode( ',', $description );
		}

		if (  $refresh_hour == 0 ) {
			$refresh_hour = 5;
		}
		
		$template_args = array(
			'source'      => $source,
			'attachment'  => $attachment,
 			'image_size'  => $image_size,
			'link_rel'    => $image_link_rel,
			'link_class'  => $image_link_class
		);

		$images_div_class = 'jr-insta-thumb';
		$ul_class         = 'thumbnails jr_col_' . $columns;
		$slider_script    = ''; 
		$selector='#'.$this->id;
		if($is_shortcode)
			$selector="";
		if ( $template != 'thumbs' ) {
			
			$template_args['description'] = $description;
			$direction_nav = ( $controls == 'prev_next' ) ? 'true' : 'false';
			$control_nav   = ( $controls == 'numberless' ) ? 'true': 'false';
			$ul_class      = 'slides';
			
			if ( $template == 'slider' ) {
				$images_div_class = 'pllexislider pllexislider-normal';
				$slider_script =
				"<script type='text/javascript'>" . "\n" .
				"	jQuery(document).ready(function($) {" . "\n" .
				"		$('{$selector} .pllexislider-normal').pllexislider({" . "\n" .
				"			animation: '{$animation}'," . "\n" .
				"			directionNav: {$direction_nav}," . "\n" .
				"			controlNav: {$control_nav}," . "\n" .
				"			slideshowSpeed: {$slider_speed}," . "\n" .
				"			pauseOnHover: true," . "\n" .
				"			touch: true," . "\n" .
				"			prevText: ''," . "\n" .
				"			nextText: ''," . "\n" .
				"		});" . "\n" .
				"	});" . "\n" .
				"</script>" . "\n";
			} else {
				$images_div_class = 'pllexislider pllexislider-overlay';
	            $slider_script =
				"<script type='text/javascript'>" . "\n" .
				"	jQuery(document).ready(function($) {" . "\n" .
				"		$('{$selector} .pllexislider-overlay').pllexislider({" . "\n" .
				"			animation: '{$animation}'," . "\n" .
				"			directionNav: {$direction_nav}," . "\n" .
				"			slideshowSpeed: {$slider_speed}," . "\n" .
				"			controlNav: {$control_nav}," . "\n" .
				"			pauseOnHover: true," . "\n" .
				"			touch: true," . "\n" .
				"			prevText: ''," . "\n" .
				"			nextText: ''," . "\n" .									
				"			start: function(slider){" . "\n" .
				"				slider.hover(" . "\n" .
				"					function () {" . "\n" .
				"						slider.find('.jr-insta-datacontainer, .pllex-control-nav, .pllex-direction-nav').stop(true,true).fadeIn();" . "\n" .
				"					}," . "\n" .
				"					function () {" . "\n" .
				"						slider.find('.jr-insta-datacontainer, .pllex-control-nav, .pllex-direction-nav').stop(true,true).fadeOut();" . "\n" .
				"					}" . "\n" .
				"				);" . "\n" .
				"			}" . "\n" .
				"		});" . "\n" .
				"	});" . "\n" .
				"</script>" . "\n";				
			}
        }

		$images_div = "<div class='{$images_div_class}'>\n";
		$images_ul  = "<ul class='no-bullet {$ul_class}'>\n";

		$output = __( 'No saved images for ' . $username, 'healthruwordsslider' );
		
			
			if ( $orderby != 'rand' ) {
				
				$orderby = explode( '-', $orderby );
				$meta_key = $orderby[0] == 'date' ? 'jr_insta_timestamp' : 'jr_insta_popularity';
				
				$query_args['meta_key'] = $meta_key;
				$query_args['orderby']  = 'meta_value_num';
				$query_args['order']    = $orderby[1];
			}
			
			$images_data = $this->healthruwords_data( $widget_name, $images_number, false ,$topic_list);
			//print_r($images_data);
			if (!empty( $images_data ) ) {
				
				$output = $slider_script . $images_div . $images_ul;
				
				foreach ( $images_data as $image_data ) {
					
					$image_data=get_object_vars($image_data);
					$url=$_SERVER['HTTP_HOST'];
					
					$sId="";
					$sId = end(explode('/', $image_data['url']));
					$query=array();
					$query[HEALTHRU_HOST]=$url;
					$query[HEALTHRU_QUOTE_ID]=$sId;
					$template_args['link_to'] = $image_data['url'].'?'.http_build_query($query);
					$template_args['logo_link_to'] = 'http://healthruwords.com/?'.http_build_query($query);

					$template_args['image'] = $image_data['media'];
					$template_args['caption']   = $image_data['title'];
					$template_args['description']   = $image_data['title'];
					$template_args['prefix']   = $image_data['cat'];
					
					
					$output .= $this->get_template( $template, $template_args );
				}
				$output .= "</ul>\n</div>";
		}			
		
		return $output;
		
	}

	/**
	 * Function to display Templates styles
	 *
	 * @param    string    $template
	 * @param    array	   $args	    
	 *
	 * return mixed
	 */
	private function get_template( $template, $args ) {

		$link_to   = isset( $args['link_to'] ) ? $args['link_to'] : false;
		$logo_link_to=$args['logo_link_to'];
			$caption   = $args['caption'];
			$time      = $args['timestamp'];
			$username  = $args['username'];
			$image_url = $args['image'];
			$seo=$args['prefix'].' - '.$caption;
			
		
		$time="";

		$short_caption = wp_trim_words( $caption, 10 );

		$image_src = '<img src="' . $image_url . '" alt="' . $seo . '" title="' . $seo . '" class="imgSlider" />';
		$image_output  = $image_src;

		if ( $link_to ) {
			$image_output  = '<a href="' . $link_to . '" target="_blank"';

			if ( ! empty( $args['link_rel'] ) ) {
				$image_output .= ' rel="' . $args['link_rel'] . '"';
			}

			if ( ! empty( $args['link_class'] ) ) {
				$image_output .= ' class="' . $args['link_class'] . '"';
			}
			$image_output .= ' title="' . $seo . '" alt="'.$seo.'">' . $image_src . '</a>';
		}		

		$output = '';
		
		// Template : Normal Slider
		if ( $template == 'slider' ) {
			
			$output .= "<li>";

				$output .= $image_output;

				if ( is_array( $args['description'] ) && count( $args['description'] ) >= 1 ) { 
					
					$output .= "<div class='jr-insta-datacontainer'>\n";
				/*
						if ( $time && in_array( 'time', $args['description'] ) ) {
							$time = human_time_diff( $time );
							$output .= "<span class='jr-insta-time'>{$time} ago</span>\n";
						}
						if ( in_array( 'username', $args['description'] ) ) {
							$output .= "<span class='jr-insta-username'>by <a rel='nofollow' href='http://healthruwords.com/{$username}' target='_blank'>{$username}</a></span>\n";
						}
				*/
						if ( $caption != '' && in_array( 'caption', $args['description'] ) ) {
							$caption   = preg_replace( '/@([a-z0-9_]+)/i', '&nbsp;<a href="http://healthruwords.com/$1" rel="nofollow" target="_blank">@$1</a>&nbsp;', $caption );
							$output .= "<span class='jr-insta-caption'>{$caption}</span>\n";
						}

					$output .= "</div>\n";
				}

			$output .= "</li>";
		
		// Template : Slider with text Overlay on mouse over
		} elseif ( $template == 'slider-overlay' ) {
			
			$output .= "<li>";
			
				$output .= $image_output;
			
				if ( $args['description']) {
					
					$output .= "<div class='jr-insta-wrap'>\n";

						$output .= "<div class='jr-insta-datacontainer'>\n";
							
							if ( $caption != '') {
								//$caption   = preg_replace( '/@([a-z0-9_]+)/i', '&nbsp;<a href="http://healthruwords.com/$1" rel="nofollow" target="_blank">@$1</a>&nbsp;', $caption );
								$output .= "<span class='jr-insta-caption'><a href='" . $link_to . "' target='_blank' class='".$args
								['image_link_class']."' title='".$seo."' alt='".$seo."'>{$caption}</a><a href='".$logo_link_to."' title='".$seo."' alt='".$seo."' target='_blank'><img src='https://healthruwords.com/wp-content/uploads/2014/10/logo-healthruwords.com_.png' width='155' height='35' style='float:right;' title='".$seo."' alt='".$seo."'></a></span>\n";
							}

						$output .= "</div>\n";

					$output .= "</div>\n";
				}
			
			$output .= "</li>";
		
		// Template : Thumbnails no text	
		} elseif ( $template == 'thumbs' ) {

			$output .= "<li>";
			$output .= $image_output;
			$output .= "</li>";

		} else {

			$output .= 'This template does not exist!';
		}

		return $output;
	}	
	
	/**
	 * Stores the fetched data from healthruwords in WordPress DB using transients
	 *	 
	 * @param    string    $username    	healthruwords Username to fetch images from
	 * @param    string    $cache_hours     Cache hours for transient
	 * @param    string    $nr_images    	Nr of images to fetch from healthruwords		  	 
	 *
	 * @return array of localy saved healthruwords data
	 */
	private function healthruwords_data( $username, $nr_images, $attachment,$topic_list) {
		
		$opt_name  = 'healthru_' . md5( $this->id );
		//delete_transient($opt_name);
		$instaData = get_transient( $opt_name );
		$user_opt  = (array) get_option( $opt_name );
		
		//if ( false === $instaData || $user_opt['username'] != $username || $user_opt['cache_hours'] != $cache_hours || $user_opt['nr_images'] != $nr_images || $user_opt['attachment'] != $attachment ) {
		if ($instaData===false) {
			
			$instaData = array();
			$curl = new Curl();
			
			$query=array('maxR'=>$nr_images,'size'=>'medium');
			if(!empty($topic_list))
				$query['t']=implode(',', $topic_list);
			$query['c']=$this->id;
			$curl->get("http://healthruwords.com/api/v1/quotes/?".http_build_query($query));
			$curl->close();
			$response = $curl->response;
			if ( is_wp_error( $response ) ) {

				return $response->get_error_message();
			}
			
			if(empty($response)){

				return $response['message'];
			}
			
			update_option( $opt_name, $user_opt );
			
			if ( is_array( $response ) && !empty( $response )  ) {

				set_transient( $opt_name, $response, CACHE_INTERVAL_HEALTHRU * 60 * 60 );
			}
			
		}
		else{
			//print_r($instaData);
			$response=$instaData;
		}
		
		return $response;
	}

	
	
	/**
	 * Sort Function for timestamp Ascending
	 */
	public function sort_timestamp_ASC( $a, $b ) {
		return $a['timestamp'] > $b['timestamp'];
	}

	/**
	 * Sort Function for timestamp Descending
	 */
	public function sort_timestamp_DESC( $a, $b ) {
		return $a['timestamp'] < $b['timestamp'];
	}

	/**
	 * Sort Function for popularity Ascending
	 */
	public function sort_popularity_ASC( $a, $b ) {
		return $a['popularity'] > $b['popularity'];
	}

	/**
	 * Sort Function for popularity Descending
	 */
	public function sort_popularity_DESC( $a, $b ) {
		return $a['popularity'] < $b['popularity'];
	}

	

	/**
	 * Sanitize 4-byte UTF8 chars; no full utf8mb4 support in drupal7+mysql stack.
	 * This solution runs in O(n) time BUT assumes that all incoming input is
	 * strictly UTF8.
	 *
	 * @param    string    $input 		The input to be sanitised
	 *
	 * @return the sanitized input
	 */
	private function sanitize( $input ) {
				
		if ( !empty( $input ) ) {
			$utf8_2byte       = 0xC0 /*1100 0000*/ ;
			$utf8_2byte_bmask = 0xE0 /*1110 0000*/ ;
			$utf8_3byte       = 0xE0 /*1110 0000*/ ;
			$utf8_3byte_bmask = 0XF0 /*1111 0000*/ ;
			$utf8_4byte       = 0xF0 /*1111 0000*/ ;
			$utf8_4byte_bmask = 0xF8 /*1111 1000*/ ;
			
			$sanitized = "";
			$len       = strlen( $input );
			for ( $i = 0; $i < $len; ++$i ) {
				
				$mb_char = $input[$i]; // Potentially a multibyte sequence
				$byte    = ord( $mb_char );
				
				if ( ( $byte & $utf8_2byte_bmask ) == $utf8_2byte ) {
					$mb_char .= $input[++$i];
				} else if ( ( $byte & $utf8_3byte_bmask ) == $utf8_3byte ) {
					$mb_char .= $input[++$i];
					$mb_char .= $input[++$i];
				} else if ( ( $byte & $utf8_4byte_bmask ) == $utf8_4byte ) {
					// Replace with ? to avoid MySQL exception
					$mb_char = '';
					$i += 3;
				}
				
				$sanitized .= $mb_char;
			}
			
			$input = $sanitized;
		}
		
		return $input;
	}
	
} // end of class HealthruwordsSlider