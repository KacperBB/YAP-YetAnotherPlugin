<?php
/**
 * YAP Gutenberg Blocks - Complete Examples
 * Examples of registering and using custom Gutenberg blocks with YAP
 */

// ============================================
// 1. BASIC BLOCK - TESTIMONIAL
// ============================================

add_action('init', 'register_yap_testimonial_block');

function register_yap_testimonial_block() {
    yap_register_block([
        'name' => 'testimonial',
        'title' => 'Testimonial',
        'description' => 'Display a customer testimonial with photo',
        'category' => 'yap-blocks',
        'icon' => 'format-quote',
        'keywords' => ['testimonial', 'quote', 'review'],
        'mode' => 'preview', // 'preview', 'edit', or 'auto'
        'supports' => [
            'align' => ['left', 'center', 'right', 'wide', 'full'],
            'mode' => true,
            'anchor' => true,
            'jsx' => true // Enable InnerBlocks
        ],
        'fields' => [
            [
                'name' => 'author_name',
                'label' => 'Author Name',
                'type' => 'short_text',
                'default_value' => 'John Doe',
                'validation' => [
                    'required' => true
                ]
            ],
            [
                'name' => 'author_title',
                'label' => 'Author Title/Company',
                'type' => 'short_text',
                'default_value' => 'CEO, Company Inc.'
            ],
            [
                'name' => 'author_photo',
                'label' => 'Author Photo',
                'type' => 'image'
            ],
            [
                'name' => 'testimonial_text',
                'label' => 'Testimonial Text',
                'type' => 'long_text',
                'options' => [
                    'rows' => 5
                ],
                'validation' => [
                    'required' => true
                ]
            ],
            [
                'name' => 'rating',
                'label' => 'Rating (1-5)',
                'type' => 'range',
                'options' => [
                    'min' => 1,
                    'max' => 5,
                    'step' => 1,
                    'default_value' => 5
                ]
            ]
        ],
        'render_template' => 'testimonial.php', // Look in theme/template-parts/blocks/
        'render_callback' => 'render_testimonial_block', // Or use callback
        'enqueue_style' => plugin_dir_url(__FILE__) . '../css/blocks/testimonial.css'
    ]);
}

// Render callback
function render_testimonial_block($data, $content, $block) {
    $author_name = $data['fields']['author_name']['value'] ?? '';
    $author_title = $data['fields']['author_title']['value'] ?? '';
    $author_photo = $data['fields']['author_photo']['value'] ?? '';
    $testimonial_text = $data['fields']['testimonial_text']['value'] ?? '';
    $rating = $data['fields']['rating']['value'] ?? 5;
    
    $photo_url = $author_photo ? wp_get_attachment_url($author_photo) : '';
    
    ?>
    <div class="yap-testimonial <?php echo esc_attr($data['attributes']['className'] ?? ''); ?>" 
         id="<?php echo esc_attr($data['attributes']['anchor'] ?? ''); ?>">
        <div class="testimonial-content">
            <div class="testimonial-rating">
                <?php for ($i = 0; $i < $rating; $i++): ?>
                    <span class="star">★</span>
                <?php endfor; ?>
            </div>
            <blockquote class="testimonial-text">
                "<?php echo esc_html($testimonial_text); ?>"
            </blockquote>
        </div>
        <div class="testimonial-author">
            <?php if ($photo_url): ?>
                <img src="<?php echo esc_url($photo_url); ?>" 
                     alt="<?php echo esc_attr($author_name); ?>" 
                     class="author-photo">
            <?php endif; ?>
            <div class="author-info">
                <strong class="author-name"><?php echo esc_html($author_name); ?></strong>
                <span class="author-title"><?php echo esc_html($author_title); ?></span>
            </div>
        </div>
    </div>
    <?php
}

// ============================================
// 2. ADVANCED BLOCK - HERO SECTION
// ============================================

add_action('init', 'register_yap_hero_block');

function register_yap_hero_block() {
    yap_register_block([
        'name' => 'hero',
        'title' => 'Hero Section',
        'description' => 'Full-width hero section with background and CTA',
        'category' => 'yap-blocks',
        'icon' => 'cover-image',
        'keywords' => ['hero', 'banner', 'header'],
        'mode' => 'preview',
        'supports' => [
            'align' => ['full'],
            'mode' => true,
            'jsx' => true, // InnerBlocks for flexible content
            'color' => [
                'background' => true,
                'text' => true
            ],
            'spacing' => [
                'padding' => true,
                'margin' => true
            ],
            'typography' => [
                'fontSize' => true
            ]
        ],
        'fields' => [
            [
                'name' => 'heading',
                'label' => 'Heading',
                'type' => 'short_text',
                'default_value' => 'Welcome to Our Site',
                'validation' => ['required' => true]
            ],
            [
                'name' => 'subheading',
                'label' => 'Subheading',
                'type' => 'long_text'
            ],
            [
                'name' => 'background_image',
                'label' => 'Background Image',
                'type' => 'image'
            ],
            [
                'name' => 'overlay_opacity',
                'label' => 'Overlay Opacity',
                'type' => 'range',
                'options' => [
                    'min' => 0,
                    'max' => 100,
                    'step' => 5,
                    'default_value' => 50
                ]
            ],
            [
                'name' => 'height',
                'label' => 'Hero Height',
                'type' => 'select',
                'options' => [
                    'choices' => [
                        ['value' => 'small', 'label' => 'Small (400px)'],
                        ['value' => 'medium', 'label' => 'Medium (600px)'],
                        ['value' => 'large', 'label' => 'Large (800px)'],
                        ['value' => 'fullscreen', 'label' => 'Full Screen']
                    ],
                    'default_value' => 'medium'
                ]
            ],
            [
                'name' => 'cta_text',
                'label' => 'CTA Button Text',
                'type' => 'short_text',
                'default_value' => 'Learn More'
            ],
            [
                'name' => 'cta_link',
                'label' => 'CTA Button Link',
                'type' => 'short_text',
                'default_value' => '#'
            ]
        ],
        'render_callback' => 'render_hero_block',
        'enqueue_style' => plugin_dir_url(__FILE__) . '../css/blocks/hero.css',
        'example' => [
            'attributes' => [
                'heading' => 'Example Hero',
                'subheading' => 'This is how your hero section will look'
            ]
        ]
    ]);
}

function render_hero_block($data, $content, $block) {
    extract($data['fields']);
    
    $bg_image = $background_image['value'] ? wp_get_attachment_url($background_image['value']) : '';
    $height = $height['value'] ?? 'medium';
    $opacity = ($overlay_opacity['value'] ?? 50) / 100;
    
    ?>
    <section class="yap-hero yap-hero--<?php echo esc_attr($height); ?>" 
             style="<?php echo $bg_image ? "background-image: url('" . esc_url($bg_image) . "');" : ''; ?>">
        <div class="hero-overlay" style="opacity: <?php echo esc_attr($opacity); ?>;"></div>
        <div class="hero-content">
            <h1 class="hero-heading"><?php echo esc_html($heading['value']); ?></h1>
            <?php if (!empty($subheading['value'])): ?>
                <p class="hero-subheading"><?php echo esc_html($subheading['value']); ?></p>
            <?php endif; ?>
            <?php if (!empty($cta_text['value'])): ?>
                <a href="<?php echo esc_url($cta_link['value']); ?>" class="hero-cta">
                    <?php echo esc_html($cta_text['value']); ?>
                </a>
            <?php endif; ?>
            
            <!-- InnerBlocks content -->
            <div class="hero-inner-content">
                <?php echo $content; ?>
            </div>
        </div>
    </section>
    <?php
}

// ============================================
// 3. BLOCK WITH REPEATER - TEAM MEMBERS
// ============================================

add_action('init', 'register_yap_team_block');

function register_yap_team_block() {
    yap_register_block([
        'name' => 'team',
        'title' => 'Team Members',
        'description' => 'Display team members in a grid',
        'category' => 'yap-blocks',
        'icon' => 'groups',
        'keywords' => ['team', 'staff', 'people'],
        'fields' => [
            [
                'name' => 'section_title',
                'label' => 'Section Title',
                'type' => 'short_text',
                'default_value' => 'Our Team'
            ],
            [
                'name' => 'columns',
                'label' => 'Columns',
                'type' => 'select',
                'options' => [
                    'choices' => [
                        ['value' => '2', 'label' => '2 Columns'],
                        ['value' => '3', 'label' => '3 Columns'],
                        ['value' => '4', 'label' => '4 Columns']
                    ],
                    'default_value' => '3'
                ]
            ],
            [
                'name' => 'team_members',
                'label' => 'Team Members',
                'type' => 'repeater',
                'is_repeater' => true,
                'repeater_min' => 1,
                'repeater_max' => 12,
                'sub_fields' => [
                    [
                        'name' => 'member_name',
                        'label' => 'Name',
                        'type' => 'short_text'
                    ],
                    [
                        'name' => 'member_role',
                        'label' => 'Role',
                        'type' => 'short_text'
                    ],
                    [
                        'name' => 'member_photo',
                        'label' => 'Photo',
                        'type' => 'image'
                    ],
                    [
                        'name' => 'member_bio',
                        'label' => 'Bio',
                        'type' => 'long_text'
                    ]
                ]
            ]
        ],
        'render_callback' => 'render_team_block'
    ]);
}

function render_team_block($data, $content, $block) {
    $title = $data['fields']['section_title']['value'] ?? 'Our Team';
    $columns = $data['fields']['columns']['value'] ?? '3';
    $members = $data['fields']['team_members']['value'] ?? [];
    
    ?>
    <div class="yap-team">
        <h2 class="team-title"><?php echo esc_html($title); ?></h2>
        <div class="team-grid team-grid--<?php echo esc_attr($columns); ?>-col">
            <?php foreach ($members as $member): ?>
                <div class="team-member">
                    <?php if (!empty($member['member_photo'])): ?>
                        <img src="<?php echo esc_url(wp_get_attachment_url($member['member_photo'])); ?>" 
                             alt="<?php echo esc_attr($member['member_name']); ?>" 
                             class="member-photo">
                    <?php endif; ?>
                    <h3 class="member-name"><?php echo esc_html($member['member_name']); ?></h3>
                    <p class="member-role"><?php echo esc_html($member['member_role']); ?></p>
                    <?php if (!empty($member['member_bio'])): ?>
                        <p class="member-bio"><?php echo esc_html($member['member_bio']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

// ============================================
// 4. BLOCK WITH THEME.JSON INTEGRATION
// ============================================

add_action('init', 'register_yap_card_block');

function register_yap_card_block() {
    yap_register_block([
        'name' => 'card',
        'title' => 'Content Card',
        'description' => 'A card with icon, title, and description',
        'category' => 'yap-blocks',
        'icon' => 'id-alt',
        'supports' => [
            'color' => [
                'background' => true,
                'text' => true,
                'gradients' => true
            ],
            'spacing' => [
                'padding' => true,
                'margin' => true
            ],
            'typography' => [
                'fontSize' => true,
                'lineHeight' => true
            ],
            'align' => true
        ],
        'fields' => [
            [
                'name' => 'icon',
                'label' => 'Icon (Dashicon)',
                'type' => 'short_text',
                'default_value' => 'star-filled'
            ],
            [
                'name' => 'title',
                'label' => 'Title',
                'type' => 'short_text',
                'validation' => ['required' => true]
            ],
            [
                'name' => 'description',
                'label' => 'Description',
                'type' => 'long_text'
            ],
            [
                'name' => 'link',
                'label' => 'Link URL',
                'type' => 'short_text'
            ]
        ],
        'render_callback' => 'render_card_block'
    ]);
}

function render_card_block($data, $content, $block) {
    $icon = $data['fields']['icon']['value'] ?? 'star-filled';
    $title = $data['fields']['title']['value'] ?? '';
    $description = $data['fields']['description']['value'] ?? '';
    $link = $data['fields']['link']['value'] ?? '';
    
    // Get theme.json colors if set
    $bg_color = $data['attributes']['backgroundColor'] ?? '';
    $text_color = $data['attributes']['textColor'] ?? '';
    
    ?>
    <div class="yap-card has-background-<?php echo esc_attr($bg_color); ?> has-text-<?php echo esc_attr($text_color); ?>">
        <span class="dashicons dashicons-<?php echo esc_attr($icon); ?> card-icon"></span>
        <h3 class="card-title"><?php echo esc_html($title); ?></h3>
        <?php if ($description): ?>
            <p class="card-description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
        <?php if ($link): ?>
            <a href="<?php echo esc_url($link); ?>" class="card-link">Learn More →</a>
        <?php endif; ?>
    </div>
    <?php
}

// ============================================
// 5. BLOCK PATTERN REGISTRATION
// ============================================

add_action('yap/register_block_patterns', 'register_yap_block_patterns');

function register_yap_block_patterns() {
    // Testimonials Grid Pattern
    yap_register_block_pattern('yap/testimonials-grid', [
        'title' => 'Testimonials Grid',
        'description' => 'Three testimonials in a row',
        'categories' => ['yap-patterns'],
        'content' => '
            <!-- wp:columns -->
            <div class="wp-block-columns">
                <!-- wp:column -->
                <div class="wp-block-column">
                    <!-- wp:yap/testimonial /-->
                </div>
                <!-- /wp:column -->
                
                <!-- wp:column -->
                <div class="wp-block-column">
                    <!-- wp:yap/testimonial /-->
                </div>
                <!-- /wp:column -->
                
                <!-- wp:column -->
                <div class="wp-block-column">
                    <!-- wp:yap/testimonial /-->
                </div>
                <!-- /wp:column -->
            </div>
            <!-- /wp:columns -->
        '
    ]);
    
    // Hero with Cards Pattern
    yap_register_block_pattern('yap/hero-with-cards', [
        'title' => 'Hero with Feature Cards',
        'description' => 'Hero section followed by three feature cards',
        'categories' => ['yap-patterns'],
        'content' => '
            <!-- wp:yap/hero {"align":"full"} /-->
            
            <!-- wp:columns -->
            <div class="wp-block-columns">
                <!-- wp:column -->
                <div class="wp-block-column">
                    <!-- wp:yap/card /-->
                </div>
                <!-- /wp:column -->
                
                <!-- wp:column -->
                <div class="wp-block-column">
                    <!-- wp:yap/card /-->
                </div>
                <!-- /wp:column -->
                
                <!-- wp:column -->
                <div class="wp-block-column">
                    <!-- wp:yap/card /-->
                </div>
                <!-- /wp:column -->
            </div>
            <!-- /wp:columns -->
        '
    ]);
}
