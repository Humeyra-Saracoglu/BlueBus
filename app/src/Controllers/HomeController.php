<?php
declare(strict_types=1);

try {
    $db = getDbConnection();
    
    $stmt = $db->query("
        SELECT 
            r.*,
            f.name as firm_name,
            (r.seat_count - COALESCE((
                SELECT COUNT(*) 
                FROM tickets t 
                WHERE t.route_id = r.id AND t.status = 'ACTIVE'
            ), 0)) as available_seats
        FROM routes r
        JOIN firms f ON r.firm_id = f.id
        WHERE r.depart_at >= datetime('now')
        ORDER BY r.depart_at ASC
        LIMIT 10
    ");
    $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $routes = [];
    error_log("Home page query error: " . $e->getMessage());
}

include __DIR__ . '/../Views/layouts/header.php';
include __DIR__ . '/../Views/home.php';
include __DIR__ . '/../Views/layouts/footer.php';