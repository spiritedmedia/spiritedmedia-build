<?php

namespace Pedestal\Posts\Clusters\Geospaces\Localities;

use \Pedestal\Utils\Utils;

use \Pedestal\Posts\Clusters\Geospaces\Place;

/**
 * Locality
 */
class Locality extends Place {

    /**
     * Term ID of the Locality Type
     *
     * @var integer
     */
    protected $locality_type_id = 0;

    protected static $post_type = 'pedestal_locality';

    protected $email_type = '';

    public function __construct( $post ) {
        global $wp;
        parent::__construct( $post );
        $this->set_locality_type_id();
        $this->set_email_type();

        // Cache these objects throughout the duration of the page request...
        if ( ! isset( $wp->pedestal_locality_cache ) || ! is_array( $wp->pedestal_locality_cache ) ) {
            $wp->pedestal_locality_cache = [];
        }
    }

    /**
     * Get the proper Locality object based on its post ID
     *
     * @param int $post_id
     */
    public static function get_by_post_id( $post_id ) {
        global $wp;

        if ( ! $post_id ) {
            return false;
        }

        // Have we already gotten this post object?
        if ( isset( $wp->pedestal_locality_cache[ $post_id ] ) && ! empty( $wp->pedestal_locality_cache[ $post_id ] ) ) {
            return $wp->pedestal_locality_cache[ $post_id ];
        }

        $post = get_post( $post_id );
        if ( ! $post ) {
            // Cache it!
            $wp->pedestal_locality_cache[ $post_id ] = false;
            return false;
        }

        // If the requested post is a Locality, then instantiate it using the
        // Locality Type class as described below. If not, then use the parent's
        // `get_by_post_id()` method.
        if ( get_post_type( $post ) === self::$post_type  ) {

            // If there is an existing class matching the Locality Type's
            // expected class name, then instantiate it. If not, then
            // instantiate the Locality class.
            //
            // Although a Locality Type class will be in the same namespace,
            // `class_exists()` requires the full namespace specified
            $class_name = self::get_class_name( $post_id );
            $class = '\\' . __NAMESPACE__ . '\\' . $class_name;
            if ( ! class_exists( $class ) || empty( $class_name ) ) {
                $class = static::class;
            }
            // Cache it!
            $wp->pedestal_locality_cache[ $post_id ] = new $class( $post );
            return $wp->pedestal_locality_cache[ $post_id ];

        } else {
            // Cache it!
            $wp->pedestal_locality_cache[ $post_id ] = parent::get_by_post_id( $post_id );
            return $wp->pedestal_locality_cache[ $post_id ];
        }
    }

    /**
     * Get the expected class name for the Locality Type given its ID
     *
     * @TODO In order to be accessible to the static method `get_by_post_id()`
     *     we're unable to use our single-purpose methods for getting meta and
     *     term IDs, instead bascailly rewriting them here. An ideal solution
     *     would be moving away from static methods as much as possible.
     *
     * @param int $post_id  Locality ID
     * @return string Class name without namespace
     */
    public static function get_class_name( $post_id ) {
        $type_id = (int) get_post_meta( $post_id, 'locality_type', true );

        if ( empty( $type_id ) ) {
            return false;
        }

        $term = get_term( $type_id, 'pedestal_locality_type' );
        $name = ucwords( str_replace( '-', ' ', sanitize_title( $term->name ) ) );
        return str_replace( ' ', '_', $name );
    }

    /**
     * Set up the Locality's HTML data attributes
     */
    protected function set_data_atts() {
        parent::set_data_atts();
        $atts = parent::get_data_atts();
        $new_atts = [
            'locality-type' => $this->get_locality_type_term_property( 'slug' ),
        ];
        $this->data_attributes = array_merge( $atts, $new_atts );
    }

    /**
     * Set the name of the email type based on the Locality Type
     */
    private function set_email_type() {
        $name = $this->get_locality_type_name();
        if ( ! empty( $name ) ) {
            $this->email_type = $name . ' updates';
        }
    }

    /**
     * Get the Locality's Type name
     *
     * @return string|bool
     */
    public function get_locality_type_name() {
        return $this->get_locality_type_term_property( 'name' );
    }

    /**
     * Get a property from the Locality Type term object
     *
     * @param  string $field Property key
     * @return mixed         Property value or false on failure
     */
    protected function get_locality_type_term_property( $field ) {
        $term = $this->get_locality_type_term();
        if ( ! empty( $term ) && ! is_wp_error( $term ) && ! empty( $term->$field ) ) {
            return $term->$field;
        }
        return false;
    }

    /**
     * Get the Locality's Type term object
     *
     * @return WP_Term|WP_Error|bool
     */
    public function get_locality_type_term() {
        $type_id = $this->get_locality_type_id();
        if ( ! empty( $type_id ) ) {
            return get_term( $type_id, 'pedestal_locality_type' );
        }
        return false;
    }

    /**
     * Get the Locality's Type ID
     *
     * @return int
     */
    public function get_locality_type_id() {
        return $this->locality_type_id;
    }

    /**
     * Set up the Locality's Type ID
     */
    public function set_locality_type_id() {
        $errors = new \WP_Error;
        $type_id = $this->get_meta( 'locality_type' );
        if ( is_numeric( $type_id ) ) {
            $this->locality_type_id = (int) $type_id;
        }
    }
}
