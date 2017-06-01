<?php
include 'include.php';
styleguide_header();
?>
    <div class="content-wrapper row">
    <main class="c-main columns large-8 js-main" role="main">
            <header class="c-main__header">
                    <h2 class="c-main__title">Utilities</h2>
                    <div class="c-main__excerpt">
                        <p>These are utility classes found in <a href="https://github.com/spiritedmedia/spiritedmedia/blob/master/wp-content/themes/pedestal/assets/scss/utilities/_utilities.scss">https://github.com/spiritedmedia/spiritedmedia/blob/master/wp-content/themes/pedestal/assets/scss/utilities/_utilities.scss</a></p>
                    </div>
                </header>

      <p><code>.u-text-color-primary</code></p>
      <p class="u-text-color-primary">This <em>should</em> be in the site's <strong>primary</strong> color!</p>
      <hr>

      <?php
        $classes = [
          'u-size-h1' => 'H1',
          'u-size-h2' => 'H2',
          'u-size-h3' => 'H3',
          'u-size-h4' => 'H4',
          'u-size-h5' => 'H5',
          'u-size-h6' => 'H6',
        ];

        $elements = [
          'h1' => 'H1',
          'h2' => 'H2',
          'h3' => 'H3',
          'h4' => 'H4',
          'h5' => 'H5',
          'h6' => 'H6',
          'p' => 'paragraph',
        ];

        foreach ( $classes as $class => $label ) {
          echo '<p><code>.' . $class . '</code></p>';
          foreach ( $elements as $elem => $descrip ) {
            echo '<' . $elem . ' class="' . $class . '">I\'m a ' . $descrip . ' but should be the same size as a ' . $label . '</' . $elem . '>';
          }

          echo '<hr>';
        }
      ?>

      <p>Our loading spinner&hellip;</p>
      <div class="c-spinner js-spinner" style="display: block;">
          <div class="c-spinner__inner sk-three-bounce">
              <div class="sk-child sk-bounce1"></div>
              <div class="sk-child sk-bounce2"></div>
              <div class="sk-child sk-bounce3"></div>
          </div>
      </div>
    </main>
    <aside class="rail columns large-4"></aside>
    </div>
    <?php
styleguide_footer();
