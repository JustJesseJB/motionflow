/**
 * Autoload function for registering with spl_autoload_register
 *
 * @param string $class_name The name of the class to load.
 * @return void
 */
public static function autoload($class_name) {
    // Check if the class is in our namespace
    if (false === strpos($class_name, 'MotionFlow\\')) {
        return;
    }

    // Remove the namespace prefix
    $class_path = str_replace('MotionFlow\\', '', $class_name);
    
    // Handle special case for Admin\Main class (and similar patterns)
    if (strpos($class_path, 'Admin\\') === 0) {
        $file_name = str_replace('Admin\\', '', $class_path);
        $admin_path = MOTIONFLOW_PLUGIN_DIR . 'admin' . DIRECTORY_SEPARATOR . 'class-' . strtolower($file_name) . '.php';
        
        if (file_exists($admin_path)) {
            require_once $admin_path;
            return;
        }
    }
    
    // Handle other namespaces in a similar way
    if (strpos($class_path, 'Frontend\\') === 0) {
        $file_name = str_replace('Frontend\\', '', $class_path);
        $frontend_path = MOTIONFLOW_PLUGIN_DIR . 'public' . DIRECTORY_SEPARATOR . 'class-' . strtolower($file_name) . '.php';
        
        if (file_exists($frontend_path)) {
            require_once $frontend_path;
            return;
        }
    }
    
    // The original code for other classes
    // Convert class name format to file name format
    $class_path = strtolower(
        str_replace(
            ['_', '\\'],
            ['-', DIRECTORY_SEPARATOR],
            $class_path
        )
    );

    // Build the file path
    $file_path = MOTIONFLOW_PLUGIN_DIR . 'includes' . DIRECTORY_SEPARATOR . 'class-' . $class_path . '.php';

    // Check if file exists
    if (file_exists($file_path)) {
        require_once $file_path;
        return;
    }

    // Check in admin directory if file not found in includes
    $admin_path = MOTIONFLOW_PLUGIN_DIR . 'admin' . DIRECTORY_SEPARATOR . 'class-' . $class_path . '.php';
    if (file_exists($admin_path)) {
        require_once $admin_path;
        return;
    }

    // Check in public directory if file not found in includes or admin
    $public_path = MOTIONFLOW_PLUGIN_DIR . 'public' . DIRECTORY_SEPARATOR . 'class-' . $class_path . '.php';
    if (file_exists($public_path)) {
        require_once $public_path;
        return;
    }
}