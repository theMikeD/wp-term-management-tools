<?php

namespace Tests\Unit;

use CNMD\TMT\HTML;

class HTML_Test extends TestCase {

	private $html;

	public function setUp(): void {
		parent::setUp();
		$this->html = new HTML();
	}


	public function tearDown(): void {
		$this->html = null;
		parent::tearDown();
	}


	public function test_insert() {
		global $taxonomy;
		$taxonomy = 'category';
		ob_start();
		$this->html->insert();
		$retrieved = ob_get_contents();
		ob_end_clean();
		$dom = new \DOMDocument();
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $retrieved );

		$retrieved = $dom->saveHTML( $dom->getElementById( 'tmt-input-set_parent') );
		$expected = '<div id="tmt-input-set_parent" style="display:none"><select name="parent" id="parent" class="postform"><option value="-1">None</option><option class="level-0" value="1">Uncategorized</option></select></div>';
		$this->assertSame( $expected, $retrieved );


		$retrieved = $dom->saveHTML( $dom->getElementById( 'tmt-input-merge') );
		$expected  = '<div id="tmt-input-merge" style="display:none">into:		<input name="bulk_to_tag" type="text" size="20"></div>';
		$this->assertSame( $expected, $retrieved );

		$retrieved = $dom->saveHTML( $dom->getElementById( 'tmt-input-change_tax') );
		$expected = '<div id="tmt-input-change_tax" style="display:none">		<select class="postform" name="new_tax"><option value="post_tag">Tags</option></select></div>';
		$this->assertSame( $expected, $retrieved );

	}

}
