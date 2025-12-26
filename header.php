<?php
/**
 * Include all headers parts
 */

get_template_part( 'template-parts/components/common/head' );
?>
<body <?php body_class(); ?>>
<div class="wrapper">

    <?php
    get_template_part( 'template-parts/components/common/header', null, array( 'color' => 'white' ) );
    ?>

    <main>
