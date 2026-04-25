# QR Menu Web Application (cPanel Ready)

## Quick Setup
1. Upload all files/folders to `public_html`.
2. Import `database.sql` from phpMyAdmin.
3. Edit DB credentials in `includes/db.php`.
4. Open `https://yourdomain.com/admin/login.php`.

## Default Admin
- Username: `admin`
- Password: `password`

## Main URL Format
- `https://yourdomain.com/menu.php?id=abc123`

## Notes
- QR image files are generated into `qrcodes/`.
- Uploaded images are stored in `uploads/`.
- App uses PDO prepared statements and password hashing.
- No Composer, no Node.js, no CLI dependencies.
