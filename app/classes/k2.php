<?php
// Prevent users from directly loading this class file
defined('K2_CURRENT') or die ( __('Error: This file can not be loaded directly.', 'k2') );

/**
 * K2 - Main class
 *
 * @package K2
 */
class K2 {

	/**
	 * Initializes K2
	 *
	 * @uses do_action() Provides 'k2_init' action
	 */
	function init() {
		// Load required classes and includes
		include_once(TEMPLATEPATH . '/app/includes/wp-compat.php');
		require_once(TEMPLATEPATH . '/app/classes/archive.php');
		require_once(TEMPLATEPATH . '/app/includes/info.php');
		require_once(TEMPLATEPATH . '/app/includes/display.php');

		if ( class_exists('WP_Widget') ) // WP 2.8+
			require_once(TEMPLATEPATH . '/app/includes/widgets.php');

/*
		if ( defined('K2_STYLES') and K2_STYLES == true )
			require_once(TEMPLATEPATH . '/app/classes/styles.php');
*/

		if ( defined('K2_HEADERS') and K2_HEADERS == true )
			require_once(TEMPLATEPATH . '/app/classes/header.php');

		// Check installed version, upgrade if needed
		$k2version = get_option('k2version');

		if ( $k2version === false )
			K2::install();
		elseif ( version_compare($k2version, K2_CURRENT, '<') )
			K2::upgrade($k2version);

		// Register our scripts with script loader
		K2::register_scripts();

		// There may be some things we need to do before K2 is initialised
		// Let's do them now
		do_action('k2_init');

		// Finally load pluggable & deprecated functions
		require_once(TEMPLATEPATH . '/app/includes/pluggable.php');
		include_once(TEMPLATEPATH . '/app/includes/deprecated.php');

		// Register our sidebars with widgets
		k2_register_sidebars();
		
		// Register the fact that K2 supports post-thumbnails
		if ( function_exists( 'add_theme_support' ) )
			add_theme_support( 'post-thumbnails' );

		// Only load Custom Background if GD is installed and WP supports it.
		if ( function_exists('add_custom_background') && extension_loaded('gd') && function_exists('gd_info') ) {
			add_custom_background();
		}

		// Automatically output feed links. Requires WP 2.8+
		automatic_feed_links();
	}


	/**
	 * Starts the installation process
	 *
	 * @uses do_action() Provides 'k2_install' action
	 */
	function install() {
		add_option('k2version', K2_CURRENT, 'This option stores K2\'s version number');

		add_option('k2style', '1', "Load the classic K2 style.");

		add_option('k2livesearch', '1', "If you don't trust JavaScript and Ajax, you can turn off LiveSearch. Otherwise I suggest you leave it on"); // (live & classic)
		add_option('k2rollingarchives', '1', "If you don't trust JavaScript and Ajax, you can turn off Rolling Archives. Otherwise it is suggested you leave it on");
		add_option('k2animations', '1', 'JavaScript Animation effects.');
		$defaultjs = "// Lightbox v2.03.3 - Adds new images to lightbox\nif (typeof myLightbox != 'undefined' && myLightbox instanceof Lightbox && myLightbox.updateImageList) {\n\tmyLightbox.updateImageList();\n}\n";
		add_option('k2ajaxdonejs', $defaultjs, 'JavaScript to execute when Ajax is completed');

		add_option('k2archives', '0', 'Set whether K2 has an archives page');

		add_option('k2asidescategory', '0', 'A category which will be treated differently from other categories');

		add_option('k2entrymeta1', __('Published by %author% on %date% in %categories%. %comments% %tags%', 'k2'), 'Customized metadata format before entry content.');
		add_option('k2entrymeta2', '', 'Customized metadata format after entry content.');

		// Call the install handlers
		do_action('k2_install');
	}


	/**
	 * Starts the upgrade process
	 *
	 * @uses do_action() Provides 'k2_upgrade' action
	 * @param string $previous Previous version K2
	 */
	function upgrade($previous) {
		// Install options
		K2::install();

		// Call the upgrade handlers
		do_action('k2_upgrade', $previous);

		// Update the version
		update_option('k2version', K2_CURRENT);

		// Clean-up deprecated options
		delete_option('k2sidebarmanager');
		delete_option('k2sbm_modules');
		delete_option('k2sidebarmanager');
	}


	/**
	 * Removes K2 options
	 *
	 * @uses do_action() Provides 'k2_uninstall' action
	 */
	function uninstall() {
		// Delete options
		delete_option('k2style');
		delete_option('k2version');
		delete_option('k2asidescategory');
		delete_option('k2livesearch');
		delete_option('k2rollingarchives');
		delete_option('k2archives');
		delete_option('k2entrymeta1');
		delete_option('k2entrymeta2');
		delete_option('k2animations');
		delete_option('k2ajaxdonejs');

		// Call the uninstall handlers
		do_action('k2_uninstall');
	}


	/**
	 * Restores K2 to default settings
	 */
	function restore_defaults() {
		K2::uninstall();
		K2::install();
	}


	/**
	 * 
	 */
	function admin_init() {
		// Inside K2 Options page
		if ( isset($_GET['page']) and ('k2-options' == $_GET['page']) and isset($_REQUEST['k2-options-submit']) ) {
			check_admin_referer('k2options');

			// Reset K2
			if ( isset($_REQUEST['restore-defaults']) ) {
				K2::restore_defaults();
				wp_redirect('themes.php?page=k2-options&defaults=true');
				die;

			// Reset Sidebars
			} elseif ( isset($_REQUEST['default-widgets']) ) {
				k2_default_widgets();
				wp_redirect('themes.php?page=k2-options&widgets=true');
				die;

				// Save Settings
			} elseif ( isset($_REQUEST['save']) and isset($_REQUEST['k2']) ) {
				K2::update_options();
				wp_redirect('themes.php?page=k2-options&saved=true');
				die;
			}
		}
	}


	/**
	 * Adds K2 Options to Appearance menu, adds actions for head and scripts
	 */
	function add_options_menu() {
		$page = add_theme_page( __('K2 Options', 'k2'), __('K2 Options', 'k2'), 'edit_themes', 'k2-options', array('K2', 'admin') );

		add_action( "admin_head-$page", array('K2', 'admin_head') );
		add_action( "admin_print_scripts-$page", array('K2', 'admin_print_scripts') );

		if ( function_exists('add_contextual_help') ) {
			add_contextual_help($page,
				'<a href="http://groups.google.com/group/k2-support/">' .  __('K2 Support Group', 'k2') . '</a><br />' .
				'<a href="http://code.google.com/p/kaytwo/issues/list">' .  __('K2 Bug Tracker', 'k2') . '</a><br />'
				);
		}
	}


	/**
	 * Displays K2 Options page
	 */
	function admin() {
		include(TEMPLATEPATH . '/app/display/options.php');
	}


	/**
	 * Displays content in HEAD tag. Called by action: admin_head
	 */
	function admin_head() {
		?>
		<script type="text/javascript" charset="utf-8">
		//<![CDATA[
			var defaults_prompt = "<?php _e('Do you want to restore K2 to default settings? This will remove all your K2 settings.', 'k2'); ?>";
		//]]>
		</script>
		<link type="text/css" rel="stylesheet" href="<?php bloginfo('template_url'); ?>/css/options.css" />
	<?php
	}


	/**
	 * Adds default K2 CSS to header if enabled in options. Called by action: k2css_head
	 */
	function k2css_head() {
		?>
		<link type="text/css" rel="stylesheet" href="<?php bloginfo('template_url'); ?>/css/k2.css" />
	<?php
	}


	/**
	 * Enqueues scripts. Called by action: admin_print_scripts
	 */
	function admin_print_scripts() {
		// Add our script to the queue
		wp_enqueue_script('k2options');
	}

	/**
	 * Updates options
	 *
	 * @uses do_action() Provides 'k2_update_options' action
	 */
	function admin_style_visual_editor($url) {
 
		if ( !empty($url) )
			$url .= ',';
	
		// Change the path here if using sub-directory
		$url .= trailingslashit( get_stylesheet_directory_uri() ) . 'css/visualeditor.css';
	 
		return $url;
	}


	/**
	 * Updates options
	 *
	 * @uses do_action() Provides 'k2_update_options' action
	 */
	function update_options() {
		// Advanced Navigation
		if ( isset($_POST['k2']['advnav']) ) {
			update_option('k2livesearch', '1');
			update_option('k2rollingarchives', '1');
		} else {
			update_option('k2livesearch', '0');
			update_option('k2rollingarchives', '0');
		}

		// JavaScript Animations
		if ( isset($_POST['k2']['animations']) ) {
			update_option('k2animations', '1');
		} else {
			update_option('k2animations', '0');
		}

		// Use the Default K2 Style?
		if ( isset($_POST['k2']['style']) ) {
			update_option('k2style', '1');
		} else {
			update_option('k2style', '0');
		}

		// Archives Page (thanks to Michael Hampton, http://www.ioerror.us/ for the assist)
		if ( isset($_POST['k2']['archives']) ) {
			update_option('k2archives', '1');
			K2Archive::create_archive();
		} else {
			update_option('k2archives', '0');
			K2Archive::delete_archive();
		}

		// Asides
		if ( isset($_POST['k2']['asidescategory']) ) {
			update_option('k2asidescategory', (int) $_POST['k2']['asidescategory']);
		}

		// Top post meta
		if ( isset($_POST['k2']['entrymeta1']) ) {
			update_option( 'k2entrymeta1', stripslashes($_POST['k2']['entrymeta1']) );
		}

		// Bottom post meta
		if ( isset($_POST['k2']['entrymeta2']) ) {
			update_option( 'k2entrymeta2', stripslashes($_POST['k2']['entrymeta2']) );
		}

		// Ajax Success JavaScript
		if ( isset($_POST['k2']['ajaxdonejs']) ) {
			update_option( 'k2ajaxdonejs', stripslashes($_POST['k2']['ajaxdonejs']) );
		}

		// K2 Hook
		do_action('k2_update_options');
	}


	/**
	 * Adds k2dynamic into the list of query variables, used for dynamic content
	 */
	function add_custom_query_vars($query_vars) {
		$query_vars[] = 'k2dynamic';

		return $query_vars;
	}


	/**
	 * Filter to prevent redirect_canonical() from redirecting dynamic content
	 */
	function prevent_dynamic_redirect($redirect_url) {
		if ( strpos($redirect_url, 'k2dynamic=' ) !== false )
			return false;

		return $redirect_url;
	}


	/**
	 * Return the home page link, used for dynamic content
	 */
	function get_home_url() {
		if ( ('page' == get_option('show_on_front')) and ($page_id = get_option('page_for_posts')) ) {
			return get_page_link($page_id);
		}
		
		return get_bloginfo('url') . '/';
	}


	/**
	 * Handles displaying dynamic content such as LiveSearch, RollingArchives
	 *
	 * @uses do_action() Provides 'k2_dynamic_content' action
	 */
	function dynamic_content() {
		$k2dynamic = get_query_var('k2dynamic');

		if ( $k2dynamic ) {
			define('DOING_AJAX', true);

			// Send the header
			header('Content-Type: ' . get_bloginfo('html_type') . '; charset=' . get_bloginfo('charset'));

			// Include the content
			include(TEMPLATEPATH . '/app/display/theloop.php');

			// K2 Hook
			do_action('k2_dynamic_content');
			exit;
		}
	}


	/**
	 * Helper function used by RollingArchives
	 */
	function setup_rolling_archives() {
		global $wp_query;

		// Get the query
		if ( is_array($wp_query->query) )
			$rolling_query = $wp_query->query;
		elseif ( is_string($wp_query->query) )
			parse_str($wp_query->query, $rolling_query);

		// Get list of page dates
		if ( !is_page() and !is_single() )
			$page_dates = get_rolling_page_dates($wp_query);

		// Get the current page
		$rolling_page = intval( get_query_var('paged') );
		if ( $rolling_page < 1 )
			$rolling_page = 1;
		?>

			<script type="text/javascript">
			// <![CDATA[

				jQuery(document).ready(function() {
					K2.RollingArchives.setState(
						<?php echo (int) $rolling_page; ?>,
						<?php echo (int) $wp_query->max_num_pages; ?>,
						<?php echo json_encode($rolling_query); ?>,
						<?php echo json_encode($page_dates); ?>
					);
				});

			<?php if ($rolling_page > 1) { ?>
				jQuery.bbq.pushState( 'page=' + <?php echo (int) $rolling_page; ?> ); // Update the hash/fragment
			<?php } else { ?>	
				jQuery.bbq.removeState( 'page' ); // Remove the hash/fragment
			<?php } ?>	

			// ]]>
			</script>

		<?php
	}
	

	/**
	 * Register K2 scripts with WordPress' script loader
	 */
	function register_scripts() {
		// We want to use the latest version of jQuery, but it may break something in
		// the admin, so we only load it on the actual site.
        global $wp_scripts;

        if ( ( version_compare('1.4.1', $wp_scripts -> registered[jquery] -> ver) == 1 ) && !is_admin() ) :
	     	wp_deregister_script('jquery'); 
	
	     	wp_register_script('jquery',
				get_bloginfo('template_directory') . '/js/jquery.js',
				false, '1.4.1');
		endif;

		// Register our scripts with WordPress
		wp_register_script('bbq',
			get_bloginfo('template_directory') . '/js/jquery.bbq.js',
			array('jquery'), '1.1.1', true);

		wp_register_script('hoverintent',
			get_bloginfo('template_directory') . '/js/jquery.hoverintent.js',
			array('jquery'), '5');

		wp_register_script('superfish',
			get_bloginfo('template_directory') . '/js/jquery.superfish.js',
			array('jquery', 'hoverintent'), '1.4.8');

		wp_register_script('easing',
			get_bloginfo('template_directory') . '/js/jquery.easing.js',
			array('jquery'), '1.3', true);

		wp_register_script('hotkeys',
			get_bloginfo('template_directory') . '/js/jquery.hotkeys.js',
			array('jquery'), '0.7.9', true);

		wp_register_script('ui',
			get_bloginfo('template_directory') . '/js/jquery.ui.js',
			array('jquery'), '1.7.2', true);

		wp_register_script('k2functions',
			get_bloginfo('template_directory') . '/js/k2.functions.js',
			array('jquery', 'superfish'), K2_CURRENT);

		wp_register_script('k2options',
			get_bloginfo('template_directory') . '/js/k2.options.js',
			array('jquery', 'jquery-ui-sortable'), K2_CURRENT);

		wp_register_script('k2slider',
			get_bloginfo('template_directory') . '/js/k2.slider.js',
			array('jquery'), K2_CURRENT, true);

		wp_register_script('k2rollingarchives',
			get_bloginfo('template_directory') . '/js/k2.rollingarchives.js',
			array('jquery', 'bbq', 'easing', 'ui', 'k2slider', 'hotkeys'), K2_CURRENT, true);

		wp_register_script('k2livesearch',
			get_bloginfo('template_directory') . '/js/k2.livesearch.js',
			array('jquery', 'bbq', 'hotkeys'), K2_CURRENT, true);
	}


	/**
	 * Enqueues scripts needed by K2
	 */
	function enqueue_scripts() {
		// Load our scripts
		if ( ! is_admin() ) {

			wp_enqueue_script('k2functions');

			if ( '1' == get_option('k2rollingarchives') )
				wp_enqueue_script('k2rollingarchives');

			if ( '1' == get_option('k2livesearch') )
				wp_enqueue_script('k2livesearch');

			// WP 2.7 threaded comments
			if ( is_singular() )
				wp_enqueue_script( 'comment-reply' );
		}
	}
	

	/**
	 * Initializes Rolling Archives and LiveSearch
	 */
	function init_advanced_navigation() {
		global $wp_query, $wp_scripts;
		
		// Get the query
		if ( is_array($wp_query->query) )
			$rolling_query = $wp_query->query;
		elseif ( is_string($wp_query->query) )
			parse_str($wp_query->query, $rolling_query);

		// Get list of page dates
		if ( !is_page() and !is_single() )
			$page_dates = get_rolling_page_dates($wp_query);

		// Get the current page
		$rolling_page = intval( get_query_var('paged') );
		if ( $rolling_page < 1 )
			$rolling_page = 1;

		// Future content will be dynamic.		
		$rolling_query['k2dynamic'] = 1;
	?>
	<script type="text/javascript">
	//<![CDATA[

		/**
		 * Initalize the Rolling Archives proper. This function will reset the RA
		 * back to the originally loaded page anytime.
		 */
		function initialRollingArchives() {
			K2.RollingArchives.setState(
				<?php echo (int) $rolling_page; ?>,
				<?php echo (int) $wp_query->max_num_pages; ?>,
				<?php echo json_encode($rolling_query); ?>,
				<?php echo json_encode($page_dates); ?>
			);

			smartPosition('#primary', 'smartposition'); // Prepare a 'sticky' scroll point
		}

		/**
		 * Set in motion all of K2's AJAX hotness (RA and LS).
		 */
		function initK2() {
			K2.AjaxURL		= "<?php bloginfo('url'); ?>/"; // For our AJAX calls
			K2.Animations	= <?php echo (int) get_option('k2animations') ?>; // Fetch the animations option

			// Initialize Livesearch
			K2.LiveSearch	= new LiveSearch( "<?php esc_attr_e('Search','k2'); ?>" );

			// Insert the Rolling Archives UI...
			K2.RollingArchives = new RollingArchives(
				"#content",
				".post",
				"<?php /* translators: 1: current page, 2: total pages */ esc_attr_e('%1$d of %2$d', 'k2'); ?>", // Page X of Y
				"<?php _e('Older', 'k2'); ?>",
				"<?php _e('Newer', 'k2'); ?>",
				"<?php _e('Loading', 'k2'); ?>"
			);

			// ...and initialize the Rolling Archives
			initialRollingArchives();

			// Save the original content for later retrieval
			K2.RollingArchives.saveState(); 

			<?php /* JS to run after jQuery Ajax calls */ if (get_option('k2ajaxdonejs') != '') { ?>
			jQuery('#content').ajaxComplete(function () {
				<?php echo get_option('k2ajaxdonejs'); ?>
			});
			<?php } ?>

			// Continually check for fragment changes if RA or LS are enabled
			if (K2.parseFragments) {
				if (K2.RollingArchives || K2.LiveSearch) {
					jQuery(window).bind( 'hashchange', function() {
						K2.parseFragments();
					});
				}

				K2.parseFragments();
			}

			K2.RollingArchives.hotkeys(50);
		}
		
		// Make ready K2's sub-systems
		jQuery(document).ready( function() { initK2() })
	//]]>
	</script>


	<?php
	} // End Init_Scripts()


	/**
	 * Helper function to load all php files in given directory using require_once
	 *
	 * @param string $dir_path directory to scan
	 * @param array $ignore list of files to ignore
	 */
	function include_all($dir_path, $ignore = false) {
		// Open the directory
		$dir = @dir($dir_path) or die( sprintf( __('Could not open required directory' , 'k2'), $dir_path ) );

		// Get all the files from the directory
		while(($file = $dir->read()) !== false) {
			// Check the file is a file, and is a PHP file
			if(is_file($dir_path . $file) and (!$ignore or !in_array($file, $ignore)) and preg_match('/\.php$/i', $file)) {
				include_once($dir_path . $file);
			}
		}

		// Close the directory
		$dir->close();
	}


	/**
	 * Helper function to search for files based on given criteria
	 *
	 * @param string $path directory to search
	 * @param array $ext file extensions
	 * @param integer $depth depth of search
	 * @param mixed $relative relative to which path
	 * @return array paths of files found
	 */
	function files_scan($path, $ext = false, $depth = 1, $relative = true) {
		$files = array();

		// Scan for all matching files
		K2::_files_scan( trailingslashit($path), '', $ext, $depth, $relative, $files);

		return $files;
	}


	/**
	 * Recursive function for files_scan
	 *
	 * @param string $base_path 
	 * @param string $path 
	 * @param string $ext 
	 * @param string $depth 
	 * @param mixed $relative 
	 * @param string $files 
	 * @return array paths of files found
	 */
	function _files_scan($base_path, $path, $ext, $depth, $relative, &$files) {
		if (!empty($ext)) {
			if (!is_array($ext)) {
				$ext = array($ext);
			}
			$ext_match = implode('|', $ext);
		}

		// Open the directory
		if(($dir = @dir($base_path . $path)) !== false) {
			// Get all the files
			while(($file = $dir->read()) !== false) {
				// Construct an absolute & relative file path
				$file_path = $path . $file;
				$file_full_path = $base_path . $file_path;

				// If this is a directory, and the depth of scan is greater than 1 then scan it
				if(is_dir($file_full_path) and $depth > 1 and !($file == '.' or $file == '..')) {
					K2::_files_scan($base_path, $file_path . '/', $ext, $depth - 1, $relative, $files);

				// If this is a matching file then add it to the list
				} elseif(is_file($file_full_path) and (empty($ext) or preg_match('/\.(' . $ext_match . ')$/i', $file))) {
					if ( $relative === true ) {
						$files[] = $file_path;
					} elseif ( $relative === false ) {
						$files[] = $file_full_path;
					} else {
						$files[] = str_replace($relative, '', $file_full_path);
					}
				}
			}

			// Close the directory
			$dir->close();
		}
	}


	/**
	 * Move an existing file to a new path
	 *
	 * @param string $source original path
	 * @param string $dest new path
	 * @param boolean $overwrite if destination exists, overwrite
	 * @return string new path to file
	 */
	function move_file($source, $dest, $overwrite = false) {
		return K2::_copy_or_move_file($source, $dest, $overwrite, true);
	}

	function copy_file($source, $dest, $overwrite = false) {
		return K2::_copy_or_move_file($source, $dest, $overwrite, false);
	}

	function _copy_or_move_file($source, $dest, $overwrite = false, $move = false) {
		// check source and destination folder
		if ( file_exists($source) and is_dir(dirname($dest)) ) {

			// destination is a folder, assume move to there
			if ( is_dir($dest) ) {
				if ( DIRECTORY_SEPARATOR != substr($dest, -1) )
					$dest .= DIRECTORY_SEPARATOR;

				$dest = $dest . basename($source);
			}

			// destination file exists
			if ( is_file($dest) ) {
				if ($overwrite) {
					// Delete existing destination file
					@unlink($dest);
				} else {
					// Find a unique name
					$dest = K2::get_unique_path($dest);
				}
			}

			if ($move) {
				if ( rename($source, $dest) )
					return $dest;
			} else {
				if ( copy($source, $dest) )
					return $dest;
			}
		}
		return false;
	}

	function get_unique_path($source) {
		$source = pathinfo($source);
		
		$path = trailingslashit($source['dirname']);
		$filename = $source['filename'];
		$ext = $source['extension'];

		$number = 0;
		while ( file_exists($path . $filename . ++$number . $ext) );

		return $path . sanitize_title_with_dashes($filename . $number) . $ext;
	}
}


// Actions and Filters
add_action( 'admin_menu', 			array('K2', 'add_options_menu') );
add_action( 'admin_init', 			array('K2', 'admin_init') );
add_filter( 'mce_css', 				array('K2', 'admin_style_visual_editor') );
add_action( 'wp_print_scripts', 	array('K2', 'enqueue_scripts') );
add_action( 'template_redirect', 	array('K2', 'dynamic_content') );
add_filter( 'query_vars', 			array('K2', 'add_custom_query_vars') );

// Use the default K2 CSS?
if ( get_option('k2style') == 1 )
	add_action( 'wp_head', 			array('K2', 'k2css_head') );

// Are LiveSearch and Rolling Archives enabled?
if ( get_option('k2livesearch') == 1 && get_option('k2rollingarchives') == 1 )
	add_action( 'wp_footer', 		array('K2', 'init_advanced_navigation') );

// Decrease the priority of redirect_canonical
remove_action( 'template_redirect', 'redirect_canonical' );
add_action( 'template_redirect', 'redirect_canonical', 11 );