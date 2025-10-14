<?php
/**
 * Simple .env file loader for PHP
 * Loads environment variables from .env file into $_ENV and getenv()
 */

function loadEnv($filePath = null) {
    // Default to .env in the root directory
    if ($filePath === null) {
        $filePath = __DIR__ . '/../.env';
    }
    
    // Check if file exists
    if (!file_exists($filePath)) {
        return false;
    }
    
    // Read the file
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse key=value
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes from value if present
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            
            // Set in environment (only if not already set)
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
    
    return true;
}

// Auto-load .env when this file is included
loadEnv();
?>
