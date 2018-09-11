<?php
use Pedestal\Objects\MailChimp;
include 'include.php';

/*
  This will inject a subscriber, set custom data, and test whether the expected
  data is returned when setting the subscriber cookie.
  Afterwards the test subscriber is deleted.
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
$email = 'test.subscriber--' . $date_string . '@' . PEDESTAL_DOMAIN_PRETTY;

if ( isset( $_GET['cleanup'] ) ) {
    $deleted = $mc->unsubscribe_contact_from_list( $email );
    wp_send_json_success();
    die();
}

// Default merge field values
$merge_fields = [
    'FNAME'     => 'Test',
    'LNAME'     => 'Subscriber',
    'CURMEM'    => true,
    'RECRMEM'   => true,
    'NOPROMOTE' => true,
    '500DONOR'  => true,
    'EXPDATE'   => '01/01/2030',
    'SUM365'    => 5000,
    'RECRAMT'   => 5050,
    'DONA7'     => 5,
    'DONA14'    => 50,
    'DONA30'    => 500,
    'DONA365'   => 5000,
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
            'TICURMEM'  => 'CURMEM',
            'TIRECRMEM' => 'RECRMEM',
            'TIEXPDATE' => 'EXPDATE',
            'TISUM365'  => 'SUM365',
            'TIRECRAMT' => 'RECRAMT',
            'TIDONA7'   => 'DONA7',
            'TIDONA14'  => 'DONA14',
            'TIDONA30'  => 'DONA30',
            'TIDONA365' => 'DONA365',
            'TIMEMLVL'  => 'MEMLVL',
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

// Setup test subscriber
$args = [
    'merge_fields' => $merge_fields,
    'interests'    => (object) [],
];
$updated = $mc->subscribe_contact( $email, $args );
// If there is a problem adding the test subscriber than bail
if ( ! empty( $updated->status ) && 400 == $updated->status ) {
    wp_die( "Couldn't add test subscriber: <code>{$updated->detail}</code>" );
}

$expected_merge_fields = [
    // Dynamic values that change whenever a subscriber is inserted
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
    'donate_7'                   => true,
    'donate_14'                  => true,
    'donate_30'                  => true,
    'donate_365'                 => true,
];

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

<script>
jQuery(document).ready(function($) {
    var expectedMergeFields = <?php echo json_encode( $expected_merge_fields ); ?>;
    // Delete the subscriber cookie
    localStorageCookie( PedSubscriber.storageKey, null );

    // Fetch subscriber details for the email
    PedSubscriber.fetchData('<?php echo $email; ?>');
    $(this).on('pedSubscriber:ready', function(e, data) {
        $('#output').html(JSON.stringify( data.data, null, 4) );
        var output = {
            'pass': [],
            'fail': []
        };
        for ( key in expectedMergeFields ) {
            var valueToCheck = data.data[key];
            var expectedValue = expectedMergeFields[key];
            if ( valueToCheck !== expectedValue ) {
                output['fail'].push({
                    key: key,
                    expected: expectedValue,
                    actualValue: valueToCheck
                });
            } else {
                output['pass'].push(key);
            }
        }

        if ( output['fail'].length <= 0 ) {
            // Our checks passed!
            $('#pass').show();
        } else {
            // Our checks failed
            $('#fail').show();
            $('#fail-output').html(JSON.stringify(output['fail'], null, 4) );
        }

        // Clean up
        $.get( '?cleanup', function(data) {});
    });
})
</script>
<?php
wp_footer();
