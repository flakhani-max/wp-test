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
            'key' => 'field_petition_title',
            'label' => 'Petition Title',
            'name' => 'petition_title',
            'type' => 'text',
            'instructions' => 'Main petition heading (leave empty to use page title)',
            'placeholder' => 'Will you sign the petition?',
        ),
        array(
            'key' => 'field_petition_image',
            'label' => 'Petition Image URL',
            'name' => 'petition_image',
            'type' => 'url',
            'instructions' => 'URL of the main petition image',
            'placeholder' => 'https://www.taxpayer.com/media/image.jpg',
        ),
        array(
            'key' => 'field_petition_intro',
            'label' => 'Introduction Text',
            'name' => 'petition_intro',
            'type' => 'textarea',
            'instructions' => 'Opening paragraph',
            'rows' => 3,
        ),
        array(
            'key' => 'field_petition_body',
            'label' => 'Body Text',
            'name' => 'petition_body',
            'type' => 'textarea',
            'instructions' => 'Main petition description',
            'rows' => 4,
        ),
        array(
            'key' => 'field_petition_text',
            'label' => 'Petition Statement',
            'name' => 'petition_text',
            'type' => 'textarea',
            'instructions' => 'The actual petition text (e.g., "We, the undersigned...")',
            'rows' => 2,
        ),
        array(
            'key' => 'field_petition_cta',
            'label' => 'Call to Action',
            'name' => 'petition_cta',
            'type' => 'textarea',
            'instructions' => 'Text above the form',
            'rows' => 2,
        ),

        array(
            'key' => 'petition_tag',
            'label' => 'Mailchimp Petition Tag',
            'name' => 'petition_tag',
            'type' => 'textarea',
            'instructions' => 'Mailchimp petition tag'
        ),
        array(
            'key' => 'field_petition_sms_text',
            'label' => 'SMS Opt-in Text',
            'name' => 'petition_sms_text',
            'type' => 'text',
            'default_value' => 'SMS: I also want to receive occasional text messages to keep me up to date.',
        ),
        array(
            'key' => 'field_privacy_text',
            'label' => 'Privacy Notice Text',
            'name' => 'privacy_text',
            'type' => 'textarea',
            'rows' => 2,
            'default_value' => 'We take data security and privacy seriously. Your information will be kept safe, and will be used to sign your petition.',
        ),
        array(
            'key' => 'field_privacy_link',
            'label' => 'Privacy Policy URL',
            'name' => 'privacy_link',
            'type' => 'url',
            'default_value' => 'https://www.taxpayer.com/privacy-policy/',
        ),
        array(
            'key' => 'field_privacy_link_text',
            'label' => 'Privacy Policy Link Text',
            'name' => 'privacy_link_text',
            'type' => 'text',
            'default_value' => 'Privacy Policy',
        ),

        array(
            'key' => 'field_privacy_link_text',
            'label' => 'Privacy Policy Link Text',
            'name' => 'privacy_link_text',
            'type' => 'text',
            'default_value' => 'Privacy Policy',
        ),
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

