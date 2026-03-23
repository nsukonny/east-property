<?php
/**
 * Single template for Properties (CPT: properties)
 */

use Entities\Property;

get_header( null, array( 'color' => 'white' ) );
?>
    <main>
        <?php
        while ( have_posts() ) {
            the_post();

            $property = new Property( get_the_ID() );

            get_template_part( 'core/components/properties/property-single', null,
                    array(
                            'property'          => $property,
                            'quote_button_args' => array(
                                    'class' => 'orange sm request-quote',
                                    'text'  => __( 'Request quote' ),
                                    'modal' => 'quote-modal',
                            ),
                    )
            );
        }
        ?>
    </main>
<?php
get_footer();

