<?php

// CORS headers for all responses
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request globally
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// Basic Router (can be replaced by a framework router)

// Include controllers and middleware
require_once APP_ROOT . '/controllers/UserController.php';
require_once APP_ROOT . '/controllers/SubjectController.php';
require_once APP_ROOT . '/controllers/TopicController.php';
require_once APP_ROOT . '/controllers/QuestionController.php';
require_once APP_ROOT . '/controllers/ExamController.php';
require_once APP_ROOT . '/controllers/ExamSubjectController.php';
require_once APP_ROOT . '/controllers/StudentSessionController.php';
require_once APP_ROOT . '/controllers/StudentAnswerController.php';
require_once APP_ROOT . '/middleware/AuthMiddleware.php';
require_once APP_ROOT . '/utils/ResponseHelper.php';
require_once APP_ROOT . '/utils/PaginationHelper.php';

// Database connection (assuming $pdo is available from index.php)
global $pdo;

// Get the request URI and script name
$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
$script_name = $_SERVER['SCRIPT_NAME'];

// Remove query string from request URI
$request_path = strtok($request_uri, '?');

// Get the base path (directory where index.php is located)
$base_path = str_replace('\\', '/', dirname($script_name));

// Remove base path from request URI
if ($base_path !== '/' && strpos($request_path, $base_path) === 0) {
    $request_path = substr($request_path, strlen($base_path));
}

// Trim leading and trailing slashes
$request_path = trim($request_path, '/');
$request_method = $_SERVER['REQUEST_METHOD'];

// Define routes
$routes = [
    'GET api/v1/test' => ['controller' => null, 'method' => null, 'action' => function() { // Example route
        header('Content-Type: application/json');
        echo json_encode(['message' => 'API is working!']);
    }],
    'POST api/v1/users/register' => ['controller' => 'UserController', 'method' => 'register'],
    'POST api/v1/users/login' => ['controller' => 'UserController', 'method' => 'login'],
    // Add other routes here
    'GET api/v1/users/profile' => ['controller' => 'UserController', 'method' => 'getProfile', 'middleware' => ['AuthMiddleware']],

    // User routes
    'GET api/v1/users' => ['controller' => 'UserController', 'method' => 'getAll'],
    'GET api/v1/users/{id}' => ['controller' => 'UserController', 'method' => 'getById'],
    'PUT api/v1/users/{id}' => ['controller' => 'UserController', 'method' => 'update'],
    'DELETE api/v1/users/{id}' => ['controller' => 'UserController', 'method' => 'delete'],

    // Exam routes
    'POST api/v1/exams' => ['controller' => 'ExamController', 'method' => 'create'],
    'GET api/v1/exams' => ['controller' => 'ExamController', 'method' => 'getAll'],
    'GET api/v1/exams/{id}' => ['controller' => 'ExamController', 'method' => 'getById'],
    'PUT api/v1/exams/{id}' => ['controller' => 'ExamController', 'method' => 'update'],
    'DELETE api/v1/exams/{id}' => ['controller' => 'ExamController', 'method' => 'delete'],

    // Subject routes
    'POST api/v1/subjects' => ['controller' => 'SubjectController', 'method' => 'create'],
    'GET api/v1/subjects' => ['controller' => 'SubjectController', 'method' => 'getAll'],
    'GET api/v1/subjects/{id}' => ['controller' => 'SubjectController', 'method' => 'getById'],
    'PUT api/v1/subjects/{id}' => ['controller' => 'SubjectController', 'method' => 'update'],
    'DELETE api/v1/subjects/{id}' => ['controller' => 'SubjectController', 'method' => 'delete'],

    // Topic routes
    'POST api/v1/topics' => ['controller' => 'TopicController', 'method' => 'create'],
    'GET api/v1/topics' => ['controller' => 'TopicController', 'method' => 'getAll'],
    'GET api/v1/topics/{id}' => ['controller' => 'TopicController', 'method' => 'getById'],
    'PUT api/v1/topics/{id}' => ['controller' => 'TopicController', 'method' => 'update'],
    'DELETE api/v1/topics/{id}' => ['controller' => 'TopicController', 'method' => 'delete'],

    // Question routes
    'POST api/v1/questions' => ['controller' => 'QuestionController', 'method' => 'create'],
    'GET api/v1/questions' => ['controller' => 'QuestionController', 'method' => 'getAll'],
    'GET api/v1/questions/{id}' => ['controller' => 'QuestionController', 'method' => 'getById'],
    'PUT api/v1/questions/{id}' => ['controller' => 'QuestionController', 'method' => 'update'],
    'DELETE api/v1/questions/{id}' => ['controller' => 'QuestionController', 'method' => 'delete'],

    // Bulk Question routes
    'POST api/v1/questions/bulk' => ['controller' => 'QuestionController', 'method' => 'bulkCreate'],
    'PUT api/v1/questions/bulk' => ['controller' => 'QuestionController', 'method' => 'bulkUpdate'],
    'DELETE api/v1/questions/bulk' => ['controller' => 'QuestionController', 'method' => 'bulkDelete'],

    // Exam Subject routes
    'POST api/v1/exam-subjects' => ['controller' => 'ExamSubjectController', 'method' => 'create'],
    'GET api/v1/exam-subjects' => ['controller' => 'ExamSubjectController', 'method' => 'getAll'],
    'GET api/v1/exam-subjects/{id}' => ['controller' => 'ExamSubjectController', 'method' => 'getById'],
    'PUT api/v1/exam-subjects/{id}' => ['controller' => 'ExamSubjectController', 'method' => 'update'],
    'DELETE api/v1/exam-subjects/{id}' => ['controller' => 'ExamSubjectController', 'method' => 'delete'],

    // Student Session routes
    'POST api/v1/student-sessions' => ['controller' => 'StudentSessionController', 'method' => 'create'],
    'GET api/v1/student-sessions' => ['controller' => 'StudentSessionController', 'method' => 'getAll'],
    'GET api/v1/student-sessions/{id}' => ['controller' => 'StudentSessionController', 'method' => 'getById'],
    'PUT api/v1/student-sessions/{id}' => ['controller' => 'StudentSessionController', 'method' => 'update'],
    'DELETE api/v1/student-sessions/{id}' => ['controller' => 'StudentSessionController', 'method' => 'delete'],

    // Student Answer routes
    'POST api/v1/student-answers' => ['controller' => 'StudentAnswerController', 'method' => 'create'],
    'GET api/v1/student-answers' => ['controller' => 'StudentAnswerController', 'method' => 'getAll'],
    'GET api/v1/student-answers/{id}' => ['controller' => 'StudentAnswerController', 'method' => 'getById'],
    'PUT api/v1/student-answers/{id}' => ['controller' => 'StudentAnswerController', 'method' => 'update'],
    'DELETE api/v1/student-answers/{id}' => ['controller' => 'StudentAnswerController', 'method' => 'delete'],
];

// Find matching route
$matched_route = null;
$route_params = [];

foreach ($routes as $route => $handler) {
    list($method, $path) = explode(' ', $route);

    if ($method === $request_method) {
        // Convert route path to a regex pattern
        $pattern = '#^' . preg_replace('/\/{([^}]+)}/', '/([^/]+)', $path) . '$#';

        if (preg_match($pattern, $request_path, $matches)) {
            $matched_route = $handler;
            // Extract route parameters
            $route_params = array_slice($matches, 1);
            break;
        }
    }
}

// Handle the request
if ($matched_route) {
    // Apply middleware if defined
    if (isset($matched_route['middleware'])) {
        foreach ($matched_route['middleware'] as $middlewareName) {
            // Assuming middleware classes are in the middleware directory and follow a naming convention
            $middlewareFile = APP_ROOT . '/middleware/' . $middlewareName . '.php';
            if (file_exists($middlewareFile)) {
                require_once $middlewareFile;
                $middleware = new $middlewareName($pdo); // Pass PDO if needed
                // Middleware should call next() or terminate the request
                // For simplicity, assuming middleware has a handle method
                $middleware->handle(function() use ($matched_route, $route_params, $request_method, $pdo) {
                    // This is the 'next' function that executes the controller action
                    // Parse request data based on method
                    $request_data = null;
                    if ($request_method === 'POST' || $request_method === 'PUT') {
                        $request_data = json_decode(file_get_contents('php://input'), true);
                    } elseif ($request_method === 'GET') {
                        // Parse query parameters for GET requests
                        $request_data = $_GET;
                    }

                    if (isset($matched_route['action'])) {
                        // Execute closure action
                        $action = $matched_route['action'];
                        $action();
                    } elseif (isset($matched_route['controller'], $matched_route['method'])) {
                        // Execute controller method
                        $controllerName = $matched_route['controller'];
                        $methodName = $matched_route['method'];

                        // Assuming controller classes are in the controllers directory
                        $controllerFile = APP_ROOT . '/controllers/' . $controllerName . '.php';
                        if (file_exists($controllerFile)) {
                            // require_once $controllerFile; // Already included at the top
                            $controller = new $controllerName($pdo); // Pass PDO to controller constructor

                            if (method_exists($controller, $methodName)) {
                                // Pass route parameters and request data to the controller method
                                $controller->$methodName($route_params, $request_data);
                            } else {
                                // Method not found in controller
                                ResponseHelper::send(500, ['error' => 'Controller method not found.']);
                            }
                        } else {
                            // Controller file not found
                            ResponseHelper::send(500, ['error' => 'Controller file not found.']);
                        }
                    }
                });
            } else {
                // Middleware file not found
                ResponseHelper::send(500, ['error' => 'Middleware file not found: ' . $middlewareName]);
            }
        }
    } else {
        // No middleware, execute controller action directly
        // Parse request data based on method
        $request_data = null;
        if ($request_method === 'POST' || $request_method === 'PUT') {
            $request_data = json_decode(file_get_contents('php://input'), true);
        } elseif ($request_method === 'GET') {
            // Parse query parameters for GET requests
            $request_data = $_GET;
        }

        if (isset($matched_route['action'])) {
            // Execute closure action
            $action = $matched_route['action'];
            $action();
        } elseif (isset($matched_route['controller'], $matched_route['method'])) {
            // Execute controller method
            $controllerName = $matched_route['controller'];
            $methodName = $matched_route['method'];

            // Assuming controller classes are in the controllers directory
            $controllerFile = APP_ROOT . '/controllers/' . $controllerName . '.php';
            if (file_exists($controllerFile)) {
                // require_once $controllerFile; // Already included at the top
                $controller = new $controllerName($pdo); // Pass PDO to controller constructor

                if (method_exists($controller, $methodName)) {
                    // Special handling for register and login: pass only request data
                    if ($controllerName === 'UserController' && in_array($methodName, ['register', 'login'])) {
                        $controller->$methodName($request_data);
                    } else {
                        // Pass route parameters and request data to the controller method
                        $controller->$methodName($route_params, $request_data);
                    }
                } else {
                    // Method not found in controller
                    ResponseHelper::send(500, ['error' => 'Controller method not found.']);
                }
            } else {
                // Controller file not found
                ResponseHelper::send(500, ['error' => 'Controller file not found.']);
            }
        }
    }
} else {
    // No route matched
    ResponseHelper::send(404, ['error' => 'Not Found']);
}

?>