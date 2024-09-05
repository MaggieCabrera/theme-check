<?php
/**
 * Check if skip links are present on block themes
 *
 * @package Theme Check
 */

/**
 * Check if skip links are present on block themes
 */
class Skip_Links_Check implements themecheck {
	/**
	 * Error messages, warnings and info notices.
	 *
	 * @var array $error
	 */
	protected $error = array();

	/**
	 * Returns true if the theme is a block theme.
	 *
	 * @var array $is_block_theme
	 */
	protected $is_block_theme = false;

	/**
	 * The WP_Theme instance being checked.
	 *
	 * @var WP_Theme $wp_theme
	 */
	protected $wp_theme = false;

	function set_context( $data ) {
		if ( isset( $data['theme'] ) ) {
			$this->wp_theme = $data['theme'];
			$theme_dir      = $this->wp_theme->get_stylesheet_directory();
			// Check if the theme has all the required files.
			if (
				file_exists( $theme_dir . '/theme.json' ) ||
				(
					file_exists( $theme_dir . '/templates/index.html' ) &&
					file_exists( $theme_dir . '/block-templates/index.html' )
				)
			) {
				$this->is_block_theme = true;
			}
		}
	}

	/**
	 * Check that return true for good/okay/acceptable, false for bad/not-okay/unacceptable.
	 *
	 * @param array $php_files File paths and content for PHP files.
	 * @param array $css_files File paths and content for CSS files.
	 * @param array $other_files Folder names, file paths and content for other files.
	 */
	public function check( $php_files, $css_files, $other_files ) {

		$info                       = '';
		$templates_without_main_tag = array();

		$directory = 'templates'; // Path to the folder containing HTML files
		$theme_dir = $this->wp_theme->get_stylesheet_directory();

		// Get all HTML files in the directory
		$files = glob( $theme_dir . '/' . $directory . '/*.html' );

		foreach ( $files as $file ) {
			$contents     = file_get_contents( $file );
			$has_main_tag = strpos( $contents, '<main' ) !== false;
			$file_name    = basename( $file );

			if ( ! $has_main_tag ) {
				$pattern_slugs = $this->template_has_patterns( $contents );
				if ( $pattern_slugs ) {
					foreach ( $pattern_slugs as $slug ) {
						$has_main_tag = $this->pattern_has_tag( $slug );
						if ( ! $has_main_tag ) {
							$templates_without_main_tag[] = $file_name;
						}
					}
				} else {
					$templates_without_main_tag[] = $file_name;
				}
			}
		}

		$info = implode( ', ', $templates_without_main_tag );

		if ( $info !== '' ) {
			$this->error[] = sprintf(
				'<span class="tc-lead tc-required">%s</span> %s ',
				__( 'REQUIRED', 'theme-check' ),
				sprintf(
					__( 'Skip links are missing from the following templates: %s Please make sure the templates have a <main> tag', 'theme-check' ),
					$info
				)
			);
		}

		return true;
	}

	function template_has_patterns( $contents ) {
		$pattern = '/<!-- wp:pattern \{"slug":"([^"]+)"\} \/-->/';
		if ( preg_match_all( $pattern, $contents, $matches ) ) {
			$slugs = $matches[1];
			return $slugs;
		} else {
			return false;
		}
	}

	function pattern_has_tag( $slug ) {
		$directory = 'patterns';
		$theme_dir = $this->wp_theme->get_stylesheet_directory();

		if ( ! is_dir( $theme_dir . '/' . $directory ) ) {
			$directory = 'block-patterns';
		}
		if ( ! is_dir( $theme_dir . '/' . $directory ) ) {
			return false;
		}

		$files = glob( $theme_dir . '/' . $directory . '/*.php' );

		$has_tag = false;

		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				$contents = file_get_contents( $file );
				$pattern  = '/\* Slug: ' . preg_quote( $slug, '/' ) . '\b/';
				if ( preg_match( $pattern, $contents ) ) {
					$has_tag = strpos( $contents, '<main' ) !== false;
				}
			}
		}

		return $has_tag;
	}

	/**
	 * Get error messages from the checks.
	 *
	 * @return array Error message.
	 */
	public function getError() {
		return $this->error;
	}
}

$themechecks[] = new Skip_Links_Check();