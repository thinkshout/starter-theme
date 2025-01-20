<?php
/**
 * Search results page
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 */

use Timber\Timber;

$templates = [ 'pages/search.twig', 'pages/archive.twig', 'pages/index.twig' ];

$context = Timber::context(
	[
		'title' => 'Search results for ' . get_search_query(),
	]
);

Timber::render( $templates, $context );
