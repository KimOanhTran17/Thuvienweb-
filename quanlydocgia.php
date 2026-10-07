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
                addReader($conn, $data);
                break;
            case 'edit':
                editReader($conn, $data);
                break;
            case 'delete':
                deleteReader($conn, $data['sothe']);
                break;
        }
    }
    exit;
}

// Hàm thêm độc giả
function addReader($conn, $data) {
    try {
        $stmt = $conn->prepare("INSERT INTO DOCGIA (SOTHE, HOTEN, NGAYSINH, GIOITINH, CHUCDANH, NGAYCN, MALOP) 
                               VALUES (:sothe, :hoten, :ngaysinh, :gioitinh, :chucdanh, :ngaycn, :malop)");
        $stmt->execute([
            ':sothe' => $data['sothe'],
            ':hoten' => $data['hoten'],
            ':ngaysinh' => $data['ngaysinh'],
            ':gioitinh' => $data['gioitinh'],
            ':chucdanh' => $data['chucdanh'],
            ':ngaycn' => $data['ngaycapnhat'],
            ':malop' => $data['malop']
        ]);
        echo json_encode(['success' => true, 'message' => 'Thêm độc giả thành công']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}

// Hàm sửa độc giả
function editReader($conn, $data) {
    try {
        $stmt = $conn->prepare("UPDATE DOCGIA SET 
                               HOTEN = :hoten, 
                               NGAYSINH = :ngaysinh, 
                               GIOITINH = :gioitinh, 
                               CHUCDANH = :chucdanh, 
                               NGAYCN = :ngaycn, 
                               MALOP = :malop 
                               WHERE SOTHE = :sothe");
        $stmt->execute([
            ':sothe' => $data['sothe'],
            ':hoten' => $data['hoten'],
            ':ngaysinh' => $data['ngaysinh'],
            ':gioitinh' => $data['gioitinh'],
            ':chucdanh' => $data['chucdanh'],
            ':ngaycn' => $data['ngaycapnhat'],
            ':malop' => $data['malop']
        ]);
        echo json_encode(['success' => true, 'message' => 'Cập nhật độc giả thành công']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}

// Hàm xóa độc giả
function deleteReader($conn, $sothe) {
    try {
        $stmt = $conn->prepare("DELETE FROM DOCGIA WHERE SOTHE = :sothe");
        $stmt->execute([':sothe' => $sothe]);
        echo json_encode(['success' => true, 'message' => 'Xóa độc giả thành công']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}

// Lấy danh sách độc giả từ CSDL
function getReaders($conn) {
    $stmt = $conn->query("SELECT * FROM DOCGIA");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$readers = getReaders($conn);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <title>Hệ thống quản lý độc giả</title>
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
        <a href="quanlydocgia.php" class="active"><i class="fas fa-user-friends"></i> Độc giả</a>
        <a href="quanlytailieu.php"><i class="fas fa-book"></i> Tài liệu</a>
        <a href="quanlyphieumuon.php"><i class="fas fa-exchange-alt"></i> Mượn trả tài liệu</a>
        <a href="baocaodocgiamuontailieu.php"><i class="fas fa-chart-bar"></i> Thống kê báo cáo</a>
    </div>

    <div class="main-content" id="reader-management">
        <h2 class="page-title">Quản lý độc giả</h2>
        <div class="toolbar">
            <div class="action-buttons">
                <button onclick="openReaderForm('add')"><i class="fas fa-plus"></i> Thêm</button>
            </div>
            <div class="search-box">
                <input type="text" id="searchReader" placeholder="Tìm kiếm...">
                <button onclick="searchReader()"><i class="fas fa-search"></i></button>
            </div>
        </div>
        <table id="readerTable">
            <thead>
                <tr>
                    <th>Số thẻ</th>
                    <th>Họ tên</th>
                    <th>Giới tính</th>
                    <th>Ngày sinh</th>
                    <th>Chức danh</th>
                    <th>Ngày cập nhật</th>
                    <th>Mã lớp</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($readers as $reader): ?>
                <tr>
                    <td><?= htmlspecialchars($reader['SOTHE']) ?></td>
                    <td><?= htmlspecialchars($reader['HOTEN']) ?></td>
                    <td><?= htmlspecialchars($reader['GIOITINH']) ?></td>
                    <td><?= htmlspecialchars($reader['NGAYSINH']) ?></td>
                    <td><?= htmlspecialchars($reader['CHUCDANH']) ?></td>
                    <td><?= htmlspecialchars($reader['NGAYCN']) ?></td>
                    <td><?= htmlspecialchars($reader['MALOP']) ?></td>
                    <td class="actions-column">
                        <button onclick="openReaderForm('edit', this)">Sửa</button>
                        <button onclick="confirmDelete('<?= $reader['SOTHE'] ?>', this)">Xóa</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="popup" id="readerForm">
        <div class="popup-content">
            <h3>Thông tin độc giả</h3>
            <div class="form-group"><label>Số thẻ</label><input type="text" id="cardId"></div>
            <div class="form-group"><label>Họ tên</label><input type="text" id="readerName"></div>
            <div class="form-group"><label>Giới tính</label>
                <select id="readerGender">
                    <option value="">--Chọn--</option>
                    <option value="Nam">Nam</option>
                    <option value="Nữ">Nữ</option>
                </select>
            </div>
            <div class="form-group"><label>Ngày sinh</label><input type="date" id="birthDate"></div>
            <div class="form-group"><label>Chức danh</label><input type="text" id="readerPosition"></div>
            <div class="form-group"><label>Ngày cập nhật</label><input type="date" id="updateDate"></div>
            <div class="form-group"><label>Mã lớp</label><input type="text" id="classCode"></div>
            <div class="form-buttons">
                <button class="cancel" onclick="closeReaderForm()">Hủy</button>
                <button class="submit" onclick="submitReaderForm()">Lưu</button>
            </div>
        </div>
    </div>

    <script>
        let editingRow = null;
        let currentAction = 'add';

        function openReaderForm(mode, el) {
            document.getElementById("readerForm").style.display = "flex";
            currentAction = mode;
            
            if (mode === 'edit') {
                editingRow = el.closest("tr");
                const cells = editingRow.getElementsByTagName("td");
                document.getElementById("cardId").value = cells[0].innerText;
                document.getElementById("readerName").value = cells[1].innerText;
                document.getElementById("readerGender").value = cells[2].innerText;
                document.getElementById("birthDate").value = cells[3].innerText;
                document.getElementById("readerPosition").value = cells[4].innerText;
                document.getElementById("updateDate").value = cells[5].innerText;
                document.getElementById("classCode").value = cells[6].innerText;
                
                // Disable editing card ID
                document.getElementById("cardId").readOnly = true;
            } else {
                editingRow = null;
                document.querySelectorAll("#readerForm input, #readerForm select").forEach(el => el.value = "");
                document.getElementById("cardId").readOnly = false;
            }
        }

        function closeReaderForm() {
            document.getElementById("readerForm").style.display = "none";
        }

        function submitReaderForm() {
            const readerData = {
                action: currentAction,
                sothe: document.getElementById("cardId").value.trim(),
                hoten: document.getElementById("readerName").value.trim(),
                gioitinh: document.getElementById("readerGender").value,
                ngaysinh: document.getElementById("birthDate").value,
                chucdanh: document.getElementById("readerPosition").value.trim(),
                ngaycapnhat: document.getElementById("updateDate").value,
                malop: document.getElementById("classCode").value.trim()
            };

            // Validate required fields
            for (let key in readerData) {
                if (key !== 'action' && !readerData[key]) {
                    alert("Vui lòng điền đầy đủ thông tin (thiếu: " + key + ")");
                    return;
                }
            }

            fetch("quanlydocgia.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(readerData)
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

        function confirmDelete(sothe, el) {
            if (confirm("Bạn có chắc chắn muốn xóa độc giả này?")) {
                fetch("quanlydocgia.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ action: 'delete', sothe: sothe })
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

        function searchReader() {
            const filter = document.getElementById("searchReader").value.toUpperCase();
            const rows = document.querySelectorAll("#readerTable tbody tr");
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