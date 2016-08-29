<?php
/**
 * Minimal page for ELB HTTP health check.
 *
 * Must return HTTP 200 (not 302) even when no host header is sent. The only
 * known page in Core that does so is wp-activate.php. Using this page instead
 * reduces server load and ensures that the theme exists.
 *
 * @link http://docs.aws.amazon.com/ElasticLoadBalancing/latest/DeveloperGuide/elb-healthchecks.html
 * @package Pedestal
 */

$_SERVER['HTTP_HOST'] = 'spiritedmedia.com'; // Use the domain of the network root site.
require( 'wp-load.php' );
echo 'OK';
