<?php
include 'include.php';
styleguide_header();
?>
    <style>
    .example {
        margin-bottom: 5em;
    }
    </style>
    <div class="content-wrapper row">
		<main class="main columns large-8 js-main" role="main">
			<p>We use the <a href="http://fontawesome.io/">Font Awesome</a> library for icons but will oneday switch to injecting SVGs inline. Below are a list of the icons we use thorughtout the sites:</p>
			<?php
				$icons = [
					'external-link' => 'External Link',
					'facebook' => 'Facebook',
					'twitter' => 'Twitter',
				    'linkedin' => 'LinkedIn',
				    'instagram' => 'Instagram',
				    'vine' => 'Vine',
				    'youtube' => 'YouTube',
				    'calendar' => 'Calendar',
				    'envelope' => 'Envelope',
					'angle-left' => 'Angle Left',
					'angle-right' => 'Angle Right',
					'level-down' => 'Level Down',
					'search' => 'Search',
					'times' => 'Times (multiplication sign)',
					'briefcase' => 'Briefcase',
					'birthday-cake' => 'Birthday Cake',
				];
				foreach ( $icons as $icon => $description ) {
					echo '<p><i class="fa fa-' . $icon . '" aria-hidden="true"></i> - ' . $description . ' (<code>fa-' . $icon . '</code>)</p>';
				}
			?>
			<p>See <a href="https://github.com/spiritedmedia/spiritedmedia/issues/1708">https://github.com/spiritedmedia/spiritedmedia/issues/1708</a> for how these icons are used.</p>

            <hr>

            <p><code>.o-icon-text</code></p>
            <div class="example">
                <div class="o-icon-text">
                    <i class="o-icon-text__icon fa fa-calendar"></i>
                    <span class="o-icon-text__text">Icon Text</span>
                </div>
            </div>

            <p><code>.o-icon-text--rev</code></p>
            <div class="example">
                <div class="o-icon-text o-icon-text--rev">
                    <span class="o-icon-text__text">Icon Text</span>
                    <i class="o-icon-text__icon fa fa-calendar"></i>
                </div>
            </div>

            <p><code>.o-icon-text--responsive</code></p>
            <div class="example">
                <div class="o-icon-text o-icon-text--responsive">
                    <i class="o-icon-text__icon fa fa-calendar"></i>
                    <span class="o-icon-text__text">Icon Text</span>
                </div>
            </div>

        </main>
		<aside class="rail columns large-4"></aside>
    </div>
    <?php
styleguide_footer();
