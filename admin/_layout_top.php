<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdminLogin();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-layout">
    <div class="admin-mobile-bar">
        <button class="admin-mobile-toggle" type="button" aria-expanded="false" aria-controls="adminSidebar" aria-label="Toggle admin menu">
            <span></span><span></span><span></span>
        </button>
        <strong>QR Menu</strong>
    </div>
    <aside id="adminSidebar" class="admin-sidebar">
        <h3 style="margin-top:0;">QR Menu</h3>
        <a href="dashboard.php">Dashboard</a>
        <?php if (isSuperAdmin()): ?>
            <a href="menu_add.php">Create Menu</a>
        <?php endif; ?>
        <a href="menus.php">My Menu</a>
        <a href="categories.php">Categories</a>
        <a href="item_add.php">Add Item</a>
        <a href="items.php">All Items</a>
        <?php if (isSuperAdmin()): ?>
            <a href="ads.php">Ads &amp; banners</a>
            <a href="admins.php">Admins</a>
            <a href="seed_demo.php">Insert Demo Data</a>
        <?php endif; ?>
        <a href="logout.php">Logout</a>
    </aside>
    <div class="admin-sidebar-overlay" aria-hidden="true"></div>
    <main class="admin-main">
