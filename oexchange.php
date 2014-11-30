<?php
/*
Plugin Name: OExchange
Plugin URI: http://wordpress.org/extend/plugins/oexchange/
Description: Adds OExchange support to WordPress' "Press This" bookmarklet
Version: 1.6.1
Author: Matthias Pfefferle
Author URI: http://notizblog.org/
*/

// Pre-2.6 compatibility
if (!defined('WP_CONTENT_URL'))
  define('WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
if (!defined('WP_CONTENT_DIR'))
  define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
if (!defined('WP_PLUGIN_URL'))
  define('WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins');
if (!defined('WP_PLUGIN_DIR'))
  define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');

add_action('init', array('OExchangePlugin', 'init'));

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
    add_action('parse_request', array('OExchangePlugin', 'parse_request'));
    add_filter('query_vars', array('OExchangePlugin', 'query_vars'));
    add_filter('host_meta', array('OExchangePlugin', 'host_meta_link'));
    add_filter('webfinger', array('OExchangePlugin', 'webfinger_link'));
    add_action('load-press-this.php', array('OExchangePlugin', 'admin_init'));
    add_action('admin_menu', array('OExchangePlugin', 'add_menu_item'));
    add_action('wp_head', array('OExchangePlugin', 'html_meta_link'), 5);
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
  public static function admin_init() {
    // oexchange to wordpress mapping
    if (isset($_GET['url']))
      $_GET['u'] = $_GET['url'];

    if (isset($_GET['title']))
      $_GET['t'] = $_GET['title'];

    if (isset($_GET['description']))
      $_GET['s'] = $_GET['description'];

    if (isset($_GET['ctype'])) {
      if ($_GET['ctype'] == "image") {
        $_GET['i'] = $_GET['imageurl'];
      }
    }
  }

  /**
   * parse request and show xrd file
   */
  public static function parse_request() {
    global $wp_query, $wp;

    if (array_key_exists('oexchange', $wp->query_vars)) {
      if ($wp->query_vars['oexchange'] == 'xrd') {
        header('Content-Type: application/xrd+xml; charset=' . get_option('blog_charset'), true);
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
    $xrd = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
    $xrd .= '<XRD xmlns="http://docs.oasis-open.org/ns/xri/xrd-1.0">'."\n";
    $xrd .= '  <Subject>'.site_url("/").'</Subject>'."\n";
    $xrd .= '  <Property type="http://www.oexchange.org/spec/0.8/prop/vendor">'.get_bloginfo('name').'</Property>'."\n";
    $xrd .= '  <Property type="http://www.oexchange.org/spec/0.8/prop/title">'.get_bloginfo('description').'</Property>'."\n";
    $xrd .= '  <Property type="http://www.oexchange.org/spec/0.8/prop/name">"Press This" bookmarklet</Property>'."\n";
    $xrd .= '  <Property type="http://www.oexchange.org/spec/0.8/prop/prompt">Press This</Property>'."\n";
    $xrd .= '  <Link rel= "icon" href="'.OExchangePlugin::get_icon_url(16).'" />'."\n";
    $xrd .= '  <Link rel= "icon32" href="'.OExchangePlugin::get_icon_url(32).'" />'."\n";
    $xrd .= '  <Link rel= "http://www.oexchange.org/spec/0.8/rel/offer" href="'.admin_url('press-this.php').'" type="text/html"/>'."\n";
    $xrd .= '</XRD>';

    return $xrd;
  }

  /**
   * generates the host-meta link
   *
   * @link http://www.oexchange.org/spec/#discovery-host
   */
  public static function host_meta_link($array) {
    $array["links"][] = array("rel" => "http://oexchange.org/spec/0.8/rel/resident-target",
                              "href" => site_url("/?oexchange=xrd"),
                              "type" => "application/xrd+xml");
    return $array;
  }

  /**
   * generates the webfinger link
   *
   * @link http://www.oexchange.org/spec/#discovery-personal
   */
  public static function webfinger_link($array) {
    $array["links"][] = array("rel" => "http://oexchange.org/spec/0.8/rel/user-target",
                              "href" => site_url("/?oexchange=xrd"),
                              "type" => "application/xrd+xml");
    return $array;
  }

  /**
   * generates header-link
   *
   * @link http://www.oexchange.org/spec/#discovery-page
   */
  public static function html_meta_link() {
    echo '<link rel="http://oexchange.org/spec/0.8/rel/related-target" type="application/xrd+xml" href="'.site_url("/?oexchange=xrd").'?oexchange=xrd" />'."\n";
  }

  /**
   * adds the yiid-items to the admin-menu
   */
  public static function add_menu_item() {
    add_options_page('OExchange', 'OExchange', 'administrator', 'oexchange', array('OExchangePlugin', 'show_settings'));
  }

  /**
   * returns different sized icons
   *
   * @param string $size
   * @return string
   */
  public static function get_icon_url($size) {
    $default = "http://www.oexchange.org/images/logo_".$size."x".$size.".png";

    $grav_url = "http://www.gravatar.com/avatar/" .
         md5(strtolower(get_bloginfo("admin_email"))) . "?d=" . urlencode($default) . "&amp;s=" . $size;

    return $grav_url;
  }

  /**
   * displays the yiid settings page
   */
  public static function show_settings() {
?>
  <div class="wrap">
    <h2>OExchange</h2>

    <p>OExchange is an open protocol for sharing any URL with any service on the web. -- <a href="http://www.oexchange.org/">oexchange.org</a></p>


    <h3>Settings</h3>

    <table class="form-table">
      <tbody>
        <tr valign="top">
        <th scope="row">OExchange URL</th>
        <td class="defaultavatarpicker"><fieldset><legend class="screen-reader-text"><span>Default Avatar</span></legend>
          Your Blogs discovery-url: <a href="<?php echo get_bloginfo('url').'/?oexchange=xrd'; ?>" target="_blank"><?php echo site_url("/?oexchange=xrd"); ?></a>
        </fieldset>
        </td>
        </tr>

        <tr valign="top">
        <th scope="row">OExchange Icon</th>
        <td class="defaultavatarpicker"><fieldset><legend class="screen-reader-text"><span>Default Avatar</span></legend>
          This Plugin uses the Gravatar of the admin-email: <strong><?php bloginfo("admin_email"); ?></strong> as OExchange icons.
          Visit <a href="http://gravatar.com" target="_blank">gravatar.com</a> to customize yours.
          <br />
          <?php echo get_avatar(get_bloginfo("admin_email"), 32, "http://www.oexchange.org/images/logo_32x32.png"); ?> 32x32<br />
          <?php echo get_avatar(get_bloginfo("admin_email"), 16, "http://www.oexchange.org/images/logo_16x16.png"); ?> 16x16<br />
        </fieldset>
        </td>
        </tr>
      </tbody>
    </table>

    <h3>Plugin dependencies</h3>

    <p>The OExchange plugin requires the following plugins to work properly:</p>

    <ul>
      <li><a href="<?php echo site_url("/wp-admin/plugin-install.php?tab=search&s=webfinger&plugin-search-input=Search+Plugins"); ?>">WebFinger plugin</a></li>
      <li><a href="<?php echo site_url("/wp-admin/plugin-install.php?tab=search&s=host-meta&plugin-search-input=Search+Plugins"); ?>">host-meta plugin</a></li>
    </ul>

    <h3>Discovery file</h3>

    <p>This is how the discovery file looks like:</p>

    <pre><?php echo htmlentities(OExchangePlugin::create_xrd()); ?></pre>
  </div>
<?php
  }
}
