<?php
include 'include.php';
styleguide_header();
?>
    <div class="content-wrapper row">
		<main class="c-main columns large-8 js-main" role="main">
			<p><button>Unstyled Button</button></p>
			<p><button class="button">Button</button></p>
			<p><button class="button--rounded button">Rounded Button</button></p>
			<p><button class="button--rounded button large">Large Rounded Button</button></p>
			<p>
				<a class="button--iconic button--rounded button expand large" href="#">
                	<i class="button--iconic__icon fa fa-facebook"></i>
                	<span class="button--iconic__text">Large Rounded Expanded Button</span>
            	</a>
			</p>
			<p>
				<a class="button--rounded button--dark button" href="#">
                	Rounded Dark Button
            	</a>
			</p>
			<p>
				<a class="button--rounded button--dark button large" href="#">
                	Large Rounded Dark Button
            	</a>
			</p>
			<p>
				<a class="button--iconic button--rounded button--dark button expand large" href="#">
                	<i class="button--iconic__icon fa fa-facebook"></i>
                	<span class="button--iconic__text">Large Rounded Dark Expanded Button</span>
            	</a>
			</p>
		</main>
		<aside class="rail columns large-4"></aside>
    </div>
    <?php
styleguide_footer();
