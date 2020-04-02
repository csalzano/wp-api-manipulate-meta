# Manipulate Meta with the WP API

This is a WordPress plugin. It adds routes to the REST API to read, write, and delete post and term meta values separately from posts and terms.

## Why though?

As of this writing, it is not possible to delete a meta value using the WordPress REST API. Post meta updates must be communicated while inserting and updating whole post objects, and the only way to remove meta fields from a post is to write blank values. This plugin allows one request to retrieve, update, or delete a meta field independently from a post object. It also allows one request to bulk delete meta fields by accepting an array of meta keys as a parameter.

## How?

## Read, Write, or Delete a Single Meta Value

Use `GET`, `POST`, or `DELETE` verbs with an endpoint like this:

`https://example.test/wp-json/wp/v2/{post_type}/{post_id}/meta/{meta_key}`

...or like this for term meta:

`https://example.test/wp-json/wp/v2/{taxonomy}/{term_id}/meta/{meta_key}`

When writing a value with `POST`, use a JSON body to specify the value:

```
{
	"value": "New meta value!"
}
```

## Bulk Delete Meta Values

Use the `DELETE` verb with an endpoint like this:

`https://example.test/wp-json/wp/v2/{post_type}/{post_id}/meta`

...and a JSON body to specify which meta values should be deleted:

```
{
	"keys":
	[
		"meta_key_to_delete_1",
		"meta_key_to_delete_2"
	]
}
```