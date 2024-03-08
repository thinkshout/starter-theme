<?php
/**
 * The Template for displaying all single posts
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */

$context         = Timber::context();
$timber_post     = Timber::get_post();
$context['post'] = $timber_post;

if ( post_password_required( $timber_post->ID ) ) {
	Timber::render( 'templates/posts/single-password.twig', $context );
} else {
	Timber::render(
		array(
			'templates/posts/single-' . $timber_post->ID . '.twig',
			'templates/posts/single-' . $timber_post->post_type . '.twig',
			'templates/posts/single-' . $timber_post->slug . '.twig',
			'templates/posts/single.twig',
		),
		$context
	);
}
