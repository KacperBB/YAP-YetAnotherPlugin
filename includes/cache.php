<?php
/**
 * YAP Cache System
 * Multi-layer caching: Memory > Object Cache (Redis/Memcached) > File Cache
 */

class YAP_Cache {
    private static $instance = null;
    private $memory_cache = [];
    private $cache_enabled = true;
    private $cache_ttl = 3600; // 1 hour default
    private $cache_driver = 'auto'; // auto, redis, memcached, file, memory
    private $file_cache_dir = '';
    private $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0
    ];

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->cache_enabled = apply_filters('yap_cache_enabled', true);
        $this->cache_ttl = apply_filters('yap_cache_ttl', 3600);
        $this->cache_driver = apply_filters('yap_cache_driver', 'auto');
        
        // Set up file cache directory
        $upload_dir = wp_upload_dir();
        $this->file_cache_dir = $upload_dir['basedir'] . '/yap-cache/';
        
        if (!file_exists($this->file_cache_dir)) {
            wp_mkdir_p($this->file_cache_dir);
        }

        // Auto-detect best cache driver
        if ($this->cache_driver === 'auto') {
            $this->cache_driver = $this->detect_cache_driver();
        }

        error_log("✅ YAP Cache initialized with driver: {$this->cache_driver}");
    }

    /**
     * Detect best available cache driver
     */
    private function detect_cache_driver() {
        // Check for Redis
        if (class_exists('Redis') && defined('WP_REDIS_HOST')) {
            return 'redis';
        }

        // Check for Memcached
        if (class_exists('Memcached') && function_exists('wp_cache_add')) {
            return 'memcached';
        }

        // Check for WordPress Object Cache
        if (wp_using_ext_object_cache()) {
            return 'object';
        }

        // Fall back to file cache
        return 'file';
    }

    /**
     * Get cached value
     * 
     * @param string $key Cache key
     * @param string $group Cache group
     * @return mixed|false Cached value or false
     */
    public function get($key, $group = 'yap') {
        if (!$this->cache_enabled) {
            return false;
        }

        $cache_key = $this->build_key($key, $group);

        // Level 1: Memory cache (fastest)
        if (isset($this->memory_cache[$cache_key])) {
            $this->stats['hits']++;
            error_log("🟢 YAP Cache HIT (memory): {$cache_key}");
            return $this->memory_cache[$cache_key];
        }

        // Level 2: Persistent cache (Redis/Memcached/Object Cache)
        $value = false;
        switch ($this->cache_driver) {
            case 'redis':
                $value = $this->get_from_redis($cache_key);
                break;
            case 'memcached':
            case 'object':
                $value = wp_cache_get($cache_key, $group);
                break;
            case 'file':
                $value = $this->get_from_file($cache_key);
                break;
        }

        if ($value !== false) {
            // Store in memory cache for subsequent calls
            $this->memory_cache[$cache_key] = $value;
            $this->stats['hits']++;
            error_log("🟢 YAP Cache HIT ({$this->cache_driver}): {$cache_key}");
            return $value;
        }

        $this->stats['misses']++;
        error_log("🔴 YAP Cache MISS: {$cache_key}");
        return false;
    }

    /**
     * Set cached value
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param string $group Cache group
     * @param int $ttl Time to live in seconds
     * @return bool Success
     */
    public function set($key, $value, $group = 'yap', $ttl = null) {
        if (!$this->cache_enabled) {
            return false;
        }

        $cache_key = $this->build_key($key, $group);
        $ttl = $ttl ?? $this->cache_ttl;

        // Level 1: Memory cache
        $this->memory_cache[$cache_key] = $value;

        // Level 2: Persistent cache
        $success = false;
        switch ($this->cache_driver) {
            case 'redis':
                $success = $this->set_to_redis($cache_key, $value, $ttl);
                break;
            case 'memcached':
            case 'object':
                $success = wp_cache_set($cache_key, $value, $group, $ttl);
                break;
            case 'file':
                $success = $this->set_to_file($cache_key, $value, $ttl);
                break;
        }

        if ($success) {
            $this->stats['sets']++;
            error_log("✅ YAP Cache SET ({$this->cache_driver}): {$cache_key}");
        }

        return $success;
    }

    /**
     * Delete cached value
     * 
     * @param string $key Cache key
     * @param string $group Cache group
     * @return bool Success
     */
    public function delete($key, $group = 'yap') {
        $cache_key = $this->build_key($key, $group);

        // Delete from memory
        unset($this->memory_cache[$cache_key]);

        // Delete from persistent cache
        switch ($this->cache_driver) {
            case 'redis':
                return $this->delete_from_redis($cache_key);
            case 'memcached':
            case 'object':
                return wp_cache_delete($cache_key, $group);
            case 'file':
                return $this->delete_from_file($cache_key);
        }

        return false;
    }

    /**
     * Flush all cache
     * 
     * @param string $group Optional group to flush
     * @return bool Success
     */
    public function flush($group = null) {
        // Flush memory cache
        if ($group) {
            foreach (array_keys($this->memory_cache) as $key) {
                if (strpos($key, $group . ':') === 0) {
                    unset($this->memory_cache[$key]);
                }
            }
        } else {
            $this->memory_cache = [];
        }

        // Flush persistent cache
        switch ($this->cache_driver) {
            case 'redis':
                return $this->flush_redis($group);
            case 'memcached':
            case 'object':
                return wp_cache_flush();
            case 'file':
                return $this->flush_file_cache($group);
        }

        error_log("✅ YAP Cache flushed" . ($group ? " (group: {$group})" : ""));
        return true;
    }

    /**
     * Build cache key
     */
    private function build_key($key, $group) {
        return $group . ':' . md5($key);
    }

    /**
     * Redis operations
     */
    private function get_from_redis($key) {
        try {
            if (!class_exists('Redis')) {
                return false;
            }
            $redis = new Redis();
            $redis->connect(WP_REDIS_HOST ?? '127.0.0.1', WP_REDIS_PORT ?? 6379);
            $value = $redis->get('yap:' . $key);
            $redis->close();
            return $value !== false ? unserialize($value) : false;
        } catch (Exception $e) {
            error_log("YAP Cache Redis error: " . $e->getMessage());
            return false;
        }
    }

    private function set_to_redis($key, $value, $ttl) {
        try {
            if (!class_exists('Redis')) {
                return false;
            }
            $redis = new Redis();
            $redis->connect(WP_REDIS_HOST ?? '127.0.0.1', WP_REDIS_PORT ?? 6379);
            $result = $redis->setex('yap:' . $key, $ttl, serialize($value));
            $redis->close();
            return $result;
        } catch (Exception $e) {
            error_log("YAP Cache Redis error: " . $e->getMessage());
            return false;
        }
    }

    private function delete_from_redis($key) {
        try {
            if (!class_exists('Redis')) {
                return false;
            }
            $redis = new Redis();
            $redis->connect(WP_REDIS_HOST ?? '127.0.0.1', WP_REDIS_PORT ?? 6379);
            $result = $redis->del('yap:' . $key);
            $redis->close();
            return $result;
        } catch (Exception $e) {
            error_log("YAP Cache Redis error: " . $e->getMessage());
            return false;
        }
    }

    private function flush_redis($group) {
        try {
            if (!class_exists('Redis')) {
                return false;
            }
            $redis = new Redis();
            $redis->connect(WP_REDIS_HOST ?? '127.0.0.1', WP_REDIS_PORT ?? 6379);
            
            if ($group) {
                $keys = $redis->keys('yap:' . $group . ':*');
                if (!empty($keys)) {
                    $redis->del($keys);
                }
            } else {
                $redis->flushDB();
            }
            
            $redis->close();
            return true;
        } catch (Exception $e) {
            error_log("YAP Cache Redis error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * File cache operations
     */
    private function get_from_file($key) {
        $file = $this->file_cache_dir . $key . '.cache';
        
        if (!file_exists($file)) {
            return false;
        }

        $data = file_get_contents($file);
        if ($data === false) {
            return false;
        }

        $cache = unserialize($data);
        if (!$cache || !isset($cache['expires']) || $cache['expires'] < time()) {
            @unlink($file);
            return false;
        }

        return $cache['value'];
    }

    private function set_to_file($key, $value, $ttl) {
        $file = $this->file_cache_dir . $key . '.cache';
        $cache = [
            'value' => $value,
            'expires' => time() + $ttl
        ];

        return file_put_contents($file, serialize($cache)) !== false;
    }

    private function delete_from_file($key) {
        $file = $this->file_cache_dir . $key . '.cache';
        if (file_exists($file)) {
            return @unlink($file);
        }
        return true;
    }

    private function flush_file_cache($group) {
        $pattern = $group ? $group . ':*.cache' : '*.cache';
        $files = glob($this->file_cache_dir . $pattern);
        
        foreach ($files as $file) {
            @unlink($file);
        }
        
        return true;
    }

    /**
     * Get cache statistics
     */
    public function get_stats() {
        $hit_rate = $this->stats['hits'] + $this->stats['misses'] > 0
            ? ($this->stats['hits'] / ($this->stats['hits'] + $this->stats['misses'])) * 100
            : 0;

        return array_merge($this->stats, [
            'hit_rate' => round($hit_rate, 2) . '%',
            'driver' => $this->cache_driver,
            'memory_items' => count($this->memory_cache)
        ]);
    }
}

/**
 * Helper functions
 */
function yap_cache_get($key, $group = 'yap') {
    return YAP_Cache::get_instance()->get($key, $group);
}

function yap_cache_set($key, $value, $group = 'yap', $ttl = null) {
    return YAP_Cache::get_instance()->set($key, $value, $group, $ttl);
}

function yap_cache_delete($key, $group = 'yap') {
    return YAP_Cache::get_instance()->delete($key, $group);
}

function yap_cache_flush($group = null) {
    return YAP_Cache::get_instance()->flush($group);
}

function yap_cache_stats() {
    return YAP_Cache::get_instance()->get_stats();
}
