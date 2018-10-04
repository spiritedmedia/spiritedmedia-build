<?php
include 'include.php';
use Timber\Timber;

$context = Timber::get_context();

$context['pullquote_with_credit_link_markup'] = '[pullquote content="This is the text for an awesome and fantastical pull quote that is highlighted to get the readers precious attention." align="right" credit="Abraham Lincoln" credit_link="https://example.com" /]';
$context['pullquote_with_credit_link'] = do_shortcode( $context['pullquote_with_credit_link_markup'] );

$context['pullquote_with_credit_markup'] = '[pullquote content="This is the text for an awesome and fantastical pull quote that is highlighted to get the readers precious attention." align="right" credit="Abraham Lincoln" /]';
$context['pullquote_with_credit'] = do_shortcode( $context['pullquote_with_credit_markup'] );

$context['pullquote_without_credit_markup'] = '[pullquote content="This is the text for an awesome and fantastical pull quote that is highlighted to get the readers precious attention." align="right" /]';
$context['pullquote_without_credit'] = do_shortcode( $context['pullquote_without_credit_markup'] );

Timber::render( 'views/post-elements.twig', $context );
