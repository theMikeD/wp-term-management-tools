<?php

namespace CNMD\TMT;

/**
 * Class Handlers
 *
 * @package CNMD\TMT
 */
class Handlers extends Base {


	/**
	 * Switchboard to Do The Right Thing.
	 *
	 * @param string $action                  The action to perform. One of [merge,set_parent,change_tax].
	 * @param string $taxonomy                The taxonomy.
	 * @param array  $terms_manually_selected The list to terms to adjust.
	 *
	 * @return bool|null        null if nothing was done, false on failure, true on success.
	 */
	public function do( string $action, string $taxonomy, array $terms_manually_selected ) : ? bool {
		$terms_manually_selected = array_map( 'absint', $terms_manually_selected );
		switch ( $action ) {
			case 'merge':
				return $this->merge_terms( $terms_manually_selected, $taxonomy );
			case 'set_parent':
				return $this->set_parent_term( $terms_manually_selected, $taxonomy );
			case 'change_tax':
				return $this->change_taxonomy( $terms_manually_selected, $taxonomy );
		}
		return null;
	}


	/**
	 * @param array  $terms_manually_selected List of affected term IDs.
	 * @param string $taxonomy                The taxonomy name.
	 *
	 * @return bool
	 */
	private function merge_terms( array $terms_manually_selected, string $taxonomy ) : bool {
		$term_name = $_REQUEST['bulk_to_tag'];

		$term = term_exists( $term_name, $taxonomy );
		if ( ! $term ) {
			$term = wp_insert_term( $term_name, $taxonomy );
		}

		if ( is_wp_error( $term ) ) {
			// @codeCoverageIgnoreStart
			return false;
			// @codeCoverageIgnoreEnd
		}

		$to_term     = (int) $term['term_id'];
		$to_term_obj = get_term( $to_term, $taxonomy );

		$first_found_parent_in_list_of_terms_to_merge = null;
		$all_have_same_parent                         = true;

		foreach ( $terms_manually_selected as $term_id ) {
			if ( (int) $term_id === $to_term ) {
				//@codeCoverageIgnoreStart
				if ( null === $first_found_parent_in_list_of_terms_to_merge ) {
					$first_found_parent_in_list_of_terms_to_merge = $to_term_obj->parent;
				}
				continue;
				//@codeCoverageIgnoreEnd
			}

			$old_term = get_term( $term_id, $taxonomy );
			// A little bit of redundancy to prevent an unnecessary hit to the db
			if ( null === $first_found_parent_in_list_of_terms_to_merge ) {
				$first_found_parent_in_list_of_terms_to_merge = $old_term->parent;
			}
			if ( $first_found_parent_in_list_of_terms_to_merge !== $old_term->parent ) {
				$all_have_same_parent = false;
			}
			$ret = wp_delete_term(
				$term_id,
				$taxonomy,
				array(
					'default'       => $to_term,
					'force_default' => true,
				)
			);
			if ( is_wp_error( $ret ) ) {
				// @codeCoverageIgnoreStart
				continue;
				// @codeCoverageIgnoreEnd
			}

			/**
			 * Action that runs after each selected term has been merged into the target term, and the source term has
			 * been deleted.
			 *
			 * @param \WP_Term  $to_term_obj   The term into which others are merged.
			 * @param \WP_Term  $old_term      The term object before merging. At this point, it has been deleted.
			 *
			 *@since 1.1.2
			 *
			 */
			do_action( 'term_management_tools_term_merged', $to_term_obj, $old_term );
		}
		if ( $all_have_same_parent ) {
			wp_update_term( $to_term, $taxonomy, array( 'parent' => $first_found_parent_in_list_of_terms_to_merge ) );
		}

		return true;
	}


	/**
	 *
	 * @param array  $terms_manually_selected
	 * @param string $taxonomy
	 *
	 * @return bool
	 *@todo: add action
	 *
	 */
	private function set_parent_term( array $terms_manually_selected, string $taxonomy ) : bool {
		$parent_id = (int) $_REQUEST['parent'];
		if ( ! term_exists( $parent_id, $taxonomy ) ) {
			return false;
		}
		foreach ( $terms_manually_selected as $term_id ) {
			if ( $term_id === $parent_id ) {
				return false;
			}
		}
		foreach ( $terms_manually_selected as $term_id ) {
			$ret = wp_update_term( $term_id, $taxonomy, array( 'parent' => $parent_id ) );
			if ( is_wp_error( $ret ) ) {
				//@codeCoverageIgnoreStart
				return false;
				//@codeCoverageIgnoreEnd
			}
		}

		return true;
	}


	/**
	 * Perform the Change Taxonomy action. Note that after the change, the parent term is reset for each affected term
	 * if applicable.
	 *
	 * @param array  $terms_manually_selected
	 * @param string $taxonomy_from
	 *
	 * @return bool
	 */
	private function change_taxonomy( array $terms_manually_selected, string $taxonomy_from ) : bool {
		global $wpdb;

		$taxonomy_to = $_POST['new_tax'];

		if ( ! taxonomy_exists( $taxonomy_to ) ) {
			//@codeCoverageIgnoreStart
			return false;
			//@codeCoverageIgnoreEnd
		}

		if ( $taxonomy_to === $taxonomy_from ) {
			//@codeCoverageIgnoreStart
			return false;
			//@codeCoverageIgnoreEnd
		}

		/*
		 * At this point, the list of terms to move can contain a mix of parent and child terms, but we don't care:
		 * any term manually selected will have it's parent reset to zero UNLESS the parent term is also selected, in
		 * which case all child terms are automatically moved.
		 *
		 * So what we have to do now is build up the term list that includes all manually selected terms plus all child
		 * terms of those manually selected.
		 */

		/**
		 * Filter provides access to the term list as selected by the user, before any child terms have been found.
		 *
		 * @param array  $term_ids_to_move    List of term IDs to be moved.
		 * @param string $taxonomy_to         The target taxonomy.
		 * @param string $taxonomy_from       The source taxonomy.
		 *
		 * @return array                      List of term taxonomy IDs of the moved terms.
		 *
		 *@since 2.0.0
		 *
		 */
		$terms_manually_selected = apply_filters( 'term_management_tools_changed_taxonomy__terms_as_supplied', $terms_manually_selected, $taxonomy_to, $taxonomy_from );

		// Set all IDs to int again, just in case.
		$terms_manually_selected = array_map( 'absint', $terms_manually_selected );

		$term_ids_to_move = $terms_manually_selected;
		foreach ( $terms_manually_selected as $term_id ) {
			$term_ids_to_move = array_merge( $term_ids_to_move, $this->get_all_child_terms_for( (int) $term_id, $taxonomy_from, $this->get_term_hierarchy( $taxonomy_from ) ) );
		}
		$term_ids_to_move = array_unique( array_map( 'absint', $term_ids_to_move ) );

		/*
		 * At this point, $term_ids_to_move contains all terms IDs to move. This is the complete list if WPML is not active.
		 * However if WPML is active, this is the complete list for the terms only in whatever language the site
		 * admin is currently set. So now we have to do the same thing for the terms in all other site languages.
		 * Via a filter, natch.
		 */

		/**
		 * Filter provides access to the complete list of terms to be moved, including all child terms.
		 *
		 * @param array  $term_ids_to_move    List of term IDs to be moved.
		 * @param string $taxonomy_to         The target taxonomy.
		 * @param string $taxonomy_from       The source taxonomy.
		 *
		 * @return array                      List of term IDs to be moved.
		 *
		 *@since 2.0.0
		 *
		 */
		$term_ids_to_move = apply_filters( 'term_management_tools_changed_taxonomy__terms_and_child_terms', $term_ids_to_move, $taxonomy_to, $taxonomy_from );
		$term_ids_to_move = array_map( 'absint', $term_ids_to_move );

		/*
		 * At this point, $term_ids_to_move contains all terms IDs to move. This is the complete list, including all
		 * translations.
		 */

		$terms_to_reset_parentage_for              = array();
		$terms_to_reset_parentage_for__placeholder = '';
		/*
		 * If the from and to tax are both hierarchical, then we want to reset the parentage for terms, but only the
		 * top-most terms in our list. So if any term has a parent, and that parent is not also being moved, it should
		 * be reset to 0 (none). This part preps the reset; the actual reset happens below, after the move.
		 */
		if ( is_taxonomy_hierarchical( $taxonomy_from ) && is_taxonomy_hierarchical( $taxonomy_to ) ) {
			foreach ( $term_ids_to_move as $term_id ) {
				$parent_term_id = (int) $this->get_parent_term_id( $term_id, $this->get_term_hierarchy( $taxonomy_from ) );
				if ( $parent_term_id && ! in_array( $parent_term_id, $term_ids_to_move ) ) {
					$terms_to_reset_parentage_for[] = $term_id;
				}
			}
			/**
			 * Filter to provide access to the hierarchical terms for which parentage is to be adjusted after a change
			 * in taxonomy.
			 *
			 * @param array  $term_ids_to_move                List of term IDs to be moved.
			 * @param string $taxonomy_from                   The source taxonomy.
			 * @param array  $terms_to_reset_parentage_for    List of term IDs to be moved.
			 *
			 * @return array                                  List of term taxonomy IDs of the moved terms.
			 *
			 *@since 2.0.0
			 *
			 */
			$terms_to_reset_parentage_for              = apply_filters( 'term_management_tools_changed_taxonomy__reset_parent_for', $terms_to_reset_parentage_for, $term_ids_to_move, $taxonomy_from );
			$terms_to_reset_parentage_for__placeholder = $this->build_sql_placeholder( $terms_to_reset_parentage_for );
		}

		// Build the SQL placeholder string, which depends on how many terms we are moving.
		$query_term_id_placeholder = $this->build_sql_placeholder( $term_ids_to_move );

		// Actually do the move.
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE $wpdb->term_taxonomy SET taxonomy = %s WHERE term_id IN (" . $query_term_id_placeholder . ')',
				$taxonomy_to,
				...$term_ids_to_move
			)
		);

		// Fix the parentage if $taxonomy_from is hierarchical.
		if ( is_taxonomy_hierarchical( $taxonomy_from ) ) {
			if ( is_taxonomy_hierarchical( $taxonomy_to ) ) {
				if ( ! empty( $terms_to_reset_parentage_for ) ) {
					// to-tax is hierarchical, so strip parentage for top-level terms only; leave the rest intact
					$wpdb->query(
						$wpdb->prepare(
							"UPDATE $wpdb->term_taxonomy SET parent = 0 WHERE term_id IN (" . $terms_to_reset_parentage_for__placeholder . ')',
							...$terms_to_reset_parentage_for
						)
					);
				}
			}
		} else {
			// No hierarchy in to-tax, so strip parentage from all moved terms.
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE $wpdb->term_taxonomy SET parent = 0 WHERE term_id IN (" . $query_term_id_placeholder . ')',
					...$term_ids_to_move
				)
			);
		}

		$term_ids_to_move__csv = implode( ',', $term_ids_to_move );
		clean_term_cache( $term_ids_to_move, $taxonomy_from );
		clean_term_cache( $term_ids_to_move, $taxonomy_to );

		/**
		 * Action that runs after the selected terms have had their taxonomy changed.
		 *
		 * @since 1.1.4
		 *
		 * @param array  $term_ids_to_move__csv CSV of term taxonomy IDs of the moved terms. Why CSV? Thats what was there before.
		 * @param string $taxonomy_to           The target taxonomy.
		 * @param string $taxonomy_from         The source taxonomy.
		 */
		do_action( 'term_management_tools_term_changed_taxonomy', $term_ids_to_move__csv, $taxonomy_to, $taxonomy_from );

		return true;
	}


}
