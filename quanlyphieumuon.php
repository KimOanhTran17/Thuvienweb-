<?php
// Kết nối CSDL
$host = 'localhost';
$dbname = 'thuvien';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Kết nối CSDL thất bại: " . $e->getMessage());
}

// Xử lý các thao tác CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['action'])) {
        switch ($data['action']) {
            case 'add':
                addBorrow($conn, $data);
                break;
            case 'edit':
                editBorrow($conn, $data);
                break;
            case 'delete':
                deleteBorrow($conn, $data['mamuontra']);
                break;
        }
    }
    exit;
}

// Hàm thêm phiếu mượn
function addBorrow($conn, $data)
{
    try {
        $stmt = $conn->prepare("INSERT INTO MUONTRA_TL 
                              (MAMUONTRA, SOTHE, MACABIET, MANV, NGAYMUON, KIEUMUON, HANTRA) 
                              VALUES 
                              (:mamuontra, :sothe, :macabiet, :manv, :ngaymuon, :kieumuon, :hantra)");

        $stmt->execute([
            ':mamuontra' => $data['mamuontra'],
            ':sothe' => $data['sothe'],
            ':macabiet' => $data['macabiet'],
            ':manv' => $data['manv'],
            ':ngaymuon' => $data['ngaymuon'],
            ':kieumuon' => $data['kieumuon'],
            ':hantra' => $data['hantra']
        ]);

        echo json_encode(['success' => true, 'message' => 'Thêm phiếu mượn thành công']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}

// Hàm sửa phiếu mượn
function editBorrow($conn, $data)
{
    try {
        $stmt = $conn->prepare("UPDATE MUONTRA_TL SET 
                              SOTHE = :sothe, 
                              MACABIET = :macabiet, 
                              MANV = :manv, 
                              NGAYMUON = :ngaymuon, 
                              KIEUMUON = :kieumuon, 
                              HANTRA = :hantra 
                              WHERE MAMUONTRA = :mamuontra");

        $stmt->execute([
            ':mamuontra' => $data['mamuontra'],
            ':sothe' => $data['sothe'],
            ':macabiet' => $data['macabiet'],
            ':manv' => $data['manv'],
            ':ngaymuon' => $data['ngaymuon'],
            ':kieumuon' => $data['kieumuon'],
            ':hantra' => $data['hantra']
        ]);

        echo json_encode(['success' => true, 'message' => 'Cập nhật phiếu mượn thành công']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}

// Hàm xóa phiếu mượn
function deleteBorrow($conn, $mamuontra)
{
    try {
        $stmt = $conn->prepare("DELETE FROM MUONTRA_TL WHERE MAMUONTRA = :mamuontra");
        $stmt->execute([':mamuontra' => $mamuontra]);
        echo json_encode(['success' => true, 'message' => 'Xóa phiếu mượn thành công']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}

// Lấy danh sách phiếu mượn từ CSDL
function getBorrows($conn)
{
    $stmt = $conn->query("SELECT * FROM MUONTRA_TL");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$borrows = getBorrows($conn);
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
            background-color: #2ecc71;
            color: white;
            padding: 12px 20px;
            border-radius: 6px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            z-index: 2000;
            animation: fadeOut 3s forwards;
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
            <a href="quanlyphieumuon.php" class="active"><i class="fas fa-file-invoice"></i> Quản lý phiếu mượn</a>
            <a href="quanlyphieutra.php"><i class="fas fa-file-invoice-dollar"></i> Quản lý phiếu trả</a>
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
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($borrows as $borrow): ?>
                    <tr>
                        <td><?= htmlspecialchars($borrow['MAMUONTRA']) ?></td>
                        <td><?= htmlspecialchars($borrow['SOTHE']) ?></td>
                        <td><?= htmlspecialchars($borrow['MACABIET']) ?></td>
                        <td><?= htmlspecialchars($borrow['MANV']) ?></td>
                        <td><?= htmlspecialchars($borrow['NGAYMUON']) ?></td>
                        <td><?= htmlspecialchars($borrow['KIEUMUON']) ?></td>
                        <td><?= htmlspecialchars($borrow['HANTRA']) ?></td>
                        <td class="actions-column">
                            <button onclick="openBorrowForm('edit', this)">Sửa</button>
                            <button onclick="confirmDelete('<?= $borrow['MAMUONTRA'] ?>', this)">Xóa</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="popup" id="borrowForm">
        <div class="popup-content">
            <h3 id="formTitle">Thông tin phiếu mượn</h3>
            <div class="form-group"><label>Mã mượn trả</label><input type="text" id="borrowId" required></div>
            <div class="form-group"><label>Số thẻ</label><input type="text" id="cardId" required></div>
            <div class="form-group"><label>Mã cá biệt</label><input type="text" id="uniqueId" required></div>
            <div class="form-group"><label>Mã nhân viên</label><input type="text" id="staffId" required></div>
            <div class="form-group"><label>Ngày mượn</label><input type="date" id="borrowDate" required></div>
            <div class="form-group"><label>Kiểu mượn</label>
                <select id="borrowType" required>
                    <option value="">--Chọn--</option>
                    <option value="Mượn tại chỗ">Mượn tại chỗ</option>
                    <option value="Mượn về nhà">Mượn về nhà</option>
                </select>
            </div>
            <div class="form-group"><label>Hạn trả</label><input type="date" id="dueDate" required></div>

            <div class="form-buttons">
                <button class="cancel" onclick="closeBorrowForm()">Hủy</button>
                <button class="submit" onclick="submitBorrowForm()">Lưu</button>
            </div>
        </div>
    </div>

    <script>
        let editingRow = null;
        let currentAction = 'add';

        function openBorrowForm(mode, el) {
            document.getElementById("borrowForm").style.display = "flex";
            document.getElementById("formTitle").textContent = mode === 'edit' ? 'Sửa thông tin phiếu mượn' : 'Thêm phiếu mượn';

            if (mode === 'edit') {
                currentAction = 'edit';
                editingRow = el.closest("tr");
                const cells = editingRow.getElementsByTagName("td");
                document.getElementById("borrowId").value = cells[0].innerText;
                document.getElementById("cardId").value = cells[1].innerText;
                document.getElementById("uniqueId").value = cells[2].innerText;
                document.getElementById("staffId").value = cells[3].innerText;
                document.getElementById("borrowDate").value = cells[4].innerText;
                document.getElementById("borrowType").value = cells[5].innerText;
                document.getElementById("dueDate").value = cells[6].innerText;
            } else {
                currentAction = 'add';
                editingRow = null;
                document.querySelectorAll("#borrowForm input, #borrowForm select").forEach(el => el.value = "");

                // Mặc định ngày mượn là hôm nay
                document.getElementById("borrowDate").valueAsDate = new Date();
            }
        }

        function closeBorrowForm() {
            document.getElementById("borrowForm").style.display = "none";
        }

        function submitBorrowForm() {
            const borrowData = {
                action: currentAction,
                mamuontra: document.getElementById("borrowId").value.trim(),
                sothe: document.getElementById("cardId").value.trim(),
                macabiet: document.getElementById("uniqueId").value.trim(),
                manv: document.getElementById("staffId").value.trim(),
                ngaymuon: document.getElementById("borrowDate").value,
                kieumuon: document.getElementById("borrowType").value,
                hantra: document.getElementById("dueDate").value
            };

            // Validate required fields
            let isValid = true;
            if (!borrowData.mamuontra) {
                alert("Vui lòng nhập mã mượn trả");
                isValid = false;
            } else if (!/^[A-Za-z0-9]+$/.test(borrowData.mamuontra)) {
                alert("Mã mượn trả chỉ được chứa chữ cái và số");
                isValid = false;
            }
            if (!borrowData.sothe) {
                alert("Vui lòng nhập số thẻ");
                isValid = false;
            }
            if (!borrowData.macabiet) {
                alert("Vui lòng nhập mã cá biệt");
                isValid = false;
            }
            if (!borrowData.manv) {
                alert("Vui lòng nhập mã nhân viên");
                isValid = false;
            }
            if (!borrowData.ngaymuon) {
                alert("Vui lòng chọn ngày mượn");
                isValid = false;
            }
            if (!borrowData.kieumuon) {
                alert("Vui lòng chọn kiểu mượn");
                isValid = false;
            }
            if (!borrowData.hantra) {
                alert("Vui lòng chọn hạn trả");
                isValid = false;
            }

            if (!isValid) return;

            fetch("quanlyphieumuon.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(borrowData)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        showSuccess(result.message);
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        alert("Lỗi: " + result.message);
                    }
                })
                .catch(error => {
                    console.error("Lỗi gửi dữ liệu:", error);
                    alert("Gặp lỗi khi gửi dữ liệu: " + error.message);
                });
        }

        function confirmDelete(mamuontra, el) {
            if (confirm("Bạn có chắc chắn muốn xóa phiếu mượn này?")) {
                fetch("quanlyphieumuon.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            mamuontra: mamuontra
                        })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            showSuccess(result.message);
                            el.closest("tr").remove();
                        } else {
                            alert("Lỗi: " + result.message);
                        }
                    })
                    .catch(error => {
                        console.error("Lỗi xóa dữ liệu:", error);
                        alert("Gặp lỗi khi xóa dữ liệu: " + error.message);
                    });
            }
        }

        function searchBorrow() {
            const filter = document.getElementById("searchBorrow").value.toUpperCase();
            const rows = document.querySelectorAll("#borrowTable tbody tr");
            rows.forEach(row => {
                const cells = row.getElementsByTagName("td");
                const match = Array.from(cells).slice(0, 7).some(td =>
                    td.textContent.toUpperCase().includes(filter)
                );
                row.style.display = match ? "" : "none";
            });
        }

        function showSuccess(message) {
            const note = document.createElement("div");
            note.className = "notification";
            note.innerHTML = `<i class='fas fa-check-circle'></i> ${message}`;
            document.body.appendChild(note);
            setTimeout(() => note.remove(), 3000);
        }
    </script>
</body>

</html>