<?php
/**
 * Header template
 */

$color = $args['color'] ?? '';

get_component_template( 'common/header', array( 'color' => $color ) );
