<?php
/**
 * Handles hooking all the actions and filters used by the module.
 *
 * To remove a filter:
 * ```php
 *  remove_filter( 'some_filter', [ tribe( Tribe\Extensions\Adminpluginfilter\Hooks::class ), 'some_filtering_method' ] );
 *  remove_filter( 'some_filter', [ tribe( 'extension.admin_plugin_filter.hooks' ), 'some_filtering_method' ] );
 * ```
 *
 * To remove an action:
 * ```php
 *  remove_action( 'some_action', [ tribe( Tribe\Extensions\Adminpluginfilter\Hooks::class ), 'some_method' ] );
 *  remove_action( 'some_action', [ tribe( 'extension.admin_plugin_filter.hooks' ), 'some_method' ] );
 * ```
 *
 * @since 1.0.0
 *
 * @package Tribe\Extensions\Adminpluginfilter;
 */

namespace Tribe\Extensions\Adminpluginfilter;

use Tribe__Main as Common;

/**
 * Class Hooks.
 *
 * @since 1.0.0
 *
 * @package Tribe\Extensions\Adminpluginfilter;
 */
class Hooks extends \tad_DI52_ServiceProvider {

	/**
	 * List of author to filter by.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $authors = [
		'The Events Calendar',
		'The Events Calendar Team', // Why?
		'Modern Tribe, Inc.', // Old
	];

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 1.0.0
	 */
	public function register() {
		$this->container->singleton( static::class, $this );
		$this->container->singleton( 'extension.admin_plugin_filter.hooks', $this );

		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Adds the actions required by the plugin.
	 *
	 * @since 1.0.0
	 */
	protected function add_actions() {
		add_action( 'tribe_load_text_domains', [ $this, 'load_text_domains' ] );
	}

	/**
	 * Adds the filters required by the plugin.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Add separate section for extensions.
	 */
	protected function add_filters() {
		// Add filter link to top of page.
		add_filter( 'views_plugins', [ $this, 'filter_views_plugins_by_tec' ] );
		add_filter( 'views_plugins', [ $this, 'filter_views_plugins_by_tec_extension' ] );
		// Filter plugins by TEC authors.
		add_filter( 'all_plugins', [ $this, 'filter_all_plugins_by_tec' ] );
		add_filter( 'all_plugins', [ $this, 'filter_all_plugins_by_tec_extension' ] );
	}

	/**
	 * Load text domain for localization of the plugin.
	 *
	 * @since 1.0.0
	 */
	public function load_text_domains() {
		$mopath = tribe( Plugin::class )->plugin_dir . 'lang/';
		$domain = 'tec-labs-admin-plugin-filter';

		// This will load `wp-content/languages/plugins` files first.
		Common::instance()->load_text_domain( $domain, $mopath );
	}

	/**
	 * Adds a link to the plugin admin page to filter plugins by author (TEC). Excludes extensions.
	 *
	 * @param array $views The array of view links.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Excludes extensions.
	 *
	 * @return array $views The array of view links with the TEC link added.
	 */
	public function filter_views_plugins_by_tec( $views ) {
		$count   = esc_html( $this->count_tec_plugins() );
		$replace = 'class="current" aria-current="page"';
		$tec     = esc_html_x( 'TEC', 'Acronym for The Events Calendar, shown in the link.', 'tec-labs-admin-plugin-filter' );


		if ( 'tec' !== strtolower( tribe_get_request_var( 'plugin_author', '' ) ) ) {
			$replace = '';
		} else {
			foreach ( $views as $index => $link ) {
				if ( false !== stripos( $link, $replace ) ) {
					$views[ $index ] = str_replace( $replace, '', $link );
				}
			}
		}

		$views['TEC'] = "<a {$replace} href='plugins.php?plugin_author=tec'>{$tec} <span class='count'>({$count})</span></a>";

		return $views;
	}

	/**
	 * Adds a link to the plugin admin page to filter plugins by author (TEC). Only includes extensions.
	 *
	 * @param array $views The array of view links.
	 *
	 * @since 1.1.0
	 *
	 * @return array $views The array of view links with the TEC link added.
	 */
	public function filter_views_plugins_by_tec_extension( $views ) {
		$count   = esc_html( $this->count_tec_extensions() );
		$replace = 'class="current" aria-current="page"';
		$tec     = esc_html_x( 'TEC Extensions', 'The Events Calendar Extensions label, shown in the link.', 'tec-labs-admin-plugin-filter' );


		if ( 'tec_extension' !== strtolower( tribe_get_request_var( 'plugin_author', '' ) ) ) {
			$replace = '';
		} else {
			foreach ( $views as $index => $link ) {
				if ( false !== stripos( $link, $replace ) ) {
					$views[ $index ] = str_replace( $replace, '', $link );
				}
			}
		}

		$views['TEC_Extensions'] = "<a {$replace} href='plugins.php?plugin_author=tec_extension'>{$tec} <span class='count'>({$count})</span></a>";

		return $views;
	}

	/**
	 * Counts all the installed plugins authored by TEC. Excludes extensions.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Excludes extensions.
	 *
	 * @return int
	 */
	public function count_tec_plugins() {
		$plugins = get_plugins();
		$plugins = array_filter(
			$plugins,
			function( $data ){
				return $this->is_tec_plugin( $data );
			}
		);

		return count( $plugins );
	}

	/**
	 * Counts all the installed extensions authored by TEC.
	 *
	 * @since 1.1.0
	 *
	 * @return int
	 */
	public function count_tec_extensions() {
		$plugins = get_plugins();
		$plugins = array_filter(
			$plugins,
			function( $data ){
				return $this->is_tec_extension( $data );
			}
		);

		return count( $plugins );
	}

	/**
	 * Filters the "all plugins" list to only include those authored by TEC. Excludes extensions.
	 *
	 * @param array $plugins The list of plugins.
	 *
	 * @since 1.0.0
	 * @since 1.1.0 Excludes extensions.
	 *
	 * @return array $plugins The filtered plugins list.
	 */
	public function filter_all_plugins_by_tec( $plugins ) {
		if ( 'tec' !== strtolower( tribe_get_request_var( 'plugin_author', '' ) ) ) {
			return $plugins;
		}

		$plugins = array_filter(
			$plugins,
			function( $data ){
				return $this->is_tec_plugin( $data );
			}
		);

		return $plugins;
	}

	/**
	 * Filters the "all plugins" list to only include extensions authored by TEC.
	 *
	 * @param array $plugins The list of plugins.
	 *
	 * @since 1.1.0
	 *
	 * @return array $plugins The filtered plugins list.
	 */
	public function filter_all_plugins_by_tec_extension( $plugins ) {
		if ( 'tec_extension' !== strtolower( tribe_get_request_var( 'plugin_author', '' ) ) ) {
			return $plugins;
		}

		$plugins = array_filter(
			$plugins,
			function( $data ){
				return $this->is_tec_extension( $data );
			}
		);

		return $plugins;
	}

	/**
	 * Determines if a plugin is authored by TEC. Excludes extensions.
	 *
	 * @param array $data The plugin data. See get_plugins().
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function is_tec_plugin( $data ) {
		return false === stripos( $data['Name'], 'extension' )
				&& (
					in_array( $data['Author'], $this->authors )
					|| in_array( $data['AuthorName'], $this->authors )
				);
	}

	/**
	 * Determines if a plugin is authored by TEC and is an extension.
	 *
	 * @param array $data The plugin data. See get_plugins().
	 *
	 * @since 1.1.0
	 *
	 * @return boolean
	 */
	public function is_tec_extension( $data ) {
		return false !== stripos( $data['Name'], 'extension' )
				&& (
					in_array( $data['Author'], $this->authors )
					|| in_array( $data['AuthorName'], $this->authors )
				);
	}
}
