<?php
// Kết nối CSDL
$host = 'localhost';
$dbname = 'thuvien';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die("Kết nối CSDL thất bại: " . $e->getMessage());
}

function getBorrowingReaders($conn)
{
    $fromDate = $_GET['from'] ?? '';
    $toDate = $_GET['to'] ?? '';
    $returnStatus = $_GET['return_status'] ?? '';
    $readerType = $_GET['type'] ?? '';
    $loanStatus = $_GET['status'] ?? '';

    $whereConditions = ["1=1"];
    $params = [];

    // Điều kiện trạng thái trả
    if ($returnStatus === 'not_returned') {
        $whereConditions[] = "(m.NGAYTRA IS NULL OR m.NGAYTRA = '0000-00-00')";
    } elseif ($returnStatus === 'returned') {
        $whereConditions[] = "(m.NGAYTRA IS NOT NULL AND m.NGAYTRA != '0000-00-00')";
    }

    // Điều kiện ngày
    if (!empty($fromDate)) {
        if ($returnStatus === 'not_returned') {
            $whereConditions[] = "(m.NGAYMUON >= :fromDate OR m.HANTRA >= :fromDate)";
        } else {
            $whereConditions[] = "(m.NGAYMUON >= :fromDate OR m.NGAYTRA >= :fromDate)";
        }
        $params[':fromDate'] = $fromDate;
    }

    if (!empty($toDate)) {
        if ($returnStatus === 'not_returned') {
            $whereConditions[] = "(m.NGAYMUON <= :toDate OR m.HANTRA <= :toDate)";
        } else {
            $whereConditions[] = "(m.NGAYMUON <= :toDate OR m.NGAYTRA <= :toDate)";
        }
        $params[':toDate'] = $toDate;
    }

    // Điều kiện loại độc giả
    if (!empty($readerType)) {
        $whereConditions[] = "d.CHUCDANH = :readerType";
        $params[':readerType'] = $readerType;
    }

    $whereClause = implode(' AND ', $whereConditions);

    $sql = "SELECT 
                d.SOTHE, 
                d.HOTEN, 
                d.CHUCDANH AS LOAI_DOCGIA,
                COUNT(m.MAMUONTRA) AS SO_TAILIEU_DANG_MUON,
                MAX(m.NGAYMUON) AS NGAY_MUON_GAN_NHAT,
                MAX(m.HANTRA) AS HAN_TRA_GAN_NHAT,
                SUM(CASE WHEN m.NGAYTRA IS NULL OR m.NGAYTRA = '0000-00-00' THEN 1 ELSE 0 END) AS SO_CHUA_TRA
            FROM DOCGIA d
            JOIN MUONTRA_TL m ON d.SOTHE = m.SOTHE
            WHERE $whereClause
            GROUP BY d.SOTHE, d.HOTEN, d.CHUCDANH
            ORDER BY MAX(m.HANTRA) ASC";

    try {
        $stmt = $conn->prepare($sql);

        // Bind tham số
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Lọc theo tình trạng mượn (Quá hạn / Sắp hết hạn / Bình thường)
        if (!empty($loanStatus)) {
            $today = new DateTime();

            $results = array_filter($results, function ($row) use ($today, $loanStatus) {
                $ngayTra = isset($row['NGAY_TRA']) ? $row['NGAY_TRA'] : null;

                // Nếu chưa trả
                if (empty($ngayTra) || $ngayTra === '0000-00-00') {
                    if (empty($row['HAN_TRA_GAN_NHAT']) || $row['HAN_TRA_GAN_NHAT'] === '0000-00-00') {
                        // Không có hạn trả thì xem như quá hạn
                        return $loanStatus === 'Quá hạn';
                    }

                    $dueDate = new DateTime($row['HAN_TRA_GAN_NHAT']);
                    $interval = $today->diff($dueDate);
                    $days = (int) $interval->format('%r%a'); // số ngày có dấu (+/-)

                    if ($loanStatus === 'Quá hạn' && $days < 0) {
                        return true;
                    } elseif ($loanStatus === 'Sắp hết hạn' && $days >= 0 && $days <= 3) {
                        return true;
                    } elseif ($loanStatus === 'Bình thường' && $days > 3) {
                        return true;
                    }
                }

                // Nếu đã trả thì bỏ qua
                return false;
            });

            $results = array_values($results); // Reset lại index mảng
        }

        return $results;
    } catch (PDOException $e) {
        die("Lỗi truy vấn SQL: " . $e->getMessage());
    }
}
function getSummaryStats($conn)
{
    $stats = [];
    $fromDate = $_GET['from'] ?? '';
    $toDate = $_GET['to'] ?? '';
    $returnStatus = $_GET['return_status'] ?? '';

    $whereConditions = ["1=1"];
    $params = [];

    // Điều kiện trạng thái trả
    if ($returnStatus === 'not_returned') {
        $whereConditions[] = "(NGAYTRA IS NULL OR NGAYTRA = '0000-00-00')";
    } elseif ($returnStatus === 'returned') {
        $whereConditions[] = "(NGAYTRA IS NOT NULL AND NGAYTRA != '0000-00-00')";
    }

    // Điều kiện ngày - sửa tương tự
    if (!empty($fromDate)) {
        if ($returnStatus === 'not_returned') {
            $whereConditions[] = "(NGAYMUON >= :fromDate OR HANTRA >= :fromDate)";
        } else {
            $whereConditions[] = "(NGAYMUON >= :fromDate OR NGAYTRA >= :fromDate)";
        }
        $params[':fromDate'] = $fromDate;
    }
    
    if (!empty($toDate)) {
        if ($returnStatus === 'not_returned') {
            $whereConditions[] = "(NGAYMUON <= :toDate OR HANTRA <= :toDate)";
        } else {
            $whereConditions[] = "(NGAYMUON <= :toDate OR NGAYTRA <= :toDate)";
        }
        $params[':toDate'] = $toDate;
    }

    $whereClause = implode(' AND ', $whereConditions);
   
   
    try {
        // Tổng số phiếu mượn
        $sql = "SELECT COUNT(*) AS total FROM MUONTRA_TL WHERE $whereClause";
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $stats['total_loans'] = $stmt->fetchColumn();

        // Tổng số độc giả đang mượn
        $sql = "SELECT COUNT(DISTINCT SOTHE) FROM MUONTRA_TL WHERE $whereClause";
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $stats['total_readers'] = $stmt->fetchColumn();

        // Phân loại độc giả
        $sql = "SELECT 
                    d.CHUCDANH, 
                    COUNT(DISTINCT d.SOTHE) AS count 
                FROM DOCGIA d
                JOIN MUONTRA_TL m ON d.SOTHE = m.SOTHE
                WHERE $whereClause
                GROUP BY d.CHUCDANH";
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $stats['reader_types'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Số tài liệu đang mượn
        $stats['total_books'] = $stats['total_loans'];

        // Số phiếu sắp hết hạn (3 ngày tới)
        $sql = "SELECT COUNT(*) FROM MUONTRA_TL 
                WHERE $whereClause
                AND HANTRA BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)";
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $stats['due_soon'] = $stmt->fetchColumn();

        return $stats;
    } catch (PDOException $e) {
        die("Lỗi truy vấn thống kê: " . $e->getMessage());
    }
}

function getMonthlyStats($conn)
{
    $sql = "SELECT 
                DATE_FORMAT(NGAYMUON, '%Y-%m') AS thang,
                COUNT(*) AS so_luot_muon,
                SUM(CASE WHEN NGAYTRA IS NOT NULL THEN 1 ELSE 0 END) AS so_luot_tra
            FROM MUONTRA_TL m
            WHERE NGAYMUON >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(NGAYMUON, '%Y-%m')
            ORDER BY thang ASC";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Lỗi truy vấn thống kê theo tháng: " . $e->getMessage());
    }
}

try {
    $borrowingReaders = getBorrowingReaders($conn);
    $stats = getSummaryStats($conn);
    $monthlyStats = getMonthlyStats($conn);
} catch (PDOException $e) {
    die("Lỗi hệ thống: " . $e->getMessage());
}



// Đóng kết nối
$conn = null;
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <title>Báo cáo độc giả đang mượn - NTech</title>
    <style>
        /* Giữ nguyên toàn bộ CSS từ file gốc */
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }

        .header {
            background-color: #2c4582;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .header-logo {
            font-size: 24px;
            font-weight: bold;
            display: flex;
            align-items: center;
        }

        .header-logo img {
            height: 30px;
            margin-right: 10px;
        }

        .user-dropdown {
            display: flex;
            align-items: center;
            cursor: pointer;
            color: white;
        }

        .user-dropdown i {
            margin-right: 8px;
            font-size: 20px;
        }

        .sidebar {
            width: 250px;
            background-color: #2f4c8c;
            color: white;
            position: fixed;
            height: calc(100vh - 60px);
            top: 60px;
            left: 0;
            overflow-y: auto;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            border-left: 4px solid transparent;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: #1e335c;
            border-left: 4px solid #ffffff;
        }

        .sidebar i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            margin-top: 60px;
            height: calc(100vh - 60px);
            overflow-y: auto;
        }

        .page-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #2f4c8c;
        }

        .sub-menu {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }

        .sub-menu a {
            padding: 10px 20px;
            text-decoration: none;
            color: #2f4c8c;
            font-weight: bold;
            border-bottom: 3px solid transparent;
        }

        .sub-menu a.active {
            border-bottom: 3px solid #2f4c8c;
            color: #2f4c8c;
        }

        .sub-menu a:hover {
            background-color: #f0f0f0;
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .filter-group label {
            font-weight: bold;
            color: #2f4c8c;
        }

        .filter-group input,
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .action-buttons button {
            padding: 8px 16px;
            background-color: #2f4c8c;
            color: white;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
        }

        .action-buttons button i {
            margin-right: 8px;
        }

        .action-buttons button:hover {
            background-color: #3b5998;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
        }

        th {
            background-color: #2f4c8c;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        .status-overdue {
            color: #e74c3c;
            font-weight: bold;
        }

        .status-normal {
            color: #27ae60;
            font-weight: bold;
        }

        .status-warning {
            color: #f39c12;
            font-weight: bold;
        }

        .summary-cards {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .summary-card {
            flex: 1;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .summary-card h3 {
            margin-top: 0;
            color: #2f4c8c;
        }

        .summary-card .value {
            font-size: 28px;
            font-weight: bold;
            margin: 10px 0;
            color: #2c4582;
        }

        .summary-card .description {
            color: #666;
            font-size: 14px;
        }

        .chart-container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .chart-placeholder {
            height: 300px;
            background-color: #f9f9f9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            border: 1px dashed #ddd;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="header-logo">
            <img src="https://imgur.com/R495jri.png" alt="NTech Logo" style="width:200px; height:40px;">
        </div>
        <div class="user-dropdown">
            <i class="fas fa-user-circle"></i>
            Admin
            <i class="fas fa-chevron-down" style="margin-left: 8px; font-size: 14px;"></i>
        </div>
    </div>

    <div class="sidebar">
        <a href="trangchuad.php"><i class="fas fa-home"></i> Trang chủ</a>
        <a href="quanlyngdung.php"><i class="fas fa-cogs"></i> Hệ thống</a>
        <a href="quanlydocgia.php"><i class="fas fa-user-friends"></i> Độc giả</a>
        <a href="quanlytailieu.php"><i class="fas fa-book"></i> Tài liệu</a>
        <a href="quanlyphieumuon.php"><i class="fas fa-exchange-alt"></i> Mượn trả tài liệu</a>
        <a href="#" class="active"><i class="fas fa-chart-bar"></i> Thống kê báo cáo</a>
    </div>

    <div class="main-content">
        <h2 class="page-title">Thống kê báo cáo</h2>

        <div class="sub-menu">
            <a href="baocaotailieumoi.php"><i class="fas fa-book-medical"></i> Tài liệu nhập mới</a>
            <a href="baocaotailieucon.php"><i class="fas fa-book-open"></i> Tài liệu còn trong thư viện</a>
            <a href="baocaodocgiavipham.php"><i class="fas fa-exclamation-triangle"></i> Độc giả vi phạm</a>
            <a href="#" class="active"><i class="fas fa-users"></i> Độc giả đang mượn</a>
        </div>

        <div class="summary-cards">
            <div class="summary-card">
                <h3>Tổng số phiếu mượn</h3>
                <div class="value"><?= $stats['total_loans'] ?></div>
                <div class="description">Đang hoạt động</div>
            </div>
            <div class="summary-card">
                <h3>Độc giả đang mượn</h3>
                <div class="value"><?= $stats['total_readers'] ?></div>
                <div class="description">
                    <?php
                    $reader_desc = [];
                    foreach ($stats['reader_types'] as $type) {
                        $reader_desc[] = "{$type['count']} {$type['CHUCDANH']}";
                    }
                    echo implode(', ', $reader_desc);
                    ?>
                </div>
            </div>
            <div class="summary-card">
                <h3>Tài liệu đang mượn</h3>
                <div class="value"><?= $stats['total_books'] ?></div>
                <div class="description">Từ thư viện</div>
            </div>
            <div class="summary-card">
                <h3>Sắp đến hạn trả</h3>
                <div class="value"><?= $stats['due_soon'] ?></div>
                <div class="description">Trong 3 ngày tới</div>
            </div>
        </div>

        <div class="chart-container">
            <h3>Thống kê mượn trả theo tháng</h3>
            <div class="chart-placeholder">
                [Biểu đồ thống kê số lượng mượn/trả theo tháng]
            </div>
        </div>

        <div class="toolbar">
            <div class="filter-group">
                <label>Từ ngày:</label>
                <input type="date" id="fromDate" value="<?= $_GET['from'] ?? date('Y-m-01') ?>">

                <label>Đến ngày:</label>
                <input type="date" id="toDate" value="<?= $_GET['to'] ?? date('Y-m-d') ?>">

                <!-- Thêm dropdown Loại độc giả -->
                <label>Loại độc giả:</label>
                <select id="readerType">
                    <option value="">Tất cả</option>
                    <option value="Sinh viên" <?= ($_GET['type'] ?? '') === 'Sinh viên' ? 'selected' : '' ?>>Sinh viên</option>
                    <option value="Giáo viên" <?= ($_GET['type'] ?? '') === 'Giáo viên' ? 'selected' : '' ?>>Giáo viên</option>
                </select>

                <!-- Thêm dropdown Trạng thái mượn -->
                <label>Trạng thái mượn:</label>
                <select id="loanStatus">
                    <option value="">Tất cả</option>
                    <option value="Bình thường" <?= ($_GET['status'] ?? '') === 'Bình thường' ? 'selected' : '' ?>>Bình thường</option>
                    <option value="Sắp hết hạn" <?= ($_GET['status'] ?? '') === 'Sắp hết hạn' ? 'selected' : '' ?>>Sắp hết hạn</option>
                    <option value="Quá hạn" <?= ($_GET['status'] ?? '') === 'Quá hạn' ? 'selected' : '' ?>>Quá hạn</option>
                </select>

                <!-- Dropdown Trạng thái trả (đã có) -->
                <label>Trạng thái trả:</label>
                <select id="returnStatus">
                    <option value="">Tất cả</option>
                    <option value="not_returned" <?= ($_GET['return_status'] ?? '') === 'not_returned' ? 'selected' : '' ?>>Chưa trả</option>
                    <option value="returned" <?= ($_GET['return_status'] ?? '') === 'returned' ? 'selected' : '' ?>>Đã trả</option>
                </select>
            </div>

            <div class="action-buttons">
                <button onclick="filterLoans()"><i class="fas fa-filter"></i> Lọc</button>
                <button onclick="exportToExcel()"><i class="fas fa-file-excel"></i> Xuất Excel</button>
                <button onclick="printReport()"><i class="fas fa-print"></i> In báo cáo</button>
            </div>
        </div>

        <table id="loanTable">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Số thẻ</th>
                    <th>Họ tên</th>
                    <th>Loại độc giả</th>
                    <th>Tổng số tài liệu</th>
                    <th>Số chưa trả</th>
                    <th>Ngày mượn gần nhất</th>
                    <th>Hạn trả gần nhất</th>
                    <th>Trạng thái</th>
                    
                </tr>
            </thead>
            <tbody>
                <?php
                $stt = 1;
                foreach ($borrowingReaders as $reader):
                    // Xác định trạng thái
                    $today = new DateTime();
                    $dueDate = new DateTime($reader['HAN_TRA_GAN_NHAT']);
                    $interval = $today->diff($dueDate);
                    $days = $interval->days;

                    if ($today > $dueDate) {
                        $status = 'Quá hạn';
                        $status_class = 'status-overdue';
                    } elseif ($days <= 3) {
                        $status = 'Sắp hết hạn';
                        $status_class = 'status-warning';
                    } else {
                        $status = 'Bình thường';
                        $status_class = 'status-normal';
                    }
                ?>
                    <tr>
                        <td><?= $stt++ ?></td>
                        <td><?= htmlspecialchars($reader['SOTHE']) ?></td>
                        <td><?= htmlspecialchars($reader['HOTEN']) ?></td>
                        <td><?= htmlspecialchars($reader['LOAI_DOCGIA']) ?></td>
                        <td><?= htmlspecialchars($reader['SO_TAILIEU_DANG_MUON']) ?></td>
                        <td><?= htmlspecialchars($reader['SO_CHUA_TRA']) ?></td>
                        <td><?= htmlspecialchars($reader['NGAY_MUON_GAN_NHAT']) ?></td>
                        <td><?= htmlspecialchars($reader['HAN_TRA_GAN_NHAT']) ?></td>
                        <td class="<?= $status_class ?>"><?= $status ?></td>
                        
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        // Trong file baocaodocgiamuontailieu.php, sửa hàm filterLoans()
        function filterLoans() {
            const fromDate = document.getElementById("fromDate").value || '';
            const toDate = document.getElementById("toDate").value || '';

            // Validate nếu cần
            if (fromDate && toDate && new Date(fromDate) > new Date(toDate)) {
                alert("Ngày bắt đầu không thể lớn hơn ngày kết thúc");
                return;
            }

            const params = new URLSearchParams();
            if (fromDate) params.append('from', fromDate);
            if (toDate) params.append('to', toDate);
            // Thêm các tham số khác
    const readerType = document.getElementById("readerType").value;
    const loanStatus = document.getElementById("loanStatus").value;
    const returnStatus = document.getElementById("returnStatus").value;
    
    if (readerType) params.append('type', readerType);
    if (loanStatus) params.append('status', loanStatus);
    if (returnStatus) params.append('return_status', returnStatus);
    
    window.location.href = `baocaodocgiamuontailieu.php?${params.toString()}`;
}
        function exportToExcel() {
            // Tạo URL để xuất Excel
            const params = new URLSearchParams(window.location.search);
            window.open(`export_borrowing_readers.php?${params.toString()}`, '_blank');
        }

        function printReport() {
            window.print();
        }

        // Dữ liệu từ PHP
        const monthlyStats = <?= json_encode($monthlyStats) ?>;

        // Chuẩn bị dữ liệu cho biểu đồ
        const labels = monthlyStats.map(stat => stat.thang);
        const borrowData = monthlyStats.map(stat => stat.so_luot_muon);
        const returnData = monthlyStats.map(stat => stat.so_luot_tra);

        // Vẽ biểu đồ
        const ctx = document.createElement('canvas');
        document.querySelector('.chart-placeholder').replaceWith(ctx);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                        label: 'Số lượt mượn',
                        data: borrowData,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Số lượt trả',
                        data: returnData,
                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Số lượng'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Tháng'
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>