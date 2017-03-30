<?php

namespace Pedestal\Posts\Entities\Originals;

class Article extends Original {

    use \Pedestal\Posts\Emailable;

    protected static $post_type = 'pedestal_article';

}
