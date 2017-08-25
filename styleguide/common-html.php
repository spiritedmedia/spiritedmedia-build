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
                    <h1>Heading 1 <small>with some small text</small></h1>
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

                    <h3>Pullquote</h3>

                    <aside class="c-pullquote alignright">
                        <blockquote class="c-pullquote__content">
                            Sometimes they’ll mislead you, whether it’s intentionally or unintentionally.
                        </blockquote>
                        <cite class="c-pullquote__credit">
                            Andre Altrez
                        </cite>
                    </aside>

                    <p>Etiam et facilisis mi. Aenean est libero, rutrum eu sem vitae, molestie porttitor ipsum. Proin eget metus egestas, fringilla lectus ac, tempor arcu. Fusce fringilla dictum dignissim. In hac habitasse platea dictumst. Nunc luctus neque quam, ac pharetra risus consequat in. Cras sed dui nulla. Etiam scelerisque, neque sit amet consectetur semper, metus ligula convallis eros, non laoreet dolor neque sed mi. Phasellus laoreet, nisl vitae scelerisque laoreet, eros nulla ullamcorper lorem, eu ornare tellus arcu sit amet leo. Nulla nec faucibus nulla. Aliquam lorem sem, euismod eu nisi consequat, accumsan bibendum est. Quisque vehicula id urna sed ornare. Donec vulputate, risus id varius faucibus, justo augue scelerisque mauris, in efficitur urna nunc eu felis. Curabitur neque diam, posuere vitae mauris nec, porttitor elementum nunc. Donec vitae consectetur erat, vel efficitur tellus. Nulla id orci viverra, euismod orci et, scelerisque metus.</p>

                    <h3>Blockquote</h3>
                    <blockquote><p>If I could reach out and be like hey, ‘I wanna do this here, walk me through these first steps, I’ll try to be as compliant as I can,’ but it’s like you don’t necessarily want to say something sometimes. You feel like you’re telling on yourself. ‘Can I trust you to actually legitimately help me?’</p></blockquote>



                </section>
                <footer class="c-main__footer [ o-rule--closure o-rule ]"></footer>
            </article>
        </main>
        <aside class="rail columns large-4"></aside>
    </div>
    <?php
styleguide_footer();
