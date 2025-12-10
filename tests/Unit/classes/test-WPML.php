<?php

namespace Tests\Unit;

use CNMD\TMT\Handlers;
use CNMD\TMT\WPML;

class WPML_Test extends TestCase {

	private $handler;
	private $wpml;

	public function setUp(): void {
		parent::setUp();
		$this->handler = new Handlers();
		$this->wpml    = new WPML();
		$this->create_sample_taxonomy__flat();
		$this->create_sample_taxonomy__hierarchical();
		// clear default categories and terms, if present.
		$this->clean_taxonomy( 'post_tag' );
		$this->clean_taxonomy( 'category' );
	}

	public function tearDown(): void {
		remove_action( 'init', 'cnmd_ct_create_sample_taxonomy__flat', 0 );
		remove_action( 'init', 'cnmd_ct_create_sample_taxonomy__hierarchical', 0 );
		$this->handler = null;
		parent::tearDown();
	}

	public function test_wpml_change_taxonomies() {

	}

}
