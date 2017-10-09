<?php
include '../include.php';
$url_groups = [
    [
        'prod'  => 'https://billypenn.com',
        'stage' => 'https://staging.billypenn.com',
        'dev'   => 'http://billypenn.dev',
    ],
    [
        'prod'  => 'https://theincline.com',
        'stage' => 'https://staging.theincline.com',
        'dev'   => 'http://theincline.dev',
    ],
    [
        'prod'  => 'https://www.denverite.com',
        'stage' => 'http://dnvrite.staging.wpengine.com',
        'dev'   => 'http://denverite.local',
    ],
];

$left_url = 'https://staging.billypenn.com';
$right_url = 'https://billypenn.com';
if ( isset( $_GET['url'] ) ) {
    $submitted_url = strtolower( $_GET['url'] );
    foreach ( $url_groups as $env ) {
        foreach ( $env as $label => $url ) {
            if ( strpos( $submitted_url, $url ) > -1 ) {
                switch ( $label ) :
                    case 'prod':
                        $left_url = str_replace( $env['prod'], $env['stage'], $submitted_url );
                        $right_url = $submitted_url;
                        break;

                    case 'stage':
                        $left_url = $submitted_url;
                        $right_url = str_replace( $env['stage'], $env['prod'], $submitted_url );
                        break;

                    case 'dev':
                        $left_url = $submitted_url;
                        $right_url = str_replace( $env['dev'], $env['prod'], $submitted_url );
                        break;
                endswitch;

                break;
            }
        }
    }
}
?>
<body>
<style>
/* Reset */
body,
form,
input,
p {
    margin: 0;
    padding: 0;
}
body {
    background-color: #000;
}
div {
    width: calc(50% - 1px);
    float: left;
}
.controls {
    display: flex;
    flex-flow: row;
    width: 100%;
}
.controls .url-bar {
    flex: 1 1 0;
}
.controls .url-bar input {
    padding: 0.25em 0.5em;
    font-size: 1.2em;
    width: 100%;
    height: 2.3em;
}
.controls .link-button {
    flex: 0 0 2em;
    display: flex;
}
.controls .link-button button {
    font-size: 1.5em;
    cursor: pointer;
    background: none;
    background-color: white;
    border: 0;
}
#right {
    float: right;
}
iframe {
    height: 1000em; /* Leave lots of extra room */
    width: 100%;
}
</style>

<div id="left">
    <div class="controls">
        <form class="url-bar" method="get">
            <input type="url" value="<?php echo esc_url( $left_url ); ?>" name="url">
        </form>
        <form class="link-button" action="<?php echo esc_url( $left_url ); ?>" method="get" target="_blank">
            <button>&#128279;</button>
        </form>
    </div>
    <iframe src="<?php echo esc_url( $left_url ); ?>" frameborder="0" scrolling="no"></iframe>
</div>

<div id="right">
    <div class="controls">
        <form class="url-bar" method="get">
            <input type="url" value="<?php echo esc_url( $right_url ); ?>" name="url">
        </form>
        <form class="link-button" action="<?php echo esc_url( $right_url ); ?>" method="get" target="_blank">
            <button>&#128279;</button>
        </form>
    </div>
    <iframe src="<?php echo esc_url( $right_url ); ?>" frameborder="0" scrolling="no"></iframe>
</div>
</body>
