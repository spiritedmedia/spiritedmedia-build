<?php

namespace Pedestal\Posts\Clusters\Geospaces\Localities;

use \Pedestal\Utils\Utils;

use Pedestal\Objects\Stream;

use Pedestal\Posts\Attachment;

use EveryBlock\EveryBlock;

/**
 * Neighborhood
 */
class Neighborhood extends Locality {

    protected $email_type = 'neighborhood updates';

    /**
     * Get cached EveryBlock news items.
     *
     * If there is data for a Redis key matching the current neighborhood slug,
     * return it. If the cache is empty, then set it and return the new value.
     *
     * @param  bool         Override the cache. Default is false.
     * @return array|string Array of EveryBlock item objects if successful, or
     *     an error message if unsuccessful.
     */
    public function get_everyblock( $force = false ) {
        $data = $this->get_everyblock_cache();
        if ( $data ) {
            return $data;
        } elseif ( ! empty( $this->set_everyblock_cache( $force ) ) ) {
            return $this->get_everyblock_cache();
        } else {
            return false;
        }
    }

    /**
     * Get this neighborhood's cached EveryBlock data.
     *
     * @return array|bool
     */
    private function get_everyblock_cache() {
        $slug = $this->get_name();
        $cache = wp_cache_get( $slug, 'everyblock' );
        if ( $cache ) {
            return json_decode( $cache );
        } else {
            return false;
        }
    }

    /**
     * Set this neighborhood's cached EveryBlock data.
     *
     * The key is equal to this neighborhood's slug (not the EveryBlock slug as
     * that is unpredictable).
     *
     * TTL is 15 minutes.
     *
     * @param  bool        Force override the cache. Default is false.
     * @return bool|string Returns true if successfully set, or else returns an error message.
     */
    private function set_everyblock_cache( $force = false ) {
        $eb = $this->get_everyblock_data();
        if ( ! empty( $eb ) ) {
            $data = json_encode( $eb );
            $slug = $this->get_name();
            $this->set_everyblock_updated();
            if ( $force ) {
                return (bool) wp_cache_set( $slug, $data, 'everyblock', 900 );
            }
            return (bool) wp_cache_add( $slug, $data, 'everyblock', 900 );
        } else {
            return false;
        }
    }

    /**
     * Get the time the EveryBlock cache was last updated
     *
     * @param  string $format Datetime format. Defaults to Pedestal default.
     * @return string
     */
    public function get_everyblock_updated( $format = PEDESTAL_DATETIME_FORMAT ) {
        $timestamp = $this->get_field( 'everyblock-updated' );
        if ( $timestamp ) {
            return date( $format, $timestamp );
        } else {
            return false;
        }
    }

    /**
     * Set the time the EveryBlock cache was last updated
     *
     * Post meta with key 'everyblock-updated' must exist prior to invocation.
     *
     * @param integer $time Unix timestamp for the updated time
     */
    private function set_everyblock_updated( $time = 0 ) {
        $time = Utils::get_time( $time );
        update_post_meta( $this->get_id(), 'everyblock-updated', $time );
    }

    /**
     * Get an EveryBlock error message.
     *
     * @return string
     */
    public function get_everyblock_error() {
        $error = '<div data-alert class="alert-box warning">';
        $error .= '<small>Sorry, there was an error retrieving data from EveryBlock. Please refresh or try again later. If the problem persists, please <a href="mailto:contact@billypenn.com">contact us</a>.</small>';
        $error .= '<a href="#" class="close">&times;</a>';
        $error .= '</div>';
        return $error;
    }

    /**
     * Get the EveryBlock slug corresponding to this neighborhood
     *
     * If there are multiple slugs stored in the 'everyblock-slug' field, then
     * will return that array of slugs.
     *
     * @return string|array
     */
    public function get_everyblock_slug() {
        return $this->get_field( 'everyblock-slug' );
    }

    /**
     * Get the EveryBlock data using this neighborhood's slug.
     *
     * @return array An array of API request results as objects
     */
    private function get_everyblock_data() {
        $response = $data = [];
        $here = $this->get_everyblock_slug();
        if ( $eb = new EveryBlock( EVERYBLOCK_API_KEY, 'philly' ) ) {
            $args = [
                'schema' => [
                    'service-requests',
                    'crime',
                ],
                'date' => 'descending',
            ];

            $i = 0;

            if ( is_array( $here ) ) {

                foreach ( $here as $slug => $name ) {
                    if ( $eb ) {
                        $response = $eb->timeline( $slug, false, $args );
                        $items = $response->results;

                        foreach ( $items as $item ) {
                            $item->section_slug = (string) $slug;
                            $item->section_name = (string) $name;
                            $data[] = $item;
                        }
                    } else {
                        return false;
                    }
                }
            } else {
                if ( $eb ) {
                    $response = $eb->timeline( $here, false, $args );
                    $data = $response->results;
                } else {
                    return false;
                }
            }

            return array_reverse( Utils::sort_obj_array_by_prop( (array) $data, 'pub_date' ) );

        } else {
            return false;
        }

    }

    /**
     * Whether or not this neighborhood has a postcard
     *
     * @return boolean
     */
    public function has_postcard() {
        return (bool) $this->get_postcard();
    }

    /**
     * Get this neighborhood's postcard
     *
     * @return obj An attachment object
     */
    public function get_postcard() {
        if ( $attachment = Attachment::get_by_post_id( $this->get_postcard_id() ) ) {
            return $attachment;
        } else {
            return false;
        }
    }

    /**
     * Get this neighborhood's postcard ID
     *
     * @return int|false
     */
    public function get_postcard_id() {
        return (int) $this->get_field( 'postcard' );
    }

    /**
     * Get this neighborhood's postcard URL
     *
     * @param  string $size
     * @param  array  $args
     * @return string
     */
    public function get_postcard_url( $size = 'full', $args = [] ) {
        $attachment = $this->get_postcard();
        if ( $attachment ) {
            return $attachment->get_url( $size, $args );
        } else {
            return '';
        }
    }

    /**
     * Get the HTML for the postcard
     *
     * @param  string $size
     * @param  array  $args
     * @return string
     */
    public function get_postcard_html( $size = 'full', $args = [] ) {
        $attachment = $this->get_postcard();
        if ( $attachment && method_exists( $attachment, 'get_html' ) ) {
            return $attachment->get_html( $size, $args );
        } else {
            return '';
        }
    }

    /**
     * Get the time of the last EveryBlock email notification
     *
     * @return mixed
     */
    public function get_last_everyblock_email_notification_date( $format = 'U' ) {
        if ( $last_date = $this->get_meta( 'last_everyblock_email_notification_date' ) ) {
            return date( $format, strtotime( date( 'Y-m-d H:i:s', $last_date ) ) );
        } else {
            return false;
        }
    }

    /**
     * Set the time of the last EveryBlock email notification
     */
    public function set_last_everyblock_email_notification_date( $time ) {
        $this->set_meta( 'last_everyblock_email_notification_date', $time );
    }

    /**
     * Get the entities since last EveryBlock email notification
     *
     * @param  bool  $limit Limit the number of recent items returned? Default is true.
     * @return array
     */
    public function get_items_since_last_everyblock_email_notification( $limit = true ) {
        $new_items = $all_items = [];
        $last_sent = $this->get_last_everyblock_email_notification_date();
        $items = $this->get_everyblock( true );

        $i = 0;
        foreach ( $items as $item ) {
            if ( 5 === $i && $limit ) {
                break;
            }
            $pub_date = strtotime( $item->pub_date );
            if ( $pub_date > $last_sent ) {
                $new_items[] = $item;
            }

            $all_items[] = $item;
            $i++;
        }

        if ( $last_sent ) {
            return $new_items;
        } else {
            return $all_items;
        }
    }
}
