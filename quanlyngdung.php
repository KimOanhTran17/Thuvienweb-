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
                addUser($conn, $data);
                break;
            case 'edit':
                editUser($conn, $data);
                break;
            case 'delete':
                deleteUser($conn, $data['username']);
                break;
        }
    }
    exit;
}

// Hàm thêm người dùng
function addUser($conn, $data) {
    try {
        
        $stmt = $conn->prepare("INSERT INTO NGUOIDUNG 
                              (USERNAME, PASSWORD, HOTEN, NAMSINH, GIOITINH, CHUCDANH, EMAIL, DIENTHOAI, ID_QUYENHAN) 
                              VALUES 
                              (:username, :password, :hoten, :namsinh, :gioitinh, :chucdanh, :email, :dienthoai, :id_quyenhan)");
        
        $stmt->execute([
            ':username' => $data['username'],
            ':password' => $data['password'],
            ':hoten' => $data['hoten'],
            ':namsinh' => $data['namsinh'],
            ':gioitinh' => $data['gioitinh'],
            ':chucdanh' => $data['chucdanh'],
            ':email' => $data['email'],
            ':dienthoai' => $data['dienthoai'],
            ':id_quyenhan' => $data['id_quyenhan']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Thêm người dùng thành công']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}

// Hàm sửa người dùng
function editUser($conn, $data) {
    try {
        $stmt = $conn->prepare("UPDATE NGUOIDUNG SET 
                            PASSWORD = :password,
                              HOTEN = :hoten, 
                              NAMSINH = :namsinh, 
                              GIOITINH = :gioitinh, 
                              CHUCDANH = :chucdanh, 
                              EMAIL = :email, 
                              DIENTHOAI = :dienthoai, 
                              ID_QUYENHAN = :id_quyenhan 
                              WHERE USERNAME = :username");
        
        $stmt->execute([
            ':username' => $data['username'],
            ':password' => $data['password'],
            ':hoten' => $data['hoten'],
            ':namsinh' => $data['namsinh'],
            ':gioitinh' => $data['gioitinh'],
            ':chucdanh' => $data['chucdanh'],
            ':email' => $data['email'],
            ':dienthoai' => $data['dienthoai'],
            ':id_quyenhan' => $data['id_quyenhan']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Cập nhật người dùng thành công']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}

// Hàm xóa người dùng
function deleteUser($conn, $username) {
    try {
        $stmt = $conn->prepare("DELETE FROM NGUOIDUNG WHERE USERNAME = :username");
        $stmt->execute([':username' => $username]);
        echo json_encode(['success' => true, 'message' => 'Xóa người dùng thành công']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}

// Lấy danh sách người dùng từ CSDL
function getUsers($conn) {
    $stmt = $conn->query("SELECT * FROM NGUOIDUNG");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$users = getUsers($conn);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <title>Hệ thống quản lý người dùng</title>
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

        th, td {
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

        .popup-content h3 {
            margin-top: 0;
            color: #2f4c8c;
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

        .form-group input.error,
        .form-group select.error {
            border: 2px solid red;
        }

        .form-group .error-message {
            color: red;
            font-size: 13px;
            margin-top: 4px;
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
            0% { opacity: 0; transform: translateY(-10px); }
            10% { opacity: 1; transform: translateY(0); }
            80% { opacity: 1; transform: translateY(0); }
            100% { opacity: 0; transform: translateY(-10px); }
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
        <a href="#" class="active"><i class="fas fa-cogs"></i> Hệ thống</a>
        <a href="quanlydocgia.php"><i class="fas fa-user-friends"></i> Độc giả</a>
        <a href="quanlytailieu.php"><i class="fas fa-book"></i> Tài liệu</a>
        <a href="quanlyphieumuon.php"><i class="fas fa-exchange-alt"></i> Mượn trả tài liệu</a>
        <a href="baocaodocgiamuontailieu.php"><i class="fas fa-chart-bar"></i> Thống kê báo cáo</a>
    </div>

    <div class="main-content" id="user-management">
        <h2 class="page-title">Hệ thống</h2>
        
        <div class="sub-menu">
            <a href="quanlyngdung.php" class="active"><i class="fas fa-users"></i> Quản lý người dùng</a>
            
        </div>
        
        <div class="toolbar">
            <div class="action-buttons">
                <button onclick="openForm('add')"><i class="fas fa-plus"></i> Thêm</button>
            </div>
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Tìm kiếm...">
                <button onclick="searchUser()"><i class="fas fa-search"></i></button>
            </div>
        </div>
        <table id="userTable">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Password</th>
                    <th>Họ tên</th>
                    <th>Giới tính</th>
                    <th>Năm sinh</th>
                    <th>Điện thoại</th>
                    <th>Email</th>
                    <th>Chức danh</th>
                    <th>ID quyền hạn</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['USERNAME']) ?></td>
                    <td><?= htmlspecialchars($user['PASSWORD']) ?></td>
                    <td><?= htmlspecialchars($user['HOTEN']) ?></td>
                    <td><?= htmlspecialchars($user['GIOITINH']) ?></td>
                    <td><?= htmlspecialchars($user['NAMSINH']) ?></td>
                    <td><?= htmlspecialchars($user['DIENTHOAI']) ?></td>
                    <td><?= htmlspecialchars($user['EMAIL']) ?></td>
                    <td><?= htmlspecialchars($user['CHUCDANH']) ?></td>
                    <td><?= htmlspecialchars($user['ID_QUYENHAN']) ?></td>
                    <td class="actions-column">
                        <button onclick="openForm('edit', this)">Sửa</button>
                        <button onclick="confirmDelete('<?= $user['USERNAME'] ?>', this)">Xóa</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="popup" id="userForm">
        <div class="popup-content">
            <h3 id="formTitle">Thêm người dùng</h3>
            <div class="form-group">
                <label>Username</label>
                <input type="text" id="username" required />
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="text" id="password" required />
            </div>

            <div class="form-group">
                <label>Họ tên</label>
                <input type="text" id="hoten" required />
            </div>
            <div class="form-group">
                <label>Giới tính</label>
                <select id="gioitinh">
                    <option value="">--Chọn--</option>
                    <option value="Nam">Nam</option>
                    <option value="Nữ">Nữ</option>
                </select>
            </div>
            <div class="form-group">
                <label>Năm sinh</label>
                <input type="number" id="namsinh" required />
            </div>
            <div class="form-group">
                <label>Điện thoại</label>
                <input type="text" id="dienthoai" required />
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" id="email" required />
            </div>
            <div class="form-group">
                <label>Chức danh</label>
                <input type="text" id="chucdanh" required />
            </div>
            <div class="form-group">
                <label>ID quyền hạn</label>
                <input type="text" id="id_quyenhan" required />
            </div>
            <div class="form-buttons">
                <button class="cancel" onclick="closeForm()">Hủy</button>
                <button class="submit" onclick="submitUserForm()">Lưu</button>
            </div>
        </div>
    </div>

    <script>
        let editingRow = null;
        let currentAction = 'add';
      
        function openForm(mode, el) {
            document.getElementById("userForm").style.display = "flex";
            document.getElementById("formTitle").textContent = mode === 'edit' ? 'Sửa thông tin người dùng' : 'Thêm người dùng';
            resetErrors();
            
            if (mode === 'edit') {
                currentAction = 'edit';
                editingRow = el.closest("tr");
                const cells = editingRow.getElementsByTagName("td");
                document.getElementById("username").value = cells[0].innerText;
                document.getElementById("password").value = cells[1].innerText;
                document.getElementById("hoten").value = cells[2].innerText;
                document.getElementById("gioitinh").value = cells[3].innerText;
                document.getElementById("namsinh").value = cells[4].innerText;
                document.getElementById("dienthoai").value = cells[5].innerText;
                document.getElementById("email").value = cells[6].innerText;
                document.getElementById("chucdanh").value = cells[7].innerText;
                document.getElementById("id_quyenhan").value = cells[8].innerText;
                
                // Không cho sửa username
                document.getElementById("username").readOnly = true;
            } else {
                currentAction = 'add';
                editingRow = null;
                resetForm();
                document.getElementById("username").readOnly = false;
            }
        }
      
        function closeForm() {
            document.getElementById("userForm").style.display = "none";
            editingRow = null;
        }
      
        function resetForm() {
            document.querySelectorAll(".form-group input, .form-group select").forEach(input => {
                input.value = "";
            });
        }
      
        function resetErrors() {
            document.querySelectorAll(".form-group input, .form-group select").forEach(input => {
                input.classList.remove("error");
                const error = input.parentElement.querySelector(".error-message");
                if (error) error.remove();
            });
        }
      
        function showError(input, message) {
            input.classList.add("error");
            if (!input.parentElement.querySelector(".error-message")) {
                const error = document.createElement("div");
                error.className = "error-message";
                error.innerText = message;
                input.parentElement.appendChild(error);
            }
        }
      
        function showSuccess(message) {
            const note = document.createElement("div");
            note.className = "notification";
            note.innerHTML = `<i class='fas fa-check-circle'></i> ${message}`;
            document.body.appendChild(note);
            setTimeout(() => note.remove(), 3000);
        }
      
        function submitUserForm() {
            resetErrors();
            let valid = true;
      
            const fields = [
                { id: "username", msg: "Vui lòng nhập username" },
                { id: "password", msg: "Vui lòng nhập password" },
                { id: "hoten", msg: "Vui lòng nhập họ tên" },
                { id: "gioitinh", msg: "Vui lòng chọn giới tính" },
                { id: "namsinh", msg: "Vui lòng nhập năm sinh" },
                { id: "dienthoai", msg: "Vui lòng nhập số điện thoại" },
                { id: "email", msg: "Vui lòng nhập email" },
                { id: "chucdanh", msg: "Vui lòng nhập chức danh" },
                { id: "id_quyenhan", msg: "Vui lòng nhập quyền hạn" }
            ];
      
            const values = {};
            fields.forEach(f => {
                const input = document.getElementById(f.id);
                values[f.id] = input.value.trim();
                if (!values[f.id]) {
                    showError(input, f.msg);
                    valid = false;
                }
            });
      
            const phoneInput = document.getElementById("dienthoai");
            const emailInput = document.getElementById("email");
      
            if (values.dienthoai && !/^\d{10}$/.test(values.dienthoai)) {
                showError(phoneInput, "Số điện thoại phải có đúng 10 chữ số");
                valid = false;
            }
      
            if (values.email && !/^\S+@\S+\.\S+$/.test(values.email)) {
                showError(emailInput, "Email không hợp lệ");
                valid = false;
            }
      
            if (!valid) return;
      
            // Thêm action vào dữ liệu gửi đi
            values.action = currentAction;
            
            fetch("quanlyngdung.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(values)
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
      
        function confirmDelete(username, el) {
            if (confirm("Bạn có chắc chắn muốn xóa người dùng này?")) {
                fetch("quanlyngdung.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify({ action: 'delete', username: username })
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

        function searchUser() {
            const input = document.getElementById("searchInput");
            const filter = input.value.toUpperCase();
            const table = document.getElementById("userTable");
            const tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                let found = false;
                const td = tr[i].getElementsByTagName("td");
                
                for (let j = 0; j < td.length - 1; j++) {
                    if (td[j]) {
                        const txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                
                tr[i].style.display = found ? "" : "none";
            }
        }
    </script>
</body>
</html>