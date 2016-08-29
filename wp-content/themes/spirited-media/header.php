<!DOCTYPE html>
<!--[if lt IE 7 ]> <html class="ie ie6" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 7 ]>    <html class="ie ie7" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 8 ]>    <html class="ie ie8" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 9 ]>    <html class="ie ie9" <?php language_attributes(); ?>> <![endif]-->
<!--[if gt IE 9]><!--><html <?php language_attributes(); ?>><!--<![endif]-->
<head>
<meta http-equiv="Content-Type" content="<?php bloginfo( 'html_type' ); ?>; charset=<?php bloginfo( 'charset' ); ?>">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="referrer" content="unsafe-url">
<link rel="alternate" type="application/rss+xml" title="<?php bloginfo( 'name' ); ?> RSS Feed" href="<?php bloginfo( 'rss2_url' ); ?>">
<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <header class="site-header section" role="banner">
        <a href="<?php echo esc_url( get_site_url() ); ?>" class="site-header--logo"><?php echo svg_logo( 'spiritedmedia-logo-with-tagline-black-gray' ); ?></a>

        <nav class="site-nav" role="navigation">
            <ul class="site-nav--items">
                <li class="site-nav--item"><a class="site-nav--link" href="/#cities">Cities</a></li>
                <li class="site-nav--item"><a class="site-nav--link" href="/#press">Press</a></li>
                <li class="site-nav--item"><a class="site-nav--link" href="https://medium.com/billy-penn">Blog</a></li>
                <li class="site-nav--item"><a class="site-nav--link" href="/#contact">Contact</a></li>
            </ul>
        </nav>
    </header>
