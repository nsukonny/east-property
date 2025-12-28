<?php
/**
 * Single template for Properties (CPT: properties)
 */

get_header();
?>
<main>
	<?php
	while ( have_posts() ) :
		the_post();
		get_template_part( 'template-parts/content', get_post_type() );

		// Optional: uncomment if you want comments on properties.
		// if ( comments_open() || get_comments_number() ) {
		// 	comments_template();
		// }
	endwhile;
	?>
</main>
<?php
get_footer();

