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
    <main class="c-main columns large-8 js-main" role="main">        <section>
            <p><code>.o-media</code></p>
            <div class="example">
                <div class="o-media">
                    <img src="https://dummyimage.com/80x80" class="o-media__img">
                    <p class="o-media__body">This is a text body.</p>
                </div>
            </div>

            <p><code>.o-media--large</code></p>
            <div class="example">
                <div class="o-media o-media--large">
                    <img src="https://dummyimage.com/80x80" class="o-media__img">
                    <p class="o-media__body">This is a text body.</p>
                </div>
            </div>

            <p><code>.o-media--rev</code></p>
            <div class="example">
                <div class="o-media o-media--rev">
                    <img src="https://dummyimage.com/80x80" class="o-media__img">
                    <p class="o-media__body">This is a text body.</p>
                </div>
            </div>

            <p><code>.o-media--responsive</code></p>
            <div class="example">
                <div class="o-media o-media--responsive">
                    <img src="https://dummyimage.com/80x80" class="o-media__img">
                    <p class="o-media__body">This is a text body.</p>
                </div>
            </div>
        </section>
    </main>
    <aside class="rail columns large-4"></aside>
</div>
<?php
styleguide_footer();
