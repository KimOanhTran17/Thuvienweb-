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
                addDocument($conn, $data);
                break;
            case 'edit':
                editDocument($conn, $data);
                break;
            case 'delete':
                deleteDocument($conn, $data['matailieu']);
                break;
        }
    }
    exit;
}

// Hàm thêm tài liệu
function addDocument($conn, $data)
{
    try {
        $stmt = $conn->prepare("INSERT INTO TAILIEU (MATAILIEU, TENTAILIEU, TENNN, VITRI, SOLUONG, NGAYCAPNHAT, SOLUONGCON, MATG, MANXB, MATL, MAPN)
                                VALUES (:matailieu, :tentailieu, :tennn, :vitri, :soluong, :ngaycapnhat, :soluongcon, :matg, :manxb, :matl, :mapn)");
        $stmt->execute([
            ':matailieu' => $data['matailieu'],
            ':tentailieu' => $data['tentailieu'],
            ':tennn' => $data['tennn'],
            ':vitri' => $data['vitri'],
            ':soluong' => $data['soluong'],
            ':ngaycapnhat' => $data['ngaycapnhat'],
            ':soluongcon' => $data['soluongcon'],
            ':matg' => $data['matg'],
            ':manxb' => $data['manxb'],
            ':matl' => $data['matl'],
            ':mapn' => $data['mapn']
        ]);
        echo json_encode(['success' => true, 'message' => 'Thêm tài liệu thành công']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}

// Hàm sửa tài liệu
function editDocument($conn, $data)
{
    try {
        $stmt = $conn->prepare("UPDATE TAILIEU SET 
                                TENTAILIEU = :tentailieu,
                                TENNN = :tennn,
                                VITRI = :vitri,
                                SOLUONG = :soluong,
                                NGAYCAPNHAT = :ngaycapnhat,
                                SOLUONGCON = :soluongcon,
                                MATG = :matg,
                                MANXB = :manxb,
                                MATL = :matl,
                                MAPN = :mapn
                                WHERE MATAILIEU = :matailieu");
        $stmt->execute([
            ':tentailieu' => $data['tentailieu'],
            ':tennn' => $data['tennn'],
            ':vitri' => $data['vitri'],
            ':soluong' => $data['soluong'],
            ':ngaycapnhat' => $data['ngaycapnhat'],
            ':soluongcon' => $data['soluongcon'],
            ':matg' => $data['matg'],
            ':manxb' => $data['manxb'],
            ':matl' => $data['matl'],
            ':mapn' => $data['mapn'],
            ':matailieu' => $data['matailieu']
        ]);
        echo json_encode(['success' => true, 'message' => 'Cập nhật tài liệu thành công']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}

// Hàm xóa tài liệu
function deleteDocument($conn, $matailieu)
{
    try {
        $stmt = $conn->prepare("DELETE FROM TAILIEU WHERE MATAILIEU = :matailieu");
        $stmt->execute([':matailieu' => $matailieu]);
        echo json_encode(['success' => true, 'message' => 'Xóa tài liệu thành công']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}

// Hàm lấy danh sách tài liệu
function getAllDocuments($conn)
{
    $stmt = $conn->query("SELECT * FROM TAILIEU");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$documents = getAllDocuments($conn);
?>


<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <title>Hệ thống quản lý thư viện</title>
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
            width: 700px;
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
        <a href="quanlytailieu.php" class="active"><i class="fas fa-book"></i> Tài liệu</a>
        <a href="quanlyphieumuon.php"><i class="fas fa-exchange-alt"></i> Mượn trả tài liệu</a>
        <a href="baocaodocgiamuontailieu.php"><i class="fas fa-chart-bar"></i> Thống kê báo cáo</a>
    </div>

    <div class="main-content" id="document-management">
        <h2 class="page-title">Quản lý Tài liệu</h2>

        <div class="sub-menu">
            <a href="quanlytailieu.php" class="active"><i class="fas fa-book"></i> Danh sách tài liệu</a>
            <a href="tailieuchitiet.php"><i class="fas fa-file"></i> Tài liệu chi tiết</a>
            <a href="qlphieunhap.php"><i class="fas fa-receipt"></i> Quản lý phiếu nhập</a>
        </div>

        <div class="toolbar">
            <div class="action-buttons">
                <button onclick="openDocumentForm('add')"><i class="fas fa-plus"></i> Thêm</button>
            </div>
            <div class="search-box">
                <input type="text" id="searchDocument" placeholder="Tìm kiếm...">
                <button onclick="searchDocument()"><i class="fas fa-search"></i></button>
            </div>
        </div>
        <table id="documentTable">
            <thead>
                <tr>
                    <th>Mã tài liệu</th>
                    <th>Tên tài liệu</th>
                    <th>Tên ngoại ngữ</th>
                    <th>Vị trí</th>
                    <th>Số lượng</th>
                    <th>Ngày cập nhật</th>
                    <th>Số lượng còn</th>
                    <th>Mã tác giả</th>
                    <th>Mã NXB</th>
                    <th>Mã thể loại</th>
                    <th>Mã phiếu nhập</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $doc): ?>
                    <tr>
                        <td><?= htmlspecialchars($doc['MATAILIEU']) ?></td>
                        <td><?= htmlspecialchars($doc['TENTAILIEU']) ?></td>
                        <td><?= htmlspecialchars($doc['TENNN']) ?></td>
                        <td><?= htmlspecialchars($doc['VITRI']) ?></td>
                        <td><?= htmlspecialchars($doc['SOLUONG']) ?></td>
                        <td><?= htmlspecialchars($doc['NGAYCAPNHAT']) ?></td>
                        <td><?= htmlspecialchars($doc['SOLUONGCON']) ?></td>
                        <td><?= htmlspecialchars($doc['MATG']) ?></td>
                        <td><?= htmlspecialchars($doc['MANXB']) ?></td>
                        <td><?= htmlspecialchars($doc['MATL']) ?></td>
                        <td><?= htmlspecialchars($doc['MAPN']) ?></td>
                        <td class="actions-column">
                            <button onclick="openDocumentForm('edit', this)"><i class="fas fa-edit"></i> Sửa</button>
                            <button onclick="confirmDelete('<?= $doc['MATAILIEU'] ?>')"><i class="fas fa-trash"></i> Xóa</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="popup" id="documentForm">
        <div class="popup-content">
            <h3 id="formTitle">Thông tin tài liệu</h3>
            <form id="docForm" onsubmit="submitDocumentForm(event)">
                <input type="hidden" id="formAction" name="action" value="add">
                <div class="form-group"><label>Mã tài liệu</label><input type="text" id="docId" name="matailieu" required></div>
                <div class="form-group"><label>Tên tài liệu</label><input type="text" id="docName" name="tentailieu" required></div>
                <div class="form-group"><label>Tên ngoại ngữ</label><input type="text" id="docLanguage" name="tennn" required></div>
                <div class="form-group"><label>Vị trí</label><input type="text" id="docLocation" name="vitri" required></div>
                <div class="form-group"><label>Số lượng</label><input type="number" id="docQuantity" name="soluong" required></div>
                <div class="form-group"><label>Ngày cập nhật</label><input type="date" id="docUpdateDate" name="ngaycapnhat" required></div>
                <div class="form-group"><label>Số lượng còn</label><input type="number" id="docRemaining" name="soluongcon" required></div>
                <div class="form-group"><label>Mã tác giả</label><input type="text" id="docAuthorId" name="matg" required></div>
                <div class="form-group"><label>Mã NXB</label><input type="text" id="docPublisherId" name="manxb" required></div>
                <div class="form-group"><label>Mã thể loại</label><input type="text" id="docCategoryId" name="matl" required></div>
                <div class="form-group"><label>Mã phiếu nhập</label><input type="text" id="docReceiptId" name="mapn" required></div>
                <div class="form-buttons">
                    <button type="button" class="cancel" onclick="closeDocumentForm()">Hủy</button>
                    <button type="submit" class="submit">Lưu</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let editingRow = null;
        let currentAction = 'add';

        function openDocumentForm(mode, el) {
            document.getElementById("documentForm").style.display = "flex";
            document.getElementById("formTitle").textContent = mode === 'edit' ? 'Sửa thông tin tài liệu' : 'Thêm tài liệu';
            document.getElementById("formAction").value = mode;

            if (mode === 'edit') {
                currentAction = 'edit';
                editingRow = el.closest("tr");
                const cells = editingRow.getElementsByTagName("td");
                
                document.getElementById("docId").value = cells[0].innerText;
                document.getElementById("docName").value = cells[1].innerText;
                document.getElementById("docLanguage").value = cells[2].innerText;
                document.getElementById("docLocation").value = cells[3].innerText;
                document.getElementById("docQuantity").value = cells[4].innerText;
                document.getElementById("docUpdateDate").value = cells[5].innerText;
                document.getElementById("docRemaining").value = cells[6].innerText;
                document.getElementById("docAuthorId").value = cells[7].innerText;
                document.getElementById("docPublisherId").value = cells[8].innerText;
                document.getElementById("docCategoryId").value = cells[9].innerText;
                document.getElementById("docReceiptId").value = cells[10].innerText;
                
                // Disable mã tài liệu khi sửa
                document.getElementById("docId").readOnly = true;
            } else {
                currentAction = 'add';
                editingRow = null;
                document.querySelectorAll("#documentForm input").forEach(el => {
                    el.value = "";
                    el.readOnly = false;
                });

                // Sinh mã tài liệu mới
                const lastId = document.querySelector("#documentTable tbody tr:last-child td:first-child")?.innerText || "TL000";
                const nextNum = parseInt(lastId.substring(2)) + 1;
                document.getElementById("docId").value = "TL" + nextNum.toString().padStart(3, '0');

                // Mặc định
                document.getElementById("docUpdateDate").valueAsDate = new Date();
                document.getElementById("docLanguage").value = "Tiếng Việt";
                document.getElementById("docRemaining").value = document.getElementById("docQuantity").value || "0";
            }
        }

        function closeDocumentForm() {
            document.getElementById("documentForm").style.display = "none";
        }

        function submitDocumentForm(event) {
            event.preventDefault();
            
            const docData = {
                action: currentAction,
                matailieu: document.getElementById("docId").value.trim(),
                tentailieu: document.getElementById("docName").value.trim(),
                tennn: document.getElementById("docLanguage").value.trim(),
                vitri: document.getElementById("docLocation").value.trim(),
                soluong: document.getElementById("docQuantity").value.trim(),
                ngaycapnhat: document.getElementById("docUpdateDate").value,
                soluongcon: document.getElementById("docRemaining").value.trim(),
                matg: document.getElementById("docAuthorId").value.trim(),
                manxb: document.getElementById("docPublisherId").value.trim(),
                matl: document.getElementById("docCategoryId").value.trim(),
                mapn: document.getElementById("docReceiptId").value.trim()
            };

            // Validate
            let isValid = true;
            if (!docData.matailieu) {
                alert("Vui lòng nhập mã tài liệu");
                isValid = false;
            }
            if (!docData.tentailieu) {
                alert("Vui lòng nhập tên tài liệu");
                isValid = false;
            }
            if (!docData.soluong) {
                alert("Vui lòng nhập số lượng");
                isValid = false;
            }
            if (!docData.ngaycapnhat) {
                alert("Vui lòng chọn ngày cập nhật");
                isValid = false;
            }

            if (!isValid) return;

            fetch("quanlytailieu.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(docData)
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

        function confirmDelete(matailieu) {
            if (confirm("Bạn có chắc chắn muốn xóa tài liệu này?")) {
                fetch("quanlytailieu.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            matailieu: matailieu
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
                        console.error("Lỗi xóa dữ liệu:", error);
                        alert("Gặp lỗi khi xóa dữ liệu: " + error.message);
                    });
            }
        }

        function searchDocument() {
            const filter = document.getElementById("searchDocument").value.toUpperCase();
            const rows = document.querySelectorAll("#documentTable tbody tr");
            rows.forEach(row => {
                const cells = row.getElementsByTagName("td");
                const match = Array.from(cells).slice(0, 11).some(td =>
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
