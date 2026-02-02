<?php
/**
 * More properties from unit property
 *
 * @var \Entities\Unit $unit
 */

$unit = $args['unit'] ?? null;
if ( empty( $unit ) ) {
    return;
}

$property = $unit->get_property();
if ( empty( $property ) ) {
    return;
}

$random_units = $property->get_random_units( 3, $unit->get_id() );
if ( empty( $random_units ) ) {
    return;
}

$cards = array();
foreach ( $random_units as $unit ) {
    $property  = $unit->get_property();
    $developer = $unit->get_developer();

    $cards[] = array(
            'title'          => $unit->get_title(),
            'price'          => $unit->get_price_html(),
            'property_name'  => $property !== null ? $property->get_title() : '',
            'property_url'   => $property !== null ? $property->get_url() : '',
            'developer_name' => $developer ? $developer->get_title() : '',
            'gallery'        => $unit->get_gallery(),
            'labels'         => $unit->get_labels(),
            'amenities'      => $unit->get_amenities(),
            'url'            => $unit->get_url(),
    );
}
?>
<section class="properties white">
    <div class="container">
        <div class="properties-wrapper">
            <?php
            get_template_part( 'components/titles/top-title', null,
                    array(
                            'h2'   => __( 'More properties in this project' ),
                            'href' => $property->get_url() . '?units=1',
                            'link' => __( 'All properties' ),
                    )
            );
            ?>
            <div class="properties-cards">
                <?php
                foreach ( $cards as $card ) {
                    get_template_part( 'template-parts/cards/property-card', null,
                            array(
                                    'title'          => $card['title'],
                                    'price'          => $card['price'],
                                    'location'       => $card['location'],
                                    'image'          => $card['gallery'][0]['sizes']['featured-card'] ?? '',
                                    'labels'         => $card['labels'] ?? array(),
                                    'amenities'      => $card['amenities'] ?? array(),
                                    'property_name'  => $card['property_name'] ?? '',
                                    'property_url'   => $card['property_url'] ?? '',
                                    'developer_name' => $card['developer_name'] ?? '',
                                    'url'            => $card['url'],
                            )
                    );
                }
                ?>
            </div>
        </div>
    </div>
</section>