<?php

namespace Pedestal\Posts\Entities;

class Article extends Entity {

    use \Pedestal\Posts\EditorialContent;

    use \Pedestal\Posts\Emailable;

    protected static $post_type = 'pedestal_article';

}
