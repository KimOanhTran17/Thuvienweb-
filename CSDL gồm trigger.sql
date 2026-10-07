CREATE DATABASE THUVIEN
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE THUVIEN;
CREATE TABLE QUYENHAN (
    ID_QUYENHAN VARCHAR(20) PRIMARY KEY,
    TEN_QUYENHAN VARCHAR(50)
);

CREATE TABLE NGUOIDUNG (
    USERNAME VARCHAR(50) PRIMARY KEY,
    PASSWORD VARCHAR(50),
    HOTEN VARCHAR(100),
    NAMSINH YEAR,
    GIOITINH VARCHAR(5),
    CHUCDANH VARCHAR(50),
    EMAIL VARCHAR(100),
    DIENTHOAI VARCHAR(20),
    ID_QUYENHAN VARCHAR(20),
    FOREIGN KEY (ID_QUYENHAN) REFERENCES QUYENHAN(ID_QUYENHAN)
);

CREATE TABLE LOP (
    MALOP VARCHAR(20) PRIMARY KEY,
    TENLOP VARCHAR(100)
);

CREATE TABLE DOCGIA (
    SOTHE VARCHAR(20) PRIMARY KEY,
    HOTEN VARCHAR(100),
    NGAYSINH DATE,
    GIOITINH VARCHAR(5),
    CHUCDANH VARCHAR(50),
    NGAYCN DATE,
    MALOP VARCHAR(20),
    FOREIGN KEY (MALOP) REFERENCES LOP(MALOP)
);

CREATE TABLE NHANVIEN (
    MANV VARCHAR(20) PRIMARY KEY,
    TENNV VARCHAR(100),
    CHUCVU VARCHAR(50)
);

CREATE TABLE XULYDOCGIA (
    MAPHAT VARCHAR(20) PRIMARY KEY,
    SOTHE VARCHAR(20),
    MANV VARCHAR(20),
    LYDOVP VARCHAR(250),
    HTXULY VARCHAR(100),
    NGAYXULY DATE,
    FOREIGN KEY (SOTHE) REFERENCES DOCGIA(SOTHE),
    FOREIGN KEY (MANV) REFERENCES NHANVIEN(MANV)
);

CREATE TABLE TACGIA (
    MATG VARCHAR(20) PRIMARY KEY,
    TENTG VARCHAR(100)
);

CREATE TABLE NHAXB (
    MANXB VARCHAR(20) PRIMARY KEY,
    TENNXB VARCHAR(100)
);

CREATE TABLE THELOAI (
    MATL VARCHAR(20) PRIMARY KEY,
    TENTL VARCHAR(100),
    GHICHU VARCHAR(250)
);

CREATE TABLE NCC (
    MANCC VARCHAR(20) PRIMARY KEY,
    TENNCC VARCHAR(100),
    DIACHI VARCHAR(250),
    SDT VARCHAR(20)
);

CREATE TABLE PHIEUNHAP (
    MAPN VARCHAR(20) PRIMARY KEY,
    MATL VARCHAR(20),
    NGAYNHAP DATE,
    SOLUONG INT,
    MANV VARCHAR(20),
    MANCC VARCHAR(20),
    FOREIGN KEY (MANV) REFERENCES NHANVIEN(MANV),
    FOREIGN KEY (MANCC) REFERENCES NCC(MANCC)
);

CREATE TABLE TAILIEU (
    MATAILIEU VARCHAR(20) PRIMARY KEY,
    TENTAILIEU VARCHAR(100),
    TENNN VARCHAR(100),
    VITRI VARCHAR(100),
    SOLUONG INT,
    NGAYCAPNHAT DATE,
    SOLUONGCON INT,
    MATG VARCHAR(20),
    MANXB VARCHAR(20),
    MATL VARCHAR(20),
    MAPN VARCHAR(20),
    FOREIGN KEY (MATG) REFERENCES TACGIA(MATG),
    FOREIGN KEY (MANXB) REFERENCES NHAXB(MANXB),
    FOREIGN KEY (MATL) REFERENCES THELOAI(MATL),
    FOREIGN KEY (MAPN) REFERENCES PHIEUNHAP(MAPN)
);

CREATE TABLE TAILIEUCHITIET (
    MACABIET VARCHAR(20) PRIMARY KEY,
    MATAILIEU VARCHAR(20),
    NGAYCN DATE,
    TINHTRANG VARCHAR(100),
    MANV VARCHAR(20),
    FOREIGN KEY (MATAILIEU) REFERENCES TAILIEU(MATAILIEU),
    FOREIGN KEY (MANV) REFERENCES NHANVIEN(MANV)
);

CREATE TABLE MUONTRA_TL (
    MAMUONTRA VARCHAR(20) PRIMARY KEY,
    SOTHE VARCHAR(20),
    MACABIET VARCHAR(20),
    MANV VARCHAR(20),
    NGAYMUON DATE,
    KIEUMUON VARCHAR(50),
    HANTRA DATE,
    NGAYTRA DATE,
    FOREIGN KEY (SOTHE) REFERENCES DOCGIA(SOTHE),
    FOREIGN KEY (MACABIET) REFERENCES TAILIEUCHITIET(MACABIET),
    FOREIGN KEY (MANV) REFERENCES NHANVIEN(MANV)
);

-- Trigger 
-- 1. Trigger khi thêm phiếu mượn (sửa lại)
DROP TRIGGER IF EXISTS after_muontra_insert;
DELIMITER //
CREATE TRIGGER after_muontra_insert
AFTER INSERT ON MUONTRA_TL
FOR EACH ROW
BEGIN
    -- Giảm số lượng còn lại, nếu NULL thì gán bằng SOLUONG - 1
    UPDATE TAILIEU t
    JOIN TAILIEUCHITIET tc ON t.MATAILIEU = tc.MATAILIEU
    SET t.SOLUONGCON = IF(t.SOLUONGCON IS NULL, t.SOLUONG - 1, t.SOLUONGCON - 1)
    WHERE tc.MACABIET = NEW.MACABIET;
END //
DELIMITER ;

-- 2. Trigger khi cập nhật phiếu trả (sửa lại)
DROP TRIGGER IF EXISTS after_muontra_update;
DELIMITER //
CREATE TRIGGER after_muontra_update
AFTER UPDATE ON MUONTRA_TL
FOR EACH ROW
BEGIN
    IF OLD.NGAYTRA IS NULL AND NEW.NGAYTRA IS NOT NULL THEN
        -- Tăng số lượng, đảm bảo không vượt quá SOLUONG
        UPDATE TAILIEU t
        JOIN TAILIEUCHITIET tc ON t.MATAILIEU = tc.MATAILIEU
        SET t.SOLUONGCON = LEAST(t.SOLUONG, IFNULL(t.SOLUONGCON, t.SOLUONG) + 1)
        WHERE tc.MACABIET = NEW.MACABIET;
    END IF;
END //
DELIMITER ;

-- 3. Trigger khi xóa phiếu mượn (sửa lại)
DROP TRIGGER IF EXISTS after_muontra_delete;
DELIMITER //
CREATE TRIGGER after_muontra_delete
AFTER DELETE ON MUONTRA_TL
FOR EACH ROW
BEGIN
    IF OLD.NGAYTRA IS NULL THEN
        -- Tăng số lượng, đảm bảo không vượt quá SOLUONG
        UPDATE TAILIEU t
        JOIN TAILIEUCHITIET tc ON t.MATAILIEU = tc.MATAILIEU
        SET t.SOLUONGCON = LEAST(t.SOLUONG, IFNULL(t.SOLUONGCON, t.SOLUONG) + 1)
        WHERE tc.MACABIET = OLD.MACABIET;
    END IF;
END //
DELIMITER ;

-- 4.Trigger xóa dữ liệu liên quan khi xóa độc giả
DROP TRIGGER IF EXISTS before_docgia_delete;
DELIMITER //
CREATE TRIGGER before_docgia_delete
BEFORE DELETE ON DOCGIA
FOR EACH ROW
BEGIN
    -- Xóa các xử lý vi phạm của độc giả
    DELETE FROM XULYDOCGIA WHERE SOTHE = OLD.SOTHE;
    
    -- Xóa các phiếu mượn trả của độc giả
    DELETE FROM MUONTRA_TL WHERE SOTHE = OLD.SOTHE;
END //
DELIMITER ;
-- 5.Trigger xóa dữ liệu liên quan khi xóa tài liệu
DROP TRIGGER IF EXISTS before_tailieu_delete;
DELIMITER //
CREATE TRIGGER before_tailieu_delete
BEFORE DELETE ON TAILIEU
FOR EACH ROW
BEGIN
    -- Xóa các phiếu mượn trả liên quan đến tài liệu chi tiết của tài liệu này
    DELETE FROM MUONTRA_TL 
    WHERE MACABIET IN (SELECT MACABIET FROM TAILIEUCHITIET WHERE MATAILIEU = OLD.MATAILIEU);
    
    -- Xóa các tài liệu chi tiết của tài liệu này
    DELETE FROM TAILIEUCHITIET WHERE MATAILIEU = OLD.MATAILIEU;
    
END //
DELIMITER ;

-- 6 Trigger tự động cập nhật tình trạng tài liệu khi mượn
DROP TRIGGER IF EXISTS after_muontra_insert_update;
DELIMITER //
CREATE TRIGGER after_muontra_insert_update
AFTER INSERT ON MUONTRA_TL
FOR EACH ROW
BEGIN
    -- Khi thêm phiếu mượn (NGAYTRA NULL) thì cập nhật thành "Đã mượn"
    IF NEW.NGAYTRA IS NULL THEN
        UPDATE TAILIEUCHITIET
        SET TINHTRANG = 'Đã mượn'
        WHERE MACABIET = NEW.MACABIET;
    END IF;
END//
DELIMITER ;

-- 7. Trigger tự động cập nhật tình trạng tài liệu khi mượn
DROP TRIGGER IF EXISTS after_muontra_update;
DELIMITER //
-- Trigger khi cập nhật phiếu mượn (chỉ xử lý khi thêm ngày trả)
CREATE TRIGGER after_muontra_update
AFTER UPDATE ON MUONTRA_TL
FOR EACH ROW
BEGIN
    -- Chỉ xử lý khi NGAYTRA thay đổi từ NULL sang có giá trị (trả sách)
    IF OLD.NGAYTRA IS NULL AND NEW.NGAYTRA IS NOT NULL THEN
        UPDATE TAILIEUCHITIET
        SET TINHTRANG = 'Chưa mượn'
        WHERE MACABIET = NEW.MACABIET;
    END IF;
END//
DELIMITER ;


-- INSERT DL 
INSERT INTO QUYENHAN VALUES
('QH001', 'Admin'),
('QH002', 'Thủ thư'),
('QH003', 'Ban kỹ thuật');


INSERT INTO NGUOIDUNG VALUES
('admin01', 'matkhau123', 'Nguyễn Thị Thu Hà', 1985, 'Nữ', 'Quản trị hệ thống', 'admin01@library.edu.vn', '0909123456', 'QH001'),
('thuthu01', 'matkhau456', 'Trần Văn Khôi', 1990, 'Nam', 'Thủ thư chính', 'khoi.thuthu@library.edu.vn', '0912345678', 'QH002'),
('thuthu02', 'matkhau789', 'Lê Thị Hồng Nhung', 1992, 'Nữ', 'Thủ thư hỗ trợ', 'nhung.le@library.edu.vn', '0987654321', 'QH002'),
('thuthu03', 'matkhauabc', 'Phạm Minh Khoa', 1988, 'Nam', 'Nhân viên thủ thư', 'khoa.pm@library.edu.vn', '0909999999', 'QH003'),
('admin02', 'matkhaudef', 'Vũ Thị Lan Anh', 1991, 'Nữ', 'Quản trị hệ thống', 'lananh.vt@library.edu.vn', '0933333333', 'QH003');

INSERT INTO LOP VALUES
('L001', 'Công nghệ thông tin 1'),
('L002', 'Kỹ thuật phần mềm 2'),
('L003', 'Hệ thống thông tin 3'),
('L004', 'An toàn thông tin 1'),
('L005', 'Khoa học dữ liệu 2');

INSERT INTO DOCGIA VALUES
('DG001', 'Nguyễn Văn Minh', '2003-05-20', 'Nam', 'Sinh viên', '2024-03-10', 'L001'),
('DG002', 'Trần Thị Hạnh', '2002-11-15', 'Nữ', 'Sinh viên', '2024-03-12', 'L002'),
('DG003', 'Phạm Anh Dũng', '2003-02-28', 'Nam', 'Sinh viên', '2024-03-15', 'L003'),
('DG004', 'Lê Thị Thu', '2001-09-01', 'Nữ', 'Sinh viên', '2024-03-18', 'L004'),
('DG005', 'Hoàng Minh Đức', '2002-12-05', 'Nam', 'Sinh viên', '2024-03-20', 'L005'),
('DG006', 'Vũ Thị Thanh Hương', '2003-07-12', 'Nữ', 'Sinh viên', '2024-03-22', 'L001'),
('DG007', 'Nguyễn Đức Anh', '2002-04-25', 'Nam', 'Sinh viên', '2024-03-23', 'L002'),
('DG008', 'Trần Minh Tuấn', '2003-09-18', 'Nam', 'Sinh viên', '2024-03-25', 'L003'),
('DG009', 'Lê Thị Mai Linh', '2001-12-30', 'Nữ', 'Sinh viên', '2024-03-28', 'L004'),
('DG010', 'Phạm Văn Hoàng', '2002-08-15', 'Nam', 'Sinh viên', '2024-04-01', 'L005'),
('DG011', 'Hoàng Thị Ngọc Anh', '2003-01-20', 'Nữ', 'Sinh viên', '2024-04-03', 'L001'),
('DG012', 'Nguyễn Văn Tú', '2002-06-08', 'Nam', 'Sinh viên', '2024-04-05', 'L002'),
('DG013', 'Trần Thị Thuỳ Linh', '2001-11-11', 'Nữ', 'Sinh viên', '2024-04-08', 'L003'),
('DG014', 'Lê Văn Nam', '2002-03-22', 'Nam', 'Sinh viên', '2024-04-10', 'L004'),
('DG015', 'Phạm Thị Hồng Nhung', '2003-10-05', 'Nữ', 'Sinh viên', '2024-04-12', 'L005');

INSERT INTO NHANVIEN VALUES
('NV001', 'Nguyễn Thị Thu Hà', 'Quản trị hệ thống'),
('NV002', 'Trần Văn Khôi', 'Thủ thư chính'),
('NV003', 'Lê Thị Hồng Nhung', 'Thủ thư hỗ trợ'),
('NV004', 'Phạm Minh Khoa', 'Nhân viên kỹ thuật'),
('NV005', 'Vũ Thị Lan Anh', 'Kỹ thuật viên IT');

INSERT INTO XULYDOCGIA VALUES
('XL001', 'DG001', 'NV002','Trả sách trễ hạn', 'Cảnh cáo', '2024-03-25'),
('XL002', 'DG002', 'NV003','Làm rách sách', 'Bồi thường sách', '2024-04-01'),
('XL003', 'DG003', 'NV002','Mượn quá số lượng quy định', 'Nhắc nhở', '2024-04-03'),
('XL004', 'DG004', 'NV003','Gây ồn ào trong thư viện', 'Cảnh cáo', '2024-04-05'),
('XL005', 'DG005', 'NV002','Mất sách', 'Bồi thường sách và cấm mượn 1 tháng', '2024-04-07');

INSERT INTO TACGIA VALUES
('TG001', 'Nguyễn Nhật Ánh'),
('TG002', 'Trần Đình Hiếu'),
('TG003', 'Lê Hoàng Nam'),
('TG004', 'Tô Hoài'),
('TG005', 'Nguyễn Thị Mai');

INSERT INTO THELOAI VALUES
('TL001', 'Văn học Việt Nam', 'Tác phẩm văn học nổi tiếng của Việt Nam'),
('TL002', 'Khoa học máy tính', 'Tài liệu chuyên ngành công nghệ thông tin'),
('TL003', 'Kinh tế học', 'Sách và giáo trình về kinh tế, tài chính'),
('TL004', 'Tâm lý học', 'Tài liệu nghiên cứu và học tập về tâm lý'),
('TL005', 'Kỹ năng sống', 'Sách hướng dẫn kỹ năng mềm, giao tiếp, quản lý thời gian');

INSERT INTO NHAXB VALUES
('NXB001', 'Nhà xuất bản Trẻ'),
('NXB002', 'Nhà xuất bản Giáo dục Việt Nam'),
('NXB003', 'Nhà xuất bản Khoa học & Kỹ thuật'),
('NXB004', 'Nhà xuất bản Lao Động'),
('NXB005', 'Nhà xuất bản Văn Học');

INSERT INTO NCC VALUES
('NCC001', 'Công ty sách Phương Nam', '123 Lê Văn Sỹ, Quận 3, TP.HCM', '02812345678'),
('NCC002', 'Công ty phát hành sách Giáo Dục', '45 Nguyễn Thị Minh Khai, Q.1, TP.HCM', '02887654321'),
('NCC003', 'Nhà sách Nhân Văn', '27 Hai Bà Trưng, TP.HCM', '02823456789'),
('NCC004', 'Công ty sách Hồng Đức', '90 Trần Hưng Đạo, TP.HCM', '02899887766'),
('NCC005', 'Nhà sách Fahasa', 'Lầu 3, TTTM Aeon Mall Bình Tân, TP.HCM', '02822334455');


INSERT INTO PHIEUNHAP VALUES
('PN001', 'TL001', '2024-02-25', 10, 'NV002', 'NCC001'),
('PN002', 'TL002', '2024-02-26', 12, 'NV002', 'NCC002'),
('PN003', 'TL003', '2024-02-28', 15, 'NV003', 'NCC003'),
('PN004', 'TL004', '2024-03-01', 7, 'NV003', 'NCC004'),
('PN005', 'TL005', '2024-03-03', 20, 'NV002', 'NCC005');

INSERT INTO TAILIEU VALUES
('TL001', 'Cho tôi xin một vé đi tuổi thơ', 'Tiếng Việt', 'Kệ A1 - Ngăn 3', 10, '2024-03-01', 10, 'TG001', 'NXB001', 'TL001', 'PN001'),
('TL002', 'Giáo trình Cấu trúc dữ liệu và giải thuật', 'Tiếng Việt', 'Kệ B2 - Ngăn 1', 12, '2024-03-02', 12, 'TG002', 'NXB002', 'TL002', 'PN002'),
('TL003', 'Hệ điều hành Linux căn bản', 'Tiếng Anh', 'Kệ C1 - Ngăn 2', 15, '2024-03-05', 15, 'TG003', 'NXB003', 'TL003', 'PN003'),
('TL004', 'Dế Mèn phiêu lưu ký', 'Tiếng Việt', 'Kệ A2 - Ngăn 4', 7, '2024-03-08', 7, 'TG004', 'NXB005', 'TL004', 'PN004'),
('TL005', 'Kỹ năng mềm cho sinh viên', 'Tiếng Việt', 'Kệ D1 - Ngăn 5', 20, '2024-03-10', 20, 'TG005', 'NXB004', 'TL005', 'PN005'),
('TL006', 'Nhà giả kim', 'Tiếng Việt', 'Kệ A3 - Ngăn 2', 8, '2024-03-15', 8, 'TG001', 'NXB001', 'TL001', 'PN001'),
('TL007', 'Lập trình Python cơ bản', 'Tiếng Việt', 'Kệ B3 - Ngăn 1', 15, '2024-03-18', 15, 'TG002', 'NXB002', 'TL002', 'PN002'),
('TL008', 'Toán rời rạc ứng dụng', 'Tiếng Anh', 'Kệ C2 - Ngăn 3', 10, '2024-03-20', 10, 'TG003', 'NXB003', 'TL002', 'PN002'),
('TL009', 'Đắc nhân tâm', 'Tiếng Việt', 'Kệ D2 - Ngăn 4', 12, '2024-03-22', 12, 'TG004', 'NXB004', 'TL005', 'PN005'),
('TL010', 'Trí tuệ nhân tạo cơ bản', 'Tiếng Việt', 'Kệ E1 - Ngăn 1', 9, '2024-03-25', 9, 'TG005', 'NXB005', 'TL002', 'PN002'),
('TL011', 'Tôi tài giỏi bạn cũng thế', 'Tiếng Việt', 'Kệ A4 - Ngăn 5', 7, '2024-03-28', 7, 'TG001', 'NXB001', 'TL005', 'PN005'),
('TL012', 'Cấu trúc dữ liệu và giải thuật nâng cao', 'Tiếng Anh', 'Kệ B4 - Ngăn 2', 11, '2024-04-01', 11, 'TG002', 'NXB002', 'TL002', 'PN002'),
('TL013', 'Hệ quản trị cơ sở dữ liệu', 'Tiếng Việt', 'Kệ C3 - Ngăn 1', 14, '2024-04-05', 14, 'TG003', 'NXB003', 'TL002', 'PN002'),
('TL014', 'Kỹ năng thuyết trình hiệu quả', 'Tiếng Việt', 'Kệ D3 - Ngăn 3', 6, '2024-04-08', 6, 'TG004', 'NXB004', 'TL005', 'PN005'),
('TL015', 'Mạng máy tính và an ninh mạng', 'Tiếng Anh', 'Kệ E2 - Ngăn 4', 10, '2024-04-10', 10, 'TG005', 'NXB005', 'TL002', 'PN002');

INSERT INTO TAILIEUCHITIET (MACABIET, MATAILIEU, NGAYCN, TINHTRANG, MANV) VALUES
('CB001', 'TL001', '2024-03-01', 'Chưa mượn', 'NV002'),
('CB002', 'TL002', '2024-03-02', 'Đã mượn', 'NV002'),
('CB003', 'TL003', '2024-03-05', 'Chưa mượn', 'NV002'),
('CB004', 'TL004', '2024-03-08', 'Chưa mượn', 'NV003'),
('CB005', 'TL005', '2024-03-10', 'Đã mượn', 'NV003'),
('CB006', 'TL001', '2024-03-12', 'Chưa mượn', 'NV002'),
('CB007', 'TL002', '2024-03-13', 'Chưa mượn', 'NV002'),
('CB008', 'TL003', '2024-03-15', 'Chưa mượn', 'NV003'),
('CB009', 'TL004', '2024-03-16', 'Chưa mượn', 'NV003'),
('CB010', 'TL005', '2024-03-18', 'Chưa mượn', 'NV002'),
('CB011', 'TL006', '2024-03-20', 'Chưa mượn', 'NV003'),
('CB012', 'TL007', '2024-03-22', 'Chưa mượn', 'NV002'),
('CB013', 'TL008', '2024-03-25', 'Chưa mượn', 'NV003'),
('CB014', 'TL009', '2024-03-28', 'Chưa mượn', 'NV002'),
('CB015', 'TL010', '2024-04-01', 'Chưa mượn', 'NV003'),
('CB016', 'TL011', '2024-04-03', 'Chưa mượn', 'NV002'),
('CB017', 'TL012', '2024-04-05', 'Chưa mượn', 'NV003'),
('CB018', 'TL013', '2024-04-08', 'Chưa mượn', 'NV002'),
('CB019', 'TL014', '2024-04-10', 'Chưa mượn', 'NV003'),
('CB020', 'TL015', '2024-04-12', 'Chưa mượn', 'NV002');

INSERT INTO MUONTRA_TL VALUES
('MT001', 'DG001', 'CB001', 'NV002', '2024-03-15', 'Mượn tại chỗ', '2024-03-15', '2024-03-15'),
('MT002', 'DG002', 'CB002', 'NV002', '2024-03-16', 'Mượn về nhà', '2024-03-23', NULL),
('MT003', 'DG003', 'CB003', 'NV003', '2024-03-17', 'Mượn về nhà', '2024-03-24', '2024-03-24'),
('MT004', 'DG004', 'CB004', 'NV002', '2024-03-18', 'Mượn về nhà', '2024-03-21', '2024-03-20'),
('MT005', 'DG005', 'CB005', 'NV003', '2024-03-19', 'Mượn về nhà', '2024-03-26', NULL);

-- test code 
select * from LOP;
select * from DOCGIA;
select * from NHANVIEN;
select * from NCC;
select * from QUYENHAN;
select * from NGUOIDUNG;
select * from NHAXB;
select * from TACGIA;
select * from THELOAI;
select * from TAILIEU; 
select * from TAILIEUCHITIET;
select * from PHIEUNHAP;
select * from MUONTRA_TL; 
select * from XULYDOCGIA;







