<?php

namespace Pedestal\Posts;

class Page extends Post {

    protected static $post_type = 'page';

    /**
     * Get CSS classes
     *
     * @return array
     */
    public function get_css_classes() {
        $classes = parent::get_css_classes();
        // @TODO should `entity` really be included as a new css class here, or
        //     should page php class extend entity php class?
        $classes = array_merge( [
            'entity',
        ], $classes );
        return $classes;
    }
}
