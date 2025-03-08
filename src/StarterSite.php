<?php

/**
 * StarterSite class
 * This class is used to add custom functionality to the theme.
 */

namespace App;

use Timber\Site;
use Timber\Timber;
use Timber_Acf_Wp_Blocks;

/**
 * Class StarterSite.
 */
class StarterSite extends Site {
	/**
	 * Scripts version.
	 *
	 * @var string $scripts_version The version of the scripts to be used.
	 */
	protected $scripts_version = '';
	/**
	 * Scripts directory.
	 *
	 * @var string $this->scripts_dir The directory of the scripts to be used.
	 */
	protected $scripts_dir = 'dist';

	/**
	 * StarterSite constructor.
	 */
	public function __construct() {
		add_action( 'after_switch_theme', array( $this, 'activate' ) );
		add_action( 'after_setup_theme', array( $this, 'content_width' ), 0 );
		add_action( 'after_setup_theme', [ $this, 'setup' ] );

		$this->register_post_types();
		$this->register_taxonomies();

		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'block_editor_scripts' ) );

		add_filter( 'timber/loader/loader', [ $this, 'timber_loader' ] );
		add_filter( 'timber/context', [ $this, 'add_to_context' ] );
		add_filter( 'timber/twig/filters', [ $this, 'add_filters_to_twig' ] );
		add_filter( 'timber/twig/functions', [ $this, 'add_functions_to_twig' ] );
		add_filter( 'timber/twig/environment/options', [ $this, 'update_twig_environment_options' ] );

		add_filter( 'timber/acf-gutenberg-blocks-templates', array( $this, 'acf_gutenberg_blocks_template_location' ) );
		add_filter( 'acf/settings/save_json', array( $this, 'acf_json_save_point' ) );
		add_filter( 'acf/settings/load_json', array( $this, 'acf_json_load_point' ) );
		add_filter( 'use_block_editor_for_post_type', array( $this, 'check_post_can_gutenberg' ), 10, 2 );
		add_filter( 'gutenberg_can_edit_post_type', array( $this, 'check_post_can_gutenberg' ), 10, 2 );
		add_filter( 'allowed_block_types_all', array( $this, 'allowed_block_types' ), 10, 2 );

		// Disable WP features.
		add_filter( 'comments_open', '__return_false', 20, 2 );
		add_filter( 'pings_open', '__return_false', 20, 2 );
		add_filter( 'comments_array', '__return_empty_array', 10, 2 );
		add_filter( 'xmlrpc_enabled', '__return_false' );

		// Set scripts version to theme version set in style.css.
		$this->scripts_version = wp_get_theme()->get( 'Version' );

		// If the Timber ACF Blocks library is installed, load it. https://github.com/palmiak/timber-acf-wp-blocks .
		if ( class_exists( 'Timber_Acf_Wp_Blocks' ) ) {
			// Register blocks in views/blocks with ACF.
			new Timber_Acf_Wp_Blocks();
		}

		parent::__construct();
	}

	/**
	 * Theme activation hook
	 */
	public function activate() {
		if ( ! get_page_by_path('style-guide') ) {
			wp_insert_post( array(
				'post_title'  => 'Style Guide',
				'post_name'   => 'style-guide',
				'post_status' => 'private',
				'post_author' => 1,
				'post_type'   => 'page',
			) );
		}
	}

	/**
	 * Register custom post types using the PostTypes library.
	 * @link https://posttypes.jjgrainger.co.uk/post-types
	 */
	public function register_post_types() {}

	/**
	 * Register custom taxonomies using the PostTypes library.
	 * @link https://posttypes.jjgrainger.co.uk/taxonomies
	 */
	public function register_taxonomies() {}

	/**
	 * Modify ACF Gutenberg Blocks Template Location
	 */
	public function acf_gutenberg_blocks_template_location() {
		$paths = array( 'views/organisms/blocks' );
		$theme_dir = get_template_directory();
		foreach ( glob( $theme_dir . '/views/organisms/blocks/*', GLOB_ONLYDIR ) as $directory_path ) {
			$directory = str_replace($theme_dir, "", $directory_path);
			$paths[] = "$directory/controller";
		}
		return $paths;
	}

	/**
	 * Add @thinktimber namespace for twig templates used in other contexts.
	 */
	public function timber_loader($loader){
		$loader->addPath(get_template_directory() . '/views', 'thinktimber');
		return $loader;
	}

	/**
	 * This is where you add some context.
	 *
	 * @param array $context context['this'] Being the Twig's {{ this }}
	 */
	public function add_to_context( $context ) {
		$context['foo'] = 'bar';
		$context['stuff'] = 'I am a value set in your functions.php file';
		$context['notes'] = 'These values are available everytime you call Timber::context();';
		$context['menus']['header_primary_navigation'] = Timber::get_menu( 'primary_navigation' );
		$context['menus']['header_utility_navigation'] = Timber::get_menu( 'utility_navigation' );
		$context['menus']['footer_about'] = Timber::get_menu( 'about' );
		$context['menus']['footer_copyright'] = Timber::get_menu( 'copyright' );
		$context['site'] = $this;

		return $context;
	}

	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 */
	public function setup() {
		// Register navigation menus
		register_nav_menus(
			[
				'primary_navigation' => __( 'Main menu', 'thinktimber' ),
				'utility_navigation' => __( 'Utility menu', 'thinktimber' ),
				'about' => __( 'About menu', 'thinktimber' ),
				'copyright' => __( 'Copyright menu', 'thinktimber' ),
			]
		);

		/*
		 * Make theme available for translation.
		 * Translations are filed in the /languages/ directory.
		 */
		load_theme_textdomain( 'thinktimber', get_template_directory() . '/languages' );

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support( 'title-tag' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support( 'post-thumbnails' );

		/*
		 * Switch default core markup for search form, comment form, and comments
		 * to output valid HTML5.
		 */
		add_theme_support(
			'html5',
			[
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
			]
		);

		/*
		 * Enable support for Post Formats.
		 *
		 * See: https://codex.wordpress.org/Post_Formats
		 */
		add_theme_support(
			'post-formats',
			[
				'aside',
				'image',
				'video',
				'quote',
				'link',
				'gallery',
				'audio',
			]
		);

		add_theme_support( 'menus' );

		add_theme_support( 'editor-styles' );
		add_editor_style( $this->scripts_dir . '/motif-admin.css' );
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public function scripts() {
		wp_enqueue_style( 'thinktimber-styles', get_template_directory_uri() . "/$this->scripts_dir/motif.css", array(), $this->scripts_version );
		wp_enqueue_script( 'thinktimber-scripts', get_template_directory_uri() . "/$this->scripts_dir/motif.js", array( 'jquery' ), $this->scripts_version, true );

		wp_localize_script(
			'thinktimber-scripts',
			'thinktimber',
			array(
				'themeBase' => get_theme_file_uri(),
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			)
		);

		wp_enqueue_script( 'thinktimber-navigation', get_template_directory_uri() . '/assets/js/navigation.js', array(), $this->scripts_version, true );

		wp_enqueue_script( 'thinktimber-skip-link-focus-fix', get_template_directory_uri() . '/assets/js/skip-link-focus-fix.js', array(), $this->scripts_version, true );

		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}

	/**
	 * Enqueue admin scripts and styles.
	 */
	public function admin_scripts() {
		// Fonts.
		wp_enqueue_style( 'thinktimber-fonts', 'https://use.typekit.net/kitId.css', array(), $this->scripts_version );
		wp_enqueue_script( 'thinktimber-font-awesome', 'https://kit.fontawesome.com/3d318b83b5.js', array(), $this->scripts_version, false );
	}

	/**
	 * Enqueue block editor scripts and styles.
	 */
	public function block_editor_scripts() {
		// Scripts.
		wp_enqueue_script( 'thinktimber-admin-scripts', get_template_directory_uri() . '/' . $this->scripts_dir . '/motif-admin.js', array( 'wp-edit-post' ), $this->scripts_version, true );
	}

	/**
	 * Filter allowed gutenberg blocks.
	 *
	 * @param array                   $allowed_block_types Allowed block types.
	 * @param WP_Block_Editor_Context $block_editor_context The block editor context.
	 *
	 * @return array
	 */
	public function allowed_block_types( $allowed_block_types, $block_editor_context ) {
		// If we're not on a post, return the default allowed block types.
		if ( empty( $block_editor_context->post ) ) {
			return $allowed_block_types;
		}

		$allowed_blocks = array(
			'core/block',
			'core/button',
			'core/code',
			'core/columns',
			'core/cover',
			'core/embed',
			'core/gallery',
			'core/group',
			'core/heading',
			'core/html',
			'core/image',
			'core/list',
			'core/paragraph',
			'core/preformatted',
			'core/pullquote',
			'core/quote',
			'core/separator',
			'core/shortcode',
			'core/spacer',
			'core/table',
			'core/text-columns',
			'core/video',
		);

		// Make sure we allow our ACF blocks.
		$acf_block_types     = acf_get_store( 'block-types' );
		$acf_block_types     = array_keys( $acf_block_types->get_data() );
		$allowed_block_types = array_merge( $allowed_blocks, $acf_block_types );

		return $allowed_block_types;
	}

	/**
	 * Get post loaded in editor
	 */
	protected function get_admin_post() {
		// @codingStandardsIgnoreStart
		if ( ! ( is_admin() && ! empty( $_GET['post'] ) ) ) {
			return null;
		}
		return $_GET['post'];
		// @codingStandardsIgnoreEnd
	}

	/**
	 * Check if we're editing the style guide page.
	 */
	protected function is_style_guide_page() {
		$post_id = $this->get_admin_post();
		if ( is_null( $post_id ) ) {
			return false;
		}
		$style_guide_page    = get_page_by_path( 'style-guide' );
		$style_guide_page_id = strval( $style_guide_page->ID );
		if ( $style_guide_page_id === $post_id ) {
			return true;
		}
		return false;
	}

	/**
	 * Remove Gutenberg Editor from Team page and any other pages that shouldn't have it.
	 *
	 * @param boolean $can_edit true or false user can edit.
	 */
	public function check_post_can_gutenberg( $can_edit ) {
		$post_id = $this->get_admin_post();
		if ( is_null( $post_id ) ) {
			return $can_edit;
		}
		$post_can_gutenberg = true;
		// Check if the page is the team landing page template, the style guide, if this is a tribe event post type, or if it's the posts page.
		if ( $this->is_style_guide_page() ) {
			$post_can_gutenberg = false;
		}
		return $post_can_gutenberg;
	}

	/**
	 * Save ACF JSON to theme directory.
	 *
	 * @param string $path The path to save the JSON.
	 *
	 * @return string
	 */
	public function acf_json_save_point( $path ) {
		$path = get_stylesheet_directory() . '/acf-json';

		return $path;
	}

	/**
	 * Load ACF JSON from theme directory.
	 *
	 * @param array $paths The paths to load the JSON from.
	 *
	 * @return array
	 */
	public function acf_json_load_point( $paths ) {
		$paths[] = get_stylesheet_directory() . '/acf-json';

		return $paths;
	}

	/**
	 * Set the content width in pixels, based on the theme's design and stylesheet.
	 *
	 * Priority 0 to make it available to lower priority callbacks.
	 *
	 * @global int $content_width
	 */
	public function content_width() {
		// This variable is intended to be overruled from themes.
		// Open WPCS issue: {@link https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/issues/1043}.
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		$GLOBALS['content_width'] = 640;
	}

	/**
	 * This would return 'foo bar!'.
	 *
	 * @param string $text being 'foo', then returned 'foo bar!'
	 */
	public function myfoo( $text ) {
		$text .= ' bar!';

		return $text;
	}

	/**
	 * Console Logging PHP Values
	 *
	 * @param multi $value The variables to be logged.
	 */
	public function php_console( $value = null ) {
		// Console Log a mixed var.
		echo '<script type="text/javascript">console.log(' . wp_json_encode( $value ) . ');</script>';
	}

	/**
	 * This is where you can add your own functions to twig.
	 *
	 * @link https://timber.github.io/docs/v2/hooks/filters/#timber/twig/filters
	 * @param array $filters an array of Twig filters.
	 */
	public function add_filters_to_twig( $filters ) {

		$additional_filters = [
			'myfoo' => [
				'callable' => [ $this, 'myfoo' ],
			],
		];

		return array_merge( $filters, $additional_filters );
	}


	/**
	 * This is where you can add your own functions to twig.
	 *
	 * @link https://timber.github.io/docs/v2/hooks/filters/#timber/twig/functions
	 * @param array $functions an array of existing Twig functions.
	 */
	public function add_functions_to_twig( $functions ) {
		$additional_functions = [
			'console' => [
				'callable' => 'php_console',
			],
		];

		return array_merge( $functions, $additional_functions );
	}

	/**
	 * Updates Twig environment options.
	 *
	 * @see https://twig.symfony.com/doc/2.x/api.html#environment-options
	 *
	 * @param array $options an array of environment options
	 *
	 * @return array
	 */
	public function update_twig_environment_options( $options ) {
		// $options['autoescape'] = true;

		return $options;
	}
}
