<?php
/*
Plugin Name: Best Related Posts
Plugin URI: http://www.sutlej.net/downloads/best-related-posts/
Description: Shows related posts with thumbnails. Allows you to design your own layout using simple interface.
Version: 1.1.0
Author: Rana Mansoor Akbar Khan
Author URI: http://www.sutlej.net/

*/

/*	Copyright 2011  Sutlej.NET  (email : rmak@sutlej.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
define('BO_POSTS', true);

@include(dirname(__FILE__) . '/en_US_options.php');
if (WPLANG != '') @include(dirname(__FILE__) . '/' . WPLANG . '_options.php');

$boposts_options = array_merge($boposts_default_options, array_filter(get_option('boposts')));

function boposts_activate()
{
    global $wpdb;

    $wpdb->query('ALTER TABLE ' . $wpdb->prefix . 'posts ADD FULLTEXT boposts_index (post_content, post_title)');

    @include(dirname(__FILE__) . '/en_US_options.php');
    if (WPLANG != '') @include(dirname(__FILE__) . '/' . WPLANG . '_options.php');

    update_option('boposts', $boposts_default_options);
}

function boposts_deactivate()
{
    global $wpdb;

    $wpdb->query('DROP INDEX boposts_index ON ' . $wpdb->prefix . 'posts');
}

function boposts_admin_menu()
{
    add_options_page('Best Related Posts', 'Best Related Posts', 'manage_options', 'best-related-posts/options.php');
}

function boposts_find_posts()
{
    global $wpdb, $post, $boposts_options;

    $max = $boposts_options['max'];
    if (!$max) $max = 5;
    $terms = preg_replace('/[^a-z0-9]/i', ' ', $post->post_title);
    $terms2 = preg_replace('/[^a-z0-9]/i', ' ', strip_tags($post->post_content));
    if (strlen($terms2) > 100) {
        $x = strpos($terms2, ' ', 100);
        if ($x > 0) $terms2 = substr($terms2, 0, $x);
    }
    $now = gmdate("Y-m-d H:i:s", time() + get_settings('gmt_offset')*3600);

    $query = 'select id, post_content, post_title, match(post_title, post_content) against (\'' . $terms . ' ' . $terms2 . '\') as score from ' . $wpdb->posts  .
    ' where match(post_title, post_content) against (\'' .
    $terms . ' ' . $terms2 . "') and post_date<='" . $now . "'" .
    ' and post_type in (\'post\') and post_status in (\'publish\') and id!=' . $post->ID .
    ' order by score desc limit ' . $max;

    return $wpdb->get_results($query);

}

/**
 * Outputs the HTML code with the related posts list.
 */
function boposts_show()
{
    global $wpdb, $post, $boposts_options;

    $results = boposts_find_posts();
    if (!$results) {
        echo 'No results';
        return;
    }

    echo $boposts_options['header'];
    $c = count($results);
    for ($i=0; $i<$c; $i++)
    {
        $r = &$results[$i];
        $p = get_post($r->id);
        $t = get_the_title($r->id);
        $excerpt = $r->post_content;
        $l = get_permalink($r->id);

        $content = $r->post_content;
        // Remove the short codes
        $content = preg_replace('/\[.*\]/', '', $content);
        // Image extraction
        $image = '';
        $x = stripos($content, '<img');

        if ($x !== false) {
            $x = stripos($content, 'src="', $x);
            if ($x !== false) {
                $x += 5;
                $y = strpos($content, '"', $x);
                $image = substr($content, $x, $y-$x);
            }
        }

        if ($image == '') $image = get_option('siteurl') . '/wp-content/plugins/best-related-posts/empty.gif';

        // Excerpt extraction
        $excerpt = strip_tags($content);
        if (strlen($excerpt) > $boposts_options['excerpt'])
        {
            $x = strpos($excerpt, ' ', $boposts_options['excerpt']);
            if ($x !== false) $excerpt = substr($excerpt, 0, $x);
        }
        $s = $boposts_options['body'];
        $s = str_replace('{link}', $l, $s);
        $s = str_replace('{title}', $t, $s);
        $s = str_replace('{image}', $image, $s);
        $s = str_replace('{excerpt}', $excerpt . '...', $s);

        echo $s;
}
    echo $boposts_options['footer'];
    
}

add_action('admin_menu', 'boposts_admin_menu');
register_activation_hook(__FILE__, 'boposts_activate');
register_deactivation_hook(__FILE__, 'boposts_deactivate');

?>
