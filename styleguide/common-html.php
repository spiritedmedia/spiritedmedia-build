<?php
include 'include.php';
use Timber\Timber;

$context = Timber::get_context();
$context['pullquote_with_credit'] = do_shortcode( '[pullquote content="This is the text for an awesome and fantastical pull quote that is highlighted to get the readers precious attention." align="right" credit="Abraham Lincoln" /]' );
$context['pullquote_without_credit'] = do_shortcode( '[pullquote content="This is the text for an awesome and fantastical pull quote that is highlighted to get the readers precious attention." align="right" /]' );
Timber::render( 'views/common-html.twig', $context );
