<?php

namespace Pedestal\Posts\Entities\Originals;

class Article extends Original {

    use \Pedestal\Posts\Emailable;

    protected static $post_type = 'pedestal_article';

    /**
     * Get the Twig context for this post
     *
     * @return array Twig context
     */
    public function get_context() {
        $context = parent::get_context();
        $context['content_classes'][] = 's-post-content';
        return $context;
    }
}
