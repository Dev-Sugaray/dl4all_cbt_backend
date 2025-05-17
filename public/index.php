<?php

// Set error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define the application root directory
define('APP_ROOT', dirname(__DIR__));

// Autoload dependencies
require_once APP_ROOT . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(APP_ROOT);
$dotenv->safeLoad();

// Set up error handling
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;
use App\Config\JWTConfig;
use App\GraphQL\Resolvers\ResolverRegistry;

// Handle CORS for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Only accept POST requests for GraphQL
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['errors' => [['message' => 'Method not allowed. Please use POST.']]]);
    exit;
}

// Get the request content
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['errors' => [['message' => 'Invalid JSON input.']]]);
    exit;
}

// Load the GraphQL schema
$schemaFile = APP_ROOT . '/src/GraphQL/Schema/schema.graphql';
if (!file_exists($schemaFile)) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['errors' => [['message' => 'Schema file not found.']]]);
    exit;
}

$schemaContent = file_get_contents($schemaFile);

try {
    // Build the schema
    $schema = BuildSchema::build($schemaContent);
    
    // Get resolvers
    $resolvers = ResolverRegistry::getResolvers();
    
    // Set up the context with user information if authenticated
    $context = ['user' => null];
    
    // Check for authentication
    $user = JWTConfig::getUserFromToken();
    if ($user) {
        $context['user'] = $user;
    }
    
    // Execute the GraphQL query
    $result = GraphQL::executeQuery(
        $schema,
        $input['query'],
        $resolvers,
        $context,
        $input['variables'] ?? null,
        $input['operationName'] ?? null
    );
    
    $output = $result->toArray();
} catch (\Exception $e) {
    $output = [
        'errors' => [
            [
                'message' => $e->getMessage(),
                'locations' => [],
                'path' => []
            ]
        ]
    ];
}

// Return the result
header('Content-Type: application/json');
echo json_encode($output);