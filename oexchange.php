<?php
/*
Plugin Name: OExchange
Plugin URI: http://wordpress.org/extend/plugins/oexchange/
Description: Adds OExchange support to WordPress' "Press This" bookmarklet
Version: 1.6.1
Author: Matthias Pfefferle
Author URI: http://notizblog.org/
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
		add_filter( 'webfinger', array( 'OExchangePlugin', 'webfinger_link' ) );
		add_action( 'load-press-this.php', array( 'OExchangePlugin', 'load_press_this' ) );
		add_action( 'admin_menu', array( 'OExchangePlugin', 'add_menu_item' ) );
		add_action( 'wp_head', array( 'OExchangePlugin', 'html_meta_link' ), 5 );
		add_action( 'site_icon_image_sizes', array( 'OExchangePlugin', 'site_icon_image_sizes' ) );
	}

	/**
	 * add 'oexchange' as a valid query var.
	 *
	 * @param array $vars
	 * @return array
	 */
	public static function query_vars($vars) {
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

		if ( array_key_exists( 'oexchange', $wp->query_vars ) ) {
			if ( 'xrd' === $wp->query_vars['oexchange'] ) {
				header( 'Content-Type: application/xrd+xml; charset=' . get_option( 'blog_charset' ), true );
				echo OExchangePlugin::create_xrd();
				exit;
			}
		}
	}

	/**
	 * generates the xrd file
	 *
	 * @link http://www.oexchange.org/spec/#discovery-targetxrd
	 * @return string
	 */
	public static function create_xrd() {
		$xrd = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		$xrd .= '<XRD xmlns="http://docs.oasis-open.org/ns/xri/xrd-1.0' . do_action( 'oexchange_ns' ) . '">' . "\n";
		$xrd .= '	<Subject>' . site_url( '/' ) . '</Subject>' . "\n";
		$xrd .= '	<Property type="http://www.oexchange.org/spec/0.8/prop/vendor">' . get_bloginfo( 'name' ) . '</Property>' . "\n";
		$xrd .= '	<Property type="http://www.oexchange.org/spec/0.8/prop/title">' . get_bloginfo( 'description' ) . '</Property>' . "\n";
		$xrd .= '	<Property type="http://www.oexchange.org/spec/0.8/prop/name">"Press This" bookmarklet</Property>' . "\n";
		$xrd .= '	<Property type="http://www.oexchange.org/spec/0.8/prop/prompt">Press This</Property>' . "\n";
		$xrd .= '	<Link rel= "icon" href="' . OExchangePlugin::get_icon_url( 16 ) . '" />' . "\n";
		$xrd .= '	<Link rel= "icon32" href="' . OExchangePlugin::get_icon_url( 32 ) . '" />' . "\n";
		$xrd .= '	<Link rel= "http://www.oexchange.org/spec/0.8/rel/offer" href="' . admin_url( 'press-this.php' ) . '" type="text/html"/>' . "\n";
		$xrd .= do_action( 'oexchange_xrd' );
		$xrd .= '</XRD>';

		return $xrd;
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
	 * generates header-link
	 *
	 * @link http://www.oexchange.org/spec/#discovery-page
	 */
	public static function html_meta_link() {
		echo '<link rel="http://oexchange.org/spec/0.8/rel/related-target" type="application/xrd+xml" href="' . site_url( '/?oexchange=xrd' ) . '" />' . "\n";
	}

	/**
	 * adds the yiid-items to the admin-menu
	 */
	public static function add_menu_item() {
		add_options_page( 'OExchange', 'OExchange', 'administrator', 'oexchange', array( 'OExchangePlugin', 'show_settings' ) );
	}

	/**
	 * returns different sized icons
	 *
	 * @param string $size
	 * @return string
	 */
	public static function get_icon_url($size) {
		if ( function_exists( 'get_site_icon_url' ) ) {
			return get_site_icon_url( null, $size );
		}

		$args = array(
			'size' => $size,
		);

		return get_avatar_url( get_bloginfo( 'admin_email' ), $args );
	}

	/**
	 * Add 16x16 icon
	 *
	 * @param  array $sizes sizes available for the site icon
	 * @return array        updated list of icons
	 */
	public static function site_icon_image_sizes( $sizes ) {
		$sizes[] = '16';
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

				<tr valign="top">
					<th scope="row">OExchange Icon</th>
					<td>
						<?php if ( function_exists( 'get_site_icon_url' ) ) { ?>
						This Plugin uses the <code>site_icon</code> feature introduced in WordPress 4.3.
						<?php } else { ?>
						This Plugin uses the Gravatar of the admin-email: <strong><?php bloginfo( 'admin_email' ); ?></strong> as OExchange icons.
						Visit <a href="http://gravatar.com" target="_blank">gravatar.com</a> to customize yours.
						<?php } ?>

						<ul>
							<li><img src="<?php echo OExchangePlugin::get_icon_url( 32 ); ?>" /> 32x32</li>
							<li><img src="<?php echo OExchangePlugin::get_icon_url( 16 ); ?>" /> 16x16</li>
						</ul>
					</td>
				</tr>
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

		<pre><?php echo htmlentities( OExchangePlugin::create_xrd() ); ?></pre>
	</div>
<?php
	}
}
