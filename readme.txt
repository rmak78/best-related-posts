=== Plugin Name ===
Contributors: rmak78, Rana Mansoor Akbar Khan
Donate link: http://www.sutlej.net/downloads/best-related-posts/donate/
Tags: posts, images, links,related posts, related links, related post with thumbnail, links, thumbnails, linkbuilding, seo, Posts, link building
Requires at least: 2.8
Tested up to: 3.1.3
Stable tag: trunk

Shows related posts with thumbnails. Allows you to design your own layout using simple interface. Good for SEO and reducing bounce rate.

== Description ==

Best Related Posts is a straight forward and easy to use plug in. It shows related posts for the post user is reading. It also add the first picture in post as thumbnail beside the related post.

It is better than other plugins and widgets as it is good for internal link building and overall pagerank. it Lets you to design the look of your related posts list. It lets to write your own HTML and gives you access to some tags (link, title, image etc)


== Installation ==

This section describes how to install the plugin and get it working.

1. Upload all files contained in best-related-post.zip file to the `/wp-content/plugins/best-related-posts/` directory of your web server.
1. Activate the plugin through the 'Plugins' menu in WordPress
1. To add the related post list, usually you have to open the single.php of your current theme and add the PHP code:

    `if (function_exists('boposts_show')) { boposts_show(); } ?>`

or can use Post Layout plugin and add such line of code in the single post bottom.

== Frequently Asked Questions ==

= Do I have to create thumbnails for each post? =

No, It takes first image of your post and displays it as thumbnail.

== Screenshots ==

1. Showing related posts to visitors.
2. Plugin Options Page for Admin.

== Changelog ==

= 1.0 =
* released the plugin
= 1.0.1 =
*fixed bug in admin menu
*fixed array_filter errors
= 1.0.2 =
*fixed image width issue
*added multiple language support
= 1.0.3 =
*fixed instal issues including file location problems
= 1.0.4 =
*Fixed the code so related posts does not include pages
= 1.0.5 =
*Fixed the error that was not showing posts on some blogs after last update
= 1.0.6 =
*Fixed Formatting issue with IE
=1.0.7 =
*Fixed the Function call bug
=1.0.8 =
*Fixed Wrapping issues in IE 6 +



