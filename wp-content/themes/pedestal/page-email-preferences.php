<?php
use Timber\Timber;
use Pedestal\Email\Email_Preferences;

$context = Timber::get_context();

if ( ! empty( $_POST['email-preferences-mc-email-id'] ) ) {

    // Check to make sure the contact exists
    $email_id      = sanitize_text_field( wp_unslash( $_POST['email-preferences-mc-email-id'] ) );
    $email_address = Email_Preferences::get_email_by_unique_email_id( $email_id );
    if ( empty( $email_address ) ) {
        // Contact not found
        // Redirect to /email-preferences/?bad-email-id so we can track useage in analytics
        $redirect_url = add_query_arg( 'bad-email-id', $email_id, get_permalink( get_the_ID() ) );
        wp_safe_redirect( $redirect_url );
        die();
    }

    // Maybe we're saving data?
    $nonce_action = 'email-preferences';
    $nonce_name   = 'email-preferences-nonce';
    if (
        ! empty( $_POST[ $nonce_name ] ) &&
        wp_verify_nonce( $_POST[ $nonce_name ], $nonce_action ) &&
        ! empty( $_POST['pedestal-email-preferences'] )
    ) {
        $result = Email_Preferences::save_preferences( $email_address, $_POST['pedestal-email-preferences'] );
        if ( $result ) {
            $result_message            = 'Success! Your preferences have been saved.';
            $context['result_message'] = apply_filters( 'the_content', $result_message );
        }
    }

    // Get the group data to display in the form
    $contact_groups = Email_Preferences::get_contact_groups( $email_address );
    if ( ! empty( $contact_groups ) ) {
        $nonce_referer                            = true;
        $echo                                     = false;
        $context['email_preferences_nonce_field'] = wp_nonce_field( $nonce_action, $nonce_name, $nonce_referer, $echo );
        $context['email_id']                      = $email_id;
        $context['email_address']                 = $email_address;
        $context['groups']                        = $contact_groups;

        Timber::render( 'emails/pages/email-preferences.twig', $context );
        die();
    }
}

// Show the default page since we don't have all of the data we need to display the email preferences form
if ( ! empty( $_GET['email-id'] ) ) {
    $context['email_id'] = $_GET['email-id'];
}
Timber::render( 'emails/pages/email-preferences-intro.twig', $context );
