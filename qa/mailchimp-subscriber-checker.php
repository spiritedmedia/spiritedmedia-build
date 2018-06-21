<?php
use Pedestal\Objects\MailChimp;
include 'include.php';

$test_email_address = '';
if ( isset( $_GET['email'] ) ) {
    $test_email_address = sanitize_email( $_GET['email'] );
}

if ( empty( $test_email_address ) ) {
?>
    <p>Enter an email address to check MailChimp to see if it is subscribed to various lists.</p>
    <form method="get">
        <input type="email" name="email">
        <input type="submit" value="Test Email Signups">
    </form>
<?php
    die();
}

// Check if the contact is subscribed to a MailChimp list
echo '<p>';
$mc = MailChimp::get_instance();
$mailchimp_lists = [ 'Billy Penn', 'The Incline', 'Denverite' ];
foreach ( $mailchimp_lists as $list_name ) {
    echo '</p><p>';
    $contact = $mc->get_contact( $test_email_address, $list_name );
    if ( ! is_object( $contact ) ) {
        echo 'Problem fetching ' . $test_email_address . ' from the ' . $list_name . ' MailChimp list.';
        continue;
    }

    if ( isset( $contact->status ) && 404 == $contact->status ) {
        echo $test_email_address . ' <strong>is not</strong> subscribed to the ' . $list_name . ' MailChimp list.';
        continue;
    }

    $all_groups = $mc->get_all_groups( $list_name );
    $subscribed_groups = [];
    foreach ( $contact->interests as $id => $bool ) {
        if ( $bool ) {
            foreach ( $all_groups as $group ) {
                if ( $id == $group->id ) {
                    $subscribed_groups[] = $group->category_title . ' - ' . $group->name;
                }
            }
        }
    }

    echo $test_email_address . ' is ' . $contact->status . ' to the ' . $list_name . ' list. They belong to ' . count( $subscribed_groups ) . ' MailChimp groups';
    if ( count( $subscribed_groups ) > 0 ) {
        echo ':';
        echo '<ol><li>';
        echo implode( '</li><li>', $subscribed_groups );
        echo '</li></ol>';
    }
}
echo '</p>';
