<?php
include 'include.php';
styleguide_header();
?>
    <div class="content-wrapper row">
        <main class="c-main columns large-8 js-main" role="main">
            <article class="c-main__content" data-author-count="1" data-author-role="staff" data-editorial-content="" data-entity="" data-post-type="article" data-primary-story="51480">
                <header class="c-main__header">
                    <h2 class="c-main__title">Post Content / Common HTML Markup</h2>
                    <div class="c-main__excerpt">
                        <p>It's the simple bits that make up our site.</p>
                    </div>
                </header>
                <section class="c-main__body s-post-content">
                    <p>Lorem ipsum dolor sit amet, id nam prima pertinax sadipscing, vix ea dicunt intellegebat. Et pri hinc labore apeirian, veritus postulant eum et, in quas epicurei sea. Mea tale detraxit senserit at. Salutatus dissentias at est, alii ullum reprimique mel cu. Ne vis corpora appellantur, partem alterum concludaturque ea mei. Posse ludus omnesque sit ea.</p>
                    <h1>Heading 1</h1>
                    <h2>Heading 2</h2>
                    <h3>Heading 3</h3>
                    <h4>Heading 4</h4>
                    <h5>Heading 5</h5>
                    <h6>Heading 6</h6>
                    <p>Text level <abbr title="HyperText Markup Language">HTML</abbr> elements may be used within other elements. They include: <em>em</em> and <strong>strong</strong> for semantic emphasis, <i>i</i> and <b>b</b> for presentational formatting, <abbr title="Abbreviation">abbr</abbr> abbreviations, <acronym title="National Basketball Association">NBA</acronym> acronym, <cite>cite</cite> citations, <code>code</code> example, <del>del</del>, <ins>ins</ins> for visibly deleted and inserted content, <dfn>dfn</dfn> definitions, <mark>mark</mark> for highlighted passages and <sup>sup</sup> superscript and <sub>sub</sub> subscript. Since this is the web, we often <a href="#">like to link out</a> to other resources.</p>
                    <p>Pre Formatted Text:</p>
                    <pre><p>
                    Lorem ipsum dolor sit amet,
                    consectetuer adipiscing elit.
                    Nullam dignissim convallis est.
                    Quisque aliquam. Donec faucibus.
                    Nunc iaculis suscipit dui.
                    Nam sit amet sem.
                    Aliquam libero nisi, imperdiet at,
                    tincidunt nec, gravida vehicula,
                    nisl.
                    Praesent mattis, massa quis
                    luctus fermentum, turpis mi
                    volutpat justo, eu volutpat
                    enim diam eget metus.
                    Maecenas ornare tortor.
                    Donec sed tellus eget sapien
                    fringilla nonummy.
                    Mauris a ante. Suspendisse
                    quam sem, consequat at,
                    commodo vitae, feugiat in,
                    nunc. Morbi imperdiet augue
                    quis tellus.
                    </p></pre>
                    <h3>Definition List</h3>
                    <dl>
                        <dt>Definition List Title</dt>
                        <dd>This is a definition list division.</dd>
                        <dt>Definition List Title</dt>
                        <dd>
                            <p>This is a definition list division.</p>
                            <p>This is a definition list division.</p>
                        </dd>
                    </dl>

                    <h3>Unordered List</h3>
                    <ul>
                        <li>List Item 1</li>
                        <li>List Item 2</li>
                        <li>List Item 3</li>
                    </ul>
                    <ul>
                        <li>List Item 1
                            <ol>
                                <li>Nested Ordered Item 1</li>
                                <li>Nested Ordered Item 2</li>
                                <li>Nested Ordered Item 3</li>
                            </ol>
                        </li>
                        <li>List Item 2
                            <ul>
                                <li>Nested Item 1</li>
                                <li>Nested Item 2</li>
                                <li>Nested Item 3</li>
                            </ul>
                        </li>
                        <li>List Item 3</li>
                    </ul>
                    <h3>Ordered List</h3>
                    <ol>
                        <li>List Item 1</li>
                        <li>List Item 2</li>
                        <li>List Item 3</li>
                    </ol>
                    <ol>
                        <li>List Item 1
                            <ol>
                                <li>Nested Item 1</li>
                                <li>Nested Item 2</li>
                                <li>Nested Item 3</li>
                            </ol>
                        </li>
                        <li>List Item 2
                            <ul>
                                <li>Nested Item 1</li>
                                <li>Nested Item 2</li>
                                <li>Nested Item 3</li>
                            </ul>
                        </li>
                        <li>List Item 3</li>
                    </ol>
                </section>
                <footer class="c-main__footer [ o-rule--closure o-rule ]"></footer>
            </article>
        </main>
        <aside class="rail columns large-4"></aside>
    </div>
    <?php
styleguide_footer();
