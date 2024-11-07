<?php
/**
 * PHP for session management and defining constants related to the base URL
 */

// Start a session to maintain state across pages 
session_start();

// Define the base URL of the application
define('BASE_URL', 'http://localhost/nakai/');

// Define the file upload directory path, combining the document root with the upload folder location
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/nakai/assets/images/uploads/');
?>
