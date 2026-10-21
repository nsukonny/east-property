<?php
/**
 * Include all headers parts
 */

get_template_part( 'core/components/common/head' );
?>
<body <?php body_class(); ?>>
<?php do_action( 'wp_body_open' ); ?>
<div class="wrapper">

	<?php get_template_part( 'core/components/common/header', null, array( 'color' => $args['color'] ?? 'white' ) ); ?>

	<main>