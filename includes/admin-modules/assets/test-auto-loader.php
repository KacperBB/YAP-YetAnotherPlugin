<?php
/**
 * Test Auto Loader
 * Automatycznie ładuje testy w Visual Builder
 * 
 * Usage: Dodaj do enqueue lub do visual-builder.php
 * 
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Ładuj testy w Visual Builder
 */
add_action( 'wp_footer', function() {
    // Tylko w Visual Builder
    if ( ! isset( $_GET['page'] ) || 'yap-visual-builder' !== $_GET['page'] ) {
        return;
    }
    
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    
    // URL do folderu testów
    $plugin_url = plugin_dir_url( dirname( __FILE__ ) );
    $test_base  = $plugin_url . 'js/tests/';
    
    // Poprawiamy ścieżkę dla XAMPP/fagpress instalacji
    $test_base = str_replace( 
        site_url() . '/wp-content', 
        '/fagpress/wp-content', 
        $test_base 
    );
    
    // Debug info
    echo "<!-- YAP Test Auto Loader -->\n";
    echo "<!-- Base URL: {$test_base} -->\n";
    
    // Skrypt autoloadera
    ?>
    <script>
    (function() {
        'use strict';
        
        if ( typeof window.YAPAutoLoaded === 'undefined' ) {
            window.YAPAutoLoaded = true;
            
            const testBase = '<?php echo esc_js( $test_base ); ?>';
            const tests = ['test-config.js', 'visual-builder-field-editing.test.js', 'visual-builder-advanced.test.js'];
            
            console.log('%c📦 YAP Auto Loading Tests...', 'color: #0073aa; font-weight: bold;');
            
            let loaded = 0;
            tests.forEach((file, i) => {
                setTimeout(() => {
                    const script = document.createElement('script');
                    script.src = testBase + file;
                    script.async = false;
                    
                    script.onload = () => {
                        console.log('✅', file);
                        if (++loaded === tests.length) {
                            console.log('%c✨ All tests loaded!', 'color: #46b450; font-weight: bold;');
                            if (typeof window.YAPBuilderTests !== 'undefined') {
                                console.log('Ready to test: YAPBuilderTests.runAll()');
                            }
                        }
                    };
                    
                    script.onerror = () => console.error('❌', file, '404');
                    
                    document.head.appendChild(script);
                }, i * 200);
            });
        }
    })();
    </script>
    <?php
}, 999 );
