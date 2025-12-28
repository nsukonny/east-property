<?php
/**
 * Archive template for Properties (CPT: properties)
 */

get_header();
?>
<main>
	<?php if ( have_posts() ) : ?>
		<header class="page-header">
			<h1 class="page-title"><?php post_type_archive_title(); ?></h1>
		</header>

		<?php
		while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/content', get_post_type() );
		endwhile;

		the_posts_pagination();
		?>
	<?php else : ?>
		<?php get_template_part( 'template-parts/content', 'none' ); ?>
	<?php endif; ?>
</main>
<?php
get_footer();

