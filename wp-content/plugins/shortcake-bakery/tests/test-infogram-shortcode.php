<?php

class Test_Infogram_Shortcode extends WP_UnitTestCase {

	public function test_post_display() {
		$html = '<script async src="//e.infogram.com/js/embed.js" id="infogram_0_washington_marijuana_sales" type="text/javascript"></script>';

		$legacy_post_id = $this->factory->post->create( array( 'post_content' => '[infogram url="http://infogr.am/washington_marijuana_sales"]' ) );
		$legacy_post = get_post( $legacy_post_id );
		$this->assertContains( $html, apply_filters( 'the_content', $legacy_post->post_content ) );

		$post_id = $this->factory->post->create( array( 'post_content' => '[infogram url="http://infogram.com/washington_marijuana_sales"]' ) );
		$post = get_post( $post_id );
		$this->assertContains( $html, apply_filters( 'the_content', $post->post_content ) );
	}

	public function test_embed_script_reversal() {
		$old_content = <<<EOT

		apples before

		<script id="infogram_0_washington_marijuana_sales" src="//e.infogram.com/js/embed.js?hbf" type="text/javascript"></script>

		bananas after
EOT;
		$transformed_content = wp_filter_post_kses( $old_content );
		$transformed_content = str_replace( '\"', '"', $transformed_content ); // Kses slashes the data
		$this->assertContains( '[infogram url="https://infogram.com/washington_marijuana_sales"]', $transformed_content );
		$this->assertContains( 'apples before', $transformed_content );
		$this->assertContains( 'bananas after', $transformed_content );

	}

	public function test_embed_iframe_reversal() {
		$old_content = <<<EOT

		apples before

		<iframe src="https://e.infogram.com/wow-so-easy-93186" width="550" height="1246" scrolling="no" frameborder="0" style="border:none;"></iframe>

		bananas after
EOT;
		$expected_content = <<<EOT

		apples before

		[infogram url="https://infogram.com/wow-so-easy-93186"]

		bananas after
EOT;
		$transformed_content = wp_filter_post_kses( $old_content );
		$transformed_content = str_replace( '\"', '"', $transformed_content ); // Kses slashes the data
		$this->assertEquals( $expected_content, $transformed_content );

	}

}
