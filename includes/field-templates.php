<?php
/**
 * YAP Field Templates
 * Pre-configured field sets for rapid development
 * Plug & play templates for common use cases
 * 
 * @package YAP
 */

if (!defined('ABSPATH')) {
    exit;
}

class YAP_Field_Templates {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hook into admin if needed
    }
    
    /**
     * Get all templates organized by category
     */
    public function get_templates() {
        return [
            'e-commerce' => [
                'label' => 'E-Commerce',
                'templates' => [
                    'product_card' => $this->template_product_card(),
                    'pricing_table' => $this->template_pricing_table(),
                    'cart_item' => $this->template_cart_item(),
                    'product_review' => $this->template_product_review(),
                    'shipping_info' => $this->template_shipping_info(),
                ]
            ],
            'blog' => [
                'label' => 'Blog & Content',
                'templates' => [
                    'post_meta' => $this->template_post_meta(),
                    'author_bio' => $this->template_author_bio(),
                    'featured_post' => $this->template_featured_post(),
                    'article_seo' => $this->template_article_seo(),
                    'blog_settings' => $this->template_blog_settings(),
                ]
            ],
            'real-estate' => [
                'label' => 'Real Estate',
                'templates' => [
                    'property_listing' => $this->template_property_listing(),
                    'agent_card' => $this->template_agent_card(),
                    'property_features' => $this->template_property_features(),
                    'location_info' => $this->template_location_info(),
                ]
            ],
            'restaurant' => [
                'label' => 'Restaurant & Food',
                'templates' => [
                    'menu_item' => $this->template_menu_item(),
                    'reservation_form' => $this->template_reservation_form(),
                    'restaurant_info' => $this->template_restaurant_info(),
                    'chef_profile' => $this->template_chef_profile(),
                ]
            ],
            'portfolio' => [
                'label' => 'Portfolio',
                'templates' => [
                    'project_showcase' => $this->template_project_showcase(),
                    'testimonial' => $this->template_testimonial(),
                    'team_member' => $this->template_team_member(),
                    'skill_set' => $this->template_skill_set(),
                ]
            ],
            'events' => [
                'label' => 'Events & Booking',
                'templates' => [
                    'event_details' => $this->template_event_details(),
                    'ticket_info' => $this->template_ticket_info(),
                    'speaker_profile' => $this->template_speaker_profile(),
                    'venue_info' => $this->template_venue_info(),
                ]
            ],
            'business' => [
                'label' => 'Business',
                'templates' => [
                    'contact_form' => $this->template_contact_form(),
                    'service_card' => $this->template_service_card(),
                    'company_info' => $this->template_company_info(),
                    'faq_item' => $this->template_faq_item(),
                ]
            ]
        ];
    }
    
    // ====== E-COMMERCE TEMPLATES ======
    
    private function template_product_card() {
        return [
            'label' => 'Product Card',
            'description' => 'Complete product information with images, pricing, and variants',
            'fields' => [
                ['name' => 'product_name', 'type' => 'text', 'label' => 'Product Name', 'required' => true],
                ['name' => 'product_sku', 'type' => 'text', 'label' => 'SKU', 'required' => true],
                ['name' => 'product_description', 'type' => 'textarea', 'label' => 'Description'],
                ['name' => 'product_price', 'type' => 'number', 'label' => 'Price', 'required' => true],
                ['name' => 'sale_price', 'type' => 'number', 'label' => 'Sale Price'],
                ['name' => 'stock_quantity', 'type' => 'number', 'label' => 'Stock Quantity'],
                ['name' => 'product_image', 'type' => 'image', 'label' => 'Product Image'],
                ['name' => 'product_gallery', 'type' => 'gallery', 'label' => 'Image Gallery'],
                ['name' => 'product_category', 'type' => 'select', 'label' => 'Category'],
                ['name' => 'product_tags', 'type' => 'text', 'label' => 'Tags'],
            ]
        ];
    }
    
    private function template_pricing_table() {
        return [
            'label' => 'Pricing Table',
            'description' => 'Pricing plans with features and CTA',
            'fields' => [
                ['name' => 'plan_name', 'type' => 'text', 'label' => 'Plan Name', 'required' => true],
                ['name' => 'plan_price', 'type' => 'number', 'label' => 'Price', 'required' => true],
                ['name' => 'billing_period', 'type' => 'select', 'label' => 'Billing Period', 'options' => ['monthly', 'yearly']],
                ['name' => 'plan_features', 'type' => 'repeater', 'label' => 'Features'],
                ['name' => 'is_featured', 'type' => 'checkbox', 'label' => 'Featured Plan'],
                ['name' => 'button_text', 'type' => 'text', 'label' => 'Button Text', 'default_value' => 'Get Started'],
                ['name' => 'button_url', 'type' => 'url', 'label' => 'Button URL'],
            ]
        ];
    }
    
    private function template_cart_item() {
        return [
            'label' => 'Cart Item',
            'description' => 'Shopping cart line item details',
            'fields' => [
                ['name' => 'item_name', 'type' => 'text', 'label' => 'Item Name'],
                ['name' => 'item_sku', 'type' => 'text', 'label' => 'SKU'],
                ['name' => 'item_quantity', 'type' => 'number', 'label' => 'Quantity'],
                ['name' => 'item_price', 'type' => 'number', 'label' => 'Unit Price'],
                ['name' => 'item_total', 'type' => 'number', 'label' => 'Total Price'],
                ['name' => 'item_image', 'type' => 'image', 'label' => 'Thumbnail'],
            ]
        ];
    }
    
    private function template_product_review() {
        return [
            'label' => 'Product Review',
            'description' => 'Customer reviews and ratings',
            'fields' => [
                ['name' => 'reviewer_name', 'type' => 'text', 'label' => 'Reviewer Name', 'required' => true],
                ['name' => 'reviewer_email', 'type' => 'email', 'label' => 'Email'],
                ['name' => 'rating', 'type' => 'number', 'label' => 'Rating (1-5)', 'min' => 1, 'max' => 5],
                ['name' => 'review_title', 'type' => 'text', 'label' => 'Review Title'],
                ['name' => 'review_text', 'type' => 'textarea', 'label' => 'Review'],
                ['name' => 'verified_purchase', 'type' => 'checkbox', 'label' => 'Verified Purchase'],
                ['name' => 'review_date', 'type' => 'date', 'label' => 'Review Date'],
            ]
        ];
    }
    
    private function template_shipping_info() {
        return [
            'label' => 'Shipping Information',
            'description' => 'Delivery and shipping details',
            'fields' => [
                ['name' => 'shipping_method', 'type' => 'select', 'label' => 'Shipping Method'],
                ['name' => 'shipping_cost', 'type' => 'number', 'label' => 'Shipping Cost'],
                ['name' => 'estimated_delivery', 'type' => 'text', 'label' => 'Estimated Delivery'],
                ['name' => 'tracking_number', 'type' => 'text', 'label' => 'Tracking Number'],
                ['name' => 'carrier', 'type' => 'select', 'label' => 'Carrier'],
            ]
        ];
    }
    
    // ====== BLOG TEMPLATES ======
    
    private function template_post_meta() {
        return [
            'label' => 'Post Meta',
            'description' => 'Blog post metadata and settings',
            'fields' => [
                ['name' => 'reading_time', 'type' => 'number', 'label' => 'Reading Time (minutes)'],
                ['name' => 'post_views', 'type' => 'number', 'label' => 'View Count'],
                ['name' => 'featured_post', 'type' => 'checkbox', 'label' => 'Featured'],
                ['name' => 'post_subtitle', 'type' => 'text', 'label' => 'Subtitle'],
                ['name' => 'custom_excerpt', 'type' => 'textarea', 'label' => 'Custom Excerpt'],
            ]
        ];
    }
    
    private function template_author_bio() {
        return [
            'label' => 'Author Bio',
            'description' => 'Author profile and biography',
            'fields' => [
                ['name' => 'author_name', 'type' => 'text', 'label' => 'Author Name', 'required' => true],
                ['name' => 'author_title', 'type' => 'text', 'label' => 'Job Title'],
                ['name' => 'author_bio', 'type' => 'textarea', 'label' => 'Biography'],
                ['name' => 'author_avatar', 'type' => 'image', 'label' => 'Avatar'],
                ['name' => 'author_email', 'type' => 'email', 'label' => 'Email'],
                ['name' => 'author_website', 'type' => 'url', 'label' => 'Website'],
                ['name' => 'author_twitter', 'type' => 'url', 'label' => 'Twitter'],
                ['name' => 'author_linkedin', 'type' => 'url', 'label' => 'LinkedIn'],
            ]
        ];
    }
    
    private function template_featured_post() {
        return [
            'label' => 'Featured Post',
            'description' => 'Highlighted post with hero image',
            'fields' => [
                ['name' => 'hero_image', 'type' => 'image', 'label' => 'Hero Image', 'required' => true],
                ['name' => 'hero_title', 'type' => 'text', 'label' => 'Hero Title'],
                ['name' => 'hero_subtitle', 'type' => 'text', 'label' => 'Hero Subtitle'],
                ['name' => 'cta_text', 'type' => 'text', 'label' => 'CTA Button Text'],
                ['name' => 'cta_link', 'type' => 'url', 'label' => 'CTA Link'],
            ]
        ];
    }
    
    private function template_article_seo() {
        return [
            'label' => 'Article SEO',
            'description' => 'SEO optimization fields for articles',
            'fields' => [
                ['name' => 'seo_title', 'type' => 'text', 'label' => 'SEO Title', 'max_length' => 60],
                ['name' => 'meta_description', 'type' => 'textarea', 'label' => 'Meta Description', 'max_length' => 160],
                ['name' => 'focus_keyword', 'type' => 'text', 'label' => 'Focus Keyword'],
                ['name' => 'canonical_url', 'type' => 'url', 'label' => 'Canonical URL'],
                ['name' => 'og_image', 'type' => 'image', 'label' => 'Open Graph Image'],
                ['name' => 'robots_meta', 'type' => 'select', 'label' => 'Robots Meta', 'options' => ['index, follow', 'noindex, nofollow']],
            ]
        ];
    }
    
    private function template_blog_settings() {
        return [
            'label' => 'Blog Settings',
            'description' => 'Global blog configuration',
            'fields' => [
                ['name' => 'posts_per_page', 'type' => 'number', 'label' => 'Posts Per Page', 'default_value' => 10],
                ['name' => 'show_author', 'type' => 'checkbox', 'label' => 'Show Author'],
                ['name' => 'show_date', 'type' => 'checkbox', 'label' => 'Show Date'],
                ['name' => 'show_comments', 'type' => 'checkbox', 'label' => 'Enable Comments'],
                ['name' => 'sidebar_position', 'type' => 'select', 'label' => 'Sidebar Position', 'options' => ['left', 'right', 'none']],
            ]
        ];
    }
    
    // ====== REAL ESTATE TEMPLATES ======
    
    private function template_property_listing() {
        return [
            'label' => 'Property Listing',
            'description' => 'Real estate property details',
            'fields' => [
                ['name' => 'property_title', 'type' => 'text', 'label' => 'Property Title', 'required' => true],
                ['name' => 'property_type', 'type' => 'select', 'label' => 'Type', 'options' => ['House', 'Apartment', 'Condo', 'Land']],
                ['name' => 'property_price', 'type' => 'number', 'label' => 'Price', 'required' => true],
                ['name' => 'bedrooms', 'type' => 'number', 'label' => 'Bedrooms'],
                ['name' => 'bathrooms', 'type' => 'number', 'label' => 'Bathrooms'],
                ['name' => 'square_feet', 'type' => 'number', 'label' => 'Square Feet'],
                ['name' => 'property_address', 'type' => 'text', 'label' => 'Address'],
                ['name' => 'property_images', 'type' => 'gallery', 'label' => 'Property Images'],
                ['name' => 'property_description', 'type' => 'textarea', 'label' => 'Description'],
            ]
        ];
    }
    
    private function template_agent_card() {
        return [
            'label' => 'Real Estate Agent',
            'description' => 'Agent profile card',
            'fields' => [
                ['name' => 'agent_name', 'type' => 'text', 'label' => 'Agent Name', 'required' => true],
                ['name' => 'agent_photo', 'type' => 'image', 'label' => 'Photo'],
                ['name' => 'agent_title', 'type' => 'text', 'label' => 'Job Title'],
                ['name' => 'agent_phone', 'type' => 'tel', 'label' => 'Phone'],
                ['name' => 'agent_email', 'type' => 'email', 'label' => 'Email'],
                ['name' => 'license_number', 'type' => 'text', 'label' => 'License Number'],
                ['name' => 'agent_bio', 'type' => 'textarea', 'label' => 'Biography'],
            ]
        ];
    }
    
    private function template_property_features() {
        return [
            'label' => 'Property Features',
            'description' => 'Property amenities and features',
            'fields' => [
                ['name' => 'has_parking', 'type' => 'checkbox', 'label' => 'Parking'],
                ['name' => 'has_pool', 'type' => 'checkbox', 'label' => 'Swimming Pool'],
                ['name' => 'has_gym', 'type' => 'checkbox', 'label' => 'Gym'],
                ['name' => 'has_garden', 'type' => 'checkbox', 'label' => 'Garden'],
                ['name' => 'has_security', 'type' => 'checkbox', 'label' => 'Security'],
                ['name' => 'year_built', 'type' => 'number', 'label' => 'Year Built'],
                ['name' => 'heating_type', 'type' => 'select', 'label' => 'Heating'],
                ['name' => 'cooling_type', 'type' => 'select', 'label' => 'Cooling'],
            ]
        ];
    }
    
    private function template_location_info() {
        return [
            'label' => 'Location Info',
            'description' => 'Location and neighborhood details',
            'fields' => [
                ['name' => 'neighborhood', 'type' => 'text', 'label' => 'Neighborhood'],
                ['name' => 'city', 'type' => 'text', 'label' => 'City'],
                ['name' => 'state', 'type' => 'text', 'label' => 'State'],
                ['name' => 'zip_code', 'type' => 'text', 'label' => 'ZIP Code'],
                ['name' => 'latitude', 'type' => 'text', 'label' => 'Latitude'],
                ['name' => 'longitude', 'type' => 'text', 'label' => 'Longitude'],
                ['name' => 'nearby_schools', 'type' => 'textarea', 'label' => 'Nearby Schools'],
                ['name' => 'public_transport', 'type' => 'textarea', 'label' => 'Public Transport'],
            ]
        ];
    }
    
    // ====== RESTAURANT TEMPLATES ======
    
    private function template_menu_item() {
        return [
            'label' => 'Menu Item',
            'description' => 'Restaurant menu dish',
            'fields' => [
                ['name' => 'dish_name', 'type' => 'text', 'label' => 'Dish Name', 'required' => true],
                ['name' => 'dish_description', 'type' => 'textarea', 'label' => 'Description'],
                ['name' => 'dish_price', 'type' => 'number', 'label' => 'Price', 'required' => true],
                ['name' => 'dish_image', 'type' => 'image', 'label' => 'Dish Photo'],
                ['name' => 'category', 'type' => 'select', 'label' => 'Category', 'options' => ['Appetizer', 'Main Course', 'Dessert', 'Beverage']],
                ['name' => 'spice_level', 'type' => 'select', 'label' => 'Spice Level', 'options' => ['Mild', 'Medium', 'Hot']],
                ['name' => 'vegetarian', 'type' => 'checkbox', 'label' => 'Vegetarian'],
                ['name' => 'vegan', 'type' => 'checkbox', 'label' => 'Vegan'],
                ['name' => 'gluten_free', 'type' => 'checkbox', 'label' => 'Gluten-Free'],
            ]
        ];
    }
    
    private function template_reservation_form() {
        return [
            'label' => 'Reservation Form',
            'description' => 'Restaurant booking form fields',
            'fields' => [
                ['name' => 'guest_name', 'type' => 'text', 'label' => 'Name', 'required' => true],
                ['name' => 'guest_email', 'type' => 'email', 'label' => 'Email', 'required' => true],
                ['name' => 'guest_phone', 'type' => 'tel', 'label' => 'Phone', 'required' => true],
                ['name' => 'reservation_date', 'type' => 'date', 'label' => 'Date', 'required' => true],
                ['name' => 'reservation_time', 'type' => 'time', 'label' => 'Time', 'required' => true],
                ['name' => 'party_size', 'type' => 'number', 'label' => 'Number of Guests', 'required' => true],
                ['name' => 'special_requests', 'type' => 'textarea', 'label' => 'Special Requests'],
            ]
        ];
    }
    
    private function template_restaurant_info() {
        return [
            'label' => 'Restaurant Info',
            'description' => 'Restaurant details and hours',
            'fields' => [
                ['name' => 'restaurant_name', 'type' => 'text', 'label' => 'Restaurant Name'],
                ['name' => 'cuisine_type', 'type' => 'text', 'label' => 'Cuisine Type'],
                ['name' => 'phone_number', 'type' => 'tel', 'label' => 'Phone'],
                ['name' => 'email', 'type' => 'email', 'label' => 'Email'],
                ['name' => 'address', 'type' => 'textarea', 'label' => 'Address'],
                ['name' => 'opening_hours', 'type' => 'textarea', 'label' => 'Opening Hours'],
                ['name' => 'dress_code', 'type' => 'text', 'label' => 'Dress Code'],
            ]
        ];
    }
    
    private function template_chef_profile() {
        return [
            'label' => 'Chef Profile',
            'description' => 'Chef biography and credentials',
            'fields' => [
                ['name' => 'chef_name', 'type' => 'text', 'label' => 'Chef Name', 'required' => true],
                ['name' => 'chef_photo', 'type' => 'image', 'label' => 'Photo'],
                ['name' => 'chef_title', 'type' => 'text', 'label' => 'Title'],
                ['name' => 'chef_bio', 'type' => 'textarea', 'label' => 'Biography'],
                ['name' => 'specialties', 'type' => 'text', 'label' => 'Specialties'],
                ['name' => 'awards', 'type' => 'repeater', 'label' => 'Awards'],
            ]
        ];
    }
    
    // ====== PORTFOLIO TEMPLATES ======
    
    private function template_project_showcase() {
        return [
            'label' => 'Project Showcase',
            'description' => 'Portfolio project display',
            'fields' => [
                ['name' => 'project_title', 'type' => 'text', 'label' => 'Project Title', 'required' => true],
                ['name' => 'project_description', 'type' => 'textarea', 'label' => 'Description'],
                ['name' => 'project_images', 'type' => 'gallery', 'label' => 'Project Images'],
                ['name' => 'client_name', 'type' => 'text', 'label' => 'Client'],
                ['name' => 'project_url', 'type' => 'url', 'label' => 'Project URL'],
                ['name' => 'project_date', 'type' => 'date', 'label' => 'Completion Date'],
                ['name' => 'project_category', 'type' => 'select', 'label' => 'Category'],
                ['name' => 'technologies_used', 'type' => 'text', 'label' => 'Technologies'],
            ]
        ];
    }
    
    private function template_testimonial() {
        return [
            'label' => 'Testimonial',
            'description' => 'Customer testimonial or review',
            'fields' => [
                ['name' => 'client_name', 'type' => 'text', 'label' => 'Client Name', 'required' => true],
                ['name' => 'client_company', 'type' => 'text', 'label' => 'Company'],
                ['name' => 'client_position', 'type' => 'text', 'label' => 'Position'],
                ['name' => 'client_photo', 'type' => 'image', 'label' => 'Photo'],
                ['name' => 'testimonial_text', 'type' => 'textarea', 'label' => 'Testimonial', 'required' => true],
                ['name' => 'rating', 'type' => 'number', 'label' => 'Rating', 'min' => 1, 'max' => 5],
            ]
        ];
    }
    
    private function template_team_member() {
        return [
            'label' => 'Team Member',
            'description' => 'Team member profile',
            'fields' => [
                ['name' => 'member_name', 'type' => 'text', 'label' => 'Name', 'required' => true],
                ['name' => 'member_photo', 'type' => 'image', 'label' => 'Photo'],
                ['name' => 'job_title', 'type' => 'text', 'label' => 'Job Title'],
                ['name' => 'member_bio', 'type' => 'textarea', 'label' => 'Bio'],
                ['name' => 'member_email', 'type' => 'email', 'label' => 'Email'],
                ['name' => 'linkedin_url', 'type' => 'url', 'label' => 'LinkedIn'],
                ['name' => 'twitter_url', 'type' => 'url', 'label' => 'Twitter'],
            ]
        ];
    }
    
    private function template_skill_set() {
        return [
            'label' => 'Skill Set',
            'description' => 'Skills and proficiency levels',
            'fields' => [
                ['name' => 'skill_name', 'type' => 'text', 'label' => 'Skill Name', 'required' => true],
                ['name' => 'proficiency_level', 'type' => 'number', 'label' => 'Proficiency (%)', 'min' => 0, 'max' => 100],
                ['name' => 'skill_category', 'type' => 'select', 'label' => 'Category'],
                ['name' => 'years_experience', 'type' => 'number', 'label' => 'Years of Experience'],
            ]
        ];
    }
    
    // ====== EVENT TEMPLATES ======
    
    private function template_event_details() {
        return [
            'label' => 'Event Details',
            'description' => 'Event information and schedule',
            'fields' => [
                ['name' => 'event_title', 'type' => 'text', 'label' => 'Event Title', 'required' => true],
                ['name' => 'event_description', 'type' => 'textarea', 'label' => 'Description'],
                ['name' => 'event_date', 'type' => 'date', 'label' => 'Event Date', 'required' => true],
                ['name' => 'start_time', 'type' => 'time', 'label' => 'Start Time'],
                ['name' => 'end_time', 'type' => 'time', 'label' => 'End Time'],
                ['name' => 'event_image', 'type' => 'image', 'label' => 'Event Image'],
                ['name' => 'venue_name', 'type' => 'text', 'label' => 'Venue'],
                ['name' => 'max_attendees', 'type' => 'number', 'label' => 'Max Attendees'],
            ]
        ];
    }
    
    private function template_ticket_info() {
        return [
            'label' => 'Ticket Info',
            'description' => 'Event ticket details',
            'fields' => [
                ['name' => 'ticket_type', 'type' => 'text', 'label' => 'Ticket Type'],
                ['name' => 'ticket_price', 'type' => 'number', 'label' => 'Price'],
                ['name' => 'quantity_available', 'type' => 'number', 'label' => 'Quantity Available'],
                ['name' => 'early_bird_price', 'type' => 'number', 'label' => 'Early Bird Price'],
                ['name' => 'early_bird_deadline', 'type' => 'date', 'label' => 'Early Bird Deadline'],
            ]
        ];
    }
    
    private function template_speaker_profile() {
        return [
            'label' => 'Speaker Profile',
            'description' => 'Event speaker information',
            'fields' => [
                ['name' => 'speaker_name', 'type' => 'text', 'label' => 'Speaker Name', 'required' => true],
                ['name' => 'speaker_photo', 'type' => 'image', 'label' => 'Photo'],
                ['name' => 'speaker_title', 'type' => 'text', 'label' => 'Title'],
                ['name' => 'speaker_company', 'type' => 'text', 'label' => 'Company'],
                ['name' => 'speaker_bio', 'type' => 'textarea', 'label' => 'Biography'],
                ['name' => 'talk_title', 'type' => 'text', 'label' => 'Talk Title'],
                ['name' => 'talk_time', 'type' => 'time', 'label' => 'Talk Time'],
            ]
        ];
    }
    
    private function template_venue_info() {
        return [
            'label' => 'Venue Info',
            'description' => 'Event venue details',
            'fields' => [
                ['name' => 'venue_name', 'type' => 'text', 'label' => 'Venue Name'],
                ['name' => 'venue_address', 'type' => 'textarea', 'label' => 'Address'],
                ['name' => 'venue_capacity', 'type' => 'number', 'label' => 'Capacity'],
                ['name' => 'parking_available', 'type' => 'checkbox', 'label' => 'Parking Available'],
                ['name' => 'wheelchair_accessible', 'type' => 'checkbox', 'label' => 'Wheelchair Accessible'],
                ['name' => 'directions', 'type' => 'textarea', 'label' => 'Directions'],
            ]
        ];
    }
    
    // ====== BUSINESS TEMPLATES ======
    
    private function template_contact_form() {
        return [
            'label' => 'Contact Form',
            'description' => 'Business contact form fields',
            'fields' => [
                ['name' => 'contact_name', 'type' => 'text', 'label' => 'Name', 'required' => true],
                ['name' => 'contact_email', 'type' => 'email', 'label' => 'Email', 'required' => true],
                ['name' => 'contact_phone', 'type' => 'tel', 'label' => 'Phone'],
                ['name' => 'company_name', 'type' => 'text', 'label' => 'Company'],
                ['name' => 'subject', 'type' => 'text', 'label' => 'Subject', 'required' => true],
                ['name' => 'message', 'type' => 'textarea', 'label' => 'Message', 'required' => true],
            ]
        ];
    }
    
    private function template_service_card() {
        return [
            'label' => 'Service Card',
            'description' => 'Business service offering',
            'fields' => [
                ['name' => 'service_name', 'type' => 'text', 'label' => 'Service Name', 'required' => true],
                ['name' => 'service_icon', 'type' => 'image', 'label' => 'Icon'],
                ['name' => 'service_description', 'type' => 'textarea', 'label' => 'Description'],
                ['name' => 'service_price', 'type' => 'number', 'label' => 'Starting Price'],
                ['name' => 'service_features', 'type' => 'repeater', 'label' => 'Features'],
            ]
        ];
    }
    
    private function template_company_info() {
        return [
            'label' => 'Company Info',
            'description' => 'Company details and contact',
            'fields' => [
                ['name' => 'company_name', 'type' => 'text', 'label' => 'Company Name'],
                ['name' => 'company_logo', 'type' => 'image', 'label' => 'Logo'],
                ['name' => 'company_description', 'type' => 'textarea', 'label' => 'Description'],
                ['name' => 'phone', 'type' => 'tel', 'label' => 'Phone'],
                ['name' => 'email', 'type' => 'email', 'label' => 'Email'],
                ['name' => 'address', 'type' => 'textarea', 'label' => 'Address'],
                ['name' => 'working_hours', 'type' => 'textarea', 'label' => 'Working Hours'],
            ]
        ];
    }
    
    private function template_faq_item() {
        return [
            'label' => 'FAQ Item',
            'description' => 'Frequently asked question',
            'fields' => [
                ['name' => 'question', 'type' => 'text', 'label' => 'Question', 'required' => true],
                ['name' => 'answer', 'type' => 'textarea', 'label' => 'Answer', 'required' => true],
                ['name' => 'category', 'type' => 'select', 'label' => 'Category'],
                ['name' => 'display_order', 'type' => 'number', 'label' => 'Display Order'],
            ]
        ];
    }
}

YAP_Field_Templates::get_instance();
