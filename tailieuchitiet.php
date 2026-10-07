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
                addDocumentDetail($conn, $data);
                break;
            case 'edit':
                editDocumentDetail($conn, $data);
                break;
            case 'delete':
                deleteDocumentDetail($conn, $data['macabiet']);
                break;
        }
    }
    exit;
}

// Hàm thêm tài liệu chi tiết
function addDocumentDetail($conn, $data)
{
    try {
        $stmt = $conn->prepare("INSERT INTO TAILIEUCHITIET (MACABIET, MATAILIEU, NGAYCN, TINHTRANG, MANV)
                                VALUES (:macabiet, :matailieu, :ngaycn, :tinhtrang, :manv)");
        $stmt->execute([
            ':macabiet' => $data['macabiet'],
            ':matailieu' => $data['matailieu'],
            ':ngaycn' => $data['ngaycn'],
            ':tinhtrang' => $data['tinhtrang'],
            ':manv' => $data['manv']
        ]);
        echo json_encode(['success' => true, 'message' => 'Thêm tài liệu chi tiết thành công']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}

// Hàm sửa tài liệu chi tiết
function editDocumentDetail($conn, $data)
{
    try {
        $stmt = $conn->prepare("UPDATE TAILIEUCHITIET SET 
                                MATAILIEU = :matailieu,
                                NGAYCN = :ngaycn,
                                TINHTRANG = :tinhtrang,
                                MANV = :manv
                                WHERE MACABIET = :macabiet");
        $stmt->execute([
            ':matailieu' => $data['matailieu'],
            ':ngaycn' => $data['ngaycn'],
            ':tinhtrang' => $data['tinhtrang'],
            ':manv' => $data['manv'],
            ':macabiet' => $data['macabiet']
        ]);
        echo json_encode(['success' => true, 'message' => 'Cập nhật tài liệu chi tiết thành công']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}

// Hàm xóa tài liệu chi tiết
function deleteDocumentDetail($conn, $macabiet)
{
    try {
        $stmt = $conn->prepare("DELETE FROM TAILIEUCHITIET WHERE MACABIET = :macabiet");
        $stmt->execute([':macabiet' => $macabiet]);
        echo json_encode(['success' => true, 'message' => 'Xóa tài liệu chi tiết thành công']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}

// Hàm lấy danh sách tài liệu chi tiết (chỉ lấy các trường từ TAILIEUCHITIET)
function getAllDocumentDetails($conn)
{
    $stmt = $conn->query("SELECT * FROM TAILIEUCHITIET");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Hàm lấy danh sách tài liệu để chọn
function getAllDocuments($conn)
{
    $stmt = $conn->query("SELECT MATAILIEU, TENTAILIEU FROM TAILIEU");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Hàm lấy danh sách nhân viên để chọn
function getAllStaff($conn)
{
    $stmt = $conn->query("SELECT MANV, TENNV FROM NHANVIEN");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$documentDetails = getAllDocumentDetails($conn);
$documents = getAllDocuments($conn);
$staff = getAllStaff($conn);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <title>Hệ thống quản lý thư viện - Tài liệu chi tiết</title>
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

    <div class="main-content" id="document-detail-management">
        <h2 class="page-title">Quản lý Tài liệu chi tiết</h2>

        <div class="sub-menu">
            <a href="quanlytailieu.php"><i class="fas fa-book"></i> Danh sách tài liệu</a>
            <a href="tailieuchitiet.php" class="active"><i class="fas fa-file"></i> Tài liệu chi tiết</a>
            <a href="qlphieunhap.php"><i class="fas fa-receipt"></i> Quản lý phiếu nhập</a>
        </div>

        <div class="toolbar">
            <div class="action-buttons">
                <button onclick="openDetailForm('add')"><i class="fas fa-plus"></i> Thêm</button>
            </div>
            <div class="search-box">
                <input type="text" id="searchDetail" placeholder="Tìm kiếm...">
                <button onclick="searchDetail()"><i class="fas fa-search"></i></button>
            </div>
        </div>
        <table id="detailTable">
            <thead>
                <tr>
                    <th>Mã cá biệt</th>
                    <th>Mã tài liệu</th>
                    <th>Ngày cập nhật</th>
                    <th>Tình trạng</th>
                    <th>Mã NV</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documentDetails as $detail): ?>
                    <tr>
                        <td><?= htmlspecialchars($detail['MACABIET']) ?></td>
                        <td><?= htmlspecialchars($detail['MATAILIEU']) ?></td>
                        <td><?= htmlspecialchars($detail['NGAYCN']) ?></td>
                        <td><?= htmlspecialchars($detail['TINHTRANG']) ?></td>
                        <td><?= htmlspecialchars($detail['MANV']) ?></td>
                        <td class="actions-column">
                            <button onclick="openDetailForm('edit', this)"><i class="fas fa-edit"></i> Sửa</button>
                            <button onclick="confirmDelete('<?= $detail['MACABIET'] ?>')"><i class="fas fa-trash"></i> Xóa</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="popup" id="detailForm">
        <div class="popup-content">
            <h3 id="formTitle">Thông tin tài liệu chi tiết</h3>
            <form id="detailFormElement" onsubmit="submitDetailForm(event)">
                <input type="hidden" id="formAction" name="action" value="add">
                <input type="hidden" id="originalId" name="originalId" value="">

                <div class="form-group">
                    <label>Mã cá biệt</label>
                    <input type="text" id="detailId" name="macabiet" required>
                </div>

                <div class="form-group">
                    <label>Mã tài liệu</label>
                    <select id="documentSelect" name="matailieu" required>
                        <option value="">-- Chọn tài liệu --</option>
                        <?php foreach ($documents as $doc): ?>
                            <option value="<?= htmlspecialchars($doc['MATAILIEU']) ?>"><?= htmlspecialchars($doc['MATAILIEU']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Ngày cập nhật</label>
                    <input type="date" id="detailUpdateDate" name="ngaycn" required>
                </div>

                <div class="form-group">
                    <label>Tình trạng</label>
                    <input type="text" id="detailStatus" name="tinhtrang" required>
                </div>

                <div class="form-group">
                    <label>Nhân viên quản lý</label>
                    <select id="staffSelect" name="manv" required>
                        <option value="">-- Chọn nhân viên --</option>
                        <?php foreach ($staff as $emp): ?>
                            <option value="<?= htmlspecialchars($emp['MANV']) ?>"><?= htmlspecialchars($emp['MANV']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-buttons">
                    <button type="button" class="cancel" onclick="closeDetailForm()">Hủy</button>
                    <button type="submit" class="submit">Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Danh sách mã cá biệt hiện có
        let existingIds = <?= json_encode(array_column($documentDetails, 'MACABIET')) ?>;
        
        // Hàm mở form thêm/sửa
        function openDetailForm(mode, el) {
            document.getElementById("detailForm").style.display = "flex";
            document.getElementById("formTitle").textContent = mode === 'edit' ? 'Sửa thông tin tài liệu chi tiết' : 'Thêm tài liệu chi tiết';
            document.getElementById("formAction").value = mode;

            if (mode === 'edit') {
                currentAction = 'edit';
                editingRow = el.closest("tr");
                const cells = editingRow.querySelectorAll("td");
                
                // Điền dữ liệu từ hàng vào form
                document.getElementById("originalId").value = cells[0].textContent;
                document.getElementById("detailId").value = cells[0].textContent;
                document.getElementById("documentSelect").value = cells[1].textContent;
                document.getElementById("detailUpdateDate").value = cells[2].textContent;
                document.getElementById("detailStatus").value = cells[3].textContent;
                document.getElementById("staffSelect").value = cells[4].textContent;
            } else {
                currentAction = 'add';
                editingRow = null;
                document.getElementById("detailFormElement").reset();
                document.getElementById("originalId").value = "";
                document.getElementById("detailUpdateDate").valueAsDate = new Date();
            }
        }

        // Hàm kiểm tra trùng mã cá biệt
        function isDuplicateId(id, originalId = '') {
            return existingIds.includes(id) && id !== originalId;
        }

        // Hàm gửi form
        function submitDetailForm(event) {
            event.preventDefault();
            
            const formElement = document.getElementById("detailFormElement");
            const formData = new FormData(formElement);
            const data = Object.fromEntries(formData.entries());
            const originalId = document.getElementById("originalId").value;
            
            // Kiểm tra trùng mã cá biệt
            if (isDuplicateId(data.macabiet, originalId)) {
                alert("Mã cá biệt đã tồn tại. Vui lòng nhập mã khác.");
                return;
            }

            fetch("tailieuchitiet.php", {
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

        // Đóng form
        function closeDetailForm() {
            document.getElementById("detailForm").style.display = "none";
        }

        // Xác nhận xóa
        function confirmDelete(macabiet) {
            if (confirm("Bạn có chắc chắn muốn xóa tài liệu chi tiết này?")) {
                fetch("tailieuchitiet.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            macabiet: macabiet
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

        // Hiển thị thông báo thành công
        function showSuccess(message) {
            const note = document.createElement("div");
            note.className = "notification";
            note.innerHTML = `<i class='fas fa-check-circle'></i> ${message}`;
            document.body.appendChild(note);
            setTimeout(() => note.remove(), 3000);
        }

        // Tìm kiếm
        function searchDetail() {
            const filter = document.getElementById("searchDetail").value.toUpperCase();
            const rows = document.querySelectorAll("#detailTable tbody tr");

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
    </script>
</body>

</html>