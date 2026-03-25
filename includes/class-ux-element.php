<?php

if (!defined('ABSPATH')) exit;

class ACF_Filter_UX_Element {

    public static function init() {
        add_action('ux_builder_setup', [__CLASS__, 'register']);
    }

    public static function register() {

        // Hent ACF taksonomier for valg i builder
        $taxonomy_options = ['none' => '– Ingen –'];
        if (function_exists('acf_get_taxonomies')) {
            $skip = ['language', 'post_translations', 'term_language', 'term_translations'];
            foreach (acf_get_taxonomies() as $name) {
                if (in_array($name, $skip)) continue;
                $tax = get_taxonomy($name);
                if ($tax) $taxonomy_options[$name] = $tax->label;
            }
        }

        add_ux_builder_shortcode('acf_filter_results', [
            'name'      => 'Blogg Filter Resultater',
            'category'  => 'Content',
            'icon'      => 'dashicons-filter',
            'options'   => [

                'layout_options' => [
                    'type'    => 'group',
                    'heading' => 'Layout',
                    'options' => [

                        'posts_per_page' => [
                            'type'    => 'slider',
                            'heading' => 'Antall innlegg per side',
                            'default' => 10,
                            'min'     => 1,
                            'max'     => 50,
                            'step'    => 1,
                        ],

                        'columns' => [
                            'type'    => 'select',
                            'heading' => 'Kolonner',
                            'default' => '3',
                            'responsive' => true,
                            'options' => [
                                '1' => '1 kolonne',
                                '2' => '2 kolonner',
                                '3' => '3 kolonner',
                                '4' => '4 kolonner',
                            ],
                        ],
                    ],
                ],

                'design_options' => [
                    'type'    => 'group',
                    'heading' => 'Design',
                    'options' => [

                        'style' => [
                            'type'    => 'select',
                            'heading' => 'Stil',
                            'default' => '',
                            'options' => [
                                'card'      => 'Kort',
                                'simple'      => 'Enkel',
                            ],
                        ],
                    ],
                ],

                'image_options' => [
                    'type'    => 'group',
                    'heading' => 'Bilde',
                    'options' => [

                        'show_image' => [
                            'type'    => 'select',
                            'heading' => 'Vis bilde',
                            'default' => 'yes',
                            'options' => [
                                'yes' => 'Ja',
                                'no'  => 'Nei',
                            ],
                        ],

                        'image_size' => [
                            'type'       => 'select',
                            'heading'    => 'Bildestørrelse',
                            'default'    => 'medium',
                            'conditions' => 'show_image == "yes"',
                            'options'    => [
                                'thumbnail' => 'Thumbnail',
                                'medium'    => 'Medium',
                                'large'     => 'Large',
                                'full'      => 'Full',
                            ],
                        ],
                    ],
                ],

                'content_options' => [
                    'type'    => 'group',
                    'heading' => 'Innhold',
                    'options' => [

                        'show_title' => [
                            'type'    => 'select',
                            'heading' => 'Vis tittel',
                            'default' => 'yes',
                            'options' => [
                                'yes' => 'Ja',
                                'no'  => 'Nei',
                            ],
                        ],

                        'show_excerpt' => [
                            'type'    => 'select',
                            'heading' => 'Vis utdrag',
                            'default' => 'yes',
                            'options' => [
                                'yes' => 'Ja',
                                'no'  => 'Nei',
                            ],
                        ],

                        'excerpt_length' => [
                            'type'       => 'slider',
                            'heading'    => 'Utdrag lengde (ord)',
                            'default'    => 20,
                            'min'        => 5,
                            'max'        => 100,
                            'step'       => 5,
                            'conditions' => 'show_excerpt == "yes"',
                        ],
                    ],
                ],

                'meta_options' => [
                    'type'    => 'group',
                    'heading' => 'Meta',
                    'options' => [

                        'show_date' => [
                            'type'    => 'select',
                            'heading' => 'Dato',
                            'default' => 'yes',
                            'options' => [
                                'yes' => 'Synlig',
                                'no'  => 'Skjult',
                            ],
                        ],

                        'show_author' => [
                            'type'    => 'select',
                            'heading' => 'Forfatter',
                            'default' => 'yes',
                            'options' => [
                                'yes' => 'Synlig',
                                'no'  => 'Skjult',
                            ],
                        ],
                    ],
                ],

                'acf_tax_options' => [
                    'type'    => 'group',
                    'heading' => 'Taksonomier',
                    'options' => [

                        'acf_taxonomies' => [
                            'type'    => 'select',
                            'heading' => 'Velg taksonomier',
                            'default' => '',
                            'config'  => [
                                'multiple'    => true,
                                'placeholder' => 'Velg taksonomier...',
                                'options'     => $taxonomy_options,
                            ],
                        ],
                    ],
                ],

                'read_more_options' => [
                    'type'    => 'group',
                    'heading' => 'Les mer knapp',
                    'options' => [

                        'show_read_more' => [
                            'type'    => 'select',
                            'heading' => 'Vis knapp',
                            'default' => 'yes',
                            'options' => [
                                'yes' => 'Ja',
                                'no'  => 'Nei',
                            ],
                        ],

                        'read_more_text' => [
                            'type'       => 'textfield',
                            'heading'    => 'Tekst',
                            'default'    => 'Les mer',
                            'conditions' => 'show_read_more == "yes"',
                        ],

                        'read_more_style' => [
                            'type'       => 'select',
                            'heading'    => 'Stil',
                            'default'    => 'outline',
                            'conditions' => 'show_read_more == "yes"',
                            'options'    => [
                                ''          => 'Standard',
                                'outline'   => 'Outline',
                                'link'      => 'Enkel',
                                'underline' => 'Understrek',
                                'shade'     => 'Shade',
                            ],
                        ],

                        'read_more_size' => [
                            'type'       => 'select',
                            'heading'    => 'Størrelse',
                            'default'    => '',
                            'conditions' => 'show_read_more == "yes"',
                            'options'    => [
                                'small'  => 'Liten',
                                ''       => 'Normal',
                                'large'  => 'Stor',
                            ],
                        ],
                    ],
                ],

                'pagination_options' => [
                    'type'    => 'group',
                    'heading' => 'Paginering',
                    'options' => [

                        'show_pagination' => [
                            'type'    => 'select',
                            'heading' => 'Vis paginering',
                            'default' => 'yes',
                            'options' => [
                                'yes' => 'Ja',
                                'no'  => 'Nei',
                            ],
                        ],

                        'prev_text' => [
                            'type'       => 'textfield',
                            'heading'    => 'Forrige tekst',
                            'default'    => '« Forrige',
                            'conditions' => 'show_pagination == "yes"',
                        ],

                        'next_text' => [
                            'type'       => 'textfield',
                            'heading'    => 'Neste tekst',
                            'default'    => 'Neste »',
                            'conditions' => 'show_pagination == "yes"',
                        ],
                    ],
                ],

                'advanced_options' => [
                    'type' => 'group',
                    'heading' => 'Advanced',
                    'options' => [
                        'label' => [
                            'type' => 'textfield',
                            'heading' => 'Label',
                            'placeholder' => 'Enter admin label here..'
                        ],
                        'class' => [
                            'type' => 'textfield',
                            'heading' => 'Class',
                            'param_name' => 'class',
                            'default' => '',
                        ],
                    ]
                ],
            ],
        ]);

        add_ux_builder_shortcode('acf_filter', [
            'name'     => 'Blogg Filter',
            'category' => 'Content',
            'icon'     => 'dashicons-filter',
            'options'  => [
                'design_options' => [
                    'type'    => 'group',
                    'heading' => 'Design',
                    'options' => [
                        'style' => [
                            'type'    => 'select',
                            'heading' => 'Stil',
                            'default' => 'sidebar',
                            'responsive' => true,   
                            'options' => [
                                'sidebar'      => 'Sidebar',
                                'topbar'       => 'Topbar',
                                'offcanvas'   => 'Offcanvas',
                            ],
                        ],
                    ],
                ],
                'Offcanvas_options' =>[
                    'type'    => 'group',
                    'heading' => 'Offcanvas',
                    'options' => [
                        'offcanvas_label' => [
                            'type'       => 'textfield',
                            'heading'    => 'Offcanvas knapp tekst',
                            'default'    => '',
                        ],
                        'apply_label' => [
                            'type'       => 'textfield',
                            'heading'    => 'Vis resultater',
                            'default'    => '',
                        ],
                        'reset_label' => [
                            'type'       => 'textfield',
                            'heading'    => 'Nullstill knapp tekst',
                            'default'    => '',
                        ],
                    ],
                ],
                'advanced_options' => [
                    'type' => 'group',
                    'heading' => 'Advanced',
                    'options' => [
                        'label' => [
                            'type' => 'textfield',
                            'heading' => 'Label',
                            'placeholder' => 'Enter admin label here..'
                        ],
                        'class' => [
                            'type' => 'textfield',
                            'heading' => 'Class',
                            'param_name' => 'class',
                            'default' => '',
                        ],
                    ]
                ],
            ],
        ]);
    }
}

ACF_Filter_UX_Element::init();