<?php

namespace Pedestal\Posts\Entities\Originals;

class Article extends Original {

    use \Pedestal\Posts\Emailable;

    protected static $post_type = 'pedestal_article';

    /**
     * Get the Twig context for this post
     *
     * @param array Existing context to filter
     * @return array Twig context
     */
    public function get_context( $context = [] ) {
        $context = parent::get_context( $context );
        $context['content_classes'][] = 's-post-content';
        return $context;
    }
}
