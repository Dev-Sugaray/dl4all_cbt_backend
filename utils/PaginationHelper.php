<?php

class PaginationHelper {
    /**
     * Generate pagination data for SQL queries
     * 
     * @param PDO $pdo Database connection
     * @param string $table Table name
     * @param string $countQuery Query to count total records (optional)
     * @param array $params Query parameters (optional)
     * @param int $page Current page number (default: 1)
     * @param int $limit Items per page (default: 10)
     * @param string $whereClause Additional WHERE conditions (optional)
     * @return array Pagination data including limit, offset, total records, total pages, current page
     */
    public static function paginate($pdo, $table, $countQuery = null, $params = [], $page = 1, $limit = 10, $whereClause = '') {
        // Ensure page and limit are integers and have minimum values
        $page = max(1, intval($page));
        $limit = max(1, intval($limit));
        
        // Calculate offset
        $offset = ($page - 1) * $limit;
        
        // Count total records
        if ($countQuery === null) {
            $countQuery = "SELECT COUNT(*) FROM {$table}";
            if (!empty($whereClause)) {
                $countQuery .= " WHERE {$whereClause}";
            }
        }
        
        $stmt = $pdo->prepare($countQuery);
        $stmt->execute($params);
        $totalRecords = (int) $stmt->fetchColumn();
        
        // Calculate total pages
        $totalPages = ceil($totalRecords / $limit);
        
        return [
            'limit' => $limit,
            'offset' => $offset,
            'total_records' => $totalRecords,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'has_next_page' => $page < $totalPages,
            'has_previous_page' => $page > 1
        ];
    }
    
    /**
     * Generate pagination metadata for API response
     * 
     * @param array $paginationData Pagination data from paginate method
     * @param string $baseUrl Base URL for pagination links
     * @return array Pagination metadata for API response
     */
    public static function getPaginationMeta($paginationData, $baseUrl = '') {
        $page = $paginationData['current_page'];
        $totalPages = $paginationData['total_pages'];
        
        // Build pagination links
        $links = [];
        
        // Add base URL query parameters if they exist
        $urlParts = parse_url($baseUrl);
        $queryParams = [];
        if (isset($urlParts['query'])) {
            parse_str($urlParts['query'], $queryParams);
        }
        
        // Base URL without query string
        $baseUrlWithoutQuery = isset($urlParts['path']) ? $urlParts['path'] : $baseUrl;
        
        // First page
        $queryParams['page'] = 1;
        $queryParams['limit'] = $paginationData['limit'];
        $links['first'] = $baseUrlWithoutQuery . '?' . http_build_query($queryParams);
        
        // Previous page
        if ($page > 1) {
            $queryParams['page'] = $page - 1;
            $links['prev'] = $baseUrlWithoutQuery . '?' . http_build_query($queryParams);
        }
        
        // Next page
        if ($page < $totalPages) {
            $queryParams['page'] = $page + 1;
            $links['next'] = $baseUrlWithoutQuery . '?' . http_build_query($queryParams);
        }
        
        // Last page
        $queryParams['page'] = $totalPages;
        $links['last'] = $baseUrlWithoutQuery . '?' . http_build_query($queryParams);
        
        return [
            'pagination' => [
                'total' => $paginationData['total_records'],
                'per_page' => $paginationData['limit'],
                'current_page' => $page,
                'total_pages' => $totalPages,
                'links' => $links
            ]
        ];
    }
}