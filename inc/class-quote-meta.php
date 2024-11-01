<?php 
/**
 * Tida Quotes Meta
 * 
 * @package		 tida-quotes 
 * @since		 1.0
 * @autor		 Rasool Vahdati
 * @modified	 2024/07/10 00:14:06
 */

namespace TidaWeb\TidaQuotes;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

if(!class_exists('TidaQuotesMeta'))
{
	class TidaQuotesMeta
	{
	
		protected static $_instance = null;
	
		/**
		 * Get the singleton instance of the class.
		 *
		 * @return TidaQuotesMeta The singleton instance.
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
	
			return self::$_instance;
		}
	
		/**
		 * Constructor of class
		 * 
		 * @return void
		 */
		public function __construct()
		{
			// add enable field metabox
			add_action( 'add_meta_boxes', array( &$this, 'enable_field_add_metabox' ) );
			add_action( 'save_post', array( &$this, 'enable_field_save_metabox' ) );

			// add enable field columns in quotes list
			add_filter( 'manage_tida-quote_posts_columns', array( &$this, 'enable_field_posts_columns' ) );
			add_action( 'manage_tida-quote_posts_custom_column', array( &$this, 'enable_field_posts_column_content' ), 10, 2 );

			// enqueue scripts and styles
			add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_scripts_styles' ) );

			// add enable ajax handler for quotes list
			add_action( 'wp_ajax_change_quote_enable_status', array( &$this, 'ajax_change_quote_enable_status' ) );

			// add quotes field metabox
			add_action( 'add_meta_boxes', array( &$this, 'quotes_field_add_metabox' ) );

			do_action('tida_quotes_meta_class_hooks');
		}

		/**
		 * Adds a enable metabox to the 'tida-quote' post type.
		 * 
		 * @return void
		 */
		public function enable_field_add_metabox()
		{
			$screens = array( 'tida-quote' );
			foreach ( $screens as $screen ) {
				add_meta_box(
					'enabled_field_section',
					__('Enable/Disable', 'tida-quotes'),
					array( &$this, 'enable_field_metabox_callback'),
					$screen,
					'side',
					'default'
				);
			}
		}

		/**
		 * Renders the enable field metabox.
		 *
		 * @param WP_Post $post The current post object.
		 */
		public function enable_field_metabox_callback( $post )
		{
			// get exchange meta fields
			wp_nonce_field( 'enable_field_meta_nonce', 'enable_field_meta_nonce' );
			$tida_quote_enable = get_option( 'tida_quotes_enable_post_id' );
			
			?>
			<div class="switch-section">
				<label class="switch">
					<input type="checkbox" name="tida_quote_enable" class="tida-quote-enable" data-post="<?php echo esc_attr( $post->ID ); ?>" <?php if( isset( $tida_quote_enable ) && !empty( $tida_quote_enable ) && $tida_quote_enable == $post->ID ) echo 'checked="checked"';  ?>>
					<span class="slider round"></span>
				</label>
			</div>
			<?php
		}

		/**
		 * Saves the value of the 'tida_quote_enable' meta field for a 'tida-quote' post.
		 *
		 * @global int $post_id The ID of the post being saved.
		 */
		public function enable_field_save_metabox( $post_id )
		{
			// Check if our nonce is set and validate it.
			if( !isset( $_POST['enable_field_meta_nonce'] ) || ( isset( $_POST['enable_field_meta_nonce'] ) && !wp_verify_nonce( sanitize_text_field( $_POST['enable_field_meta_nonce'] ), 'enable_field_meta_nonce' ) ) )
			{
				return;
			}

			// Check the user's permissions to ensure they can edit the post
			if (!current_user_can('edit_post', $post_id)) {
				return;
			}
		
			// If this is an autosave, our form has not been submitted, so we don't want to do anything.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}
		
			// Check the user's permissions.
			if ( isset( $_POST['post_type'] ) && 'tida-quote' == sanitize_text_field( $_POST['post_type'] ) ) {
		
				if ( ! current_user_can( 'edit_page', $post_id ) ) {
					return;
				}
		
			} else {
		
				if ( ! current_user_can( 'edit_post', $post_id ) ) {
					return;
				}
			}

			// Update the meta field in the database.
			if( isset( $_POST['tida_quote_enable'] ) && !empty( $_POST['tida_quote_enable'] ) )
				update_option( 'tida_quotes_enable_post_id', $post_id , false );

		}
		
		/**
         * Deletes the 'tida_quotes_enable_post_id' option from the WordPress options table.
         *
         * @param void This function doesn't accept any parameters.
         * @return void This function doesn't return a value.
         */
		public static function delete_meta_option()
		{
		    delete_option( 'tida_quotes_enable_post_id' );
		}

		/**
		 * Adds a enable status column to the posts list that displays a custom post meta.
		 *
		 * @param array $columns The array of column names and labels for the posts list.
		 * @return array The modified array of column names and labels.
		 */
		public function enable_field_posts_columns( $columns )
		{
			// Add a new column to the posts list with the label 'My Meta'
			$columns['tida_quote_enable_status'] = __('Enable/Disable', 'tida-quotes');
		
			// Return the modified columns array
			return $columns;
		}

		/**
		 * Displays the value of a enable status metabox in the enable status column.
		 *
		 * @param string $column_name The name of the current column.
		 * @param int $post_id The ID of the current post.
		 */
		public function enable_field_posts_column_content( $column_name, $post_id )
		{
			// Check if the current column is the custom column 'my_meta'
			if ( $column_name == 'tida_quote_enable_status' ) {
				// Retrieve the value of the custom post meta with the key 'my_meta_key'
				$tida_quote_enable = get_option( 'tida_quotes_enable_post_id' );
		
				// Display the value of the custom post meta
				?>
				<div class="switch-section">
					<label class="switch">
						<input type="checkbox" name="tida_quote_enable" class="tida-quote-enable" data-post="<?php echo esc_attr( $post_id ); ?>" <?php if( isset( $tida_quote_enable ) && !empty( $tida_quote_enable ) && $tida_quote_enable == $post_id ) echo 'checked="checked"';  ?>>
						<span class="slider round"></span>
					</label>
				</div>
				<?php
			}
		}

		/**
		 * Enqueues scripts and styles for the admin.
		 *
		 * @global string $post_type The current post type.
		 * @global string $pagenow The current page.
		 */
		public function enqueue_scripts_styles($hook) {
			global $post_type;
			if ( $hook == 'edit.php' && $post_type == 'tida-quote' ) {
				wp_register_script( 'tida-quotes-admin-script', TIDAQUOTES_PLUGIN_URL .'assets/js/tida-quotes-admin-script.js' , array( 'jquery' ), null, true );
				wp_localize_script( 'tida-quotes-admin-script', 'tidaquotes_params', array(
				    'ajaxurl' => admin_url('admin-ajax.php'),
				    'enable_field_nonce' => esc_attr( wp_create_nonce( 'enable_field_meta_nonce' ) )
				));
				wp_enqueue_script( 'tida-quotes-admin-script' );

				wp_register_style( 'tida-quotes-admin-style', TIDAQUOTES_PLUGIN_URL .'assets/css/tida-quotes-admin-style.css' , array(), null );
				wp_enqueue_style( 'tida-quotes-admin-style' );
			}
		}

		/**
		 * AJAX callback to change the enable status of a quote.
		 *
		 * @return void
		 */
		public function ajax_change_quote_enable_status()
		{
			$data = array();
			// check nonce
			check_ajax_referer( 'enable_field_meta_nonce', 'nonce_key' );

			if( isset( $_POST['post_id'] ) && !empty( $_POST['post_id'] ) )
			{
				$post_info = get_post( absint( $_POST['post_id'] ) );
				if( !empty( $post_info ) && $post_info->ID > 0 )
				{
					//wp_send_json_error( $_POST );
					if( isset( $_POST['enabled'] ) )
					{
						if( $_POST['enabled'] )
						{
							// update option
							update_option( 'tida_quotes_enable_post_id', $post_info->ID , false );
							wp_send_json_success( __('The enable status of the post was updated.', 'tida-quotes') );
						}
						else if( $_POST['enabled'] == 0 )
						{
							// update option
							update_option( 'tida_quotes_enable_post_id', '' , false );
							wp_send_json_success( __('The enable status of the post was updated.', 'tida-quotes') );
						}
						else
						{
							wp_send_json_error( __('The enabled type of post is invalid.', 'tida-quotes') );
						}
					}
					else
					{
						wp_send_json_error( __('The enabled type of post does not exist.', 'tida-quotes') );
					}
				}
				else
				{
					wp_send_json_error( __('The Post ID does not exist.', 'tida-quotes') );
				}
			}
			else
			{
				wp_send_json_error( __('The Post ID does not exist.', 'tida-quotes') );
			}
		}

		/**
		 * Adds a quotes metabox to the 'tida-quote' post type.
		 * 
		 * @return void
		 */
		public function quotes_field_add_metabox()
		{
			$screens = array( 'tida-quote' );
			foreach ( $screens as $screen ) {
				add_meta_box(
					'quotes_field_section',
					__('Quotes', 'tida-quotes'),
					array( &$this, 'quotes_field_metabox_callback'),
					$screen,
					'normal',
					'default'
				);
			}
		}

		/**
		 * Renders the quotes field metabox.
		 *
		 * @param WP_Post $post The current post object.
		 */
		public function quotes_field_metabox_callback( $post )
		{
			// get exchange meta fields
			wp_nonce_field( 'quotes_field_meta_nonce', 'quotes_field_meta_nonce' );
			$quotes_content = $post->post_content;
			?>
			<textarea class="quotes-content" name="content" id="quotes-content" cols="100%" rows="8" placeholder="<?php esc_attr_e('Seperate expressions with enter.', 'tida-quotes'); ?>"><?php echo esc_attr( $quotes_content ); ?></textarea>
			<?php
		}
	} // END class TidaQuotesMeta
} // END if( ! class_exists('TidaQuotesMeta') )

// instantiate the plugin class
TidaQuotesMeta::instance();