/**
 * Test Logger Utility
 * Centralized logging for all tests
 * 
 * @since 1.5.0
 */

const TestLogger = {
    results: [],
    
    /**
     * Log message with type
     */
    log(message, type = 'info') {
        const icons = {
            'pass': '✅',
            'fail': '❌',
            'test': '🧪',
            'info': 'ℹ️',
            'debug': '🔍',
            'warn': '⚠️'
        };
        
        const icon = icons[type] || type;
        const timestamp = new Date().toLocaleTimeString();
        
        console.log(`${icon} [${timestamp}] ${message}`);
        
        this.results.push({
            message,
            type,
            timestamp
        });
    },
    
    /**
     * Log group start
     */
    group(title) {
        console.group(`🧪 ${title}`);
        this.log(`Starting: ${title}`, 'test');
    },
    
    /**
     * Log group end
     */
    groupEnd() {
        console.groupEnd();
    },
    
    /**
     * Clear results
     */
    clear() {
        this.results = [];
        console.clear();
    },
    
    /**
     * Get all results
     */
    getResults() {
        return this.results;
    },
    
    /**
     * Get results by type
     */
    getResultsByType(type) {
        return this.results.filter(r => r.type === type);
    }
};

// Export
window.TestLogger = TestLogger;
