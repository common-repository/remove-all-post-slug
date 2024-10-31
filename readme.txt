=== Plugin Name ===
Contributors: Saeed Ullah Khan
Tags: slug, remove, post type, custom post type, remove slug, remove product-category,remove product-category slug, remove woocommerce product type
Requires at least: 2.9
Tested up to: 4.8
Stable tag: trunk

Add rewrite rules for custom post type so that the urls for them are in the same way as normal posts: http://siteurl/%custom_post_type_title%/.

== Description ==

In WordPress 2.9 custom post type was introduced, that opened a new world for many WordPress developers. 
When it comes to removing the slug and not destroying the rewrite rules for other post types this plugin works great. 
This only concerns those who are using %postname% as permalink structure and want to add a custom post type so that 
the urls for them are in the same way as normal posts and pages: http://siteurl/%custom_post_type_title%/.

Find more useful tips and plugins: [web tips](http://www.arhamsoft.com/ "More Wordpress plugins")

== Installation ==

1. Upload `remove-slug-post` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Which permalink structure do I need to have for this to work? =

This only works if permalink structure is "/%postname%/. also work with multi lang plguin like Polylang


== Changelog ==

= 1.0.1 =
* Permalink bug fixed
* Product-category Slug bug fixed

= 1.0 =
* Released