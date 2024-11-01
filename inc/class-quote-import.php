<?php 
/**
 * Tida Quotes Import Data
 * 
 * @package		 tida-quotes 
 * @since		 1.0
 * @author		 Rasool Vahdati
 * @modified	 2024/06/28 03:16:24
 */

namespace TidaWeb\TidaQuotes;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

if(!class_exists('TidaQuotesImport'))
{
	class TidaQuotesImport
	{
	
		protected static $_instance = null;
	
		/**
		 * Get the singleton instance of the class.
		 *
		 * @return Tida_Quotes_Post_Type The singleton instance.
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
			// add submenu page
			add_action( 'admin_menu', array( &$this, 'add_import_submenu_page' ), 103 );

			// handle submission
			add_action( 'admin_init', array( &$this, 'handle_import_list' ) );

			do_action('tida_quotes_import_class_hooks');
		}

		/**
		 * Add an Import Data submenu page under the 'tida-quote' post type in the WordPress admin menu.
		 *
		 * @param void
		 * @return void
		 */
		public function add_import_submenu_page()
		{
			add_submenu_page(
				'edit.php?post_type=tida-quote', // parent_slug
				__('Import Data', 'tida-quotes'), // page_title
				__('Import Data', 'tida-quotes'), // menu_title
				'manage_options', // capability
				'tida-quote-import', // menu_slug
				array( &$this, 'import_data_page' ) // function
			);
		}

		/**
		 * Display the 'Import Data' submenu page content.
		 *
		 * @param void
		 * @return void
		 */
		public function import_data_page()
		{
			$quote_import_nonce = sanitize_text_field( $_POST['tida_quote_import_nonce'] );
			if( isset( $_GET['status'] ) && isset( $quote_import_nonce ) && wp_verify_nonce( $quote_import_nonce, 'tida_quote_import_nonce' ) )
			{
				if( $_GET['status'] )
				{
					$notice = __( 'Data imported successfully!', 'tida-quotes' );
					add_settings_error( 'tida-quote-import', 'import-success', $notice, 'updated' );
				}
				else
				{
					$notice = __( 'There was an error importing the data.', 'tida-quotes' );
					add_settings_error( 'tida-quote-import', 'import-error', $notice, 'error' );
				}
			}

			// Display notices from transient
			settings_errors( 'tida-quote-import' );
			
			?>
			<div class="wrap">
				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
				<form method="post" action="">
					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row"><label for="import-option"><?php esc_attr_e('Import List', 'tida-quotes'); ?></label></th>
								<td>
									<select id="import-option" name="import_option">
										<option value=""><?php esc_attr_e('Select an option...', 'tida-quotes'); ?></option>
										<?php if( !empty( $this->get_import_data_lists() ) ) : ?>
										<?php foreach($this->get_import_data_lists() as $key => $import_list_item) : ?>
										<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $import_list_item ); ?></option>
										<?php endforeach; ?>
										<?php endif; ?>
									</select>
									<p class="description"><?php esc_attr_e('Choose an option to import the sentences. Note that each option will only be available in the language enclosed in parentheses.', 'tida-quotes'); ?></p>
								</td>
							</tr>
						</tbody>
					</table>
					<p class="submit">
						<?php wp_nonce_field('tida_quote_import_nonce', 'tida_quote_import_nonce'); ?>
						<?php echo esc_attr( submit_button( __('Import', 'tida-quotes') ) ); ?>
					</p>
				</form>
			</div>
			<?php
		}

		/**
		 * Handle the data import process when the 'Import' button is submitted on the 'Import Data' page.
		 *
		 * @param void
		 * @return void
		 */
		public function handle_import_list()
		{
			$status = 0;
			if( isset($_GET['page'] ) && $_GET['page'] == 'tida-quote-import')
			{
				$tida_quote_import_nonce = sanitize_text_field( $_POST['tida_quote_import_nonce'] );
				if ( isset( $_POST['submit'] ) && isset( $tida_quote_import_nonce ) && wp_verify_nonce( $tida_quote_import_nonce, 'tida_quote_import_nonce' ) ) {
					$import_option = sanitize_text_field( $_POST['import_option'] );
					$import_lists = $this->get_import_data_lists();
					if( in_array( $import_option , array_keys( $import_lists ) ) )
					{
						$result = $this->import_data( $import_option );
						if( $result )
						{
							$status = 1;
						}
					}
	
					// redirect to page
					wp_redirect( add_query_arg( array( 'post_type' => 'tida-quote' , 'page' => 'tida-quote-import', 'status' => $status, 'tida_quote_import_nonce' => wp_create_nonce('tida_quote_import_nonce') ), admin_url('edit.php') ) );
					exit;
				}
			}
		}

		/**
		 * Get the list of import options with their corresponding labels.
		 *
		 * @param void
		 * @return array An associative array of import options where the key is the option name and the value is the option label.
		 */
		public function get_import_data_lists()
		{
			$import_list = array(
				'hello-dolly' => __('Hello Dolly (English) - By Matt Mullenweg', 'tida-quotes'),
				'words-elders-1' => __('Words of Elders 1 (Persian) - By Rasool Vahdati', 'tida-quotes'),
				'words-elders-2' => __('Words of Elders 2 (Persian) - By Rasool Vahdati', 'tida-quotes')
			);

			return apply_filters( 'tida_quotes_import_data_lists', $import_list );
		}

		/**
		 * Import data based on the selected import option.
		 *
		 * @param string $import_option The selected import option.
		 * @return bool Returns true if the data import is successful, false otherwise.
		 */
		public function import_data( $import_option )
		{
			$status = false;

			// Check if the selected import option is valid and available in the import data lists.
			$import_lists = $this->get_import_data_lists();
			if( in_array( $import_option , array_keys( $import_lists ) ) )
			{
				// Based on the selected import option, fetch the quote title and content.
				switch( $import_option )
				{
					case 'hello-dolly':
						$quote_title = __('Hello Dolly (English) - By Matt Mullenweg', 'tida-quotes');
						$quote_content = $this->get_hello_dolly_lyrics();
						break;
					case 'words-elders-1':
						$quote_title = __('Words of Elders 1 (Persian) - By Rasool Vahdati', 'tida-quotes');
						$quote_content = $this->get_persian_words_elders_1();
						break;
					case 'words-elders-2':
						$quote_title = __('Words of Elders 2 (Persian) - By Rasool Vahdati', 'tida-quotes');
						$file = TIDAQUOTES_PLUGIN_DIR . 'data/elders-sentences.txt';
						$content = $this->get_data_from_file( $file );
						if( !empty( $content ) )
							$quote_content = implode("", $content );
						break;

					do_action( 'tida_quotes_import_data_condition' ); // This line should be placed outside the switch statement.
				}

				// Check if both the quote title and content are not empty before inserting the new post.
				if( !empty( $quote_title ) && !empty( $quote_content ) )
				{
					$new_id = wp_insert_post( array( 
						'post_type' => 'tida-quote',
						'post_title' => $quote_title,
						'post_author' => get_current_user_id(),
						'post_content' => $quote_content,
						'post_status' => 'publish'
					) );

					// Set the status based on the success of the post insertion.
					if( $new_id > 0 )
						$status = true;
				}
				
			}

			return $status;
		}

		/**
		 * Read data from a file and return an array containing each line as an element.
		 *
		 * @param string $file The file path to read data from.
		 * @return array An array containing each line of the file as an element.
		 */
		public function get_data_from_file( $file )
		{
			$response = wp_remote_get( $file ); // Use WordPress HTTP API to get content from a remote file

			if ( ! is_wp_error( $response ) ) {
				$body = wp_remote_retrieve_body( $response );
				$lines = explode( "\n", $body );

				// Return the array of lines fetched from the remote file
				return $lines;
			}

			// Return an empty array if there was an error fetching the remote file
			return array();
		}

		/**
		 * Get the lyrics of the song "Hello Dolly" in English.
		 *
		 * @return string The lyrics of the song "Hello Dolly" in English.
		 */
		public function get_hello_dolly_lyrics()
		{
			$lyrics = "Hello, Dolly
Well, hello, Dolly
It's so nice to have you back where you belong
You're lookin' swell, Dolly
I can tell, Dolly
You're still glowin', you're still crowin'
You're still goin' strong
I feel the room swayin'
While the band's playin'
One of our old favorite songs from way back when
So, take her wrap, fellas
Dolly, never go away again
Hello, Dolly
Well, hello, Dolly
It's so nice to have you back where you belong
You're lookin' swell, Dolly
I can tell, Dolly
You're still glowin', you're still crowin'
You're still goin' strong
I feel the room swayin'
While the band's playin'
One of our old favorite songs from way back when
So, golly, gee, fellas
Have a little faith in me, fellas
Dolly, never go away
Promise, you'll never go away
Dolly'll never go away again";

			return $lyrics;
		}

		/**
		 * Get a collection of Persian words of wisdom from elders.
		 *
		 * @return string The collection of Persian words of wisdom from elders.
		 */
		public function get_persian_words_elders_1()
		{
			$words_elders = "زندگی سراسر تجربه است. هر چه بیشتر تجربه کنید، بهتر است . وینستون چرچیل
شعله های بزرگ ناشی از جرقه‌ای کوچک است. دانته
کسی که به پشتکار خود اعتماد دارد، ارزشی برای شانس قائل نیست. ژاپنی
لیاقت انسانها کیفیت زندگی را تعیین می کند نه آرزوهایشان. مهاتما گاندی
گفتگو با آدمیان ترسو، خواری دنبال دارد. حکیم ارد بزرگ
زندگی سفری جسورانه است و دیگر هیچ. فرانکلین روزولت
علت هر شکستی، عمل کردن بدون فکر است. الکساندر مکنزی
موفقیت، مساوی با رسیدن به هدف نیست، بلکه خود سفر است. جان ماکسول
بزرگترین حادثه ی زندگی، زیستن رویاهایت است. مایکل جوردن
موفقیت بر روی ستون های شکست شکل می گیرد. سری چتری
در جستجوی نور باش، نور را می یابی. آرنت
محترم بودن نتیجه یک عمر لیاقت اندوختن است. مادام دامبر
زندگی کشف خود نیست، ساختن خود است. لیو بوسکالیا
بدترین و خطرناک ترین کلمه این است: همه این جورند. تولستوی
باید به فریب حواس خود پیروز شویم. فریدریش نیچه
تنها ناتوانی در زندگی نگرش و رفتار بد و منفی است. ویلیام جیمز
از پیروزی تا شکست فقط یک قدم فاصله است. ناپلئون بناپارت
وقتی امید داشته باشید، هر چیزی ممکن می‌شود. کریستوفر ریو
انسان همان چیزی است که باور دارد. آنتوان چخوف
انسان‌ها با باور رویاهاشون به آنها رنگ واقعیت می‌زنند. هرژه
حرص و طمع مایه نگرانی و پریشانی خاطر است. بزرگمهر
برای آنکه عمر طولانی باشد، باید آهسته زندگی کنیم. سیسرون
حیات آدمی در دنیا همچون حبابی است در سطح دریا. جان راسکن
هر روز، روزی پر اهمیت است. فلورانس اسکاول شین
خوش بین باشید اما خوش بین دیر باور. ساموئل اسمایلز
هر کس باید طوری زندگی کند که الگویی برای سایرین باشد. بیلی گراهام
اگر ما از زیاده خواهی چشم پوشی کنیم جنگ هم تکرار نخواهد شد. کورت توخلسکی
ارزش مرد به ارزشی است که برای وقت خود قائل می‌شود. آلبرت هوبارد
هیچ کس نمی تواند ما را بهتر از خودمان فریب دهد. گوته
طبیعت هنر خداوند است. دانته
تغییری باش که در جهان می خواهی . گاندی
بکوش تا عظمت در نگاه تو باشد نه در آنچه می نگری. آندره ژید
وجدان صدای خداوندی است. لامارتین
تنها گنجی که ارزش جستجو کردن دارد، هدف است. پاستور
آفرینش آینده وظیفه ماست. چارلز هندی
من تنها یک چیز می دانم و آن اینکه هیچ نمی دانم. سقراط
			";

			return $words_elders;
		}

	} // END class TidaQuotesImport
} // END if( ! class_exists('TidaQuotesImport') )

// instantiate the plugin class
TidaQuotesImport::instance();