<?php
/**
 * Supabase Configuration
 * Loads Supabase credentials from environment variables
 * 
 * SECURITY: Never commit actual credentials to version control!
 * Set these in your .env file for local development
 */

// Load environment variables from .env file
require_once __DIR__ . '/env_loader.php';

// Return configuration array
return [
    'SUPABASE_URL' => getenv('SUPABASE_URL') ?: '',
    'SUPABASE_ANON_KEY' => getenv('SUPABASE_ANON_KEY') ?: '',
    'SUPABASE_SERVICE_ROLE_KEY' => getenv('SUPABASE_SERVICE_ROLE_KEY') ?: '',
];

