<?php
/** Optional homepage copy. Registering controls never changes existing options. */
defined( 'ABSPATH' ) || exit;

function pod_store_theme_customize( $manager ) {
	$manager->add_section( 'pod_home', array( 'title' => __( 'POD Homepage', 'pod-store-theme' ), 'priority' => 30 ) );
	$fields = array(
		'pod_home_eyebrow' => array( 'label' => __( 'Intro label', 'pod-store-theme' ), 'default' => __( 'Wear what feels like you', 'pod-store-theme' ) ),
		'pod_home_title' => array( 'label' => __( 'Headline', 'pod-store-theme' ), 'default' => __( 'Good tees. Great company.', 'pod-store-theme' ) ),
		'pod_home_description' => array( 'label' => __( 'Description', 'pod-store-theme' ), 'default' => __( 'A fresh perspective on your everyday wardrobe. Find a graphic, make it yours.', 'pod-store-theme' ) ),
	);
	foreach ( $fields as $key => $field ) {
		$manager->add_setting( $key, array( 'default' => $field['default'], 'sanitize_callback' => 'sanitize_text_field', 'transport' => 'refresh' ) );
		$manager->add_control( $key, array( 'section' => 'pod_home', 'label' => $field['label'], 'type' => 'text' ) );
	}
}
add_action( 'customize_register', 'pod_store_theme_customize' );
