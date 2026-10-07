<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "thuvien";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize notification variables
$notification = '';
$notificationType = '';

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $borrowId = $_POST['borrowId'];
        $cardId = $_POST['cardId'];
        $uniqueId = $_POST['uniqueId'];
        $staffId = $_POST['staffId'];
        $borrowDate = $_POST['borrowDate'];
        $borrowType = $_POST['borrowType'];
        $dueDate = $_POST['dueDate'];
        $returnDate = $_POST['returnDate'] ?? null;

        if ($action == 'add') {
            // Add new record
            $stmt = $conn->prepare("INSERT INTO MUONTRA_TL (MAMUONTRA, SOTHE, MACABIET, MANV, NGAYMUON, KIEUMUON, HANTRA, NGAYTRA) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $borrowId, $cardId, $uniqueId, $staffId, $borrowDate, $borrowType, $dueDate, $returnDate);
            if ($stmt->execute()) {
                $notification = "Thêm phiếu trả thành công!";
                $notificationType = "success";
            } else {
                $notification = "Lỗi khi thêm phiếu trả: " . $stmt->error;
                $notificationType = "error";
            }
            $stmt->close();
            header("Location: quanlyphieutra.php?success=" . urlencode($notification) . "&type=" . $notificationType);
            exit();
        } elseif ($action == 'edit') {
            // Update existing record
            $stmt = $conn->prepare("UPDATE MUONTRA_TL SET SOTHE=?, MACABIET=?, MANV=?, NGAYMUON=?, KIEUMUON=?, HANTRA=?, NGAYTRA=? WHERE MAMUONTRA=?");
            $stmt->bind_param("ssssssss", $cardId, $uniqueId, $staffId, $borrowDate, $borrowType, $dueDate, $returnDate, $borrowId);
            if ($stmt->execute()) {
                $notification = "Cập nhật phiếu trả thành công!";
                $notificationType = "success";
            } else {
                $notification = "Lỗi khi cập nhật phiếu trả: " . $stmt->error;
                $notificationType = "error";
            }
            $stmt->close();
            header("Location: quanlyphieutra.php?success=" . urlencode($notification) . "&type=" . $notificationType);
            exit();
        }
    }
}

// Handle delete action
if (isset($_GET['delete'])) {
    $borrowId = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM MUONTRA_TL WHERE MAMUONTRA=?");
    $stmt->bind_param("s", $borrowId);
    if ($stmt->execute()) {
        $notification = "Xóa phiếu trảtrả thành công!";
        $notificationType = "success";
    } else {
        $notification = "Lỗi khi xóa phiếu trảtrả: " . $stmt->error;
        $notificationType = "error";
    }
    $stmt->close();
    header("Location: quanlyphieutra.php?success=" . urlencode($notification) . "&type=" . $notificationType);
    exit();
}

// Fetch all records from the database
$sql = "SELECT * FROM MUONTRA_TL";
$result = $conn->query($sql);
$borrows = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $borrows[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <title>Hệ thống quản lý mượn trả</title>
    <style>
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
            cursor: button;
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
        .form-group select {
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

        .notification {
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

        .notification.success {
            background-color: #2ecc71;
            color: white;
        }

        .notification.error {
            background-color: #e74c3c;
            color: white;
        }

        .notification i {
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
    <?php if (isset($_GET['success'])): ?>
        <div class="notification <?php echo isset($_GET['type']) ? $_GET['type'] : 'success'; ?>">
            <i class='fas <?php echo (isset($_GET['type']) && $_GET['type'] == 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'); ?>'></i>
            <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
        <script>
            setTimeout(() => {
                const notification = document.querySelector('.notification');
                if (notification) notification.remove();
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
        <a href="quanlytailieu.php"><i class="fas fa-book"></i> Tài liệu</a>
        <a href="#" class="active"><i class="fas fa-exchange-alt"></i> Mượn trả tài liệu</a>
        <a href="baocaodocgiamuontailieu.php"><i class="fas fa-chart-bar"></i> Thống kê báo cáo</a>
    </div>

    <div class="main-content" id="borrow-management">
        <h2 class="page-title">Quản lý mượn trả tài liệu</h2>

        <div class="sub-menu">
            <a href="quanlyphieumuon.php"><i class="fas fa-file-invoice"></i> Quản lý phiếu mượn</a>
            <a href="quanlyphieutra.php" class="active"><i class="fas fa-file-invoice-dollar"></i> Quản lý phiếu trả</a>
            <a href="xulyvipham.php"><i class="fas fa-exclamation-triangle"></i> Xử lý độc giả vi phạm</a>
        </div>
        <div class="toolbar">
            <div class="action-buttons">
                <button onclick="openBorrowForm('add')"><i class="fas fa-plus"></i> Thêm</button>
            </div>
            <div class="search-box">
                <input type="text" id="searchBorrow" placeholder="Tìm kiếm...">
                <button onclick="searchBorrow()"><i class="fas fa-search"></i></button>
            </div>
        </div>
        <table id="borrowTable">
            <thead>
                <tr>
                    <th>Mã mượn trả</th>
                    <th>Số thẻ</th>
                    <th>Mã cá biệt</th>
                    <th>Mã nhân viên</th>
                    <th>Ngày mượn</th>
                    <th>Kiểu mượn</th>
                    <th>Hạn trả</th>
                    <th>Ngày trả</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($borrows as $borrow): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($borrow['MAMUONTRA']); ?></td>
                        <td><?php echo htmlspecialchars($borrow['SOTHE']); ?></td>
                        <td><?php echo htmlspecialchars($borrow['MACABIET']); ?></td>
                        <td><?php echo htmlspecialchars($borrow['MANV']); ?></td>
                        <td><?php echo htmlspecialchars($borrow['NGAYMUON']); ?></td>
                        <td><?php echo htmlspecialchars($borrow['KIEUMUON']); ?></td>
                        <td><?php echo htmlspecialchars($borrow['HANTRA']); ?></td>
                        <td><?php echo htmlspecialchars($borrow['NGAYTRA'] ?? ''); ?></td>
                        <td class="actions-column">
                            <button onclick="openBorrowForm('edit', this)">Sửa</button>
                            <button onclick="deleteBorrow('<?php echo $borrow['MAMUONTRA']; ?>')">Xóa</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <form id="borrowFormPopup" class="popup" method="post" action="quanlyphieutra.php">
        <div class="popup-content">
            <h3>Thông tin phiếu trả</h3>
            <input type="hidden" id="action" name="action" value="add">
            <div class="form-group"><label>Mã mượn trả</label><input type="text" id="borrowId" name="borrowId" readonly></div>
            <div class="form-group"><label>Số thẻ</label><input type="text" id="cardId" name="cardId" required></div>
            <div class="form-group"><label>Mã cá biệt</label><input type="text" id="uniqueId" name="uniqueId" required></div>
            <div class="form-group"><label>Mã nhân viên</label><input type="text" id="staffId" name="staffId" required></div>
            <div class="form-group"><label>Ngày mượn</label><input type="date" id="borrowDate" name="borrowDate" required></div>
            <div class="form-group"><label>Kiểu mượn</label>
                <select id="borrowType" name="borrowType" required>
                    <option value="">--Chọn--</option>
                    <option value="Mượn tại chỗ">Mượn tại chỗ</option>
                    <option value="Mượn về nhà">Mượn về nhà</option>
                </select>
            </div>
            <div class="form-group"><label>Hạn trả</label><input type="date" id="dueDate" name="dueDate" required></div>
            <div class="form-group"><label>Ngày trả</label><input type="date" id="returnDate" name="returnDate"></div>
            <div class="form-buttons">
                <button type="button" class="cancel" onclick="closeBorrowForm()">Hủy</button>
                <button type="submit" class="submit">Lưu</button>
            </div>
        </div>
    </form>

    <script>
        let editingRow = null;

        function openBorrowForm(mode, el) {
            document.getElementById("borrowFormPopup").style.display = "flex";
            if (mode === 'edit') {
                document.getElementById("action").value = "edit";
                editingRow = el.closest("tr");
                const cells = editingRow.getElementsByTagName("td");
                document.getElementById("borrowId").value = cells[0].innerText;
                document.getElementById("cardId").value = cells[1].innerText;
                document.getElementById("uniqueId").value = cells[2].innerText;
                document.getElementById("staffId").value = cells[3].innerText;
                document.getElementById("borrowDate").value = cells[4].innerText;
                document.getElementById("borrowType").value = cells[5].innerText;
                document.getElementById("dueDate").value = cells[6].innerText;
                document.getElementById("returnDate").value = cells[7].innerText;
            } else {
                document.getElementById("action").value = "add";
                editingRow = null;
                document.querySelectorAll("#borrowFormPopup input, #borrowFormPopup select").forEach(el => {
                    if (el.id !== 'action') el.value = "";
                });
                // Auto-generate new borrow ID
                const lastId = "<?php 
                    if (!empty($borrows)) {
                        $last = end($borrows);
                        echo $last['MAMUONTRA'];
                    } else {
                        echo 'MT000';
                    }
                ?>";
                const nextNum = parseInt(lastId.substring(2)) + 1;
                document.getElementById("borrowId").value = "MT" + nextNum.toString().padStart(3, '0');
                // Default borrow date to today
                document.getElementById("borrowDate").valueAsDate = new Date();
            }
        }

        function closeBorrowForm() {
            document.getElementById("borrowFormPopup").style.display = "none";
        }

        function deleteBorrow(borrowId) {
            if (confirm("Bạn có chắc chắn muốn xóa phiếu trả này?")) {
                window.location.href = "quanlyphieutra.php?delete=" + borrowId;
            }
        }

        function searchBorrow() {
            const filter = document.getElementById("searchBorrow").value.toUpperCase();
            const rows = document.querySelectorAll("#borrowTable tbody tr");
            rows.forEach(row => {
                const cells = row.getElementsByTagName("td");
                const match = Array.from(cells).slice(0, 8).some(td =>
                    td.textContent.toUpperCase().includes(filter)
                );
                row.style.display = match ? "" : "none";
            });
        }
    </script>
</body>

</html>