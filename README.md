# Manipulate Meta with the WP API

This is a WordPress plugin. It adds routes to the REST API to read, write, and delete post and term meta values separately from posts.

## Why though?

As of this writing, it is not possible to delete a meta value using the WordPress REST API. Post meta updates must be communicated while inserting and updating whole post objects, and the only way to remove meta fields from a post is to write blank values. This plugin allows one request to retrieve, update, or delete a meta field independently from a post object.