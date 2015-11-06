<?php
header( 'Content-Type: application/xrd+xml; charset=' . get_option( 'blog_charset' ), true );
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>

<XRD xmlns="http://docs.oasis-open.org/ns/xri/xrd-1.0<?php do_action( 'oexchange_ns' ); ?>">
	<Subject><?php echo site_url( '/' ); ?></Subject>
	<Property type="http://www.oexchange.org/spec/0.8/prop/vendor"><?php bloginfo( 'name' ); ?></Property>
	<Property type="http://www.oexchange.org/spec/0.8/prop/title"><?php bloginfo( 'description' ); ?></Property>
	<Property type="http://www.oexchange.org/spec/0.8/prop/name">"Press This" bookmarklet</Property>
	<Property type="http://www.oexchange.org/spec/0.8/prop/prompt">Press This</Property>
	<Link rel="http://www.oexchange.org/spec/0.8/rel/offer" href="<?php echo admin_url( 'press-this.php' ); ?>" type="text/html" />
<?php do_action( 'oexchange_xrd' ); ?>
</XRD>
