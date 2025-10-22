<?php
/**
 * ACF Field Group for Petition Pages
 * Add this to functions.php or use ACF UI to create these fields
 */

if( function_exists('acf_add_local_field_group') ):

acf_add_local_field_group(array(
    'key' => 'group_petition_fields',
    'title' => 'Petition Content',
    'fields' => array(
        array(
            'key' => 'field_petition_image',
            'label' => 'Petition Image URL',
            'name' => 'petition_image',
            'type' => 'url',
            'instructions' => 'URL of the main petition image',
            'placeholder' => 'https://www.taxpayer.com/media/image.jpg',
        ),
        array(
            'key' => 'petition_tag',
            'label' => 'Mailchimp Petition Tag',
            'name' => 'petition_tag',
            'type' => 'textarea',
            'instructions' => 'Mailchimp petition tag'
        )
    ),
    'location' => array(
        array(
            array(
                'param' => 'post_type',
                'operator' => '==',
                'value' => 'petition',
            ),
        ),
    ),
    'menu_order' => 0,
    'position' => 'normal',
    'style' => 'default',
));

endif;

