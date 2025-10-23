<?php
declare(strict_types=1);

$routeId = $_GET['id'] ?? null;

if (!$routeId) {
    $origin = $_GET['origin'] ?? '';
    $destination = $_GET['destination'] ?? '';
    $date = $_GET['date'] ?? '';
    
    try {
        $db = getDbConnection();
        
        $sql = "
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
        ";
        
        $params = [];
        
        if ($origin) {
            $sql .= " AND LOWER(r.origin) = LOWER(:origin)";
            $params[':origin'] = $origin;
        }
        
        if ($destination) {
            $sql .= " AND LOWER(r.destination) = LOWER(:destination)";
            $params[':destination'] = $destination;
        }
        
        if ($date) {
            $sql .= " AND DATE(r.depart_at) = :date";
            $params[':date'] = $date;
        }
        
        $sql .= " ORDER BY r.depart_at ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $routes = [];
        error_log("Routes query error: " . $e->getMessage());
    }
    
    include __DIR__ . '/../Views/layouts/header.php';
    include __DIR__ . '/../Views/home.php'; 
    include __DIR__ . '/../Views/layouts/footer.php';
    exit;
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Bilet satın almak için giriş yapmalısınız.';
    header('Location: /login');
    exit;
}

include __DIR__ . '/../Views/layouts/header.php';
include __DIR__ . '/../Views/sefer-detay.php';
include __DIR__ . '/../Views/layouts/footer.php';