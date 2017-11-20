<?php
use Sunra\PhpSimple\HtmlDomParser;
include 'include.php';

$links_to_check = [
    [
        'name'           => 'Billy Penn Homepage',
        'url'            => 'https://billypenn.com/',
        'email_endpoint' => 'https://billypenn.com/subscribe-to-email-list/',
    ],
    [
        'name'           => 'Billy Penn Newsletter Signup Page',
        'url'            => 'https://billypenn.com/newsletter-signup/',
        'email_endpoint' => 'https://billypenn.com/subscribe-to-email-list/',
    ],
    [
        'name'           => 'Billy Penn Article with Story',
        'url'            => 'https://billypenn.com/2017/10/02/the-new-yards-tasting-room-could-be-open-in-6-weeks/',
        'email_endpoint' => 'https://billypenn.com/subscribe-to-email-list/',
    ],
    [
        'name'           => 'The Incline Homepage',
        'url'            => 'https://theincline.com/',
        'email_endpoint' => 'https://theincline.com/subscribe-to-email-list/',
    ],
    [
        'name'           => 'The Incline Newsletter Signup Page',
        'url'            => 'https://theincline.com/newsletter-signup/',
        'email_endpoint' => 'https://theincline.com/subscribe-to-email-list/',
    ],
    [
        'name'           => 'The Incline Article with Story',
        'url'            => 'https://theincline.com/2017/10/03/grocery-delivery-is-expanding-in-pittsburgh-and-that-means-you-can-get-fresh-food-without-ever-leaving-your-couch/',
        'email_endpoint' => 'https://theincline.com/subscribe-to-email-list/',
    ],
];
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
    <p>Enter an email address to automatically sign up for newsletters and follow updates. Check your email afterwards to make sure the confirm link works.</p>
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
        <li>List ids for an email address to be subscribed to</li>
    </ol>
    <p>Finally, we subscribe to each form to make sure everything is working.</p>
    <?php
    $confirmations_to_check = 0;
    foreach ( $links_to_check as $link ) {
        $html = HtmlDomParser::file_get_html( $link['url'] );
        $forms = $html->find( 'form[action*=/subscribe-to-email-list/]' );
        foreach ( $forms as $form ) {
            $list_ids = [];
            $honeypot_count = count( $form->find( '.pedestal-current-year-check' ) );
            $email_input_count = count( $form->find( '[name=email_address]' ) );
            $hidden_inputs = $form->find( 'input[type=hidden]' );
            foreach ( $hidden_inputs as $input ) {
                if ( 'list-ids[]' == $input->name ) {
                    $list_ids[ $input->name ][] = intval( $input->value );
                }
            }
            $list_id_count = count( $list_ids );
            $failure_reasons = [];
            if ( 1 !== $honeypot_count ) {
                $failure_reasons[] = 'No honeypot found!';
            }
            if ( 1 !== $email_input_count ) {
                $failure_reasons[] = 'No email input!';
            }
            if ( empty( $list_ids ) ) {
                $failure_reasons[] = 'No list ids are set!';
            }
            if ( ! empty( $failure_reasons ) ) {
                echo '<p class="fail">FAIL: <a href="' . $link['url'] . '" target="_blank">' . $link['name'] . '</a> REASONS: ' . implode( ' ', $failure_reasons ) . '</p>';
            } else {
                echo '<p class="pass">PASS: <a href="' . $link['url'] . '" target="_blank">' . $link['name'] . '</a></p>';

                // Send an email
                $args = [
                    'body' => [
                        'email_address'               => $test_email_address,
                        'pedestal-current-year-check' => date( 'Y' ),
                        'pedestal-blank-field-check'  => '',
                    ],
                ];
                foreach ( $list_ids as $key => $ids ) {
                    $key = str_replace( '[]', '', $key );
                    $args['body'][ $key ] = $ids;
                }
                wp_remote_post( $link['email_endpoint'], $args );
                $confirmations_to_check++;
            }
        }
    }
}
