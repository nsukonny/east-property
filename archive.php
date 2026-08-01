<?php
/**
 * The template for displaying archive pages.
 *
 * Learn more: https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package woostify
 */

get_header();

wp_safe_redirect( home_url( '/404' ) );
exit;

get_footer();
