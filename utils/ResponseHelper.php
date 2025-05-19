<?php

class ResponseHelper {
    public static function send($statusCode, $data = []) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit(); // Terminate script execution after sending response
    }
}

?>