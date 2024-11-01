<?php 
/**
 * Tida Quotes Post Type
 * 
 * @package		 tida-quotes
 * @since		 1.0
 * @author		 Akbar Doosti
 * @modified	 2024/03/29 05:10:52
 */

namespace TidaWeb\TidaQuotes;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

if(!class_exists('TidaQuotesPostType'))
{
	class TidaQuotesPostType
	{
	
		protected static $_instance = null;
	
		/**
		 * Get the singleton instance of the class.
		 *
		 * @return TidaQuotesPostType The singleton instance.
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
			// create tida quote post type
			add_action( 'init', array( $this, 'create_post_type' ) );

			// show tida quote post
			add_action( 'admin_notices', array( $this, 'show_tida_quote' ) );
			add_action( 'admin_footer', array( $this, 'tida_quote_css' ) );

			do_action('tida_quotes_post_type_class_hooks');

		}
	
		/**
		 * Register the quotes post type.
		 *
		 * @return void
		 */
		public function create_post_type()
		{
			$labels = array(
				'name'                  => esc_html__( 'Quotes', 'tida-quotes' ),
				'singular_name'         => esc_html__( 'Qoute', 'tida-quotes' ),
				'menu_name'             => esc_html__( 'Quotes', 'tida-quotes' ),
				'name_admin_bar'        => esc_html__( 'Quote', 'tida-quotes' ),
				'archives'              => esc_html__( 'Quote Archives', 'tida-quotes' ),
				'attributes'            => esc_html__( 'Quote Attributes', 'tida-quotes' ),
				'parent_item_colon'     => esc_html__( 'Parent Quote:', 'tida-quotes' ),
				'all_items'             => esc_html__( 'All Quotes', 'tida-quotes' ),
				'add_new_item'          => esc_html__( 'Add New Quote', 'tida-quotes' ),
				'add_new'               => esc_html__( 'Add New Quote', 'tida-quotes' ),
				'new_item'              => esc_html__( 'New Quote', 'tida-quotes' ),
				'edit_item'             => esc_html__( 'Edit Quote', 'tida-quotes' ),
				'update_item'           => esc_html__( 'Update Quote', 'tida-quotes' ),
				'view_item'             => esc_html__( 'View Quote', 'tida-quotes' ),
				'view_items'            => esc_html__( 'View Quotes', 'tida-quotes' ),
				'search_items'          => esc_html__( 'Search Quote', 'tida-quotes' ),
				'not_found'             => esc_html__( 'Not found', 'tida-quotes' ),
				'not_found_in_trash'    => esc_html__( 'Not found in trash', 'tida-quotes' ),
				'featured_image'        => esc_html__( 'Featured Image', 'tida-quotes' ),
				'set_featured_image'    => esc_html__( 'Set featured image', 'tida-quotes' ),
				'remove_featured_image' => esc_html__( 'Remove featured image', 'tida-quotes' ),
				'use_featured_image'    => esc_html__( 'Use as featured image', 'tida-quotes' ),
				'insert_into_item'      => esc_html__( 'Add to Quote', 'tida-quotes' ),
				'uploaded_to_this_item' => esc_html__( 'Uploaded to this Quote', 'tida-quotes' ),
				'items_list'            => esc_html__( 'Quotes list', 'tida-quotes' ),
				'items_list_navigation' => esc_html__( 'Quotes list navigation', 'tida-quotes' ),
				'filter_items_list'     => esc_html__( 'Filter Quotes list', 'tida-quotes' ),
			);
	
			$args = array(
				'label'               => esc_html__( 'Quote', 'tida-quotes' ),
				'description'         => '',
				'labels'              => $labels,
				'menu_icon'           => 'dashicons-format-quote',
				'supports'            => array( 'title', 'author' ),
				'taxonomies'          => array(),
				'public'              => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'menu_position'       => 5,
				'show_in_admin_bar'   => true,
				'show_in_nav_menus'   => false,
				'can_export'          => true,
				'has_archive'         => false,
				'hierarchical'        => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				'capability_type'     => 'post',
				'show_in_rest'        => false,
			);
	
			register_post_type( 'tida-quote', apply_filters( 'tida_quotes_create_post_type_args_filter', $args ) );
		}

		/**
		 * Get the lyrics of the song "Hello Dolly" in English.
		 *
		 * @return string The lyrics of the song "Hello Dolly" in English.
		 */
		public function show_tida_quote()
		{
			$chosen_quote = $this->get_enabled_tida_quote();

			if( !empty( $chosen_quote ) )
			{
				$lang   = '';
				if ( 'en_' !== substr( get_user_locale(), 0, 3 ) ) {
					$lang = ' lang="en"';
				}
	
				printf(
					'<p id="tida-quote"><span dir="%s"%s>%s</span></p>',
					(is_rtl()) ? 'rtl' : 'ltr',
					esc_attr( $lang ),
					esc_attr( $chosen_quote )
				);
			}
		}

		/**
		 * Retrieve an enabled TIDA quote.
		 *
		 * @return string The enabled TIDA quote.
		 */
		public function get_enabled_tida_quote()
		{
			$quotes_list = array();
			$chosen_quote = '';
			$enabled_tida_quote = get_option('tida_quotes_enable_post_id');
			if( !empty( $enabled_tida_quote ) )
			{
				$tida_quote_info = get_post( absint( $enabled_tida_quote ) );
				if( !empty( $tida_quote_info ) && $tida_quote_info->ID > 0 )
				{
					// get content of quote
					$quotes = esc_attr( $tida_quote_info->post_content );

					// Here we split it into lines.
					$quotes_list = explode( "\n", $quotes );

				}
			}
			
			if( !empty( $quotes_list ) )
			    $chosen_quote = wptexturize( $quotes_list[ wp_rand( 0, count( $quotes_list ) - 1 ) ] );
			
			// And then randomly choose a line.
			return $chosen_quote;
		}

		/**
		 * Output CSS styles for the TIDA quote.
		 *
		 * @return void
		 */
		public function tida_quote_css()
		{
			?>
			<style type='text/css'>
			#tida-quote {
				float: right;
				padding: 5px 10px;
				margin: 0;
				font-size: 12px;
				line-height: 1.6666;
			}
			.rtl #tida-quote {
				float: left;
			}
			.block-editor-page #tida-quote {
				display: none;
			}
			@media screen and (max-width: 782px) {
				#tida-quote,
				.rtl #tida-quote {
					float: none;
					padding-left: 0;
					padding-right: 0;
				}
			}
			</style>
			<?php
		}
	
	} // END class TidaQuotesPostType
} // END if( ! class_exists('TidaQuotesPostType') )

// instantiate the plugin class
TidaQuotesPostType::instance();