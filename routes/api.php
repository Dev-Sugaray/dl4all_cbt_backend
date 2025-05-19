<?php

// Basic Router (can be replaced by a framework router)

// Include controllers and middleware
require_once APP_ROOT . '/controllers/UserController.php';
require_once APP_ROOT . '/middleware/AuthMiddleware.php';

// Database connection (assuming $pdo is available from index.php)
global $pdo;

// Get the request URI and script name
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];

// Remove query string from request URI
if (($query_string_pos = strpos($request_uri, '?')) !== false) {
    $request_uri = substr($request_uri, 0, $query_string_pos);
}

// Determine the base path by comparing script name and request URI
$base_path = '';
$script_name_parts = explode('/', trim($script_name, '/'));
$request_uri_parts = explode('/', trim($request_uri, '/'));

// Find the common part of the path
$common_parts = [];
for ($i = 0; $i < min(count($script_name_parts), count($request_uri_parts)); $i++) {
    if ($script_name_parts[$i] === $request_uri_parts[$i]) {
        $common_parts[] = $script_name_parts[$i];
    } else {
        break;
    }
}

// The base path is the common part, excluding the script file itself
if (count($common_parts) > 0 && $common_parts[count($common_parts) - 1] === 'index.php') {
    array_pop($common_parts);
}
$base_path = '/' . implode('/', $common_parts);

// Remove the base path from the request URI
if ($base_path !== '/' && strpos($request_uri, $base_path) === 0) {
    $request_uri = substr($request_uri, strlen($base_path));
}

// Trim leading and trailing slashes
$request_uri = trim($request_uri, '/');
$request_method = $_SERVER['REQUEST_METHOD'];

// Define routes
$routes = [
    'GET api/test' => ['controller' => null, 'method' => null, 'action' => function() { // Example route
        header('Content-Type: application/json');
        echo json_encode(['message' => 'API is working!']);
    }],
    'POST api/users/register' => ['controller' => 'UserController', 'method' => 'register'],
    'POST api/users/login' => ['controller' => 'UserController', 'method' => 'login'],
    // Add other routes here
    'GET api/users/profile' => ['controller' => 'UserController', 'method' => 'getProfile', 'middleware' => ['AuthMiddleware']],

    // Subject routes
    'POST api/subjects' => ['controller' => 'SubjectController', 'method' => 'create'],
    'GET api/subjects' => ['controller' => 'SubjectController', 'method' => 'getAll'],
    'GET api/subjects/{id}' => ['controller' => 'SubjectController', 'method' => 'getById'],
    'PUT api/subjects/{id}' => ['controller' => 'SubjectController', 'method' => 'update'],
    'DELETE api/subjects/{id}' => ['controller' => 'SubjectController', 'method' => 'delete'],

    // Topic routes
    'POST api/topics' => ['controller' => 'TopicController', 'method' => 'create'],
    'GET api/topics' => ['controller' => 'TopicController', 'method' => 'getAll'],
    'GET api/topics/{id}' => ['controller' => 'TopicController', 'method' => 'getById'],
    'PUT api/topics/{id}' => ['controller' => 'TopicController', 'method' => 'update'],
    'DELETE api/topics/{id}' => ['controller' => 'TopicController', 'method' => 'delete'],

    // Question routes
    'POST api/questions' => ['controller' => 'QuestionController', 'method' => 'create'],
    'GET api/questions' => ['controller' => 'QuestionController', 'method' => 'getAll'],
    'GET api/questions/{id}' => ['controller' => 'QuestionController', 'method' => 'getById'],
    'PUT api/questions/{id}' => ['controller' => 'QuestionController', 'method' => 'update'],
    'DELETE api/questions/{id}' => ['controller' => 'QuestionController', 'method' => 'delete'],

    // Student Session routes
    'POST api/student-sessions' => ['controller' => 'StudentSessionController', 'method' => 'create'],
    'GET api/student-sessions' => ['controller' => 'StudentSessionController', 'method' => 'getAll'],
    'GET api/student-sessions/{id}' => ['controller' => 'StudentSessionController', 'method' => 'getById'],
    'PUT api/student-sessions/{id}' => ['controller' => 'StudentSessionController', 'method' => 'update'],
    'DELETE api/student-sessions/{id}' => ['controller' => 'StudentSessionController', 'method' => 'delete'],

    // Student Answer routes
    'POST api/student-answers' => ['controller' => 'StudentAnswerController', 'method' => 'create'],
    'GET api/student-sessions/{session_id}/answers' => ['controller' => 'StudentAnswerController', 'method' => 'getBySessionId'],
];

// Match route
$matched_route = null;
$route_params = [];

foreach ($routes as $route => $handler) {
    list($method, $uri_pattern) = explode(' ', $route);

    // Convert dynamic segments like {id} to regex capture groups
    $regex_pattern = '#^' . preg_replace('/\/{([^}]+)}/', '/([^/]+)', $uri_pattern) . '$#';

    if ($request_method === $method && preg_match($regex_pattern, $request_uri, $matches)) {
        $matched_route = $handler;
        // Extract dynamic parameters (skip the full match at index 0)
        $route_params = array_slice($matches, 1);
        break;
    }
}

// Handle matched route
if ($matched_route) {
    $userData = null;
    // Apply middleware if any
    if (isset($matched_route['middleware'])) {
        foreach ($matched_route['middleware'] as $middleware) {
            // Call middleware and check if request should proceed
            // Assuming middleware returns user data on success or exits on failure
            if ($middleware === 'AuthMiddleware') {
                $userData = AuthMiddleware::handle();
                if (!$userData) {
                    // AuthMiddleware handles the response and exits on failure
                    return;
                }
            }
            // Add other middleware checks here
        }
    }

    if (isset($matched_route['action'])) {
        // Execute inline action
        $matched_route['action']();
    } elseif (isset($matched_route['controller']) && isset($matched_route['method'])) {
        // Execute controller method
        $controllerName = $matched_route['controller'];
        $methodName = $matched_route['method'];

        // Instantiate controller
        $controller = new $controllerName($pdo);

        // Get request data (for POST, PUT, etc.)
        $request_data = json_decode(file_get_contents('php://input'), true);

        // Pass user data from middleware to controller if available
        if ($userData !== null) {
            // This is a simplified way; a framework would handle this better.
            // We'll pass user data as part of the request data or as a separate argument.
            // For getProfile, we need the user ID, which is in $userData.
            // Pass user data and request data/route params to the controller method.
            // The controller method will need to handle the different argument types.
            // For getProfile, we specifically need the user ID from $userData.
            if ($methodName === 'getProfile' && isset($userData['user_id'])) {
                $controller->$methodName($userData['user_id']);
            } else {
                // For other methods, pass user data and other parameters as needed
                // This part might need further refinement depending on other controller methods
                $controller->$methodName($userData, $request_data, $route_params);
            }
        } else {
             // Call the controller method without user data, but with request data/route params
            $controller->$methodName($request_data, $route_params);
        }

    }
} else {
    // Handle 404 Not Found
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not Found']);
}

?>