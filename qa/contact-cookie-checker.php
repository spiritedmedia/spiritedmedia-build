<?php
use Pedestal\Objects\MailChimp;
include 'include.php';

/*
  This will inject a contact, set custom data, and test whether the expected
  data is returned when setting the contact cookie.
  Afterwards the test contact is deleted.
*/

$mc = MailChimp::get_instance();

// Get the time rounded up to the nearest 10 minutes
$time = time() + 600;
$next_minutes = floor( date( 'i' , $time ) / 10 ) * 10;
if ( $next_minutes < 10 ) {
    $next_minutes = '00';
}
$timestamp = strtotime( date( "d-m-Y H:{$next_minutes}:00", $time ) );
$date_string = date( 'Y.m.d-H.i', $timestamp );

// We need to have a dynamic email address otherwise the MailChimp API can rate limit us
// See https://stackoverflow.com/questions/41160763/not-allowing-more-signups-for-now-in-mailchimp-api
$email = 'test.contact--' . $date_string . '@' . PEDESTAL_DOMAIN_PRETTY;

if ( isset( $_GET['cleanup'] ) ) {
    $deleted = $mc->unsubscribe_contact_from_list( $email );
    wp_send_json_success();
    die();
}

// Default merge field values
$merge_fields = [
    'FNAME'     => 'Test',
    'LNAME'     => 'Contact',
    'CURMEM'    => true,
    'RECRMEM'   => true,
    'NOPROMOTE' => true,
    '500DONOR'  => true,
    'EXPDATE'   => '01/01/2030',
    'SUM365'    => 5000,
    'RECRAMT'   => 5050,
    'DONA7'     => false,
    'DONA14'    => false,
    'DONA30'    => true,
    'DONA365'   => true,
    'MEMLVL'    => 3,
    'SIGNUP'    => '???',
];

// Normalize based on the site being tested
switch ( get_current_blog_id() ) {
    // Billy Penn
    case 2:
        $mapping = [
            'BPCURMEM'  => 'CURMEM',
            'BPRECRMEM' => 'RECRMEM',
            'BPEXPDATE' => 'EXPDATE',
            'BPSUM365'  => 'SUM365',
            'BPRECRAMT' => 'RECRAMT',
            'BPDONA7'   => 'DONA7',
            'BPDONA14'  => 'DONA14',
            'BPDONA30'  => 'DONA30',
            'BPDONA365' => 'DONA365',
            'BPMEMLVL'  => 'MEMLVL',
        ];
        break;

    // The Incline
    case 3:
        $mapping = [
            'TICURMEM'   => 'CURMEM',
            'TIRECRMEM'  => 'RECRMEM',
            'TIEXPDATE'  => 'EXPDATE',
            'TISUM365'   => 'SUM365',
            'TINRECRAMT' => 'RECRAMT',
            'TIDONA7'    => 'DONA7',
            'TIDONA14'   => 'DONA14',
            'TIDONA30'   => 'DONA30',
            'TIDONA365'  => 'DONA365',
            'TIMEMLVL'   => 'MEMLVL',
        ];
        break;

    // Denverite
    case 4:
        $mapping = [
            'DENCURMEM'  => 'CURMEM',
            'DENRECRMEM' => 'RECRMEM',
            'DENEXPDATE' => 'EXPDATE',
            'DENSUM365'  => 'SUM365',
            'DENRECRAMT' => 'RECRAMT',
            'DENDONA7'   => 'DONA7',
            'DENDONA14'  => 'DONA14',
            'DENDONA30'  => 'DONA30',
            'DENDONA365' => 'DONA365',
            'DENMEMLVL'  => 'MEMLVL',
        ];
        break;

    default:
        $mapping = [];
        break;
}

foreach ( $mapping as $new_key => $old_key ) {
    if ( isset( $merge_fields[ $old_key ] ) ) {
        $val = $merge_fields[ $old_key ];
        $merge_fields[ $new_key ] = $val;
        unset( $merge_fields[ $old_key ] );
    } else {
        wp_die( '<code>' . $old_key . '</code> is not set in <code>$merge_fields</code>' );
    }
}

// Transform data
foreach ( $merge_fields as $key => $val ) {
    // All bool values need to be converted to strings
    if ( is_bool( $val ) ) {
        $val = ( $val ) ? 'true' : 'false';
    }
    $merge_fields[ $key ] = $val;
}

// Setup test contact
$args = [
    'merge_fields' => $merge_fields,
    'interests'    => (object) [],
];
$updated = $mc->subscribe_contact( $email, $args );
// If there is a problem adding the test contact than bail
if ( ! empty( $updated->status ) && 400 == $updated->status ) {
    wp_die( "Couldn't add test contact: <code>{$updated->detail}</code>" );
}

$expected_merge_fields = [
    // Dynamic values that change whenever a contact is inserted
    'mc_id'                      => $updated->unique_email_id,
    'since'                      => $updated->timestamp_opt,

    // Static values that we can hard code what to check against
    'subscribed_to_list'         => true,
    'newsletter_subscriber'      => false,
    'breaking_news_subscriber'   => false,
    'rating'                     => 2,
    'current_member'             => true,
    'member_level'               => 3,
    'recurring_member'           => true,
    'suggested_recurring_amount' => 5050,
    'no_promote'                 => true,
    'major_donor'                => true,
    'member_expiration'          => '2030-01-01',
    'donate_7'                   => false,
    'donate_14'                  => false,
    'donate_30'                  => true,
    'donate_365'                 => true,
];

$script_handle = 'pedestal-qa-contact-cookie-checker';
add_action( 'wp_enqueue_scripts', function() use ( $script_handle, $expected_merge_fields, $email ) {
    $in_footer = true;
    wp_enqueue_script(
        'local-storage-cookie',
        PEDESTAL_DIST_DIRECTORY_URI . '/js/globalLocalStorageCookie.js',
        PEDESTAL_VERSION,
        $in_footer
    );
    wp_enqueue_script(
        $script_handle,
        PEDESTAL_DIST_DIRECTORY_URI . '/js/contact-cookie-checker.js',
        [ 'jquery', 'local-storage-cookie' ],
        PEDESTAL_VERSION,
        $in_footer
    );
    wp_localize_script( $script_handle, 'contactExpectedMergeFields', [
        'data' => $expected_merge_fields,
    ] );
    wp_localize_script( $script_handle, 'contactEmail', $email );
} );

wp_head();
?>
<div id="pass" style="display: none;">
    <h2 style="color: green;">All tests passed!</h2>
</div>
<div id="fail" style="display: none;">
    <h2 style="color: red;">The test failed for the following reasons:</h2>
    <code><pre id="fail-output"></pre></code>
</div>

<p>Email address: <code><?php echo $email; ?></code></p>

<?php
wp_footer();
