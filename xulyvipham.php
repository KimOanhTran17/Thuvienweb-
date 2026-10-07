<?php
// Bật báo lỗi để debug (nhớ tắt khi đưa lên production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Kết nối CSDL
$servername = "localhost";
$username = "root";
$password = ""; // Mật khẩu CSDL của bạn
$dbname = "thuvien";

$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối CSDL thất bại: " . $conn->connect_error);
}

// Xử lý form submit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    $maPhat = !empty($_POST['violationId']) ? $_POST['violationId'] : '';
    $soThe = !empty($_POST['cardId']) ? $_POST['cardId'] : '';
    $maNV = !empty($_POST['staffId']) ? $_POST['staffId'] : '';
    $lyDo = !empty($_POST['reason']) ? $_POST['reason'] : '';
    $hinhThuc = !empty($_POST['penalty']) ? $_POST['penalty'] : '';
    $ngayXuLy = !empty($_POST['processDate']) ? $_POST['processDate'] : '';

    if ($action == 'add' || $action == 'edit') {
        try {
            if ($action == 'add') {
                $stmt = $conn->prepare("INSERT INTO XULYDOCGIA (MAPHAT, SOTHE, MANV, LYDOVP, HTXULY, NGAYXULY) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $maPhat, $soThe, $maNV, $lyDo, $hinhThuc, $ngayXuLy);
            } else {
                $stmt = $conn->prepare("UPDATE XULYDOCGIA SET SOTHE=?, MANV=?, LYDOVP=?, HTXULY=?, NGAYXULY=? WHERE MAPHAT=?");
                $stmt->bind_param("ssssss", $soThe, $maNV, $lyDo, $hinhThuc, $ngayXuLy, $maPhat);
            }

            if ($stmt->execute()) {
                $thongBao = $action == 'add' ? "Thêm thành công!" : "Cập nhật thành công!";
                $loai = "success";
            } else {
                throw new Exception($stmt->error);
            }
        } catch (Exception $e) {
            $thongBao = "Lỗi: " . $e->getMessage();
            $loai = "error";
        } finally {
            if (isset($stmt)) $stmt->close();

            if (!isset($_GET['debug'])) {
                header("Location: xulyvipham.php?thongbao=" . urlencode($thongBao) . "&loai=" . $loai);
                exit();
            }
        }
    }
}

// Xử lý xóa
if (isset($_GET['xoa'])) {
    $maPhat = $_GET['xoa'];
    $stmt = $conn->prepare("DELETE FROM XULYDOCGIA WHERE MAPHAT=?");
    $stmt->bind_param("s", $maPhat);

    if ($stmt->execute()) {
        $thongBao = "Xóa thành công!";
        $loai = "success";
    } else {
        $thongBao = "Lỗi: " . $stmt->error;
        $loai = "error";
    }
    $stmt->close();

    header("Location: xulyvipham.php?thongbao=" . urlencode($thongBao) . "&loai=" . $loai);
    exit();
}

// Lấy dữ liệu từ CSDL
$dsViPham = [];
$sql = "SELECT * FROM XULYDOCGIA";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $dsViPham[] = $row;
    }
}

// KHÔNG đóng kết nối ở đây nữa
// $conn->close();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <title>Hệ thống quản lý mượn trả</title>
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

        .toolbar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .action-buttons button {
            margin-right: 10px;
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

        .search-box input {
            padding: 8px 16px;
            border: 1px solid #2f4c8c;
            border-radius: 4px;
            width: 300px;
            font-size: 14px;
        }

        .search-box button {
            background-color: #2f4c8c;
            color: white;
            border: none;
            padding: 8px;
            border-radius: 4px;
            cursor: pointer;
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
            padding: 10px;
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

        .actions-column button {
            margin: 0 5px;
            padding: 5px 10px;
            background-color: #2f4c8c;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .popup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1001;
        }

        .popup-content {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            width: 600px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .form-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .form-buttons button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
        }

        .form-buttons .cancel {
            background-color: #ccc;
        }

        .form-buttons .submit {
            background-color: #2f4c8c;
            color: white;
        }

        .thong-bao {
            position: fixed;
            top: 70px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 6px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            z-index: 2000;
            animation: fadeOut 3s forwards;
            display: flex;
            align-items: center;
        }

        .thong-bao.success {
            background-color: #2ecc71;
            color: white;
        }

        .thong-bao.error {
            background-color: #e74c3c;
            color: white;
        }

        .thong-bao i {
            margin-right: 10px;
            font-size: 18px;
        }

        @keyframes fadeOut {
            0% {
                opacity: 0;
                transform: translateY(-10px);
            }

            10% {
                opacity: 1;
                transform: translateY(0);
            }

            80% {
                opacity: 1;
                transform: translateY(0);
            }

            100% {
                opacity: 0;
                transform: translateY(-10px);
            }
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
    </style>
</head>

<body>
    <?php if (isset($_GET['thongbao'])): ?>
        <div class="thong-bao <?php echo isset($_GET['loai']) ? $_GET['loai'] : 'success'; ?>">
            <i class='fas <?php echo (isset($_GET['loai']) && $_GET['loai'] == 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'); ?>'></i>
            <?php echo htmlspecialchars($_GET['thongbao']); ?>
        </div>
        <script>
            setTimeout(() => {
                const thongBao = document.querySelector('.thong-bao');
                if (thongBao) thongBao.remove();
            }, 3000);
        </script>
    <?php endif; ?>

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
        <a href="trangchu (1).html"><i class="fas fa-home"></i> Trang chủ</a>
        <a href="quanlyngdung.php"><i class="fas fa-cogs"></i> Hệ thống </a>
        <a href="quanlydocgia.php"><i class="fas fa-user-friends"></i> Độc giả</a>
        <a href="tailieuchitiet.php"><i class="fas fa-book"></i> Tài liệu</a>
        <a href="#" class="active"><i class="fas fa-exchange-alt"></i> Mượn trả tài liệu</a>
        <a href="baocaodocgiamuontailieu.php"><i class="fas fa-chart-bar"></i> Thống kê báo cáo</a>
    </div>

    <div class="main-content" id="violation-management">
        <h2 class="page-title">Xử lý độc giả vi phạm</h2>

        <div class="sub-menu">
            <a href="quanlyphieumuon.php"><i class="fas fa-file-invoice"></i> Quản lý phiếu mượn</a>
            <a href="quanlyphieutra.php"><i class="fas fa-file-invoice-dollar"></i> Quản lý phiếu trả</a>
            <a href="xulyvipham.php" class="active"><i class="fas fa-exclamation-triangle"></i> Xử lý độc giả vi phạm</a>
        </div>
        <div class="toolbar">
            <div class="action-buttons">
                <button onclick="moFormViPham('add')"><i class="fas fa-plus"></i> Thêm</button>
            </div>
            <div class="search-box">
                <input type="text" id="searchViolation" placeholder="Tìm kiếm...">
                <button onclick="timKiemViPham()"><i class="fas fa-search"></i></button>
            </div>
        </div>
        <table id="violationTable">
            <thead>
                <tr>
                    <th>Mã phạt</th>
                    <th>Số thẻ</th>
                    <th>Mã nhân viên</th>
                    <th>Lý do</th>
                    <th>Hình thức</th>
                    <th>Ngày xử lý</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dsViPham as $viPham): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($viPham['MAPHAT']); ?></td>
                        <td><?php echo htmlspecialchars($viPham['SOTHE']); ?></td>
                        <td><?php echo htmlspecialchars($viPham['MANV']); ?></td>
                        <td><?php echo htmlspecialchars($viPham['LYDOVP']); ?></td>
                        <td><?php echo htmlspecialchars($viPham['HTXULY']); ?></td>
                        <td><?php echo htmlspecialchars($viPham['NGAYXULY']); ?></td>
                        <td class="actions-column">
                            <button onclick="moFormViPham('edit', this)">Sửa</button>
                            <button onclick="xoaViPham('<?php echo $viPham['MAPHAT']; ?>')">Xóa</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <form id="violationFormPopup" class="popup" method="post" action="xulyvipham.php">
        <div class="popup-content">
            <h3>Thông tin xử lý vi phạm</h3>
            <input type="hidden" id="action" name="action" value="add">
            <div class="form-group"><label>Mã phạt</label><input type="text" id="violationId" name="violationId" readonly></div>
            <div class="form-group"><label>Số thẻ</label><input type="text" id="cardId" name="cardId" required></div>
            <div class="form-group"><label>Mã nhân viên</label><input type="text" id="staffId" name="staffId" required></div>
            <div class="form-group"><label>Lý do</label><textarea id="reason" name="reason" rows="3" required></textarea></div>
            <div class="form-group"><label>Hình thức</label><textarea id="penalty" name="penalty" rows="3" required></textarea></div>
            <div class="form-group"><label>Ngày xử lý</label><input type="date" id="processDate" name="processDate" required></div>
            <div class="form-buttons">
                <button type="button" class="cancel" onclick="dongFormViPham()">Hủy</button>
                <button type="submit" class="submit">Lưu</button>
            </div>
        </div>
    </form>

    <script>
        function moFormViPham(cheDo, nutBam) {
            // Reset form trước khi mở
            document.getElementById("violationFormPopup").reset();

            document.getElementById("violationFormPopup").style.display = "flex";
            document.getElementById("action").value = cheDo;

            if (cheDo === 'edit') {
                const hang = nutBam.closest("tr");
                const o = hang.querySelectorAll("td");
                document.getElementById("violationId").value = o[0].textContent;
                document.getElementById("cardId").value = o[1].textContent;
                document.getElementById("staffId").value = o[2].textContent;
                document.getElementById("reason").value = o[3].textContent;
                document.getElementById("penalty").value = o[4].textContent;
                document.getElementById("processDate").value = o[5].textContent;
            } else {
                // Tạo mã phạt mới
                const maCuoi = "<?php
                                if (!empty($dsViPham)) {
                                    $cuoi = end($dsViPham);
                                    echo $cuoi['MAPHAT'];
                                } else {
                                    echo 'XL000';
                                }
                                ?>";
                const soTiepTheo = parseInt(maCuoi.substring(2)) + 1;
                document.getElementById("violationId").value = "XL" + soTiepTheo.toString().padStart(3, '0');
                document.getElementById("processDate").valueAsDate = new Date();
            }
        }

        function dongFormViPham() {
            document.getElementById("violationFormPopup").style.display = "none";
        }

        function xoaViPham(maPhat) {
            if (confirm("Bạn có chắc chắn muốn xóa thông tin vi phạm này?")) {
                window.location.href = "xulyvipham.php?xoa=" + maPhat;
            }
        }

        function timKiemViPham() {
            const tuKhoa = document.getElementById("searchViolation").value.toUpperCase();
            const cacHang = document.querySelectorAll("#violationTable tbody tr");
            cacHang.forEach(hang => {
                const cacO = hang.getElementsByTagName("td");
                const trungKhop = Array.from(cacO).slice(0, 6).some(o =>
                    o.textContent.toUpperCase().includes(tuKhoa)
                );
                hang.style.display = trungKhop ? "" : "none";
            });
        }
    </script>
</body>
</html>

<?php
// Đóng kết nối ở CUỐI FILE
$conn->close();
?>