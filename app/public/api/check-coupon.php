<?php
declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once __DIR__ . '/../../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['coupon_code'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Kupon kodu gerekli!'
    ]);
    exit;
}

$couponCode = strtoupper(trim($input['coupon_code']));
$routeId = isset($input['route_id']) ? (int)$input['route_id'] : 0;

if (empty($couponCode)) {
    echo json_encode([
        'success' => false,
        'message' => 'Lütfen geçerli bir kupon kodu girin!'
    ]);
    exit;
}

try {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT c.*, 
               CASE 
                   WHEN c.firm_id IS NOT NULL THEN f.name 
                   ELSE NULL 
               END as firm_name
        FROM coupons c
        LEFT JOIN firms f ON c.firm_id = f.id
        WHERE c.code = :code
    ");
    $stmt->execute([':code' => $couponCode]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$coupon) {
        echo json_encode([
            'success' => false,
            'message' => 'Geçersiz kupon kodu!'
        ]);
        exit;
    }
    
    if ($coupon['active'] != 1) {
        echo json_encode([
            'success' => false,
            'message' => 'Bu kupon artık geçerli değil!'
        ]);
        exit;
    }
    
    if ($coupon['expires_at']) {
        $now = new DateTime();
        $expiresAt = new DateTime($coupon['expires_at']);
        
        if ($now > $expiresAt) {
            echo json_encode([
                'success' => false,
                'message' => 'Bu kuponun süresi dolmuş!'
            ]);
            exit;
        }
    }

    if ($coupon['usage_limit'] !== null) {
        if ($coupon['used_count'] >= $coupon['usage_limit']) {
            echo json_encode([
                'success' => false,
                'message' => 'Bu kuponun kullanım limiti dolmuş!'
            ]);
            exit;
        }
    }

    if ($coupon['firm_id'] !== null && $routeId > 0) {
        $routeStmt = $db->prepare("SELECT firm_id FROM routes WHERE id = :id");
        $routeStmt->execute([':id' => $routeId]);
        $route = $routeStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($route && $route['firm_id'] != $coupon['firm_id']) {
            $firmName = $coupon['firm_name'] ?? 'belirtilen firma';
            echo json_encode([
                'success' => false,
                'message' => "Bu kupon sadece {$firmName} seferleri için geçerli!"
            ]);
            exit;
        }
    }

    if (isset($_SESSION['user_id'])) {
        $usageStmt = $db->prepare("
            SELECT COUNT(*) as usage_count 
            FROM coupon_usages 
            WHERE coupon_id = :coupon_id AND user_id = :user_id
        ");
        $usageStmt->execute([
            ':coupon_id' => $coupon['id'],
            ':user_id' => $_SESSION['user_id']
        ]);
        $usage = $usageStmt->fetch(PDO::FETCH_ASSOC);
        

        if ($usage['usage_count'] > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Bu kuponu daha önce kullandınız!'
            ]);
            exit;
        }
    }
    
    $message = "Kupon uygulandı! %{$coupon['percent']} indirim kazandınız";
    if ($coupon['firm_name']) {
        $message .= " ({$coupon['firm_name']} kuponu)";
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'discount_percent' => (int)$coupon['percent'],
        'coupon_id' => (int)$coupon['id'],
        'coupon_code' => $coupon['code']
    ]);
    
} catch (Exception $e) {
    error_log("Kupon kontrolü hatası: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Bir hata oluştu, lütfen tekrar deneyin!'
    ]);
}