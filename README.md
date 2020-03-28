# Manipulate Meta with the WP API

This is a WordPress plugin. It adds routes to the REST API to read, write, and delete post and term meta values separately from posts.

## Why though?

As of this writing, it is not possible to delete a meta value using the WordPress REST API. Post meta updates must be communicated while inserting and updating whole post objects, and the only way to remove meta fields from a post is to write blank values. This plugin allows one request to retrieve, update, or delete a meta field independently from a post object.

## How?

Use `GET`, `POST`, or `DELETE` verbs with a endpoint like this:

`https://example.test/wp-json/wp/v2/{post_type}/{post_id}/meta/{meta_key}`

...or like this for Term meta:

`https://example.test/wp-json/wp/v2/{taxonomy}/{term_id}/meta/{meta_key}`

When writing a value with `POST`, use a JSON body to specify the value:

```
{
	"value": "New meta value!"
}
```
