# Các công cụ kiểm thử gói cập nhật hệ thống

**Yêu cầu:**  
- Git
- Webserver tương đương yêu cầu của NukeViet
- Composer v2.6+
- Node.JS v18.17+
- NPM v10.5+

**Cài đặt:**

Lấy code:

```bash
git clone https://github.com/nukeviet/update-testing-tools.git
cd update-testing-tools
```

Cài các thư viện cần thiết:

```bash
composer install
```

Chuẩn bị Selenium server:

```bash
npm install selenium-standalone -g
selenium-standalone install
```

**Kiểm thử**

Chạy `bash run4.5.sh` chờ nó kiểm thử nếu không có lỗi nào khiến nó dừng lại là quá trình hoàn tất. Nếu có lỗi gì nó dừng lại thì kiểm tra và sửa.
