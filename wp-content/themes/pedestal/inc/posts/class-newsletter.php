<?php

namespace Pedestal\Posts;

class Newsletter extends Post {

    use \Pedestal\Posts\Emailable;

    protected static $post_type = 'pedestal_newsletter';

    /**
     * Get CSS classes
     *
     * @return array
     */
    public function get_css_classes() {
        $classes = parent::get_css_classes();
        // @TODO should `entity` really be included as a new css class here, or
        //     should newsletter php class extend entity php class?
        $classes = array_merge( [
            'entity'
        ], $classes );
        return $classes;
    }
}
