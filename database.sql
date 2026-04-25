CREATE DATABASE IF NOT EXISTS menus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE menus;

CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    username VARCHAR(60) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin','admin') NOT NULL DEFAULT 'admin',
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS menus (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    menu_code VARCHAR(40) NOT NULL UNIQUE,
    description TEXT NULL,
    logo_image VARCHAR(255) NULL,
    banner_image VARCHAR(255) NULL,
    banner_image_docked VARCHAR(255) NULL,
    views INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    menu_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_categories_menu FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    menu_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    image VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_items_menu FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE,
    CONSTRAINT fk_items_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS qr_codes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    menu_id INT UNSIGNED NOT NULL,
    qr_path VARCHAR(255) NOT NULL,
    qr_url VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_qr_menu FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_menu_assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NOT NULL,
    menu_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uniq_admin_menu (admin_id, menu_id),
    CONSTRAINT fk_admin_menu_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
    CONSTRAINT fk_admin_menu_menu FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Migration safety for existing installs
ALTER TABLE admins
    ADD COLUMN IF NOT EXISTS role ENUM('super_admin','admin') NOT NULL DEFAULT 'admin' AFTER password;

INSERT INTO admins (name, username, password, role, created_at)
SELECT 'Super Admin', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', NOW()
WHERE NOT EXISTS (SELECT 1 FROM admins WHERE username = 'admin');

UPDATE admins SET role = 'super_admin' WHERE username = 'admin';
