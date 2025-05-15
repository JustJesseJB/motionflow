<?php
/**
 * Logger Class
 *
 * @package    MotionFlow
 * @subpackage MotionFlow/includes
 */

/**
 * MotionFlow Logger Class
 *
 * Handles all logging functionality for the plugin.
 */
class MotionFlow_Logger {

    /**
     * Log levels
     */
    const LEVEL_ERROR   = 'error';
    const LEVEL_WARNING = 'warning';
    const LEVEL_INFO    = 'info';
    const LEVEL_DEBUG   = 'debug';
    const LEVEL_TRACE   = 'trace';

    /**
     * Current log level
     *
     * @var string
     */
    private static $log_level = self::LEVEL_INFO;

    /**
     * Log file path
     *
     * @var string
     */
    private static $log_file = '';

    /**
     * Initialize the logger
     *
     * @return void
     */
    public static function init() {
        // Set default log file
        self::$log_file = WP_CONTENT_DIR . '/motionflow-logs/motionflow-' . date('Y-m-d') . '.log';
        
        // Create logs directory if it doesn't exist
        $log_dir = dirname(self::$log_file);
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
            
            // Create .htaccess to protect log files
            $htaccess_content = "Order deny,allow\nDeny from all";
            file_put_contents($log_dir . '/.htaccess', $htaccess_content);
        }
        
        // Set log level based on environment
        if (defined('WP_DEBUG') && WP_DEBUG) {
            self::$log_level = self::LEVEL_DEBUG;
        }
    }

    /**
     * Set the log level
     *
     * @param string $level The log level to set.
     * @return void
     */
    public static function set_level($level) {
        if (in_array($level, [self::LEVEL_ERROR, self::LEVEL_WARNING, self::LEVEL_INFO, self::LEVEL_DEBUG, self::LEVEL_TRACE])) {
            self::$log_level = $level;
        }
    }

    /**
     * Log a message
     *
     * @param string $level   The log level.
     * @param string $message The message to log.
     * @param array  $context Additional context data.
     * @return void
     */
    public static function log($level, $message, array $context = []) {
        // Check if level is enabled
        if (!self::is_level_enabled($level)) {
            return;
        }

        // Format the message with context
        $formatted_message = self::format_message($level, $message, $context);
        
        // Write to log file
        self::write_log($formatted_message);
    }

    /**
     * Log an error message
     *
     * @param string $message The message to log.
     * @param array  $context Additional context data.
     * @return void
     */
    public static function error($message, array $context = []) {
        self::log(self::LEVEL_ERROR, $message, $context);
    }

    /**
     * Log a warning message
     *
     * @param string $message The message to log.
     * @param array  $context Additional context data.
     * @return void
     */
    public static function warning($message, array $context = []) {
        self::log(self::LEVEL_WARNING, $message, $context);
    }

    /**
     * Log an info message
     *
     * @param string $message The message to log.
     * @param array  $context Additional context data.
     * @return void
     */
    public static function info($message, array $context = []) {
        self::log(self::LEVEL_INFO, $message, $context);
    }

    /**
     * Log a debug message
     *
     * @param string $message The message to log.
     * @param array  $context Additional context data.
     * @return void
     */
    public static function debug($message, array $context = []) {
        self::log(self::LEVEL_DEBUG, $message, $context);
    }

    /**
     * Log a trace message
     *
     * @param string $message The message to log.
     * @param array  $context Additional context data.
     * @return void
     */
    public static function trace($message, array $context = []) {
        self::log(self::LEVEL_TRACE, $message, $context);
    }

    /**
     * Check if a log level is enabled
     *
     * @param string $level The log level to check.
     * @return bool
     */
    private static function is_level_enabled($level) {
        $levels = [
            self::LEVEL_ERROR   => 1,
            self::LEVEL_WARNING => 2,
            self::LEVEL_INFO    => 3,
            self::LEVEL_DEBUG   => 4,
            self::LEVEL_TRACE   => 5,
        ];

        return isset($levels[$level]) && isset($levels[self::$log_level]) && $levels[$level] <= $levels[self::$log_level];
    }

    /**
     * Format a log message
     *
     * @param string $level   The log level.
     * @param string $message The message to log.
     * @param array  $context Additional context data.
     * @return string
     */
    private static function format_message($level, $message, array $context = []) {
        // Get backtrace info
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = isset($trace[2]) ? $trace[2] : [];
        
        $file = isset($caller['file']) ? basename($caller['file']) : 'unknown';
        $line = isset($caller['line']) ? $caller['line'] : 0;
        
        // Format timestamp
        $timestamp = date('Y-m-d H:i:s');
        
        // Format level
        $level_upper = strtoupper($level);
        
        // Format context data
        $context_str = '';
        if (!empty($context)) {
            $context_str = ' ' . json_encode($context);
        }
        
        // Build message
        return "[{$timestamp}] [{$level_upper}] [{$file}:{$line}] {$message}{$context_str}" . PHP_EOL;
    }

    /**
     * Write to log file
     *
     * @param string $message The formatted message to write.
     * @return void
     */
    private static function write_log($message) {
        // Ensure log file is set
        if (empty(self::$log_file)) {
            self::init();
        }
        
        // Append to log file
        error_log($message, 3, self::$log_file);
    }

    /**
     * Get all log files
     *
     * @return array
     */
    public static function get_log_files() {
        $log_dir = WP_CONTENT_DIR . '/motionflow-logs/';
        $files = [];
        
        if (file_exists($log_dir)) {
            foreach (glob($log_dir . 'motionflow-*.log') as $file) {
                $files[] = $file;
            }
        }
        
        return $files;
    }

    /**
     * Clear all log files
     *
     * @return bool
     */
    public static function clear_logs() {
        $files = self::get_log_files();
        $success = true;
        
        foreach ($files as $file) {
            if (!unlink($file)) {
                $success = false;
            }
        }
        
        return $success;
    }
}

// Initialize the logger
MotionFlow_Logger::init();