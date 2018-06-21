<?php
use Sunra\PhpSimple\HtmlDomParser;
include 'include.php';

// Check if a wp_remote_post request was sent to speed things up
$cached_requests = [];
function pedestal_is_request_cached( $key ) {
    global $cached_requests;
    if ( isset( $cached_requests[ $key ] ) ) {
        return true;
    }
    return false;
}

// Grab the root domain name of the sites for the current environment
$billy_penn_root  = pedestal_get_root_url( 2 );
$the_incline_root = pedestal_get_root_url( 3 );
$denverite_root   = pedestal_get_root_url( 4 );

$links_to_check = [
    [
        'name'           => 'Billy Penn Homepage',
        'url'            => $billy_penn_root . '/',
        'expected_forms' => 2,
    ],
    [
        'name'           => 'Billy Penn Newsletter Signup Page',
        'url'            => $billy_penn_root . '/newsletter-signup/',
        'expected_forms' => 2,
    ],
    [
        'name'           => 'Billy Penn Article with Story',
        'url'            => $billy_penn_root . '/2017/10/02/the-new-yards-tasting-room-could-be-open-in-6-weeks/',
        'expected_forms' => 2,
    ],
    [
        'name'           => 'Billy Penn Story',
        'url'            => $billy_penn_root . '/stories/the-new-love-park/',
        'expected_forms' => 3,
    ],
    [
        'name'           => 'The Incline Homepage',
        'url'            => $the_incline_root . '/',
        'expected_forms' => 2,
    ],
    [
        'name'           => 'The Incline Newsletter Signup Page',
        'url'            => $the_incline_root . '/newsletter-signup/',
        'expected_forms' => 2,
    ],
    [
        'name'           => 'The Incline Article with Story',
        'url'            => $the_incline_root . '/2017/10/03/grocery-delivery-is-expanding-in-pittsburgh-and-that-means-you-can-get-fresh-food-without-ever-leaving-your-couch/',
        'expected_forms' => 2,
    ],
    [
        'name'           => 'The Incline Story',
        'url'            => $the_incline_root . '/stories/self-driving-vehicles/',
        'expected_forms' => 3,
    ],
    [
        'name'           => 'Denverite Homepage',
        'url'            => $denverite_root . '/',
        'expected_forms' => 2,
    ],
    [
        'name'           => 'The Incline Newsletter Signup Page',
        'url'            => $denverite_root . '/newsletter-signup/',
        'expected_forms' => 2,
    ],
];

$file_get_html_context = stream_context_create( [
    'ssl' => [
        'verify_peer'      => false,
        'verify_peer_name' => false,
    ],
] );
?>


<style>
.pass,
.pass a {
    color: #999;
}

.fail,
.fail a {
    color: red;
}
</style>
<?php
$test_email_address = '';
if ( isset( $_GET['email'] ) ) {
    $test_email_address = sanitize_email( $_GET['email'] );
}
if ( empty( $test_email_address ) ) {
?>
    <p>Enter an email address to automatically sign up for newsletters and follow updates. Get a temporary one from <a href="https://getnada.com/" target="_blank">Nada</a></p>
    <form method="get">
        <input type="email" name="email">
        <input type="submit" value="Test Email Signups">
    </form>
<?php
} else {
    ?>
    <p>Check each email signup form on each page looking for:</p>
    <ol>
        <li>A honey pot to prevent spam</li>
        <li>An email input field</li>
        <li>Group id(s) for an email address to be subscribed to</li>
    </ol>
    <?php
    $confirmations_to_check = 0;
    foreach ( $links_to_check as $link ) {
        $found_forms = [];
        $use_include_path = false;
        $html = HtmlDomParser::file_get_html( $link['url'], $use_include_path, $file_get_html_context );
        $forms = $html->find( 'form[action*=/subscribe-to-email-group/]' );
        foreach ( $forms as $form ) {
            $honeypot_count = count( $form->find( '.pedestal-current-year-check' ) );
            $email_input_count = count( $form->find( '[name=email_address]' ) );
            $hidden_inputs = $form->find( 'input[type=hidden]' );
            $found_input_values = [];
            foreach ( $hidden_inputs as $input ) {
                if ( 'group-ids[]' == $input->name ) {
                    $found_input_values[ $input->name ][] = $input->value;
                }
                if ( 'group-category' == $input->name ) {
                    $found_input_values['group-category'] = $input->value;
                }
            }
            $failure_reasons = [];
            if ( 1 !== $honeypot_count ) {
                $failure_reasons[] = 'No honeypot found!';
            }
            if ( 1 !== $email_input_count ) {
                $failure_reasons[] = 'No email input!';
            }
            if ( empty( $found_input_values ) ) {
                $failure_reasons[] = 'No group ids are set!';
            }
            $found_forms[] = (object) [
                'form'     => $form,
                'failures' => $failure_reasons,
            ];

            if ( empty( $failure_reasons ) ) {
                // Send an email
                $args = [
                    'timeout' => 60, // 60 second timeout because staging server is slow
                    'body' => [
                        'email_address'               => $test_email_address,
                        'pedestal-current-year-check' => date( 'Y' ),
                        'pedestal-blank-field-check'  => '',
                    ],
                ];
                foreach ( $found_input_values as $key => $ids ) {
                    $key = str_replace( '[]', '', $key );
                    $args['body'][ $key ] = $ids;
                }

                // If we are dealing with a staging URL add an
                // Authorization: 'Basic ' header to the request
                if ( strpos( strtolower( $form->action ), 'staging' ) ) {
                    $args['headers']['Authorization'] = 'Basic ' . base64_encode( 'spirited:media' );
                }

                ksort( $args );
                $cache_args = [
                    'endpoint' => $form->action,
                    'args'     => $args,
                ];
                $cache_key = md5( json_encode( $cache_args ) );
                if ( ! pedestal_is_request_cached( $cache_key ) ) {
                    $resp = wp_remote_post( pedestal_stagify_url( $form->action ), $args );
                    if ( 200 != $resp['response']['code'] ) {
                        echo '<p class="fail">Bad POST request!</p>';
                        echo '<xmp class="fail">';
                        var_dump( $cache_args );
                        echo '</xmp>';
                    }
                    $cached_requests[ $cache_key ] = 1;
                }
            }
        }

        $total_successful_forms = 0;
        foreach ( $found_forms as $found_form ) {
            if ( empty( $found_form->failures ) ) {
                $total_successful_forms++;
            }
        }

        if ( $total_successful_forms == $link['expected_forms'] ) {
            echo '<p class="pass">PASS: <a href="' . esc_url( $link['url'] ) . '" target="_blank">' . $link['name'] . '</a> - Found ' . $total_successful_forms . '/' . $link['expected_forms'] . ' expected forms</p>';
        } else {
            echo '<p class="fail">Fail: <a href="' . esc_url( $link['url'] ) . '" target="_blank">' . $link['name'] . '</a> - Found ' . $total_successful_forms . '/' . $link['expected_forms'] . ' expected forms<br> REASONS: ' . implode( ' ', $found_form->failures ) . '</p>';
        }
    }

    echo '<p><a href="mailchimp-subscriber-checker.php?email=' . urlencode( $test_email_address ) . '">Check if ' . $test_email_address . ' is subscribed to MailChimp lists</a></p>';
}
