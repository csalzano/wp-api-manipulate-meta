=== Manipulate Meta with the WP API ===
Contributors: salzano
Tags: REST API, WP API, delete meta
Requires at least: 5.0.0
Tested up to: 5.4.0
Stable tag: 1.4.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds routes to the REST API to read, write, and delete post and term meta values separately from posts and terms.


== Description ==

Instructions for use live in README.md


== Frequently Asked Questions ==

= Why? =
As of this writing, it is not possible to delete a meta value using the WordPress REST API. Post meta updates must be communicated while inserting and updating whole post objects, and the only way to remove meta fields from a post is to write blank values. This plugin allows one request to retrieve, update, or delete a meta field independently from a post object. It also allows one request to bulk delete meta fields by accepting an array of meta keys as a parameter.


== Changelog ==

= 1.4.0 =
* [Added] Adds methods to refactor the code by sharing methods between post and term meta manipulations. The methods `get_rest_base`, `object_is_post`, and `object_is_term` allow one callback method to determine if the request is for a post or a term and act accordingly.

= 1.3.0 =
* [Added] Adds a route to allow the bulk deletion of term meta keys, similar to the bulk delete feature for posts introduced in 1.1.0.
* [Fixed] Fixes a bug where a method intended for `sanitize_callback` was provided instead as the `validate_callback`.

= 1.2.0 =
* [Added] Adds this readme.txt
