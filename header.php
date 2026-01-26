<?php
/**
 * Include all headers parts
 */

get_template_part( 'components/common/head' );
?>
<body <?php body_class(); ?>>
<div class="wrapper">

    <?php get_template_part( 'components/common/header', null, array( 'color' => $args['color'] ?? 'white' ) ); ?>

    <main>