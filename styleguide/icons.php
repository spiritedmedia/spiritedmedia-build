<?php
include 'include.php';
styleguide_header();
?>
    <style>
    .example {
        margin-bottom: 5em;
    }
    .icon-example {
        display: inline-block;
        width: 1rem;
    }
    </style>
    <div class="content-wrapper row">
		<main class="c-main columns large-8 js-main" role="main">
            <header class="c-main__header">
                <h1 class="c-main__title">Icons</h1>
                <div class="c-main__excerpt">

<p>We load SVG icons from a folder in Pedestal using the Twig function
<code>ped_icon()</code>. Most of these are currently from <a
href="http://fontawesome.io/">Font Awesome</a> but we can use any custom icon.
Below is a list of the icons we use throughout the sites:</p>

                </div>
            </header>

			<?php
				$icons = [
                    'angle-left'    => 'Angle Left',
                    'angle-right'   => 'Angle Right',
                    'bars'          => 'Bars AKA Hamburger',
                    'birthday-cake' => 'Birthday Cake',
                    'briefcase'     => 'Briefcase',
                    'calendar'      => 'Calendar',
                    'close'         => 'Close / X (multiplication sign)',
                    'envelope-o'    => 'Envelope',
                    'external-link' => 'External Link',
                    'facebook'      => 'Facebook',
                    'instagram'     => 'Instagram',
                    'level-down'    => 'Level Down',
                    'linkedin'      => 'LinkedIn',
                    'play'          => 'Play',
                    'scribd'        => 'Scribd',
                    'search'        => 'Search',
					'twitter'       => 'Twitter',
				    'vine'          => 'Vine',
				    'youtube'       => 'YouTube',
				];
				foreach ( $icons as $icon => $description ) {
					echo '<p>';
                    echo '<div class="icon-example">';
                    echo styleguide_icon( $icon );
                    echo '</div>';
                    echo ' &emsp; ' . $description . ' &mdash; <code>' . $icon . '</code></p>';
				}
			?>

            <hr>

            <p><code>.o-icon-text</code></p>
            <div class="example">
                <div class="o-icon-text">
                    <?php styleguide_icon( 'facebook', 'o-icon-text__icon' ); ?>
                    <span class="o-icon-text__text">Icon Text</span>
                </div>
            </div>

            <p><code>.o-icon-text--rev</code></p>
            <div class="example">
                <div class="o-icon-text o-icon-text--rev">
                    <span class="o-icon-text__text">Icon Text</span>
                    <?php styleguide_icon( 'calendar', 'o-icon-text__icon' ); ?>
                </div>
            </div>

        </main>
		<aside class="rail columns large-4"></aside>
    </div>
    <?php
styleguide_footer();
