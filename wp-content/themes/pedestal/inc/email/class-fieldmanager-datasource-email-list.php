<?php
namespace Pedestal\Email;

use function Pedestal\Pedestal;

use Pedestal\Objects\ActiveCampaign;
use \Fieldmanager_Datasource;

class Fieldmanager_Datasource_Email_List extends Fieldmanager_Datasource {

    /**
     * This datasource should use AJAX
     *
     * @param boolean $options  Yes
     */
    public $use_ajax = true;

    /**
     * Name of the option for storing all lists for a site
     *
     * @var string
     */
    private static $all_lists_option_name = 'activecampaign-all-lists';

    /**
     * Setup the datasource
     *
     * @param array $options Set of options
     */
    public function __construct( $options = [] ) {
        parent::__construct( $options );
        $this->activecampaign = ActiveCampaign::get_instance();
    }

    /**
     * Transform the saved value from an ID to a human readable title
     *
     * @param  Integer $val  The saved ID
     * @return String        Title of list
     */
    public function get_value( $val = 0 ) {
        $val = intval( $val );
        if ( ! $val || '-1' == $val ) {
            return '';
        }
        $list = $this->activecampaign->get_list( $val );
        return $this->get_list_name( $list );
    }

    /**
     * Fetch lists optionally filtered to match a given fragment
     * @param  string $fragment  String to match list names against for filtering
     * @return array             Array of lists keyed by their numeric ID
     */
    public function get_items( $fragment = null ) {
        // Get only lists with 1 or more subscribers
        $lists = $this->get_all_lists_with_subscribers();
        if ( $fragment ) {
            // If the fragment is numeric we can look up the list directly
            if ( is_numeric( $fragment ) ) {
                $lists = $this->activecampaign->get_list( $fragment );
                $lists = [ $lists ];
            } else {
                // Otherwise we filter the lists whose name partially matches the fragment
                $lists = array_filter( $lists, function( $obj ) use ( $fragment ) {
                    if ( isset( $obj->name ) && -1 < stripos( $obj->name, $fragment ) ) {
                        return true;
                    }
                    return false;
                } );
            }
        }
        $output = [];
        foreach ( $lists as $list ) {
            // If no list id is set then on to the next one
            if ( ! isset( $list->id ) ) {
                continue;
            }
            $output[ $list->id ] = $this->get_list_name( $list );
        }

        // If we have no output return a helpful message instead
        if ( empty( $output ) ) {
            $output['-1'] = 'No lists found!';
        }
        return $output;
    }

    /**
     * Get the name of a list from a given list object from ActiveCampaign
     *
     * @param  object $list  A list object from ActiveCampaign
     * @return string        Name of the list
     */
    private function get_list_name( $list = false ) {
        if ( ! is_object( $list ) ) {
            return '';
        }
        $name = '';
        if ( isset( $list->name ) ) {
            $name = $this->activecampaign->scrub_list_name( $list->name );
        }
        if ( isset( $list->subscriber_count ) ) {
            $name .= ' (' . $list->subscriber_count . ' subs)';
        }
        return $name;
    }

    /**
     * Delete the option that stores info about all of the lists from ActiveCampaign
     */
    private function purge_all_lists() {
        delete_option( $this->$all_lists_option_name );
    }

    /**
     * Fetch all lists from ActiveCampaign and cache them in an option
     *
     * @return array   A set of objects about lists
     */
    private function get_all_lists() {
        $lists = get_option( $this->$all_lists_option_name );
        if ( $lists ) {
            return $lists;
        }

        $args = [
            'filters[name]' => '- ' . PEDESTAL_BLOG_NAME,
        ];
        $lists = $this->activecampaign->get_lists( $args );
        if ( $lists ) {
            foreach ( $lists as $list ) {
                // Clean up the list names before storing
                $list->name = $this->activecampaign->scrub_list_name( $list->name );
            }
        }
        $autoload = 'no';
        add_option( $this->$all_lists_option_name, (array) $lists, '', $autoload );
        return $lists;
    }

    /**
     * Filter all lists to the ones that have subscribers
     *
     * @return array   A set of objects with lists that have 1 or more subscribers
     */
    private function get_all_lists_with_subscribers() {
        $lists = $this->get_all_lists();
        $output = [];
        foreach ( $lists as $list ) {
            if ( isset( $list->subscriber_count ) && 0 < $list->subscriber_count ) {
                $output[] = $list;
            }
        }
        return $output;
    }
}
