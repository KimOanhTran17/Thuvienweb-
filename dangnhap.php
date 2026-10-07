<?php
session_start();

// Kết nối CSDL
$host = 'localhost';
$dbname = 'thuvien';
$username = 'root'; // Thay bằng username của bạn
$password = ''; // Thay bằng password của bạn

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Kết nối CSDL thất bại: " . $e->getMessage());
}

// Xử lý đăng nhập
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        try {
            $stmt = $conn->prepare("SELECT * FROM nguoidung WHERE USERNAME = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && $user['PASSWORD'] === $password) {
                // Lưu thông tin user vào session
                $_SESSION['user'] = [
                    'username' => $user['USERNAME'],
                    'hoten' => $user['HOTEN'],
                    'id_quyenhan' => $user['ID_QUYENHAN'],
                    'chucdanh' => $user['CHUCDANH']
                ];

                // Chuyển hướng đến trangchu.html
                header('Location: http://localhost/thuvienwweb//trangchu.html');
                exit();  // Luôn thêm exit() sau header()
            } else {
                $error = 'Tên đăng nhập hoặc mật khẩu không đúng!';
            }
        } catch (PDOException $e) {
            $error = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    } else {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Đăng nhập - Thư viện</title>
    <style>
        body,
        html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: Arial, sans-serif;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('https://i.imgur.com/aRldoqE.jpg');
            background-size: cover;
            background-position: center;
            filter: blur(5px);
            z-index: -1;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            margin: auto;
            margin-top: 10vh;
        }

        .login-title {
            text-align: center;
            font-size: 28px;
            color: #1d3b85;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .input-field {
            width: 100%;
            padding: 12px 15px;
            font-size: 16px;
            border: 2px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
        }

        .input-error {
            border-color: red;
        }

        .error-message {
            color: red;
            text-align: center;
            margin-bottom: 10px;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: #1d3b85;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }

        .submit-btn:hover {
            background-color: #2750a7;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <h1 class="login-title">ĐĂNG NHẬP</h1>
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="dangnhap.php">
            <div class="form-group">
                <input class="input-field" id="username" name="username" placeholder="Tên đăng nhập" type="text" required />
            </div>
            <div class="form-group">
                <input class="input-field" id="password" name="password" placeholder="Mật khẩu" type="password" required />
            </div>
            <button class="submit-btn" type="submit">Đăng nhập</button>
        </form>
    </div>
</body>

</html>