<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('acf/init', function () {
    if ( ! function_exists('acf_add_local_field_group') ) return;

    acf_add_local_field_group([
        'key' => 'group_poc_carousel',
        'title' => 'Point One Card Carousel',
        'location' => [[
            ['param' => 'post_type', 'operator' => '==', 'value' => 'pointone_carousel']
        ]],
        'fields' => [
            [
                'key' => 'field_poc_peek_wrap',
                'label' => 'Peek Wrap',
                'name' => 'peek_wrap',
                'type' => 'true_false',
                'ui' => 1,
                'message' => 'Enable Peek Wrap (outer slides partially visible)',
                'default_value' => 1,
            ],
            [
                'key' => 'field_poc_slides',
                'label' => 'Slides',
                'name' => 'slides',
                'type' => 'repeater',
                'layout' => 'row',
                'button_label' => 'Add Slide',
                'min' => 1,
                'sub_fields' => [
                    [
                        'key' => 'field_poc_media',
                        'label' => 'Background Media',
                        'name' => 'media',
                        'type' => 'file',
                        'required' => 1,
                        'return_format' => 'array',
                        'mime_types' => 'jpg,jpeg,png,webp,mp4',
                        'instructions' => 'Image (JPG/PNG/WEBP) or MP4 video.',
                    ],
                    [
                        'key' => 'field_poc_eyebrow',
                        'label' => 'Eyebrow',
                        'name' => 'eyebrow',
                        'type' => 'text',
                    ],
                    [
                        'key' => 'field_poc_headline',
                        'label' => 'Headline',
                        'name' => 'headline',
                        'type' => 'text',
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_poc_body',
                        'label' => 'Body',
                        'name' => 'body',
                        'type' => 'textarea',
                        'rows' => 3,
                        'required' => 1,
                    ],
                    [
                        'key' => 'field_poc_link_url',
                        'label' => 'Link URL',
                        'name' => 'link_url',
                        'type' => 'url',
                    ],
                    [
                        'key' => 'field_poc_link_text',
                        'label' => 'Link Text',
                        'name' => 'link_text',
                        'type' => 'text',
                        'instructions' => 'Shown at bottom of the card.',
                    ],
                ],
            ],
        ],
    ]);
});
