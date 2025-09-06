<?php
require_once __DIR__ . '/env.php';

/**
 * Application configuration helper
 */
class App {
    private static $initialized = false;
    
    /**
     * Initialize application configuration
     */
    public static function init() {
        if (self::$initialized) {
            return;
        }
        
        // Load environment variables
        Env::load();
        
        // Set timezone
        $timezone = Env::get('TIMEZONE', 'UTC');
        date_default_timezone_set($timezone);
        
        // Set error reporting based on debug mode
        if (Env::get('APP_DEBUG', false)) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        } else {
            error_reporting(0);
            ini_set('display_errors', 0);
        }
        
        // Session configuration
        ini_set('session.gc_maxlifetime', Env::get('SESSION_LIFETIME', 120) * 60);
        ini_set('session.cookie_lifetime', Env::get('SESSION_LIFETIME', 120) * 60);
        
        if (Env::get('SESSION_SECURE_COOKIE', false)) {
            ini_set('session.cookie_secure', 1);
            ini_set('session.cookie_httponly', 1);
        }
        
        self::$initialized = true;
    }
    
    /**
     * Get application name
     */
    public static function name() {
        return Env::get('APP_NAME', 'POD Order Manager');
    }
    
    /**
     * Get application URL
     */
    public static function url($path = '') {
        $baseUrl = rtrim(Env::get('APP_URL', 'http://localhost'), '/');
        return $baseUrl . '/' . ltrim($path, '/');
    }
    
    /**
     * Check if in debug mode
     */
    public static function isDebug() {
        return Env::get('APP_DEBUG', false) === true;
    }
    
    /**
     * Check if in production
     */
    public static function isProduction() {
        return Env::get('APP_ENV', 'local') === 'production';
    }
    
    /**
     * Get environment
     */
    public static function environment() {
        return Env::get('APP_ENV', 'local');
    }
}

// Auto-initialize when included
App::init();