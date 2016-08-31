<?php

namespace Pedestal;

use function Pedestal\Pedestal;

use Timber\Timber;

use \Pedestal\Utils\Utils;

use \Pedestal\Registrations\Post_Types\Types;

use \Pedestal\Posts\Post;
use \Pedestal\Posts\Newsletter;

use \Pedestal\Posts\Clusters\Cluster;
use \Pedestal\Posts\Clusters\Geospaces\Localities\Neighborhood;
use \Pedestal\Posts\Clusters\Story;

use \Pedestal\Objects\User;
use \Pedestal\Objects\Notifications;

class Subscriptions {

    private static $errors;

    private static $default_notify_channel = PEDESTAL_SLACK_CHANNEL_BOTS_EDITORIAL;

    private $math_honeypot_answers = [
        '5',
        'five',
        'Five',
        'FIVE',
    ];

    private static $instance;

    public static function get_instance() {

        if ( ! isset( self::$instance ) ) {
            self::$instance = new Subscriptions;
            self::$instance->setup_actions();
            self::$instance->setup_filters();
        }
        return self::$instance;
    }

    /**
     * Set up subscription actions
     */
    private function setup_actions() {

        add_action( 'init', [ $this, 'action_init_register_rewrites' ] );
        add_action( 'template_redirect', [ $this, 'action_template_redirect' ] );

        add_action( 'admin_init', function() {

            $terms = get_terms( 'pedestal_subscriptions', [ 'hide_empty' => false, 'fields' => 'id=>slug' ] );
            if ( ! in_array( 'daily-newsletter', $terms ) ) {
                wp_insert_term( 'Daily Newsletter', 'pedestal_subscriptions', [ 'slug' => 'daily-newsletter' ] );
            }
        });

        add_action( 'pre_user_query', [ $this, 'action_pre_user_query' ] );

        add_action( 'add_meta_boxes', [ $this, 'action_add_meta_boxes' ] );
        add_action( 'save_post', [ $this, 'action_save_post_late' ], 100 );

    }

    /**
     * Set up subscription filters
     */
    private function setup_filters() {

        add_filter( 'query_vars', function( $query_vars ) {
            $query_vars[] = 'pedestal-api';
            $query_vars[] = 'pedestal-unsubscribe';
            $query_vars[] = 'subscription-action';
            $query_vars[] = 'pedestal-test-email';
            $query_vars[] = 'email-type';
            $query_vars[] = 'user-id';
            $query_vars[] = 'cluster-id';
            $query_vars[] = 'unsubscribe-hash';
            return $query_vars;
        });

        add_filter( 'manage_users_columns', function( $columns ) {
            $columns['pedestal_subscriptions'] = esc_html__( 'Subscriptions', 'pedestal' );
            $columns['register_date'] = esc_html__( 'Registered', 'pedestal' );
            return $columns;
        } );

        // Make users sortable by registered date
        add_filter( 'manage_users_sortable_columns', function( $columns ) {
            return wp_parse_args( [
                'register_date' => 'registered',
            ], $columns );
        } );

        // Calculate order if users sorted by date
        add_filter( 'request', function( $vars ) {
            if ( isset( $vars['orderby'] ) && 'register_date' == $vars['orderby'] ) {
                $vars = array_merge( $vars, [
                        'meta_key' => 'register_date',
                        'orderby'  => 'meta_value',
                ] );
            }
            return $vars;
        } );

        add_filter( 'manage_users_custom_column', function( $out, $column_name, $user_id ) {
            $user = new User( $user_id );
            switch ( $column_name ) {
                case 'pedestal_subscriptions':
                    $subscriptions = [];
                    if ( $user->is_subscribed_daily_newsletter() ) {
                        $query_args = [
                            'subscription'  => 'daily-newsletter',
                        ];
                        $url = add_query_arg( $query_args, admin_url( 'users.php' ) );
                        $subscriptions[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Daily Newsletter', 'pedestal' ) . '</a>';
                    }
                    foreach ( $user->get_following_clusters() as $cluster ) {
                        $query_args = [
                            'subscription'  => 'cluster',
                            'cluster_id'   => $cluster->get_id(),
                        ];
                        $url = add_query_arg( $query_args, admin_url( 'users.php' ) );
                        $subscriptions[] = '<a href="' . esc_url( $url ) . '">' . esc_html( $cluster->get_the_title() ) . '</a>';
                    }
                    $out = implode( ', ', $subscriptions );
                    break;
                case 'register_date':
                    $out = '<span>' . $user->get_registered_date() . '</span>';
                    break;
            }
            return $out;
        }, 10, 3 );

        add_filter( 'manage_edit-pedestal_subscriptions_columns', function() {
            return [
                'pedestal_subscription_name'     => esc_html__( 'Name', 'pedestal' ),
                'pedestal_subscription_count'    => esc_html__( 'Subscriber Count', 'pedestal' ),
            ];
        } );

        add_filter( 'manage_pedestal_subscriptions_custom_column', function( $out, $column_name, $tag_id ) {
            $term = get_term_by( 'id', $tag_id, 'pedestal_subscriptions' );
            switch ( $column_name ) {
                case 'pedestal_subscription_name':
                    $out = esc_html( $term->name );
                    break;
                case 'pedestal_subscription_count':
                    $query_args = [
                        'subscription'       => 'daily-newsletter',
                    ];
                    $out = '<a href="' . esc_url( add_query_arg( $query_args, admin_url( 'users.php' ) ) ) . '">' . (int) $term->count . '</a>';
                    break;
            }
            return $out;
        }, 10, 3 );

    }

    /**
     * Register rewrite rules
     */
    public function action_init_register_rewrites() {

        add_rewrite_rule( 'api/subscription/([^/]+)/?$', 'index.php?pedestal-api=subscription&subscription-action=$matches[1]', 'top' );
        add_rewrite_rule( 'unsubscribe/daily-newsletter/([\d]+)/([^/]+)/?$', 'index.php?pedestal-unsubscribe=daily-newsletter&user-id=$matches[1]&unsubscribe-hash=$matches[2]', 'top' );
        add_rewrite_rule( 'unsubscribe/cluster/([\d]+)/([\d]+)/([^/]+)/?$', 'index.php?pedestal-unsubscribe=cluster&user-id=$matches[1]&cluster-id=$matches[2]&unsubscribe-hash=$matches[3]', 'top' );
        add_rewrite_rule( 'test-email/([^/]+)/?$', 'index.php?pedestal-test-email=1&email-type=$matches[1]', 'top' );

    }

    /**
     * Modify user queries
     */
    public function action_pre_user_query( $wp_user_query ) {
        global $pagenow, $wpdb;

        $subscription_filter = false;
        if ( 'users.php' == $pagenow && ! empty( $_GET['subscription'] ) && empty( $wp_user_query->query_vars['include'] ) ) {

            if ( 'daily-newsletter' == $_GET['subscription'] ) {
                $subscription_filter = 'daily-newsletter';
            } else if ( 'cluster' == $_GET['subscription'] && ! empty( $_GET['cluster_id'] ) ) {
                $wp_user_query->query_from .= " INNER JOIN {$wpdb->p2p}";
                $wp_user_query->query_where .= $wpdb->prepare( " AND {$wpdb->p2p}.p2p_from=%d AND {$wpdb->p2p}.p2p_to={$wpdb->users}.ID", (int) $_GET['story_id'] );
            }
        }

        if ( ! empty( $wp_user_query->query_vars['pedestal_subscriptions'] ) ) {
            $subscription_filter = $wp_user_query->query_vars['pedestal_subscriptions'];
        }

        if ( $subscription_filter ) {
            $tax_query = new \WP_Tax_Query( [
                [
                    'taxonomy'       => 'pedestal_subscriptions',
                    'terms'          => [
                        $subscription_filter,
                    ],
                    'field'          => 'slug',
                ],
            ] );
            $clauses = $tax_query->get_sql( $wpdb->users, 'ID' );
            $wp_user_query->query_from .= $clauses['join'];
            $wp_user_query->query_where .= $clauses['where'];
        }

    }

    /**
     * Handle API requests
     */
    public function action_template_redirect() {

        if ( 'subscription' == get_query_var( 'pedestal-api' ) ) {

            nocache_headers();

            switch ( get_query_var( 'subscription-action' ) ) {
                case 'follow-cluster':
                    $this->handle_subscription_action_follow_cluster();
                    break;

                case 'signup-daily-newsletter':
                    $this->handle_subscription_action_signup_daily_newsletter();
                    break;

                default:
                    status_header( 404 );
                    echo 'Invalid subscription action.';
            }
            exit;

        } else if ( get_query_var( 'pedestal-unsubscribe' ) ) {

            nocache_headers();

            $user_id = get_query_var( 'user-id' );
            $unsubscribe_hash = get_query_var( 'unsubscribe-hash' );
            switch ( get_query_var( 'pedestal-unsubscribe' ) ) {
                case 'daily-newsletter':

                    if ( $unsubscribe_hash !== $this->generate_secure_hash( 'unsubscribe-' . $user_id . '-daily-newsletter' ) ) {
                        break;
                    }

                    $user = get_user_by( 'id', (int) $user_id );
                    if ( ! $user ) {
                        break;
                    }

                    $user = new User( $user );
                    if ( $user->is_subscribed_daily_newsletter() ) {
                        $user->unsubscribe_daily_newsletter();
                        $subject = __( 'You have unsubscribed from ' . PEDESTAL_BLOG_NAME . ' Daily and breaking news updates', 'pedestal' );
                        $body = $this->get_email_template( 'unsubscribe-daily-confirmation', [
                            'email_type' => 'Daily',
                            'shareable'  => false,
                        ] );
                        mandrill_wp_mail( $user->get_email(), $subject, $body );
                    }

                    wp_safe_redirect( home_url( 'unsubscribe-confirmation/' ) );
                    exit;

                case 'cluster':

                    $cluster_id = get_query_var( 'cluster-id' );
                    if ( $unsubscribe_hash !== $this->generate_secure_hash( 'unsubscribe-' . $user_id . '-cluster-' . $cluster_id ) ) {
                        break;
                    }

                    $user = get_user_by( 'id', (int) $user_id );
                    if ( ! $user ) {
                        break;
                    }

                    $cluster = Post::get_by_post_id( (int) $cluster_id );
                    if ( ! $cluster ) {
                        break;
                    }

                    $user = new User( $user );
                    if ( $user->is_following_cluster( $cluster ) ) {
                        $user->unfollow_cluster( $cluster );
                        $subject = sprintf( __( 'You have unsubscribed from "%s"', 'pedestal' ), $cluster->get_title() );
                        $body = $this->get_email_template( 'unfollow-confirmation', [
                            'item' => $cluster,
                            'email_type' => $cluster->get_email_type(),
                            'shareable' => false,
                        ] );
                        mandrill_wp_mail( $user->get_email(), $subject, $body );
                    }

                    wp_safe_redirect( home_url( 'unfollow-confirmation/' ) );
                    exit;

                default:
                    break;

            }

            wp_die( esc_html__( sprintf(
                'Invalid unsubscribe link. Please contact support at <a href="mailto:%s">%s</a>.',
                PEDESTAL_EMAIL_CONTACT,
                PEDESTAL_EMAIL_CONTACT
            ), 'pedestal' ) );

        } else if ( get_query_var( 'pedestal-test-email' ) && current_user_can( 'manage_options' ) ) {

            switch ( get_query_var( 'email-type' ) ) {
                case 'subscribe-daily-confirmation';
                case 'unsubscribe-daily-confirmation';
                    echo $this->get_email_template( get_query_var( 'email-type' ), [
                        'email_type' => 'Daily',
                        'shareable' => false,
                    ] );
                    break;

                case 'follow-update-story':
                    $stories = new \WP_Query( [
                        'post_type'      => 'pedestal_story',
                        'posts_per_page' => 1,
                    ] );
                    if ( empty( $stories->posts ) ) {
                        echo 'No published stories to test with.';
                        break;
                    }
                    $story = new Story( $stories->posts[0] );
                    echo $this->get_email_template( 'follow-update', [
                        'item' => $story,
                        'entities' => $story->get_entities_since_last_email_notification(),
                        'email_type' => $story->get_email_type(),
                        'shareable' => true,
                    ] );
                    break;

                case 'follow-update-everyblock':
                    $hoods = new \WP_Query( [
                        'post_type'      => 'pedestal_hood',
                        'posts_per_page' => 1,
                    ] );
                    if ( empty( $hoods->posts ) ) {
                        echo 'No published neighborhoods to test with.';
                        break;
                    }
                    $hood = new Neighborhood( $hoods->posts[0] );
                    echo $this->get_email_template( 'follow-update-everyblock', [
                        'item'       => $hood,
                        'eb_items'   => $hood->get_items_since_last_everyblock_email_notification(),
                        'email_type' => $hood->get_email_type(),
                        'shareable'  => true,
                        'email_date' => date( 'F d, Y' ),
                    ] );
                    break;

                case 'follow-update-hood':
                    $hoods = new \WP_Query( [
                        'post_type'      => 'pedestal_hood',
                        'posts_per_page' => 1,
                    ] );
                    if ( empty( $hoods->posts ) ) {
                        echo 'No published neighborhoods to test with.';
                        break;
                    }
                    $hood = new Neighborhood( $hoods->posts[0] );
                    echo $this->get_email_template( 'follow-update', [
                        'item' => $hood,
                        'entities' => $hood->get_entities_since_last_email_notification(),
                        'email_type' => $hood->get_email_type(),
                        'shareable' => true,
                    ] );
                    break;

                case 'follow-confirmation':
                    $clusters = new \WP_Query( [
                        'post_type'      => Types::get_cluster_post_types(),
                        'posts_per_page' => 1,
                    ] );
                    if ( empty( $clusters->posts ) ) {
                        echo 'No published clusters to test with.';
                        break;
                    }
                    $cluster = $clusters->posts[0];
                    $cluster_class = Types::get_post_type_class( get_post_type( $cluster ) );
                    $cluster = new $cluster_class( $cluster );
                    echo $this->get_email_template( 'follow-confirmation', [
                        'item' => $cluster,
                        'email_type' => $cluster->get_email_type(),
                        'shareable' => false,
                    ] );

                    break;

                case 'unfollow-confirmation':
                    $clusters = new \WP_Query( [
                        'post_type'      => Types::get_cluster_post_types(),
                        'posts_per_page' => 1,
                    ] );
                    if ( empty( $clusters->posts ) ) {
                        echo 'No published clusters to test with.';
                        break;
                    }
                    $cluster = $clusters->posts[0];
                    $cluster_class = Types::get_post_type_class( get_post_type( $cluster ) );
                    $cluster = new $cluster_class( $cluster );
                    echo $this->get_email_template( 'unfollow-confirmation', [
                        'item' => $cluster,
                        'email_type' => $cluster->get_email_type(),
                        'shareable' => false,
                    ] );
                    break;

                case 'newsletter':
                    $newsletters = new \WP_Query( [
                        'post_type'      => 'pedestal_newsletter',
                        'posts_per_page' => 1,
                    ] );
                    if ( empty( $newsletters->posts ) ) {
                        echo 'No published newsletters to test with.';
                        break;
                    }
                    $newsletter = new Newsletter( $newsletters->posts[0] );
                    echo $this->get_email_template( 'newsletter', [
                        'item' => $newsletter,
                        'email_type' => 'Daily',
                        'shareable' => true,
                    ] );
                    break;

                case 'breaking-news':
                    $breaking_news = new \WP_Query( [
                        'post_type'      => Types::get_emailable_post_types(),
                        'meta_query'     => [
                            [
                                'key'   => 'sent_email',
                                'value' => 'breaking-news',
                            ],
                        ],
                        'posts_per_page' => 1,
                    ] );
                    if ( empty( $breaking_news->posts ) ) {
                        echo 'No breaking news emails to test with.';
                        break;
                    }
                    $post = Post::get_by_post_id( $breaking_news->posts[0]->ID );
                    echo $this->get_email_template( 'breaking-news', [
                        'item' => $post,
                        'email_type' => 'Breaking News',
                        'shareable' => true,
                    ] );
                    break;

                default:
                    echo '<img src="http://i.imgur.com/lnKvhQ7.jpg" alt="Invalid email type" title="dingus!"><br>';
                    break;
            }
            exit;
        }

    }

    /**
     * Add subscription-related meta boxes
     */
    public function action_add_meta_boxes() {
        foreach ( Types::get_emailable_post_types() as $post_type ) {
            $callback = '';
            $callback_args = [];
            $email_type = '';

            if ( post_type_supports( $post_type, 'breaking' ) ) {
                $email_type = 'Breaking News';
                $callback = 'handle_notify_primary_list_subscribers_meta_box';
            } elseif ( 'pedestal_newsletter' === $post_type ) {
                $email_type = 'Newsletter';
                $callback = 'handle_notify_primary_list_subscribers_meta_box';
            } elseif ( Types::is_cluster( $post_type ) ) {
                $email_type = Types::get_post_type_labels( $post_type )['singular_name'];
                $callback = 'handle_notify_cluster_subscribers_meta_box';
            }

            if ( empty( $email_type ) || ! method_exists( $this, $callback ) ) {
                return;
            }

            $email_template = sanitize_title( $email_type );
            $callback_args = [
                'email_type' => $email_type,
                'template'   => $email_template,
            ];

            add_meta_box( 'pedestal-' . $email_template .'-notify-subscribers',
                esc_html__( 'Notify ' . $email_type . ' Subscribers', 'pedestal' ),
                [ $this, $callback ],
                $post_type, 'side', 'default', $callback_args
            );
        }
    }

    /**
     * Handle the meta box to trigger an email send to EveryBlock/hood subscribers
     *
     * @param  object $post WP_Post
     * @return string HTML
     */
    public function handle_notify_hood_subscribers_meta_box( $post ) {
        $post_id = $post->ID;
        $hood = Neighborhood::get_by_post_id( (int) $post_id );
        $hood_type = Post::get_post_type( $hood );
        $type = $hood->get_type_name();

        if ( ! $hood->get_everyblock_slug() ) {
            ob_start();
            ?>

<p><?php esc_html_e( 'This neighborhood does not have an EveryBlock slug defined!', 'pedestal' ); ?></p>
<p><?php _e( 'To notify subscribers, you\'ll need to add a custom field with the key <code>everyblock-slug</code> (no backticks) with the EveryBlock slug as the value.', 'pedestal' ); ?></p>
<p><?php _e( 'If you have any other questions, ping the dev team on #product.', 'pedestal' ); ?></p>

            <?php
            echo ob_get_clean();
            return;
        }

        if ( $last_sent = $hood->get_last_everyblock_email_notification_date() ) {
            $last_sent = get_date_from_gmt( date( 'Y-m-d H:i:s', $last_sent ) );
            $last_sent = sprintf( esc_html__( 'It was last sent: %s.', 'pedestal' ), $last_sent );
        } else {
            $last_sent = esc_html__( 'It has never been sent.', 'pedestal' );
        }

        $attributes = [];
        $eb_item_count = 0;

        if ( $eb_items = $hood->get_items_since_last_everyblock_email_notification() ) :

            ob_start();
            ?>

<p><?php esc_html_e( 'Are you ready to send an email notification to those following this ' . $type . '?', 'pedestal' ); ?></p>

<p><?php esc_html_e( 'These items will be included:', 'pedestal' ); ?>
    <ol>
    <?php foreach ( $eb_items as $eb_item ) : ?>
        <li>
            <strong><?php echo $eb_item->title ?></strong>
            <br>
            <?php echo date( 'Y-m-d H:i:s', strtotime( $eb_item->pub_date ) ); ?>
        </li>
    <?php endforeach; ?>
    </ol>
</p>

            <?php
            echo ob_get_clean();

        else :

            $attributes['disabled'] = 'disabled';

            ob_start();
            ?>

<p><?php esc_html_e( 'There are no new EveryBlock items since the last email notification was sent.', 'pedestal' ); ?></p>

            <?php
            echo ob_get_clean();

        endif;

        ob_start();
        ?>

<p><?php echo esc_html( $last_sent ); ?></p>

<?php if ( empty( $attributes['disabled'] ) ) : ?>
    <input type="text" name="test-email-addresses" placeholder="<?php esc_attr_e( 'Separate addresses by commas' ); ?>" style="width:100%" />
    <?php submit_button( esc_html__( 'Send Test Email', 'pedestal' ), 'secondary', 'pedestal-everyblock-send-test-email' ); ?>
<?php endif; ?>

<hr />

<?php submit_button( sprintf( esc_html__( 'Send EveryBlock Email To %d Followers', 'pedestal' ), $hood->get_following_users_count() ), 'primary', 'pedestal-everyblock-notify-subscribers', true, $attributes ); ?>

        <?php
        echo ob_get_clean();

    }

    /**
     * Handle the meta box to trigger an email send to cluster subscribers
     *
     * @param  object $post WP_Post
     * @return string HTML
     */
    public function handle_notify_cluster_subscribers_meta_box( $post ) {
        $post_id = $post->ID;
        $cluster = Cluster::get_by_post_id( (int) $post_id );
        $cluster_type = Post::get_post_type( $cluster );
        $type = $cluster->get_type();

        if ( $last_sent = $cluster->get_last_email_notification_date() ) {
            $last_sent = get_date_from_gmt( date( 'Y-m-d H:i:s', $last_sent ) );
            $last_sent = sprintf( esc_html__( 'It was last sent: %s.', 'pedestal' ), $last_sent );
        } else {
            $last_sent = esc_html__( 'It has never been sent.', 'pedestal' );
        }

        $attributes = [];

        if ( $entities = $cluster->get_entities_since_last_email_notification() ) :

            ob_start();
            ?>

<p><?php esc_html_e( 'Are you ready to send an email notification to those following this ' . $type . '?', 'pedestal' ); ?></p>

<p><?php esc_html_e( 'These entities will be included:', 'pedestal' ); ?>
    <ol>
    <?php foreach ( $entities as $entity ) : ?>
        <li><a href="<?php echo $entity->get_the_permalink(); ?>"><?php echo $entity->get_the_title(); ?></a></li>
    <?php endforeach; ?>
    </ol>
</p>

            <?php
            echo ob_get_clean();

        else :

            $attributes['disabled'] = 'disabled';

            ob_start();
            ?>

<p><?php esc_html_e( 'There have been no entities published since the last email notification.', 'pedestal' ); ?></p>

            <?php
            echo ob_get_clean();

        endif;

        ob_start();
        ?>

<p><?php echo esc_html( $last_sent ); ?></p>

<?php if ( empty( $attributes['disabled'] ) ) : ?>
    <input type="text" name="test-email-addresses" placeholder="<?php esc_attr_e( 'Separate addresses by commas' ); ?>" style="width:100%" />
    <?php submit_button( esc_html__( 'Send Test Email', 'pedestal' ), 'secondary', 'pedestal-cluster-send-test-email' ); ?>
<?php endif; ?>

<hr />

<?php submit_button( sprintf( esc_html__( 'Send Email To %d Followers', 'pedestal' ), $cluster->get_following_users_count() ), 'primary', 'pedestal-cluster-notify-subscribers', true, $attributes ); ?>

        <?php
        echo ob_get_clean();

    }

    /**
     * Handle the meta box to trigger a newsletter or breaking news email send to subscribers
     *
     * @param  object $post WP_Post
     */
    public function handle_notify_primary_list_subscribers_meta_box( $post, $metabox ) {
        $post = Post::get_by_post_id( (int) $post->ID );
        $sent_date = $post->get_sent_date();
        $sent_num = $post->get_sent_num();
        $status = $post->get_status();
        $args = $metabox['args'];

        if ( empty( $args['email_type'] ) || empty( $args['template'] ) ) {
            echo 'Something went wrong. Please contact #product.';
            return;
        }

        $send_button_text = esc_html__( sprintf(
            'Send %s To %d Subscribers',
            $args['email_type'],
            $this->get_daily_newsletter_subscriber_count()
        ), 'pedestal' );

        $context = [
            'item'            => $post,
            'template'        => $args['template'],
            'disabled'        => false,
            'message'         => '',
            'confirm_message' => '',
            'btn_send_test'   => get_submit_button(
                esc_html__( 'Send Test Email', 'pedestal' ),
                'secondary',
                'pedestal-' . $args['template'] . '-send-test-email'
            ),
        ];

        if ( $sent_date && $sent_num ) {

            $sent_date = get_date_from_gmt( date( 'Y-m-d H:i:s', $sent_date ), PEDESTAL_DATETIME_FORMAT );
            $sent_confirm = sprintf( 'The %s email was sent on %s in %d API requests.',
                strtolower( $args['email_type'] ),
                $sent_date,
                $sent_num
            );
            $context['message'] = esc_html__( $sent_confirm, 'pedestal' );

        } elseif ( 'publish' === $status && ! $sent_date && ! $sent_num ) {

            $context['message'] = esc_html__( sprintf(
                'Are you ready to send this %s email to %d subscribers on the primary email list?',
                strtolower( $args['email_type'] ),
                Pedestal()->subscriptions->get_daily_newsletter_subscriber_count()
            ), 'pedestal' );

            if ( 'breaking-news' === $args['template'] ) {
                $context['confirm_message'] = 'Type <code>SEND BREAKING NEWS</code> below to send a Breaking News email blast!';
            }
        } elseif ( 'publish' !== $status ) {

            $context['message'] = esc_html__( sprintf(
                'The %s must be published in order to send it to subscribers on the primary email list.',
                strtolower( Post::get_post_type_name( Post::get_post_type( $post ), false ) )
            ), 'pedestal' );

        } else {
            $malformed = sprintf( '%s metadata is malformed. Please contact #product.', $args['email_type'] );
            $context['message'] = esc_html__( $malformed, 'pedestal' );
        }

        if ( empty( $context['message'] ) ) {
            echo 'Something went wrong. Please contact #product.';
            return;
        }

        $btn_attributes = [];
        if ( 'publish' !== $status || $sent_date ) {
            $context['disabled'] = true;
            $btn_attributes['disabled'] = '';
        }

        $context['btn_send'] = get_submit_button(
            $send_button_text,
            'primary',
            'pedestal-' . $args['template'] . '-notify-subscribers',
            true,
            $btn_attributes
        );

        Timber::render( 'partials/admin/metabox-send-email-primary.twig', $context );
    }

    /**
     * Handle requests to send email notifications
     */
    public function action_save_post_late( $post_id ) {
        if ( ! in_array( get_post_type( $post_id ), Types::get_emailable_post_types() ) ) {
            return;
        }

        if ( ! empty( $_POST['pedestal-everyblock-notify-subscribers'] ) ) {
            $hood = Neighborhood::get_by_post_id( (int) $post_id );
            $this->send_email_to_users_following_everyblock( $hood );
        } else if ( ! empty( $_POST['pedestal-everyblock-send-test-email'] ) ) {
            $email_addresses = array_map( 'trim', explode( ',', $_POST['test-email-addresses'] ) );
            $hood = Neighborhood::get_by_post_id( (int) $post_id );
            $subject = sprintf( '[TEST] %s Update: %s', PEDESTAL_BLOG_NAME, $hood->get_title() );
            $body = $this->get_email_template( 'follow-update-everyblock', [
                'item'                      => $hood,
                'eb_items'                  => $hood->get_items_since_last_everyblock_email_notification(),
                'email_type'                => $hood->get_email_type(),
                'mandrill_unsubscribe_link' => true,
                'shareable'                 => true,
                'email_date'                => date( 'F d, Y' ),
            ] );
            mandrill_wp_mail( implode( ',', $email_addresses ), $subject, $body );
        }

        if ( ! empty( $_POST['pedestal-cluster-notify-subscribers'] ) ) {
            $cluster = Cluster::get_by_post_id( (int) $post_id );
            $this->send_email_to_users_following_cluster( $cluster );
        } else if ( ! empty( $_POST['pedestal-cluster-send-test-email'] ) ) {
            $email_addresses = array_map( 'trim', explode( ',', $_POST['test-email-addresses'] ) );
            $cluster = Cluster::get_by_post_id( (int) $post_id );
            $subject = sprintf( '[TEST] %s Update: %s', PEDESTAL_BLOG_NAME, $cluster->get_title() );
            $body = $this->get_email_template( 'follow-update', [
                'item'                      => $cluster,
                'entities'                  => $cluster->get_entities_since_last_email_notification(),
                'email_type'                => $cluster->get_email_type(),
                'mandrill_unsubscribe_link' => true,
                'shareable'                 => true,
            ] );
            mandrill_wp_mail( implode( ',', $email_addresses ), $subject, $body );
        }

        if ( ! empty( $_POST['pedestal-newsletter-notify-subscribers'] ) ) {
            $newsletter = Newsletter::get_by_post_id( (int) $post_id );
            $this->send_primary_list_email( $newsletter );
        } else if ( ! empty( $_POST['pedestal-newsletter-send-test-email'] ) ) {
            $email_addresses = array_map( 'trim', explode( ',', $_POST['test-email-addresses'] ) );
            $newsletter = Newsletter::get_by_post_id( (int) $post_id );
            $subject = sprintf( '[TEST] %s Daily: %s', PEDESTAL_BLOG_NAME, $newsletter->get_title() );
            $body = $this->get_email_template( 'newsletter', [
                'item'                      => $newsletter,
                'email_type'                => 'Daily',
                'mandrill_unsubscribe_link' => true,
                'shareable'                 => true,
            ] );
            mandrill_wp_mail( implode( ',', $email_addresses ), $subject, $body );
        }

        if ( ! empty( $_POST['pedestal-breaking-news-notify-subscribers'] )
            && ! empty( $_POST['confirm-send-email'] )
            && 'SEND BREAKING NEWS' === strtoupper( $_POST['confirm-send-email'] ) ) {
            $post = Post::get_by_post_id( (int) $post_id );
            $this->send_primary_list_email( $post );
        } else if ( ! empty( $_POST['pedestal-breaking-news-send-test-email'] ) ) {
            $email_addresses = array_map( 'trim', explode( ',', $_POST['test-email-addresses'] ) );
            $post = Post::get_by_post_id( (int) $post_id );
            $subject = sprintf( '[TEST] BREAKING NEWS: %s', $post->get_title() );
            $body = $this->get_email_template( 'breaking-news', [
                'item'                      => $post,
                'email_type'                => 'Breaking News',
                'mandrill_unsubscribe_link' => true,
                'shareable'                 => true,
            ] );
            mandrill_wp_mail( implode( ',', $email_addresses ), $subject, $body );
        }

    }

    /**
     * Handle a request to follow an cluster
     */
    protected function handle_subscription_action_follow_cluster() {

        if ( empty( $_POST['email_address'] ) || empty( $_POST['cluster_id'] ) ) {
            status_header( 400 );
            exit;
        }

        // Honeypot
        if ( ! in_array( $_POST['website'], $this->math_honeypot_answers ) ) {
            status_header( 400 );
            echo sprintf( 'Incorrect answer! It seems you are a bot. If you are not a bot, please email %s.', PEDESTAL_EMAIL_CONTACT );
            exit;
        }

        $email_address = sanitize_email( $_POST['email_address'] );
        if ( ! is_email( $email_address ) ) {
            status_header( 400 );
            echo 'Invalid email address.';
            exit;
        }

        $cluster = Cluster::get_by_post_id( (int) $_POST['cluster_id'] );
        $valid_type = in_array( Cluster::get_post_type( $cluster ), Types::get_cluster_post_types() );
        if ( ! $cluster || ! $valid_type || 'publish' !== $cluster->get_status() ) {
            status_header( 400 );
            exit;
        }

        $user = User::get_or_create_user( $email_address );
        if ( is_wp_error( $user ) ) {
            status_header( 400 );
            echo $user->get_error_message();
            exit;
        }

        if ( ! $user->is_following_cluster( $cluster ) ) {
            $user->follow_cluster( $cluster );
            $subject = sprintf( 'You are now following "%s"', $cluster->get_title() );
            $body = $this->get_email_template( 'follow-confirmation', [
                'item'             => $cluster,
                'email_type'       => $cluster->get_email_type(),
                'unsubscribe_link' => $this->get_user_cluster_unsubscribe_link( $user->get_id(), $cluster->get_id() ),
            ] );
            mandrill_wp_mail( $user->get_email(), $subject, $body );
        }

        status_header( 200 );
        exit;

    }

    /**
     * Handle a request to sign up for the daily newsletter and breaking news
     */
    private function handle_subscription_action_signup_daily_newsletter() {

        if ( empty( $_POST['email_address'] ) || ! is_email( $_POST['email_address'] ) ) {
            status_header( 400 );
            echo 'Invalid email address.';
            exit;
        }

        // Honeypot
        if ( ! in_array( $_POST['website'], $this->math_honeypot_answers ) ) {
            status_header( 400 );
            echo sprintf( 'Incorrect answer! It seems you are a bot. If you are not a bot, please email %s.', PEDESTAL_EMAIL_CONTACT );
            exit;
        }

        $newsletter_subscribe = $this->subscribe_daily_newsletter( $_POST['email_address'] );

        if ( is_wp_error( $newsletter_subscribe ) ) {
            status_header( 400 );
            echo $newsletter_subscribe->get_error_message();
            exit;
        }

        status_header( 200 );
        exit;

    }

    /**
     * Subscribe an email address to the newsletter and send confirmation
     *
     * @param  string $email_address Email
     * @return bool|WP_Error Returns true if subscribed, false if not.
     */
    public function subscribe_daily_newsletter( $email_address ) {
        self::$errors = new \WP_Error;

        if ( ! is_email( $email_address ) ) {
            self::$errors->add( 'invalid_email_address', sprintf( '%s is not a valid email address!', $email_address ) );
            return self::$errors;
        }

        $email_address = sanitize_email( $email_address );
        $user = User::get_or_create_user( $email_address );

        if ( is_wp_error( $user ) ) {
            return $user;
        }

        if ( ! $user->is_subscribed_daily_newsletter() ) {
            $user->subscribe_daily_newsletter();
            $subject = sprintf( 'You have subscribed to %s Daily and breaking news emails', PEDESTAL_BLOG_NAME );
            $body = $this->get_email_template( 'subscribe-daily-confirmation', [
                'email_type'       => 'Daily',
                'unsubscribe_link' => $this->get_user_daily_newsletter_unsubscribe_link( $user->get_id() ),
            ] );

            mandrill_wp_mail( $user->get_email(), $subject, $body );
            return true;
        }

        self::$errors->add( 'email_already_subscribed', sprintf( 'The email address %s is already subscribed to the newsletter and breaking news emails!', $email_address ) );
        return self::$errors;
    }

    /**
     * Send an email notification to the users following an EveryBlock neighborhood
     *
     * @param Neighborhood
     */
    public function send_email_to_users_following_everyblock( $hood ) {

        $users = $hood->get_following_users();
        $merge_vars = $email_addresses = [];
        foreach ( $users as $user ) {
            $email_addresses[] = $user->user_email;
            $merge_vars[] = (object) [
                'rcpt'    => $user->user_email,
                'vars'    => [
                    (object) [
                        'name'     => 'MANDRILL_UNSUBSCRIBE_LINK',
                        'content'  => $this->get_user_cluster_unsubscribe_link( $user->ID, $hood->get_id() ),
                    ],
                ],
            ];
        }
        $merge_vars_func = function( $args ) use ( $merge_vars ) {
            $args['merge_vars'] = $merge_vars;
            return $args;
        };
        add_filter( 'mandrill_wp_mail_pre_message_args', $merge_vars_func );
        $subject = sprintf( '%s Update: %s', PEDESTAL_BLOG_NAME, $hood->get_title() );
        $body = $this->get_email_template( 'follow-update-everyblock', [
            'item'                      => $hood,
            'eb_items'                  => $hood->get_items_since_last_everyblock_email_notification(),
            'email_type'                => $hood->get_email_type(),
            'mandrill_unsubscribe_link' => true,
            'shareable'                 => true,
            'email_date'                => date( 'F d, Y' ),
        ] );
        $response = mandrill_wp_mail( implode( ',', $email_addresses ), $subject, $body );
        remove_filter( 'mandrill_wp_mail_pre_message_args', $merge_vars_func );

        if ( 200 === $response ) {
            $hood->set_last_everyblock_email_notification_date( time() );
        } else {
            add_filter( 'redirect_post_location', function( $location, $post_id ) use ( $response ) {
                remove_filter( 'redirect_post_location', __FILTER__, '99' );
                return add_query_arg( 'mandrill_resp', $response, $location );
            }, '99');
        }

    }

    /**
     * Send an email notification to the users following an cluster
     *
     * @param Cluster
     */
    private function send_email_to_users_following_cluster( $cluster ) {

        $users = $cluster->get_following_users();
        $merge_vars = $email_addresses = [];
        $cluster_id = $cluster->get_id();
        foreach ( $users as $user ) {
            $email_addresses[] = $user->user_email;
            $merge_vars[] = (object) [
                'rcpt'    => $user->user_email,
                'vars'    => [
                    (object) [
                        'name'     => 'MANDRILL_UNSUBSCRIBE_LINK',
                        'content'  => $this->get_user_cluster_unsubscribe_link( $user->ID, $cluster->get_id() ),
                    ],
                ],
            ];
        }
        $merge_vars_func = function( $args ) use ( $merge_vars ) {
            $args['merge_vars'] = $merge_vars;
            return $args;
        };

        add_filter( 'mandrill_wp_mail_pre_message_args', $merge_vars_func );
        $subject = sprintf( '%s Update: %s', PEDESTAL_BLOG_NAME, $cluster->get_title() );
        $body = $this->get_email_template( 'follow-update', [
            'item'                      => $cluster,
            'entities'                  => $cluster->get_entities_since_last_email_notification(),
            'email_type'                => $cluster->get_email_type(),
            'mandrill_unsubscribe_link' => true,
            'shareable'                 => true,
        ] );
        $response = mandrill_wp_mail( implode( ',', $email_addresses ), $subject, $body );
        remove_filter( 'mandrill_wp_mail_pre_message_args', $merge_vars_func );

        // Set the last sent email date regardless of Mandrill's response code -
        // if the email really has not sent, then this will need to be reset
        $cluster->set_last_email_notification_date( time() );

        if ( 200 === $response ) {
            $this->notify_on_subscription_action( [
                'type'        => 'cluster',
                'action'      => 'send',
                'post_id' => $cluster_id,
            ] );
        } else {
            add_filter( 'redirect_post_location', function( $location ) use ( $response ) {
                remove_filter( 'redirect_post_location', __FILTER__, '99' );
                return add_query_arg( 'mandrill_resp', $response, $location );
            }, '99');
            $msg = "*Bad response from Mandrill API upon sending follow update for cluster {$cluster_id}. Email was still most likely sent. Check API logs to be sure.*";
            $notifier = new Notifications;
            $notifier->send( $msg, [ 'channel' => self::$default_notify_channel ] );
        }
    }

    /**
     * Send an email notification to users subscribed to the primary list
     *
     * The email type depends on the post type of the supplied post.
     */
    private function send_primary_list_email( $post, $limit = 555 ) {
        $offset = 0;
        $num_emails = 1;
        $recipients = [];
        $users = get_users( [ 'pedestal_subscriptions' => 'daily-newsletter' ] );
        $num_recipients = count( $users );

        while ( $num_recipients > 0 ) {
            $merge_vars = $email_addresses = [];
            $recipients = get_users( [
                'pedestal_subscriptions' => 'daily-newsletter',
                'offset'                 => $offset,
                'number'                 => $limit,
            ] );
            $num_recipients = count( $recipients );

            if ( empty( $num_recipients ) ) {
                continue;
            }

            foreach ( $recipients as $recipient ) {
                $email = $recipient->user_email;
                $email_addresses[] = $email;
                $merge_vars[] = (object) [
                    'rcpt'    => $email,
                    'vars'    => [
                        (object) [
                            'name'     => 'MANDRILL_UNSUBSCRIBE_LINK',
                            'content'  => $this->get_user_daily_newsletter_unsubscribe_link( $recipient->ID ),
                        ],
                    ],
                ];
            }

            $merge_vars_func = function( $args ) use ( $merge_vars ) {
                $args['merge_vars'] = $merge_vars;
                return $args;
            };

            add_filter( 'mandrill_wp_mail_pre_message_args', $merge_vars_func );

            if ( post_type_supports( Post::get_post_type( $post ), 'breaking' ) ) {
                $email_type = 'Breaking News';
                $subject_email_type = strtoupper( $email_type );
                $template = 'breaking-news';
            } elseif ( 'newsletter' === $post->get_type() ) {
                $email_type = 'Daily';
                $subject_email_type = PEDESTAL_BLOG_NAME . ' ' . $email_type;
                $template = 'newsletter';
            } else {
                return;
            }

            $subject = sprintf( '%s: %s', $subject_email_type, $post->get_title() );
            $body = $this->get_email_template( $template, [
                'item'                      => $post,
                'email_type'                => $email_type,
                'mandrill_unsubscribe_link' => true,
                'shareable'                 => true,
            ] );

            $response = mandrill_wp_mail( implode( ',', $email_addresses ), $subject, $body );

            remove_filter( 'mandrill_wp_mail_pre_message_args', $merge_vars_func );

            if ( 200 === $response ) {
                $post->set_sent_flag( $template );
                $post->set_sent_date( time() );
                $post->set_sent_num( $num_emails );
            } else {
                add_filter( 'redirect_post_location', function( $location, $post_id ) use ( $response ) {
                    remove_filter( 'redirect_post_location', __FILTER__, '99' );
                    return add_query_arg( 'mandrill_resp', $response, $location );
                }, '99');
            }

            $offset += $limit;
            $num_emails++;
        }

        $notification_args = [
            'type'       => $template,
            'action'     => 'send',
            'num_emails' => $num_emails,
        ];
        if ( 'breaking-news' === $template ) {
            $notification_args['post_id'] = $post->get_id();
        }
        $this->notify_on_subscription_action( $notification_args );
    }

    /**
     * Get the number of users subscribed to the daily newsletter and breaking news
     *
     * @return int
     */
    public function get_daily_newsletter_subscriber_count() {
        $key = 'daily_newsletter_subscriber_count';
        if ( $subscriber_count = get_transient( $key ) ) {
            // Our work here is done. Take the rest of the day off.
            return $subscriber_count;
        }

        $args = [
            'pedestal_subscriptions' => 'daily-newsletter',
            'count_total'            => true,
        ];
        $user_query = new \WP_User_Query( $args );
        $subscriber_count = $user_query->get_total();
        set_transient( $key, $subscriber_count, 12 * HOUR_IN_SECONDS );
        return $subscriber_count;
    }

    /**
     * Get the unsubscribe link for a user to unsubscribe from the daily newsletter and breaking news
     *
     * @param int $user_id
     * @return string
     */
    private function get_user_daily_newsletter_unsubscribe_link( $user_id ) {
        return home_url( sprintf( 'unsubscribe/daily-newsletter/%d/%s', $user_id, $this->generate_secure_hash( 'unsubscribe-' . $user_id . '-daily-newsletter' ) ) );
    }

    /**
     * Get the unsubscribe link for a user to unsubscribe from an cluster
     *
     * @param int $user_id
     * @param int $cluster_id
     * @return string
     */
    private function get_user_cluster_unsubscribe_link( $user_id, $cluster_id ) {
        return home_url( sprintf( 'unsubscribe/cluster/%d/%d/%s', $user_id, $cluster_id, $this->generate_secure_hash( 'unsubscribe-' . $user_id . '-cluster-' . $cluster_id ) ) );
    }

    /**
     * Generate a secure hash to represent the action
     *
     * @param string
     * @return string
     */
    private function generate_secure_hash( $action ) {
        return hash( 'sha256', constant( 'AUTH_KEY' ) . constant( 'SECURE_AUTH_KEY' ) . $action );
    }

    /**
     * Get a rendered email template
     *
     * @param string $template_name
     * @param array $vars
     * @return string
     */
    private function get_email_template( $template_name, $vars = [] ) {

        $vars['template_name'] = $template_name;
        $full_path = get_template_directory() . '/email.php';
        if ( ! file_exists( $full_path ) ) {
            return '';
        }

        ob_start();
        include $full_path;
        return ob_get_clean();

    }

    /**
     * Notify Slack of the current newsletter subscriber count
     */
    public function notify_newsletter_subscriber_count( $notification_args = [] ) {
        $count = $this->get_daily_newsletter_subscriber_count();

        if ( empty( $count ) ) {
            return;
        }

        $notification_args = wp_parse_args( $notification_args, [
            'channel' => PEDESTAL_SLACK_CHANNEL_NEWSLETTER,
        ] );

        $msg = sprintf( 'There are currently %d email addresses subscribed to the Daily Newsletter and Breaking News emails.', $count );
        $notifier = new Notifications;
        $notifier->send( $msg, $notification_args );
    }

    /**
     * Handle subscription action notifications
     *
     * @param  array  $args Email action settings
     */
    private function notify_on_subscription_action( $args = [] ) {
        $args = wp_parse_args( $args, [
            'user_id' => 0,
            'type'    => '',
            'action'  => '',
            'post_id' => 0,
        ] );

        // Define allowed subscription types and actions
        $types = [ 'newsletter', 'cluster', 'breaking-news' ];
        $actions = [ 'subscribe', 'unsubscribe', 'send' ];
        $types_needing_post_id = [ 'cluster', 'breaking-news' ];

        // Subscription type and action are always required
        if ( ! in_array( $args['type'], $types )
            || ! in_array( $args['action'], $actions )
        ) {
            return;
        }

        // User ID is required for all non-sending subscription type
        if ( 'send' !== $args['action'] && empty( $args['user_id'] ) ) {
            return;
        }

        // If subscription type needs a post ID and the ID is missing, then return
        if ( in_array( $args['type'], $types_needing_post_id ) && empty( $args['post_id'] ) ) {
            return;
        }

        switch ( $args['type'] ) {
            case 'breaking-news':
            case 'cluster':
                $post = Post::get_by_post_id( $args['post_id'] );
                $title = $post->get_type() . ' “' . $post->get_title() . '”';
                break;
            case 'newsletter':
                $title = 'Daily Newsletter';
                break;
            default:
                $title = $args['type'];
                break;
        }

        if ( 'breaking-news' === $args['type'] ) {
            $title = 'Breaking News email for ' . $title;
        }

        switch ( $args['action'] ) {
            case 'subscribe':
                $action = 'subscribed to';
                break;
            case 'unsubscribe':
                $action = 'unsubscribed from';
                break;
            case 'send':
                $action = 'sent';
                break;
            default:
                return;
                break;
        }

        if ( 'send' === $args['action'] ) {
            switch ( $args['type'] ) {
                case 'cluster':
                    $msg_str = 'Follow update for %s with ID %d %s';
                    $msg = sprintf( $msg_str,
                        $title,
                        $args['post_id'],
                        $action
                    );
                    break;
                case 'breaking-news':
                case 'newsletter':
                    $msg = sprintf( '%s %s',
                        $title,
                        $action
                    );
                    if ( ! empty( $args['num_emails'] ) ) {
                        $msg .= sprintf( ' in %d API requests', $args['num_emails'] );
                    }
                    break;
                default:
                    return;
                    break;
            }
        } else {
            $user = new User( $args['user_id'] );
            $msg = sprintf( 'User %d with email address %s %s %s',
                $args['user_id'],
                $user->get_email(),
                $action,
                $title
            );
        }

        $notification_args = [ 'channel' => self::$default_notify_channel ];
        if ( ! empty( $msg ) ) {
            $notifier = new Notifications;
            $notifier->send( $msg, $notification_args );
        }
    }
}
