<?php
include 'include.php';
styleguide_header();
?>
    <div class="content-wrapper row">
		<main class="c-main columns large-8 js-main" role="main">
			<section class="c-stream c-stream--standard js-stream">

				<p>Starting Pagination</p>
				<footer class="c-stream__footer">
					<div aria-label="Pagination" class="c-pagination js-pagination c-pagination--fifths">
						<div class="c-pagination__inner">
							<a class="c-pagination__dir--prev c-pagination__dir c-pagination__item is-disabled js-is-disabled js-pagination-item" data-ga-category="Pagination" data-ga-label="Previous" href="#">
								<i class="c-pagination__dir__icon fa fa-angle-left"></i>
							</a>
							<a class="c-pagination__num c-pagination__num--current c-pagination__item is-disabled js-is-disabled js-pagination-item" data-ga-category="Pagination" data-ga-label="Number|Current|1" href="#">1</a>
							<a class="c-pagination__num c-pagination__num--larger c-pagination__item js-pagination-item" data-ga-category="Pagination" data-ga-label="Number|2" href="https://billypenn.com/page/2/">2</a>
							<a class="c-pagination__num c-pagination__num--larger c-pagination__item js-pagination-item" data-ga-category="Pagination" data-ga-label="Number|3" href="https://billypenn.com/page/3/">3</a>
							<a class="c-pagination__num c-pagination__num--larger c-pagination__item js-pagination-item" data-ga-category="Pagination" data-ga-label="Number|4" href="https://billypenn.com/page/4/">4</a>
							<a class="c-pagination__num c-pagination__num--larger c-pagination__item js-pagination-item" data-ga-category="Pagination" data-ga-label="Number|5" href="https://billypenn.com/page/5/">5</a>
							<a class="c-pagination__dir--next c-pagination__dir c-pagination__item js-pagination-item" data-ga-category="Pagination" data-ga-label="Next" href="https://billypenn.com/page/2/">
								<i class="c-pagination__dir__icon fa fa-angle-right"></i>
							</a>
						</div>
					</div>
				</footer>

				<p>Middle Pagination</p>
				<footer class="c-stream__footer">
					<div aria-label="Pagination" class="c-pagination js-pagination c-pagination--fifths">
						<div class="c-pagination__inner">
							<a class="c-pagination__dir--prev c-pagination__dir c-pagination__item js-pagination-item" data-ga-category="Pagination" data-ga-label="Previous" href="https://theincline.com/page/4/">
								<i class="c-pagination__dir__icon fa fa-angle-left"></i>
							</a>
							<a class="c-pagination__num c-pagination__num--smaller c-pagination__item js-pagination-item" data-ga-category="Pagination" data-ga-label="Number|3" href="https://theincline.com/page/3/">3</a>
							<a class="c-pagination__num c-pagination__num--smaller c-pagination__item js-pagination-item" data-ga-category="Pagination" data-ga-label="Number|4" href="https://theincline.com/page/4/">4</a>
							<a class="c-pagination__num c-pagination__num--current c-pagination__item is-disabled js-is-disabled js-pagination-item" data-ga-category="Pagination" data-ga-label="Number|Current|5" href="#">5</a> <a class="c-pagination__num c-pagination__num--larger c-pagination__item js-pagination-item" data-ga-category="Pagination" data-ga-label="Number|6" href="https://theincline.com/page/6/">6</a>
							<a class="c-pagination__num c-pagination__num--larger c-pagination__item js-pagination-item" data-ga-category="Pagination" data-ga-label="Number|7" href="https://theincline.com/page/7/">7</a>
							<a class="c-pagination__dir--next c-pagination__dir c-pagination__item js-pagination-item" data-ga-category="Pagination" data-ga-label="Next" href="https://theincline.com/page/6/">
								<i class="c-pagination__dir__icon fa fa-angle-right"></i>
							</a>
						</div>
					</div>
				</footer>

				<p>Ending Pagination</p>
				<footer class="c-stream__footer">
					<div aria-label="Pagination" class="c-pagination js-pagination c-pagination--fifths">
						<div class="c-pagination__inner">
							<a class="c-pagination__dir--prev c-pagination__dir c-pagination__item js-pagination-item" data-ga-category="Pagination" data-ga-label="Previous" href="https://theincline.com/page/64/">
								<i class="c-pagination__dir__icon fa fa-angle-left"></i>
							</a>
							<a class="c-pagination__num c-pagination__num--smaller c-pagination__item js-pagination-item" data-ga-category="Pagination" data-ga-label="Number|61" href="https://theincline.com/page/61/">61</a>
							<a class="c-pagination__num c-pagination__num--smaller c-pagination__item js-pagination-item" data-ga-category="Pagination" data-ga-label="Number|62" href="https://theincline.com/page/62/">62</a>
							<a class="c-pagination__num c-pagination__num--smaller c-pagination__item js-pagination-item" data-ga-category="Pagination" data-ga-label="Number|63" href="https://theincline.com/page/63/">63</a>
							<a class="c-pagination__num c-pagination__num--smaller c-pagination__item js-pagination-item" data-ga-category="Pagination" data-ga-label="Number|64" href="https://theincline.com/page/64/">64</a>
							<a class="c-pagination__num c-pagination__num--current c-pagination__item is-disabled js-is-disabled js-pagination-item" data-ga-category="Pagination" data-ga-label="Number|Current|65" href="#">65</a>
							<a class="c-pagination__dir--next c-pagination__dir c-pagination__item is-disabled js-is-disabled js-pagination-item" data-ga-category="Pagination" data-ga-label="Next" href="#">
								<i class="c-pagination__dir__icon fa fa-angle-right"></i>
							</a>
						</div>
					</div>
				</footer>

                <p>Pagination with Text</p>
                <footer class="c-stream__footer">
            		<div aria-label="Pagination" class="c-pagination js-pagination c-pagination--fifths">
            			<div class="c-pagination__text">
            				Page <span class="c-pagination__text__paged">1</span> of <span class="c-pagination__text__total">16</span>
            			</div>
            			<div class="c-pagination__inner">
            				<a class="c-pagination__dir--prev c-pagination__dir c-pagination__item is-disabled js-is-disabled js-pagination-item" data-ga-category="Pagination" data-ga-label="Previous" href="#">
                                <i class="c-pagination__dir__icon fa fa-angle-left"></i>
                            </a>
                            <a class="c-pagination__num c-pagination__num--current c-pagination__item is-disabled js-is-disabled js-pagination-item" data-ga-category="Pagination" data-ga-label="Number|Current|1" href="#">1</a>
                            <a class="c-pagination__num c-pagination__num--larger c-pagination__item js-pagination-item" data-ga-category="Pagination" data-ga-label="Number|2" href="https://billypenn.com/stories/election-2016/page/2/">2</a>
                            <a class="c-pagination__num c-pagination__num--larger c-pagination__item js-pagination-item" data-ga-category="Pagination" data-ga-label="Number|3" href="https://billypenn.com/stories/election-2016/page/3/">3</a>
                            <a class="c-pagination__num c-pagination__num--larger c-pagination__item js-pagination-item" data-ga-category="Pagination" data-ga-label="Number|4" href="https://billypenn.com/stories/election-2016/page/4/">4</a>
                            <a class="c-pagination__num c-pagination__num--larger c-pagination__item js-pagination-item" data-ga-category="Pagination" data-ga-label="Number|5" href="https://billypenn.com/stories/election-2016/page/5/">5</a>
                            <a class="c-pagination__dir--next c-pagination__dir c-pagination__item js-pagination-item" data-ga-category="Pagination" data-ga-label="Next" href="https://billypenn.com/stories/election-2016/page/2/">
                                <i class="c-pagination__dir__icon fa fa-angle-right"></i>
                            </a>
            			</div>
            		</div>
            	</footer>

			</section>
		</main>
		<aside class="rail columns large-4"></aside>
    </div>
    <?php
styleguide_footer();
