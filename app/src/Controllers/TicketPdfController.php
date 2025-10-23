<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/../Lib/fpdf.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

if (!isset($_GET['id'])) {
    die('Bilet ID gerekli');
}

$ticketId = (int)$_GET['id'];
$userId = $_SESSION['user_id'];

try {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT 
            t.id,
            t.seat_no,
            t.price_paid_cents,
            t.created_at,
            r.origin,
            r.destination,
            r.depart_at,
            r.bus_type,
            f.name as firm_name,
            u.ad,
            u.soyad,
            u.email,
            u.telefon
        FROM tickets t
        JOIN routes r ON t.route_id = r.id
        JOIN firms f ON r.firm_id = f.id
        JOIN users u ON t.user_id = u.id
        WHERE t.id = ? AND t.user_id = ?
    ");
    
    $stmt->execute([$ticketId, $userId]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        die('Bilet bulunamadi');
    }

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    
    function turkce($str) {
    $tr = array('ı','İ','ş','Ş','ğ','Ğ','ü','Ü','ö','Ö','ç','Ç');
    $en = array('i','I','s','S','g','G','u','U','o','O','c','C');
    return str_replace($tr, $en, $str);
}

    // Başlık ve Logo Alanı
    $pdf->SetFillColor(41, 128, 185);
    $pdf->Rect(0, 0, 210, 40, 'F');
    
    // Logo yerine büyük başlık
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', 'B', 32);
    $pdf->SetY(15);
    $pdf->Cell(0, 10, turkce('BiletGo'), 0, 1, 'C');
    
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetY(28);
    $pdf->Cell(0, 5, turkce('Elektronik Otobus Bileti'), 0, 1, 'C');
    
    // Bilet Başlığı
    $pdf->SetY(50);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Helvetica', 'B', 20);
    $pdf->Cell(0, 10, turkce('YOLCU BİLETİ'), 0, 1, 'C');
    
    // Çizgi
    $pdf->SetDrawColor(41, 128, 185);
    $pdf->SetLineWidth(0.5);
    $pdf->Line(20, 65, 190, 65);
    
    // Bilet Detayları
    $pdf->SetY(75);
    $pdf->SetFont('Helvetica', 'B', 12);
    
    // Sol Kolon
    $leftX = 30;
    $rightX = 110;
    $y = 75;
    $lineHeight = 8;
    
    // Bilet No
    $pdf->SetXY($leftX, $y);
    $pdf->Cell(50, $lineHeight, turkce('Bilet No:'), 0, 0);
    $pdf->SetFont('Helvetica', '', 12);
    $pdf->Cell(0, $lineHeight, $ticket['id'], 0, 1);
    $y += $lineHeight;
    
    // Firma
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->SetXY($leftX, $y);
    $pdf->Cell(50, $lineHeight, turkce('Firma:'), 0, 0);
    $pdf->SetFont('Helvetica', '', 12);
    $pdf->Cell(0, $lineHeight, turkce($ticket['firm_name']), 0, 1);
    $y += $lineHeight;
    
    // Rota
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->SetXY($leftX, $y);
    $pdf->Cell(50, $lineHeight, turkce('Güzergah:'), 0, 0);
    $pdf->SetFont('Helvetica', '', 12);
    $pdf->Cell(0, $lineHeight, turkce($ticket['origin'] . ' -> ' . $ticket['destination']), 0, 1);
    $y += $lineHeight;
    
    // Tarih-Saat
    $departDate = date('d.m.Y H:i', strtotime($ticket['depart_at']));
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->SetXY($leftX, $y);
    $pdf->Cell(50, $lineHeight, turkce('Kalkış:'), 0, 0);
    $pdf->SetFont('Helvetica', '', 12);
    $pdf->Cell(0, $lineHeight, $departDate, 0, 1);
    $y += $lineHeight;
    
    // Koltuk
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->SetXY($leftX, $y);
    $pdf->Cell(50, $lineHeight, turkce('Koltuk No:'), 0, 0);
    $pdf->SetFont('Helvetica', '', 12);
    $pdf->Cell(0, $lineHeight, (string)$ticket['seat_no'], 0, 1);
    $y += $lineHeight;
    
    // Otobüs Tipi
    $pdf->SetFont('Helvetica', 'B', 12);
    $pdf->SetXY($leftX, $y);
    $pdf->Cell(50, $lineHeight, turkce('Otobüs Tipi:'), 0, 0);
    $pdf->SetFont('Helvetica', '', 12);
    $pdf->Cell(0, $lineHeight, $ticket['bus_type'], 0, 1);
    $y += $lineHeight + 3;
    
    // Çizgi
    $pdf->Line(20, $y, 190, $y);
    $y += 10;
    
    // Yolcu Bilgileri
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->SetXY($leftX, $y);
    $pdf->Cell(0, $lineHeight, turkce('YOLCU BİLGİLERİ'), 0, 1);
    $y += $lineHeight;
    
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->SetXY($leftX, $y);
    $pdf->Cell(50, $lineHeight, turkce('Ad Soyad:'), 0, 0);
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->Cell(0, $lineHeight, turkce($ticket['ad'] . ' ' . $ticket['soyad']), 0, 1);
    $y += $lineHeight;
    
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->SetXY($leftX, $y);
    $pdf->Cell(50, $lineHeight, turkce('E-posta:'), 0, 0);
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->Cell(0, $lineHeight, $ticket['email'], 0, 1);
    $y += $lineHeight;
    
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->SetXY($leftX, $y);
    $pdf->Cell(50, $lineHeight, turkce('Telefon:'), 0, 0);
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->Cell(0, $lineHeight, $ticket['telefon'], 0, 1);
    $y += $lineHeight + 3;
    
    // Çizgi
    $pdf->Line(20, $y, 190, $y);
    $y += 10;
    
    // Ücret
    $price = number_format($ticket['price_paid_cents'] / 100, 2, ',', '.');
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->SetXY($leftX, $y);
    $pdf->Cell(50, $lineHeight, turkce('Ücret:'), 0, 0);
    $pdf->SetTextColor(41, 128, 185);
    $pdf->Cell(0, $lineHeight, $price . ' TL', 0, 1);
    $y += $lineHeight + 5;
    
    // İptal Kuralları Kutusu
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Rect(20, $y, 170, 35, 'DF');
    
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetXY(25, $y + 5);
    $pdf->Cell(0, 5, turkce('İPTAL KURALLARI'), 0, 1);
    
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetXY(25, $y + 12);
    $pdf->MultiCell(160, 5, turkce(' 
    - Biletiniz kalkis saatinden en az 1 saat once iptal edilebilir.
    - Iptal edilen biletlerin ucreti hesabiniza iade edilir.
    - Kalkis saatine 1 saatten az sure kaldiginda iptal islemleri yapilamaz.'));
    
    $y += 35;
    
    // Alt Bilgi
    $y += 10;
    $pdf->SetY($y);
    $pdf->SetFont('Helvetica', 'I', 8);
    $pdf->SetTextColor(128, 128, 128);
    $pdf->Cell(0, 5, turkce('Bu belge elektronik bilet niteligindedir. Lutfen yolculuk sirasinda yanınızda bulundurunuz.'), 0, 1, 'C');
    $pdf->Cell(0, 5, turkce('Alis Tarihi: ' . date('d.m.Y H:i', strtotime($ticket['created_at']))), 0, 1, 'C');
    $pdf->Cell(0, 5, 'BiletGo - www.biletgo.com', 0, 1, 'C');
    
    // PDF çıktısı
    $pdf->Output('D', 'BiletGo_Bilet_' . $ticket['id'] . '.pdf');
    
} catch (Exception $e) {
    die('Hata: ' . $e->getMessage());
}