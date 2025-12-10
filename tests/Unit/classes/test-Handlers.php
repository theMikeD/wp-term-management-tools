<?php

namespace Tests\Unit;

use CNMD\TMT\Handlers;

class Handlers_Test extends TestCase {

	private $handler;

	public function setUp(): void {
		parent::setUp();
		$this->handler = new Handlers();
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


	public function test_do__merge() {
		global $_REQUEST;
		$_REQUEST['bulk_to_tag'] = 'Merged';

		// Merge 5 terms into one, flat taxonomy.
		$taxonomy          = 'post_tag';
		$term_ids_to_merge = $this->add_terms( $taxonomy, 5 );
		$all_terms         = $this->get_all_terms( $taxonomy );
		$this->assertEquals( 5, count( $all_terms ) );
		$this->handler->do( 'merge', $taxonomy, $term_ids_to_merge );
		$all_terms = $this->get_all_terms( $taxonomy );
		$this->assertEquals( 1, count( $all_terms ) );
		$this->clean_taxonomy( $taxonomy );

		// Merge 5 terms into one, flat taxonomy, target term exists.
		$_REQUEST['bulk_to_tag'] = 'Term 1';
		$taxonomy                = 'flat';
		$term_ids_to_merge       = $this->add_terms( $taxonomy, 5 );
		$all_terms               = $this->get_all_terms( $taxonomy );
		$this->assertEquals( 5, count( $all_terms ) );
		$this->handler->do( 'merge', $taxonomy, $term_ids_to_merge );
		$all_terms = $this->get_all_terms( $taxonomy );
		$this->assertEquals( 1, count( $all_terms ) );
		$this->clean_taxonomy( $taxonomy );

		// Merge 5 terms into one, hier taxonomy.
		$_REQUEST['bulk_to_tag'] = 'Merged';
		$taxonomy                = 'hierarchical';
		$term_ids_to_merge       = $this->add_terms( $taxonomy, 5 );
		$all_terms               = $this->get_all_terms( $taxonomy );
		$this->assertEquals( 5, count( $all_terms ) );
		$this->handler->do( 'merge', $taxonomy, $term_ids_to_merge );
		$all_terms = $this->get_all_terms( $taxonomy );
		$this->assertEquals( 1, count( $all_terms ) );
		foreach ( $all_terms as $term_obj ) {
			$this->assertTrue( $this->term_has_no_parent( $term_obj ) );
		}
		$this->clean_taxonomy( $taxonomy );

		// Merge 5 terms into one, custom hier taxonomy as children of the same parent
		$taxonomy          = 'hierarchical';
		$parent_term_id    = $this->add_terms( $taxonomy, 1 );
		$term_ids_to_merge = $this->add_terms( $taxonomy, 5, $parent_term_id[0] );
		$all_terms         = $this->get_all_terms( $taxonomy );
		$this->assertEquals( 6, count( $all_terms ) );
		$this->handler->do( 'merge', $taxonomy, $term_ids_to_merge );
		$all_terms = $this->get_all_terms( $taxonomy );
		// We should have two terms, 1 parent and one child
		$this->assertEquals( 2, count( $all_terms ) );
		foreach ( $all_terms as $term_obj ) {
			if ( $parent_term_id[0] === $term_obj->term_id ) {
				// There should be only one parent, and we know its ID
				$this->assertTrue( $this->term_has_no_parent( $term_obj ) );
			} else {
				// The rest are children, and all have the same parent
				$this->assertTrue( $this->term_has_parent( $term_obj ) );
				$this->assertEquals( $parent_term_id[0], $term_obj->parent );
			}
		}
		$this->clean_taxonomy( $taxonomy );

		// Merge 5 terms into one, custom heir taxonomy, children and parent terms
		$taxonomy          = 'hierarchical';
		$parent_term_ids   = $this->add_terms( $taxonomy, 2 );
		$term_ids_to_merge = $this->add_terms( $taxonomy, 5, $parent_term_ids[0] );
		$all_terms         = $this->get_all_terms( $taxonomy );
		$this->assertEquals( 7, count( $all_terms ) );
		// Add the second parent term to the list
		$term_ids_to_merge[] = $parent_term_ids[1];
		$this->handler->do( 'merge', $taxonomy, $term_ids_to_merge );
		$all_terms = $this->get_all_terms( $taxonomy );
		// We should have two terms, 1 parent and one child
		$this->assertEquals( 2, count( $all_terms ) );
		foreach ( $all_terms as $term_obj ) {
			// All terms should have no parent.
			$this->assertTrue( $this->term_has_no_parent( $term_obj ) );
			$this->assertEquals( 0, $term_obj->parent );
		}
		$this->clean_taxonomy( $taxonomy );

	}

	public function test_do__set_parent() {
		global $_REQUEST;

		// Set parent terms
		$taxonomy                = 'hierarchical';
		$terms_to_set_parent_for = $this->add_terms( $taxonomy, 6 );
		$all_terms               = $this->get_all_terms( $taxonomy );
		$this->assertEquals( 6, count( $all_terms ) );
		$_REQUEST['parent'] = array_shift( $terms_to_set_parent_for );
		$this->handler->do( 'set_parent', $taxonomy, $terms_to_set_parent_for );
		$all_terms = $this->get_all_terms( $taxonomy );
		$this->assertEquals( 6, count( $all_terms ) );
		foreach ( $all_terms as $term_obj ) {
			if ( $_REQUEST['parent'] === $term_obj->term_id ) {
				// There should be only one parent, and we know its ID
				$this->assertTrue( $this->term_has_no_parent( $term_obj ) );
			} else {
				// The rest are children, and all have the same parent
				$this->assertTrue( $this->term_has_parent( $term_obj ) );
				$this->assertEquals( $_REQUEST['parent'], $term_obj->parent );
			}
		}
		$this->clean_taxonomy( $taxonomy );

		// Set parent terms when one of the selected terms is the target parent
		$taxonomy                = 'hierarchical';
		$terms_to_set_parent_for = $this->add_terms( $taxonomy, 6 );
		$all_terms               = $this->get_all_terms( $taxonomy );
		$this->assertEquals( 6, count( $all_terms ) );
		$_REQUEST['parent'] = $terms_to_set_parent_for[0];
		$this->handler->do( 'set_parent', $taxonomy, $terms_to_set_parent_for );
		$all_terms = $this->get_all_terms( $taxonomy );
		$this->assertEquals( 6, count( $all_terms ) );
		foreach ( $all_terms as $term_obj ) {
			$this->assertTrue( $this->term_has_no_parent( $term_obj ) );
		}
		$this->clean_taxonomy( $taxonomy );

		// Set parent terms when the parent term doesn't exist
		$taxonomy                = 'hierarchical';
		$terms_to_set_parent_for = $this->add_terms( $taxonomy, 6 );
		$all_terms               = $this->get_all_terms( $taxonomy );
		$this->assertEquals( 6, count( $all_terms ) );
		$_REQUEST['parent'] = 99;
		$this->handler->do( 'set_parent', $taxonomy, $terms_to_set_parent_for );
		$all_terms = $this->get_all_terms( $taxonomy );
		$this->assertEquals( 6, count( $all_terms ) );
		foreach ( $all_terms as $term_obj ) {
			$this->assertTrue( $this->term_has_no_parent( $term_obj ) );
		}
		$this->clean_taxonomy( $taxonomy );

	}

	public function test_do__change_taxonomy__flat() {
		global $_POST;
		// flat to flat
		$taxonomy_from = 'post_tag';
		$taxonomy_to   = 'flat';
		$terms_to_move = $this->add_terms( $taxonomy_from, 5 );
		$all_terms     = $this->get_all_terms( $taxonomy_from );
		$this->assertEquals( 5, count( $all_terms ) );
		$_POST['new_tax'] = $taxonomy_to;
		$this->handler->do( 'change_tax', $taxonomy_from, $terms_to_move );
		$all_terms = $this->get_all_terms( $taxonomy_to );
		$this->assertEquals( 5, count( $all_terms ) );
		$this->clean_taxonomy( $taxonomy_from );
		$this->clean_taxonomy( $taxonomy_to );

		// flat to hier
		$taxonomy_from = 'post_tag';
		$taxonomy_to   = 'hierarchical';
		$terms_to_move = $this->add_terms( $taxonomy_from, 5 );
		$all_terms     = $this->get_all_terms( $taxonomy_from );
		$this->assertEquals( 5, count( $all_terms ) );
		$_POST['new_tax'] = $taxonomy_to;
		$this->handler->do( 'change_tax', $taxonomy_from, $terms_to_move );
		$all_terms = $this->get_all_terms( $taxonomy_to );
		$this->assertEquals( 5, count( $all_terms ) );
		$this->clean_taxonomy( $taxonomy_from );
		$this->clean_taxonomy( $taxonomy_to );


	}

	public function test_do__change_taxonomy__hierarchical_to_flat() {
		global $_POST;
		//hier to flat, simple
		$taxonomy_from = 'hierarchical';
		$taxonomy_to   = 'flat';
		$terms_to_move = $this->add_terms( $taxonomy_from, 5 );
		$all_terms     = $this->get_all_terms( $taxonomy_from );
		$this->assertEquals( 5, count( $all_terms ) );
		$_POST['new_tax'] = $taxonomy_to;
		$this->handler->do( 'change_tax', $taxonomy_from, $terms_to_move );
		$all_terms = $this->get_all_terms( $taxonomy_to );
		$this->assertEquals( 5, count( $all_terms ) );
		$this->clean_taxonomy( $taxonomy_from );
		$this->clean_taxonomy( $taxonomy_to );

		//hier to flat, children without parent
		$taxonomy_from = 'hierarchical';
		$taxonomy_to   = 'flat';
		$parent        = $this->add_terms( $taxonomy_from, 1 );
		$terms_to_move = $this->add_terms( $taxonomy_from, 5, $parent[0] );
		$all_terms     = $this->get_all_terms( $taxonomy_from );
		$this->assertEquals( 6, count( $all_terms ) );
		$_POST['new_tax'] = $taxonomy_to;
		$this->handler->do( 'change_tax', $taxonomy_from, $terms_to_move );
		$all_terms = $this->get_all_terms( $taxonomy_to );
		$this->assertEquals( 5, count( $all_terms ) );
		$this->clean_taxonomy( $taxonomy_from );
		$this->clean_taxonomy( $taxonomy_to );

		//hier to flat, parent with children
		$taxonomy_from = 'hierarchical';
		$taxonomy_to   = 'flat';
		$parent        = $this->add_terms( $taxonomy_from, 1 );
		$terms_to_move = $this->add_terms( $taxonomy_from, 5, $parent[0] );
		$all_terms     = $this->get_all_terms( $taxonomy_from );
		$this->assertEquals( 6, count( $all_terms ) );
		$_POST['new_tax'] = $taxonomy_to;
		$this->handler->do( 'change_tax', $taxonomy_from, $parent );
		$all_terms = $this->get_all_terms( $taxonomy_to );
		$this->assertEquals( 6, count( $all_terms ) );
		$this->clean_taxonomy( $taxonomy_from );
		$this->clean_taxonomy( $taxonomy_to );

	}

	public function test_do__change_taxonomy__hierarchical_to_hierarchical() {
		global $_POST;

		//hier to heir, simple
		$taxonomy_from = 'category';
		$taxonomy_to   = 'hierarchical';
		$terms_to_move = $this->add_terms( $taxonomy_from, 5 );
		$all_terms     = $this->get_all_terms( $taxonomy_from );
		// +1 for default category
		$this->assertEquals( 6, count( $all_terms ) );
		$_POST['new_tax'] = $taxonomy_to;
		$this->handler->do( 'change_tax', $taxonomy_from, $terms_to_move );
		$all_terms = $this->get_all_terms( $taxonomy_to );
		$this->assertEquals( 5, count( $all_terms ) );
		foreach ( $all_terms as $term_obj ) {
			$this->assertTrue( $this->term_has_no_parent( $term_obj ) );
		}
		$this->clean_taxonomy( $taxonomy_from );
		$this->clean_taxonomy( $taxonomy_to );



		//hier to heir, children without parent
		$taxonomy_from = 'category';
		$taxonomy_to   = 'hierarchical';
		$parent        = $this->add_terms( $taxonomy_from, 1 );
		$terms_to_move = $this->add_terms( $taxonomy_from, 5, $parent[0] );
		$all_terms     = $this->get_all_terms( $taxonomy_from );
		// +1 for default category
		$this->assertEquals( 7, count( $all_terms ) );
		$_POST['new_tax'] = $taxonomy_to;
		$this->handler->do( 'change_tax', $taxonomy_from, $terms_to_move );
		$all_terms = $this->get_all_terms( $taxonomy_to );
		$this->assertEquals( 5, count( $all_terms ) );
		foreach ( $all_terms as $term_obj ) {
			$this->assertTrue( $this->term_has_no_parent( $term_obj ) );
		}
		$this->clean_taxonomy( $taxonomy_from );
		$this->clean_taxonomy( $taxonomy_to );



		//hier to hier, parent with children
		$taxonomy_from = 'category';
		$taxonomy_to   = 'hierarchical';
		$parent        = $this->add_terms( $taxonomy_from, 1 );
		$terms_to_move = $this->add_terms( $taxonomy_from, 5, $parent[0] );
		$all_terms     = $this->get_all_terms( $taxonomy_from );
		// +1 for default category
		$this->assertEquals( 7, count( $all_terms ) );
		foreach ( $all_terms as $term_obj ) {
			if ( $parent[0] === $term_obj->term_id || 1 === $term_obj->term_id ) {
				// There should be only one parent, and we know its ID
				$this->assertTrue( $this->term_has_no_parent( $term_obj ) );
			} else {
				// The rest are children, and all have the same parent
				$this->assertTrue( $this->term_has_parent( $term_obj ) );
				$this->assertEquals( $parent[0], $term_obj->parent );
			}
		}
		$_POST['new_tax'] = $taxonomy_to;
		$this->handler->do( 'change_tax', $taxonomy_from, $parent );
		$all_terms = $this->get_all_terms( $taxonomy_to );
		$this->assertEquals( 6, count( $all_terms ) );
		foreach ( $all_terms as $term_obj ) {
			if ( $parent[0] === $term_obj->term_id ) {
				// There should be only one parent, and we know its ID
				$this->assertTrue( $this->term_has_no_parent( $term_obj ) );
			} else {
				// The rest are children, and all have the same parent
				$this->assertTrue( $this->term_has_parent( $term_obj ) );
				$this->assertEquals( $parent[0], $term_obj->parent );
			}
		}
		$this->clean_taxonomy( $taxonomy_from );
		$this->clean_taxonomy( $taxonomy_to );


		//hier to hier, mix
		$taxonomy_from = 'category';
		$taxonomy_to   = 'hierarchical';
		$parents       = $this->add_terms( $taxonomy_from, 3 );
		$children      = $this->add_terms( $taxonomy_from, 5, $parents[0] );
		$all_terms     = $this->get_all_terms( $taxonomy_from );
		// +1 for default category
		$this->assertEquals( 9, count( $all_terms ) );
		$_POST['new_tax'] = $taxonomy_to;
		$this->handler->do( 'change_tax', $taxonomy_from, array( $children[1], $children[3], $parents[1] ) );
		$all_terms = $this->get_all_terms( $taxonomy_to );
		$this->assertEquals( 3, count( $all_terms ) );
		foreach ( $all_terms as $term_obj ) {
			$this->assertTrue( $this->term_has_no_parent( $term_obj ) );
		}
		$this->clean_taxonomy( $taxonomy_from );
		$this->clean_taxonomy( $taxonomy_to );


		//hier to hier, nested parent with children
		$taxonomy_from = 'category';
		$taxonomy_to   = 'hierarchical';
		$parent1       = $this->add_terms( $taxonomy_from, 1 );
		$parent2       = $this->add_terms( $taxonomy_from, 1, $parent1[0] );
		$terms_to_move = $this->add_terms( $taxonomy_from, 5, $parent2[0] );
		$all_terms     = $this->get_all_terms( $taxonomy_from );
		// +1 for default category
		$this->assertEquals( 8, count( $all_terms ) );
		foreach ( $all_terms as $term_obj ) {
			if ( $parent1[0] === $term_obj->term_id || 1 === $term_obj->term_id ) {
				$this->assertTrue( $this->term_has_no_parent( $term_obj ) );
			} else {
				// The rest are children, and all have the same parent
				$this->assertTrue( $this->term_has_parent( $term_obj ) );

				if ( $term_obj->term_id === $parent2[0] ) {
					$this->assertEquals( $parent1[0], $term_obj->parent );
				} else {
					$this->assertEquals( $parent2[0], $term_obj->parent );
				}
			}
		}
		$_POST['new_tax'] = $taxonomy_to;
		$this->handler->do( 'change_tax', $taxonomy_from, $parent2 );
		$all_terms = $this->get_all_terms( $taxonomy_to );
		$this->assertEquals( 6, count( $all_terms ) );
		foreach ( $all_terms as $term_obj ) {
			if ( $parent2[0] === $term_obj->term_id ) {
				// There should be only one parent, and we know its ID
				$this->assertTrue( $this->term_has_no_parent( $term_obj ) );
			} else {
				// The rest are children, and all have the same parent
				$this->assertTrue( $this->term_has_parent( $term_obj ) );
				$this->assertEquals( $parent2[0], $term_obj->parent );
			}
		}
		$this->clean_taxonomy( $taxonomy_from );
		$this->clean_taxonomy( $taxonomy_to );



		//hier to hier, nested parent with children, move everything
		$taxonomy_from = 'category';
		$taxonomy_to   = 'hierarchical';
		$parent1       = $this->add_terms( $taxonomy_from, 1 );
		$parent2       = $this->add_terms( $taxonomy_from, 1, $parent1[0] );
		$terms_to_move = $this->add_terms( $taxonomy_from, 5, $parent2[0] );
		$all_terms     = $this->get_all_terms( $taxonomy_from );
		// +1 for default category
		$this->assertEquals( 8, count( $all_terms ) );
		foreach ( $all_terms as $term_obj ) {
			if ( $parent1[0] === $term_obj->term_id || 1 === $term_obj->term_id ) {
				$this->assertTrue( $this->term_has_no_parent( $term_obj ) );
			} else {
				// The rest are children, and all have the same parent
				$this->assertTrue( $this->term_has_parent( $term_obj ) );

				if ( $term_obj->term_id === $parent2[0] ) {
					$this->assertEquals( $parent1[0], $term_obj->parent );
				} else {
					$this->assertEquals( $parent2[0], $term_obj->parent );
				}
			}
		}
		$_POST['new_tax'] = $taxonomy_to;
		$this->handler->do( 'change_tax', $taxonomy_from, $parent1 );
		$all_terms = $this->get_all_terms( $taxonomy_to );
		$this->assertEquals( 7, count( $all_terms ) );
		foreach ( $all_terms as $term_obj ) {
			if ( $parent1[0] === $term_obj->term_id ) {
				// There should be only one parent, and we know its ID
				$this->assertTrue( $this->term_has_no_parent( $term_obj ) );
			} else {
				// The rest are children, and all have the same parent
				$this->assertTrue( $this->term_has_parent( $term_obj ) );
				if ( $term_obj->term_id === $parent2[0] ) {
					$this->assertEquals( $parent1[0], $term_obj->parent );
				} else {
					$this->assertEquals( $parent2[0], $term_obj->parent );
				}
			}
		}
		$this->clean_taxonomy( $taxonomy_from );
		$this->clean_taxonomy( $taxonomy_to );

	}

	public function test_misc() {
		// bad action
		$retrieved = $this->handler->do( 'bad_action', 'category', array( 0 ) );
		$this->assertNull( $retrieved );
	}

}

function md_echo( $o ) {
	echo print_r( $o, true ) . "\n";
}
