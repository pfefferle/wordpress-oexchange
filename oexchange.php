<?php
/**
 * Plugin Name: OExchange
 * Plugin URI: http://wordpress.org/plugins/oexchange/
 * Description: Adds OExchange support to WordPress' "Press This" bookmarklet
 * Version: 2.0.1
 * Author: Matthias Pfefferle
 * Author URI: http://notizblog.org/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

add_action( 'init', array( 'OExchangePlugin', 'init' ) );

/**
 * OExchange class
 *
 * @author Matthias Pfefferle
 * @link http://www.oexchange.org/spec/ OExchange Spec
 */
class OExchangePlugin {
	/**
	 * init plugin
	 */
	public static function init() {
		add_action( 'parse_request', array( 'OExchangePlugin', 'parse_request' ) );
		add_filter( 'query_vars', array( 'OExchangePlugin', 'query_vars' ) );
		add_filter( 'host_meta', array( 'OExchangePlugin', 'host_meta_link' ) );
		add_filter( 'webfinger_user_data', array( 'OExchangePlugin', 'webfinger_link' ) );
		add_action( 'load-press-this.php', array( 'OExchangePlugin', 'load_press_this' ) );
		add_action( 'admin_menu', array( 'OExchangePlugin', 'add_menu_item' ) );
		add_action( 'wp_head', array( 'OExchangePlugin', 'html_meta_link' ), 5 );
		add_filter( 'site_icon_image_sizes', array( 'OExchangePlugin', 'site_icon_image_sizes' ) );

		add_action( 'oexchange_render_xrd', array( 'OExchangePlugin', 'render_xrd' ) );
		add_action( 'oexchange_xrd', array( 'OExchangePlugin', 'oexchange_extend_xrd' ) );
	}

	/**
	 * add 'oexchange' as a valid query var.
	 *
	 * @param array $vars
	 * @return array
	 */
	public static function query_vars( $vars ) {
		$vars[] = 'oexchange';

		return $vars;
	}

	/**
	 * Runs after WordPress has finished loading but
	 * before any headers are sent. Useful for intercepting $_GET or $_POST triggers.
	 */
	public static function load_press_this() {
		// oexchange to wordpress mapping
		if ( isset( $_GET['url'] ) && ! empty( $_GET['url'] ) ) {
			$_GET['u'] = $_GET['url'];
		}

		if ( isset( $_GET['title'] ) && ! empty( $_GET['title'] ) ) {
			$_GET['t'] = $_GET['title'];
		}

		if ( isset( $_GET['description'] ) && ! empty( $_GET['description'] ) ) {
			$_GET['s'] = $_GET['description'];
		}

		if ( isset( $_GET['ctype'] ) && ! empty( $_GET['ctype'] ) ) {
			if ( 'image' === $_GET['ctype'] ) {
				$_GET['i'] = $_GET['imageurl'];
			}
		}
	}

	/**
	 * parse request and show xrd file
	 */
	public static function parse_request() {
		global $wp_query, $wp;

		if ( ! array_key_exists( 'oexchange', $wp->query_vars ) ) {
			return;
		}

		$format = $wp->query_vars['oexchange'];

		do_action( 'oexchange_render', $format );
		do_action( "oexchange_render_{$format}" );

		exit;
	}

	/**
	 * generates the xrd file
	 *
	 * @link http://www.oexchange.org/spec/#discovery-targetxrd
	 */
	public static function render_xrd() {
		load_template( dirname( __FILE__ ) . '/oexchange-xrd.php' );
	}

	/**
	 * generates the host-meta link
	 *
	 * @link http://www.oexchange.org/spec/#discovery-host
	 */
	public static function host_meta_link($array) {
		$array['links'][] = array(
			'rel' => 'http://oexchange.org/spec/0.8/rel/resident-target',
			'href' => site_url( '/?oexchange=xrd' ),
			'type' => 'application/xrd+xml',
		);

		return $array;
	}

	/**
	 * generates the webfinger link
	 *
	 * @link http://www.oexchange.org/spec/#discovery-personal
	 */
	public static function webfinger_link( $array ) {
		$array['links'][] = array(
			'rel' => 'http://oexchange.org/spec/0.8/rel/user-target',
			'href' => site_url( '/?oexchange=xrd' ),
			'type' => 'application/xrd+xml',
		);

		return $array;
	}


	/**
	 * Adds OExchange Images
	 */
	public static function oexchange_extend_xrd() {
		if ( function_exists( 'get_site_icon_url' ) && has_site_icon() ) {
?>
	<Link rel="icon" href="<?php echo get_site_icon_url( 16 ); ?>" />
	<Link rel="icon32" href="<?php echo get_site_icon_url( 32 ); ?>" />
<?php
		}
	}

	/**
	 * generates header-link
	 *
	 * @link http://www.oexchange.org/spec/#discovery-page
	 */
	public static function html_meta_link() {
		echo '<link rel="http://oexchange.org/spec/0.8/rel/related-target" type="application/xrd+xml" href="' . site_url( '/?oexchange=xrd' ) . '" />' . PHP_EOL;
	}

	/**
	 * adds the yiid-items to the admin-menu
	 */
	public static function add_menu_item() {
		add_options_page( 'OExchange', 'OExchange', 'administrator', 'oexchange', array( 'OExchangePlugin', 'show_settings' ) );
	}

	/**
	 * Add 16x16 icon
	 *
	 * @param  array $sizes sizes available for the site icon
	 * @return array        updated list of icons
	 */
	public static function site_icon_image_sizes( $sizes ) {
		$sizes[] = '16';
		$sizes[] = '32';

		return array_unique( $sizes );
	}

	/**
	 * displays the yiid settings page
	 */
	public static function show_settings() {
	?>
	<div class="wrap">
		<h2>OExchange</h2>

		<p>OExchange is an open protocol for sharing any URL with any service on the web.</p>
		<p>-- <a href="http://www.oexchange.org/">OExchange.org</a></p>


		<h3>Settings</h3>

		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">OExchange URL</th>
					<td>Your Blogs discovery-url: <a href="<?php echo site_url( '/?oexchange=xrd' ); ?>" target="_blank"><?php echo site_url( '/?oexchange=xrd' ); ?></a></td>
				</tr>

				<?php if ( function_exists( 'get_site_icon_url' ) ) { ?>
				<tr valign="top">
					<th scope="row">OExchange Icon</th>
					<td>
						This Plugin uses the <code>site_icon</code> feature introduced in WordPress 4.3.

						<?php if ( get_site_icon_url() ) { ?>
						<ul>
							<li><img src="<?php site_icon_url( 32 ); ?>" /> 32x32</li>
							<li><img src="<?php site_icon_url( 16 ); ?>" /> 16x16</li>
						</ul>
						<?php } ?>
					</td>
				</tr>
				<?php } ?>
			</tbody>
		</table>

		<h3>Plugin dependencies</h3>

		<p>The OExchange plugin requires the following plugins to work properly:</p>

		<ul>
			<li><a href="<?php echo site_url( '/wp-admin/plugin-install.php?tab=search&s=webfinger&plugin-search-input=Search+Plugins' ); ?>">WebFinger plugin</a></li>
			<li><a href="<?php echo site_url( '/wp-admin/plugin-install.php?tab=search&s=host-meta&plugin-search-input=Search+Plugins' ); ?>">host-meta plugin</a></li>
		</ul>

		<h3>Discovery file</h3>

		<p>This is how the discovery file looks like:</p>

		<pre><?php
			$xrd = file_get_contents( site_url( '/?oexchange=xrd' ) );

			echo htmlentities( $xrd );
		?></pre>
	</div>
	<?php
	}
}
