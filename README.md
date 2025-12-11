# Term Management Tools 
Contributors: theMikeD, scribu  
Tags: admin, category, tag, term, taxonomy  
Requires at least: 4.2  
Tested up to: 6.9  
Stable tag: 2.0.2
Requires PHP: 7.1  
License: GPLv2 or later  
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows you to merge terms, move terms between taxonomies, and set term parents, individually or in bulk.

## Description 

_Please note this plugin requires at least PHP 7.1._

If you need to reorganize your tags and categories, this plugin will make it easier for you. It adds three new options to the Bulk Actions dropdown on term management pages:

* Merge - combine two or more terms into one
* Set Parent - set the parent for one or more terms (for hierarchical taxonomies)
* Change Taxonomy - convert terms from one taxonomy to another

It works with tags, categories and [custom taxonomies](http://codex.wordpress.org/Custom_Taxonomies).

## Usage 

1. Go to the taxonomy page containing terms you want to modify. For example, for categories go to `WP-Admin → Posts → Categories`.
2. Select the terms you want to reorganize
3. Find the Bulk Actions dropdown, and select the task you'd like done.
4. Disco.

## WPML
[WPML](https://wpml.org)-translated terms are partially supported. Currently only the "Change Taxonomy" task is WPML-aware. If a term with translations is moved to a new taxonomy, its translations are moved as well, and the translation relationships are preserved.

> Currently only the "Change Taxonomy" task is WPML-aware

Work on the WPML component was sponsored by the [Rainforest Alliance](https://www.rainforest-alliance.org/).

## Support
Limited support is handled in the forum created for this purpose (see the [support](https://wordpress.org/support/plugin/term-management-tools/) tab on wp.org).

Find a problem? Fixes can be submitted on [Github](https://github.com/theMikeD/wp-term-management-tools).

## Installation 

Either use the WordPress Plugin Installer (Dashboard → Plugins → Add New, then search for "term management tools"), or manually as follows:

1. Upload the entire `wp-term-management-tools` folder to your `/wp-content/plugins/` directory
1. DO NOT change the name of the `wp-term-management-tools` folder
1. Activate the plugin through the 'Plugins' menu in the WordPress Dashboard

Note for WordPress Multisite users:

* Install the plugin in your `/plugins/` directory (do not install in the `/mu-plugins/` directory).
* In order for this plugin to be visible to Site Admins, the plugin has to be activated for each blog by the Network Admin.

## Upgrading from a previous version

Use the upgrade link in the Dashboard (Dashboard → Updates) to upgrade this plugin.

## Notes
Initial version of this plugin was by [scribu](http://scribu.net/), with contributions from others. See full code history on [Github](https://github.com/theMikeD/wp-term-management-tools).

## Screenshots 

1. Set Parent option. In this case, the term "New EN" will be set as a child of "Parent One EN"
2. Merge option. Here, the two selected terms will be merged into a new term named "Merged." In addition, because both source terms share the same parent term ("Parent One EN"), the new term will also have "Parent One EN" as its parent term.
3. Change Taxonomy option. Here, the "Parent One EN" category will be sent to the custom taxonomy "Hierarchical" (which I added for the sake of testing). A few other things to note here. First, the two child terms will also be moved and, because the target taxonomy is also hierarchical the parent-child relationships will be preserved. Second, if there are any WPML translations of these terms, they will also be moved and the translations maintained.

## Changelog 

### 2.0.2
* Confirmed php suppoprt up to php8.4
* Confirmed WordPress support up to 6.9

### 2.0.1
* FIX: a WPML translation that only exists in a single site non-primary language was not being migrated correctly

### 2.0.0
* under new management by @theMikeD :)
* full code refactoring
* inline documentation
* [user documentation](https://www.codenamemiked.com/plugins/term-management-tools)
* clean phpcs using Wordpress-Extra
* unit/integration tests, all of which pass
* term cache clearing now actually works
* for the taxonomy change option, only public taxonomies are listed
* for the taxonomy change option, WPML-translated terms are also moved
* for the term merge option, if all terms to be merged have the same parent term, the merged term will also have that parent term.
* for the term parent option, if one of the supplied terms is also the term selected to be the parent, no terms are adjusted.
* new filter term_management_tools_changed_taxonomy__terms_as_supplied
* new filter term_management_tools_changed_taxonomy__terms_and_child_terms
* new filter term_management_tools_changed_taxonomy__reset_parent_for

### 1.1.4
* improved taxonomy cache cleaning. props Mustafa Uysal
* added 'term_management_tools_term_changed_taxonomy' action hook. props Daniel Bachhuber
* fixed redirection for taxonomies attached to custom post types. props Thomas Bartels
* added Japanese translation. props mt8

### 1.1.3
* preserve term hierarchy when switching taxonomies. props Chris Caller

### 1.1.2 
* added 'term_management_tools_term_merged' action hook. props Amit Gupta

### 1.1.1 
* fixed error notices
* added Persian translation

### 1.1 
* added 'Change taxonomy' action

### 1.0 
* initial release
* [more info](http://scribu.net/wordpress/term-management-tools/tmt-1-0.html)

## Upgrade Notice

= 2.0.0 =
Improved logic around parent-child terms, bug fixes, WPML support, tested and reviewed refactoring. 