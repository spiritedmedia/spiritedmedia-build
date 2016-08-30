<?php

namespace Pedestal\Posts\Clusters;

use \Pedestal\Utils\Utils;

class Topic extends Cluster {

    protected static $post_type = 'pedestal_topic';

    protected $email_type = 'topic updates';

}
