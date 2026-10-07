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

// Xử lý yêu cầu POST (thêm, sửa, xóa)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['action'])) {
        switch ($data['action']) {
            case 'add':
                addReceipt($conn, $data);
                break;
            case 'edit':
                editReceipt($conn, $data);
                break;
            case 'delete':
                deleteReceipt($conn, $data['mapn']);
                break;
        }
    }
    exit;
}

// Hàm thêm phiếu nhập
function addReceipt($conn, $data)
{
    try {
        $stmt = $conn->prepare("INSERT INTO PHIEUNHAP (MAPN, MATL, NGAYNHAP, SOLUONG, MANV, MANCC)
                                VALUES (:mapn, :matl, :ngaynhap, :soluong, :manv, :mancc)");
        $stmt->execute([
            ':mapn' => $data['mapn'],
            ':matl' => $data['matl'],
            ':ngaynhap' => $data['ngaynhap'],
            ':soluong' => $data['soluong'],
            ':manv' => $data['manv'],
            ':mancc' => $data['mancc']
        ]);
        echo json_encode(['success' => true, 'message' => 'Thêm phiếu nhập thành công']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}

// Hàm sửa phiếu nhập
function editReceipt($conn, $data)
{
    try {
        $stmt = $conn->prepare("UPDATE PHIEUNHAP SET 
                                MATL = :matl,
                                NGAYNHAP = :ngaynhap,
                                SOLUONG = :soluong,
                                MANV = :manv,
                                MANCC = :mancc
                                WHERE MAPN = :mapn");
        $stmt->execute([
            ':matl' => $data['matl'],
            ':ngaynhap' => $data['ngaynhap'],
            ':soluong' => $data['soluong'],
            ':manv' => $data['manv'],
            ':mancc' => $data['mancc'],
            ':mapn' => $data['mapn']
        ]);
        echo json_encode(['success' => true, 'message' => 'Cập nhật phiếu nhập thành công']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}

// Hàm xóa phiếu nhập
function deleteReceipt($conn, $mapn)
{
    try {
        $stmt = $conn->prepare("DELETE FROM PHIEUNHAP WHERE MAPN = :mapn");
        $stmt->execute([':mapn' => $mapn]);
        echo json_encode(['success' => true, 'message' => 'Xóa phiếu nhập thành công']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}

// Hàm lấy danh sách phiếu nhập
function getAllReceipts($conn)
{
    $stmt = $conn->query("SELECT * FROM PHIEUNHAP ORDER BY NGAYNHAP DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$receipts = getAllReceipts($conn);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <title>Hệ thống quản lý thư viện - Quản lý phiếu nhập</title>
    <style>
        /* Giữ nguyên toàn bộ CSS từ file quanlytailieu.php */
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
            width: 500px;
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
       
        <a href="quanlyphieumuon.php"><i class="fas fa-exchange-alt"></i> Mượn trả tài liệu</a>
        <a href="baocaodocgiamuontailieu.php"><i class="fas fa-chart-bar"></i> Thống kê báo cáo</a>
    </div>

    <div class="main-content" id="receipt-management">
        <h2 class="page-title">Quản lý Phiếu nhập</h2>

        <div class="sub-menu">
            <a href="quanlytailieu.php"><i class="fas fa-book"></i> Danh sách tài liệu</a>
            <a href="tailieuchitiet.php"><i class="fas fa-file"></i> Tài liệu chi tiết</a>
            <a href="qlphieunhap.php" class="active"><i class="fas fa-receipt"></i> Quản lý phiếu nhập</a>
        </div>

        <div class="toolbar">
            <div class="action-buttons">
                <button onclick="openReceiptForm('add')"><i class="fas fa-plus"></i> Thêm</button>
            </div>
            <div class="search-box">
                <input type="text" id="searchReceipt" placeholder="Tìm kiếm...">
                <button onclick="searchReceipt()"><i class="fas fa-search"></i></button>
            </div>
        </div>
        <table id="receiptTable">
            <thead>
                <tr>
                    <th>Mã phiếu nhập</th>
                    <th>Mã tài liệu</th>
                    <th>Ngày nhập</th>
                    <th>Số lượng</th>
                    <th>Mã nhân viên</th>
                    <th>Mã nhà cung cấp</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($receipts as $receipt): ?>
                    <tr>
                        <td><?= htmlspecialchars($receipt['MAPN']) ?></td>
                        <td><?= htmlspecialchars($receipt['MATL']) ?></td>
                        <td><?= htmlspecialchars($receipt['NGAYNHAP']) ?></td>
                        <td><?= htmlspecialchars($receipt['SOLUONG']) ?></td>
                        <td><?= htmlspecialchars($receipt['MANV']) ?></td>
                        <td><?= htmlspecialchars($receipt['MANCC']) ?></td>
                        <td class="actions-column">
                            <button onclick="openReceiptForm('edit', this)"><i class="fas fa-edit"></i> Sửa</button>
                            <button onclick="confirmDelete('<?= $receipt['MAPN'] ?>')"><i class="fas fa-trash"></i> Xóa</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="popup" id="receiptForm">
        <div class="popup-content">
            <h3 id="formTitle">Thông tin phiếu nhập</h3>
            <form id="receiptFormElement" onsubmit="submitReceiptForm(event)">
                <input type="hidden" id="formAction" name="action" value="add">
                
                <div class="form-group">
                    <label>Mã phiếu nhập</label>
                    <input type="text" id="receiptId" name="mapn" required>
                </div>
                
                <div class="form-group">
                    <label>Mã tài liệu</label>
                    <input type="text" id="docId" name="matl" required>
                </div>
                
                <div class="form-group">
                    <label>Ngày nhập</label>
                    <input type="date" id="receiptDate" name="ngaynhap" required>
                </div>
                
                <div class="form-group">
                    <label>Số lượng</label>
                    <input type="number" id="receiptQuantity" name="soluong" required min="1">
                </div>
                
                <div class="form-group">
                    <label>Mã nhân viên</label>
                    <input type="text" id="staffId" name="manv" required>
                </div>
                
                <div class="form-group">
                    <label>Mã nhà cung cấp</label>
                    <input type="text" id="supplierId" name="mancc" required>
                </div>
                
                <div class="form-buttons">
                    <button type="button" class="cancel" onclick="closeReceiptForm()">Hủy</button>
                    <button type="submit" class="submit">Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let editingRow = null;
        let currentAction = 'add';

        // Mở form thêm/sửa
        function openReceiptForm(mode, el) {
            document.getElementById("receiptForm").style.display = "flex";
            document.getElementById("formTitle").textContent = mode === 'edit' ? 'Sửa thông tin phiếu nhập' : 'Thêm phiếu nhập';
            document.getElementById("formAction").value = mode;

            // Đặt ngày mặc định là hôm nay
            document.getElementById("receiptDate").valueAsDate = new Date();

            if (mode === 'edit') {
                currentAction = 'edit';
                editingRow = el.closest("tr");
                const cells = editingRow.querySelectorAll("td");
                
                // Điền dữ liệu từ hàng vào form
                document.getElementById("receiptId").value = cells[0].textContent;
                document.getElementById("receiptId").readOnly = true; // Không cho sửa mã khi edit
                document.getElementById("docId").value = cells[1].textContent;
                document.getElementById("receiptDate").value = cells[2].textContent;
                document.getElementById("receiptQuantity").value = cells[3].textContent;
                document.getElementById("staffId").value = cells[4].textContent;
                document.getElementById("supplierId").value = cells[5].textContent;
            } else {
                currentAction = 'add';
                editingRow = null;
                document.getElementById("receiptFormElement").reset();
                document.getElementById("receiptId").readOnly = false;
                
                // Sinh mã phiếu nhập mới
                const lastId = document.querySelector("#receiptTable tbody tr:last-child td:first-child")?.innerText || "PN0000";
                const nextNum = parseInt(lastId.substring(2)) + 1;
                document.getElementById("receiptId").value = "PN" + nextNum.toString().padStart(3, '0');
                
                document.getElementById("receiptDate").valueAsDate = new Date();
            }
        }

        // Đóng form
        function closeReceiptForm() {
            document.getElementById("receiptForm").style.display = "none";
        }

        // Gửi form
        function submitReceiptForm(event) {
            event.preventDefault();
            
            const formElement = document.getElementById("receiptFormElement");
            const formData = new FormData(formElement);
            const data = Object.fromEntries(formData.entries());

            // Validate
            if (!data.mapn) {
                alert("Vui lòng nhập mã phiếu nhập");
                return;
            }
            if (!data.matl) {
                alert("Vui lòng nhập mã tài liệu");
                return;
            }
            if (!data.ngaynhap) {
                alert("Vui lòng chọn ngày nhập");
                return;
            }
            if (!data.soluong || data.soluong <= 0) {
                alert("Số lượng phải lớn hơn 0");
                return;
            }
            if (!data.manv) {
                alert("Vui lòng nhập mã nhân viên");
                return;
            }
            if (!data.mancc) {
                alert("Vui lòng nhập mã nhà cung cấp");
                return;
            }

            fetch("qlphieunhap.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(data)
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
                    console.error("Lỗi:", error);
                    alert("Có lỗi xảy ra: " + error.message);
                });
        }

        // Xác nhận xóa
        function confirmDelete(mapn) {
            if (confirm("Bạn có chắc chắn muốn xóa phiếu nhập này?")) {
                fetch("qlphieunhap.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            mapn: mapn
                        })
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
                        console.error("Lỗi:", error);
                        alert("Có lỗi xảy ra khi xóa");
                    });
            }
        }

        // Tìm kiếm
        function searchReceipt() {
            const filter = document.getElementById("searchReceipt").value.toUpperCase();
            const rows = document.querySelectorAll("#receiptTable tbody tr");

            rows.forEach(row => {
                const cells = row.getElementsByTagName("td");
                let showRow = false;

                for (let i = 0; i < cells.length - 1; i++) {
                    if (cells[i].textContent.toUpperCase().includes(filter)) {
                        showRow = true;
                        break;
                    }
                }

                row.style.display = showRow ? "" : "none";
            });
        }

        // Hiển thị thông báo thành công
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