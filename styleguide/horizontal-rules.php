<?php
include 'include.php';
styleguide_header();
?>
    <style>
        hr {
            margin-bottom: 3.5em;
        }
    </style>
    <div class="content-wrapper row">
		<main class="c-main columns large-8 js-main" role="main">
            <p>Generic, no classes or styling</p>
            <hr>

            <p><code>.o-rule</code> A noticeably thick horizontal rule before an element</p>
            <hr class="o-rule">

            <p><code>.o-rule--slim</code> A slightly less obtrusive rule</p>
            <hr class="o-rule o-rule--slim">

            <p><code>.o-rule--closure</code> A rule with a logo centered in the middle</p>
            <hr class="o-rule o-rule--closure">

            <p><code>.o-rule--underline</code> Underline an element with a rule-thickness primary color bar</p>
            <h2 class="o-rule--underline">A Heading That is Underlined</h2>
		</main>
		<aside class="rail columns large-4"></aside>
    </div>
    <?php
styleguide_footer();
