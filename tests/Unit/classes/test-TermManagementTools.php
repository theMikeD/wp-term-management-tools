<?php

namespace Tests\Unit;

use CNMD\TMT\TermManagementTools;

class TermManagementTools_Test extends TestCase {

	private $tmt;

	public function setUp(): void {
		parent::setUp();
		$this->tmt = new TermManagementTools();
	}


	public function tearDown(): void {
		$this->tmt = null;
		parent::tearDown();
	}


	public function test_notice() {
		global $_GET;

		$_GET['message'] = 'tmt-updated';
		ob_start();
		$this->tmt->notice();
		$retrieved = ob_get_contents();
		ob_end_clean();
		$expected = '<div id="message" class="updated"><p>Terms updated.</p></div>';
		$this->assertSame( $expected, $retrieved );

		$_GET['message'] = 'tmt-error';
		ob_start();
		$this->tmt->notice();
		$retrieved = ob_get_contents();
		ob_end_clean();
		$expected = '<div id="message" class="error"><p>Terms not updated.</p></div>';
		$this->assertSame( $expected, $retrieved );

		$_GET['message'] = 'notvalid';
		ob_start();
		$this->tmt->notice();
		$retrieved = ob_get_contents();
		ob_end_clean();
		$this->assertSame( '', $retrieved );

		$_GET['message'] = null;
		ob_start();
		$this->tmt->notice();
		$retrieved = ob_get_contents();
		ob_end_clean();
		$this->assertSame( '', $retrieved );
	}
}
