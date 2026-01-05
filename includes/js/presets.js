/**
 * Field Presets Library
 * 
 * Ready-to-use field configurations for common patterns:
 * - Address (Country/City/Code/Street)
 * - CTA Button (Label/URL/Target/Style)
 * - SEO (Title/Description/NoIndex/Canonical)
 * - Product (Price/Currency/Gallery)
 * - Author (Name/Email/Bio/Avatar)
 * - Video (URL/Poster/Width/Height)
 * - Rating (Stars/Count/Average)
 * - FAQ (Question/Answer/Category)
 * 
 * @package YetAnotherPlugin
 * @since 2.0.0
 */

window.FieldPresets = window.FieldPresets || {};

/**
 * ============================================
 * ADDRESS PRESET - Complete address fields
 * ============================================
 */
FieldPresets.address = {
    name: 'address',
    label: 'Address',
    description: 'Full address with country, city, code, and street',
    icon: '📍',
    category: 'contact',
    fields: [
        {
            type: 'select',
            label: 'Country',
            name: 'country',
            required: true,
            options: [
                { label: 'United States', value: 'us' },
                { label: 'United Kingdom', value: 'uk' },
                { label: 'Canada', value: 'ca' },
                { label: 'Australia', value: 'au' },
                { label: 'Germany', value: 'de' },
                { label: 'France', value: 'fr' },
                { label: 'Spain', value: 'es' },
                { label: 'Italy', value: 'it' },
                { label: 'Poland', value: 'pl' },
                { label: 'Other', value: 'other' }
            ],
            conditional_logic: []
        },
        {
            type: 'text',
            label: 'City',
            name: 'city',
            required: true,
            placeholder: 'e.g., New York',
            validation: { type: 'text', min_length: 2 }
        },
        {
            type: 'text',
            label: 'Postal Code',
            name: 'postal_code',
            required: true,
            placeholder: 'e.g., 10001',
            validation: { type: 'text', pattern: '^[0-9\\-]+$' }
        },
        {
            type: 'text',
            label: 'Street',
            name: 'street',
            required: true,
            placeholder: 'e.g., 123 Main Street',
            validation: { type: 'text', min_length: 5 }
        }
    ]
};

/**
 * ============================================
 * CTA BUTTON PRESET - Call-to-action button
 * ============================================
 */
FieldPresets.ctaButton = {
    name: 'cta_button',
    label: 'CTA Button',
    description: 'Call-to-action button with label, URL, target, and style options',
    icon: '🔘',
    category: 'interactive',
    fields: [
        {
            type: 'text',
            label: 'Button Label',
            name: 'button_label',
            required: true,
            placeholder: 'e.g., Click Here',
            help_text: 'Text displayed on the button',
            validation: { type: 'text', max_length: 50 }
        },
        {
            type: 'text',
            label: 'Button URL',
            name: 'button_url',
            required: true,
            placeholder: 'https://example.com',
            help_text: 'Where the button links to',
            validation: { type: 'url' }
        },
        {
            type: 'select',
            label: 'Open Target',
            name: 'button_target',
            options: [
                { label: 'Same Window', value: '_self' },
                { label: 'New Tab', value: '_blank' },
                { label: 'New Window', value: '_blank' }
            ]
        },
        {
            type: 'select',
            label: 'Button Style',
            name: 'button_style',
            options: [
                { label: 'Primary', value: 'primary' },
                { label: 'Secondary', value: 'secondary' },
                { label: 'Danger', value: 'danger' },
                { label: 'Success', value: 'success' },
                { label: 'Outline', value: 'outline' }
            ]
        },
        {
            type: 'text',
            label: 'Button Class',
            name: 'button_class',
            placeholder: 'e.g., btn-large',
            help_text: 'Custom CSS classes'
        }
    ]
};

/**
 * ============================================
 * SEO PRESET - Search engine optimization
 * ============================================
 */
FieldPresets.seo = {
    name: 'seo',
    label: 'SEO',
    description: 'SEO meta tags including title, description, noindex, and canonical',
    icon: '🔍',
    category: 'meta',
    fields: [
        {
            type: 'text',
            label: 'Meta Title',
            name: 'meta_title',
            placeholder: 'Page title for search engines',
            help_text: 'Recommended: 50-60 characters',
            validation: { type: 'text', max_length: 60 }
        },
        {
            type: 'text',
            label: 'Meta Description',
            name: 'meta_description',
            placeholder: 'Page description for search engines',
            help_text: 'Recommended: 150-160 characters',
            validation: { type: 'text', max_length: 160 }
        },
        {
            type: 'checkbox',
            label: 'Noindex',
            name: 'noindex',
            help_text: 'Check to prevent search engines from indexing this page'
        },
        {
            type: 'text',
            label: 'Canonical URL',
            name: 'canonical_url',
            placeholder: 'https://example.com/page',
            help_text: 'Preferred URL version for this page',
            validation: { type: 'url' }
        },
        {
            type: 'text',
            label: 'Focus Keyword',
            name: 'focus_keyword',
            placeholder: 'e.g., best pizza in NYC',
            help_text: 'Primary keyword for this page'
        }
    ]
};

/**
 * ============================================
 * PRODUCT PRESET - E-commerce product
 * ============================================
 */
FieldPresets.product = {
    name: 'product',
    label: 'Product',
    description: 'Product information including price, currency, and image gallery',
    icon: '📦',
    category: 'ecommerce',
    fields: [
        {
            type: 'number',
            label: 'Price',
            name: 'price',
            required: true,
            placeholder: '0.00',
            validation: { type: 'number', min: 0 }
        },
        {
            type: 'select',
            label: 'Currency',
            name: 'currency',
            options: [
                { label: 'USD ($)', value: 'usd' },
                { label: 'EUR (€)', value: 'eur' },
                { label: 'GBP (£)', value: 'gbp' },
                { label: 'JPY (¥)', value: 'jpy' },
                { label: 'PLN (zł)', value: 'pln' },
                { label: 'Other', value: 'other' }
            ]
        },
        {
            type: 'number',
            label: 'Stock Quantity',
            name: 'stock_quantity',
            placeholder: '0',
            validation: { type: 'number', min: 0 }
        },
        {
            type: 'repeater',
            label: 'Product Gallery',
            name: 'product_gallery',
            fields: [
                {
                    type: 'text',
                    label: 'Image URL',
                    name: 'image_url',
                    validation: { type: 'url' }
                },
                {
                    type: 'text',
                    label: 'Image Alt Text',
                    name: 'image_alt'
                }
            ]
        },
        {
            type: 'select',
            label: 'Tax Class',
            name: 'tax_class',
            options: [
                { label: 'Standard', value: 'standard' },
                { label: 'Reduced', value: 'reduced' },
                { label: 'Zero', value: 'zero' },
                { label: 'Exempt', value: 'exempt' }
            ]
        }
    ]
};

/**
 * ============================================
 * AUTHOR PRESET - Author information
 * ============================================
 */
FieldPresets.author = {
    name: 'author',
    label: 'Author',
    description: 'Author information including name, email, bio, and avatar',
    icon: '👤',
    category: 'content',
    fields: [
        {
            type: 'text',
            label: 'Author Name',
            name: 'author_name',
            required: true,
            placeholder: 'e.g., John Doe',
            validation: { type: 'text', min_length: 2 }
        },
        {
            type: 'text',
            label: 'Author Email',
            name: 'author_email',
            placeholder: 'author@example.com',
            validation: { type: 'email' }
        },
        {
            type: 'text',
            label: 'Author Bio',
            name: 'author_bio',
            placeholder: 'Short biography of the author',
            help_text: 'Brief description of the author'
        },
        {
            type: 'text',
            label: 'Avatar URL',
            name: 'avatar_url',
            placeholder: 'https://example.com/avatar.jpg',
            validation: { type: 'url' }
        },
        {
            type: 'text',
            label: 'Author Website',
            name: 'author_website',
            placeholder: 'https://author-website.com',
            validation: { type: 'url' }
        }
    ]
};

/**
 * ============================================
 * VIDEO PRESET - Video embedding
 * ============================================
 */
FieldPresets.video = {
    name: 'video',
    label: 'Video',
    description: 'Video player with URL, poster, width, and height settings',
    icon: '🎬',
    category: 'media',
    fields: [
        {
            type: 'text',
            label: 'Video URL',
            name: 'video_url',
            required: true,
            placeholder: 'https://example.com/video.mp4',
            validation: { type: 'url' }
        },
        {
            type: 'text',
            label: 'Poster Image',
            name: 'poster_image',
            placeholder: 'https://example.com/poster.jpg',
            help_text: 'Image shown before video plays',
            validation: { type: 'url' }
        },
        {
            type: 'number',
            label: 'Video Width (px)',
            name: 'video_width',
            placeholder: '640',
            validation: { type: 'number', min: 100 }
        },
        {
            type: 'number',
            label: 'Video Height (px)',
            name: 'video_height',
            placeholder: '360',
            validation: { type: 'number', min: 100 }
        },
        {
            type: 'checkbox',
            label: 'Autoplay',
            name: 'autoplay'
        },
        {
            type: 'checkbox',
            label: 'Loop',
            name: 'loop'
        }
    ]
};

/**
 * ============================================
 * RATING PRESET - Star rating system
 * ============================================
 */
FieldPresets.rating = {
    name: 'rating',
    label: 'Rating',
    description: 'Star rating with count and average score',
    icon: '⭐',
    category: 'review',
    fields: [
        {
            type: 'number',
            label: 'Star Rating (1-5)',
            name: 'star_rating',
            required: true,
            placeholder: '4.5',
            validation: { type: 'number', min: 1, max: 5 }
        },
        {
            type: 'number',
            label: 'Number of Ratings',
            name: 'rating_count',
            placeholder: '0',
            validation: { type: 'number', min: 0 }
        },
        {
            type: 'number',
            label: 'Average Score',
            name: 'average_score',
            placeholder: '4.5',
            validation: { type: 'number', min: 1, max: 5 }
        }
    ]
};

/**
 * ============================================
 * FAQ PRESET - Frequently Asked Questions
 * ============================================
 */
FieldPresets.faq = {
    name: 'faq',
    label: 'FAQ',
    description: 'FAQ repeater with question, answer, and category',
    icon: '❓',
    category: 'content',
    fields: [
        {
            type: 'repeater',
            label: 'FAQ Items',
            name: 'faq_items',
            required: true,
            fields: [
                {
                    type: 'text',
                    label: 'Question',
                    name: 'question',
                    required: true,
                    placeholder: 'Ask a question...',
                    validation: { type: 'text', min_length: 5 }
                },
                {
                    type: 'text',
                    label: 'Answer',
                    name: 'answer',
                    required: true,
                    placeholder: 'Provide the answer...',
                    validation: { type: 'text', min_length: 10 }
                },
                {
                    type: 'select',
                    label: 'Category',
                    name: 'category',
                    options: [
                        { label: 'General', value: 'general' },
                        { label: 'Product', value: 'product' },
                        { label: 'Shipping', value: 'shipping' },
                        { label: 'Returns', value: 'returns' },
                        { label: 'Support', value: 'support' }
                    ]
                }
            ]
        }
    ]
};

/**
 * ============================================
 * SOCIAL PRESET - Social media links
 * ============================================
 */
FieldPresets.social = {
    name: 'social',
    label: 'Social Links',
    description: 'Social media profile links',
    icon: '🔗',
    category: 'contact',
    fields: [
        {
            type: 'text',
            label: 'Facebook URL',
            name: 'facebook_url',
            placeholder: 'https://facebook.com/...',
            validation: { type: 'url' }
        },
        {
            type: 'text',
            label: 'Twitter URL',
            name: 'twitter_url',
            placeholder: 'https://twitter.com/...',
            validation: { type: 'url' }
        },
        {
            type: 'text',
            label: 'Instagram URL',
            name: 'instagram_url',
            placeholder: 'https://instagram.com/...',
            validation: { type: 'url' }
        },
        {
            type: 'text',
            label: 'LinkedIn URL',
            name: 'linkedin_url',
            placeholder: 'https://linkedin.com/...',
            validation: { type: 'url' }
        },
        {
            type: 'text',
            label: 'YouTube URL',
            name: 'youtube_url',
            placeholder: 'https://youtube.com/...',
            validation: { type: 'url' }
        }
    ]
};

/**
 * ============================================
 * FORM PRESET - Contact form
 * ============================================
 */
FieldPresets.form = {
    name: 'form',
    label: 'Contact Form',
    description: 'Basic contact form with name, email, message',
    icon: '📝',
    category: 'interactive',
    fields: [
        {
            type: 'text',
            label: 'Full Name',
            name: 'full_name',
            required: true,
            placeholder: 'Your name',
            validation: { type: 'text', min_length: 2 }
        },
        {
            type: 'text',
            label: 'Email Address',
            name: 'email_address',
            required: true,
            placeholder: 'your@email.com',
            validation: { type: 'email' }
        },
        {
            type: 'text',
            label: 'Subject',
            name: 'subject',
            required: true,
            placeholder: 'Message subject',
            validation: { type: 'text', min_length: 5 }
        },
        {
            type: 'text',
            label: 'Message',
            name: 'message',
            required: true,
            placeholder: 'Your message here...',
            help_text: 'Please provide details',
            validation: { type: 'text', min_length: 10 }
        }
    ]
};

/**
 * ============================================
 * TEASER PRESET - Content teaser/card
 * ============================================
 */
FieldPresets.teaser = {
    name: 'teaser',
    label: 'Content Teaser',
    description: 'Content preview card with title, description, image, and link',
    icon: '🎯',
    category: 'content',
    fields: [
        {
            type: 'text',
            label: 'Title',
            name: 'teaser_title',
            required: true,
            placeholder: 'Teaser title',
            validation: { type: 'text', max_length: 100 }
        },
        {
            type: 'text',
            label: 'Description',
            name: 'teaser_description',
            placeholder: 'Brief description',
            validation: { type: 'text', max_length: 500 }
        },
        {
            type: 'text',
            label: 'Image URL',
            name: 'teaser_image',
            placeholder: 'https://example.com/image.jpg',
            validation: { type: 'url' }
        },
        {
            type: 'text',
            label: 'Link URL',
            name: 'teaser_link',
            placeholder: 'https://example.com',
            validation: { type: 'url' }
        },
        {
            type: 'text',
            label: 'Link Text',
            name: 'teaser_link_text',
            placeholder: 'Read More'
        }
    ]
};

/**
 * ============================================
 * PRESET MANAGER METHODS
 * ============================================
 */

/**
 * Get all available presets
 */
FieldPresets.getAll = function() {
    return {
        address: this.address,
        ctaButton: this.ctaButton,
        seo: this.seo,
        product: this.product,
        author: this.author,
        video: this.video,
        rating: this.rating,
        faq: this.faq,
        social: this.social,
        form: this.form,
        teaser: this.teaser
    };
};

/**
 * Get preset by name
 */
FieldPresets.getPreset = function(presetName) {
    const presets = this.getAll();
    return presets[presetName] || null;
};

/**
 * Get presets by category
 */
FieldPresets.getByCategory = function(category) {
    const all = this.getAll();
    return Object.values(all).filter(p => p.category === category);
};

/**
 * Get all categories
 */
FieldPresets.getCategories = function() {
    const all = this.getAll();
    const categories = new Set();
    Object.values(all).forEach(p => categories.add(p.category));
    return Array.from(categories).sort();
};

/**
 * Add preset to schema
 */
FieldPresets.addToSchema = function(presetName, position = 'end') {
    console.log('🎯 FieldPresets.addToSchema called:', presetName);
    
    const preset = this.getPreset(presetName);
    if (!preset) {
        console.error('❌ Preset not found:', presetName);
        return { success: false, error: 'Preset not found' };
    }
    console.log('✅ Preset found:', preset);

    if (!window.yapBuilder || !window.yapBuilder.schema) {
        console.error('❌ Schema not initialized');
        return { success: false, error: 'Schema not initialized' };
    }
    console.log('✅ Schema available, current fields:', window.yapBuilder.schema.fields.length);

    // Create group field for preset using sub_fields (compatible with renderField)
    const groupField = {
        id: FieldStabilization.generateShortId('fld_'),
        key: FieldStabilization.generateShortId('fld_'),
        name: preset.name,
        label: preset.label,
        type: 'group',
        _created_at: Date.now(),
        _updated_at: Date.now(),
        _locked_key: false,
        sub_fields: preset.fields.map(f => ({
            ...f,
            id: FieldStabilization.generateShortId('fld_'),
            key: FieldStabilization.generateShortId('fld_')
        }))
    };
    console.log('✅ Group field created:', groupField);

    // Add to schema
    if (position === 'end') {
        window.yapBuilder.schema.fields.push(groupField);
    } else if (position === 'start') {
        window.yapBuilder.schema.fields.unshift(groupField);
    }
    console.log('✅ Field added to schema, total now:', window.yapBuilder.schema.fields.length);

    // Record in history
    if (typeof FieldHistory !== 'undefined' && FieldHistory.recordAdd) {
        FieldHistory.recordAdd(groupField);
        console.log('✅ Added to history');
    }

    // Refresh canvas to show new preset
    console.log('🔄 YAPBuilder available?', typeof YAPBuilder !== 'undefined');
    console.log('🔄 refreshCanvas method available?', YAPBuilder && typeof YAPBuilder.refreshCanvas === 'function');
    
    if (typeof YAPBuilder !== 'undefined' && YAPBuilder.refreshCanvas) {
        console.log('🔄 Calling YAPBuilder.refreshCanvas()');
        YAPBuilder.refreshCanvas();
        console.log('✅ Canvas refreshed');
    } else {
        console.warn('⚠️ YAPBuilder.refreshCanvas not available, field added but canvas not refreshed');
    }

    return {
        success: true,
        field: groupField,
        preset: presetName,
        fieldCount: preset.fields.length
    };
};

/**
 * Render preset selector HTML
 */
FieldPresets.renderSelector = function() {
    const categories = this.getCategories();
    const all = this.getAll();

    let html = '<div class="preset-selector">';
    html += '<div class="preset-tabs">';

    // Tabs
    categories.forEach((cat, idx) => {
        const active = idx === 0 ? 'active' : '';
        html += `<button class="preset-tab ${active}" data-category="${cat}">${cat}</button>`;
    });

    html += '</div>';
    html += '<div class="preset-list">';

    // Preset buttons
    categories.forEach((cat, idx) => {
        const show = idx === 0 ? 'show' : '';
        html += `<div class="preset-group ${show}" data-category="${cat}">`;

        const presetsInCat = this.getByCategory(cat);
        presetsInCat.forEach(p => {
            html += `
                <button class="preset-button" data-preset="${p.name}" title="${p.description}">
                    <span class="preset-icon">${p.icon}</span>
                    <span class="preset-name">${p.label}</span>
                </button>
            `;
        });

        html += '</div>';
    });

    html += '</div>';
    html += '</div>';

    return html;
};

/**
 * Handle preset selection
 */
FieldPresets.handlePresetClick = function(presetName) {
    const result = this.addToSchema(presetName);
    if (result.success) {
        console.log('✅ Preset added:', presetName);
        return result;
    } else {
        console.error('❌ Failed to add preset:', result.error);
        return result;
    }
};

console.log('%c✅ Field Presets Library loaded', 'color: #0f0; font-weight: bold;');
console.log('Available presets:', FieldPresets.getAll());
console.log('Use: FieldPresets.addToSchema("address")');
