<?php
/**
 * The template for displaying all pages.
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages
 * and that other 'pages' on your WordPress site will use a
 * different template.
 */

namespace App;

use Timber\Timber;

$context = Timber::context();
$post = $context['post'];

// Check if this is the style guide page.
if ( 'style-guide' === $post->post_name ) {
	$acf_block_types      = acf_get_store( 'block-types' );
	$context['ts_blocks'] = $acf_block_types->get_data();
}

$templates = array( 'pages/page-' . $post->post_name . '.twig', 'pages/page.twig' );

if ( is_front_page() ) {
	array_unshift( $templates, 'pages/front-page.twig' );
}

Timber::render( $templates, $context );
