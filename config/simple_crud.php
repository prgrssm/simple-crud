<?php

return [
    'simple_crud' => [
        'form' => [
            'banned_attributes' => ['name', 'class', 'type'],
        ],

        'form_data_setter' => [
            'remove_spaces_keys' => [
                'meta_title',
                'meta_keywords',
                'meta_description',
                'position_meta_title_template',
                'position_meta_description_template'
            ],

            'ignore_keys' => [
                'id',
                'image',
                'sharing_image'
            ]
        ]
    ]
];
