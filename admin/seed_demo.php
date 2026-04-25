<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireSuperAdmin();
require_once __DIR__ . '/_layout_top.php';

$message = '';
$messageColor = '#059669';
$steps = [];
$menuCode = 'demo2026';
$flash = consumeFlashMessage();
if ($flash && isset($flash['text']) && is_string($flash['text'])) {
    $message = $flash['text'];
    $messageColor = (($flash['type'] ?? 'success') === 'error') ? '#dc2626' : '#059669';
}

function seedDemoRunStep(string $label, callable $callback, array &$steps): void
{
    try {
        $callback();
        $steps[] = ['ok' => true, 'label' => $label];
    } catch (Throwable $e) {
        $steps[] = ['ok' => false, 'label' => $label . ': ' . $e->getMessage()];
    }
}

function seedDemoColumnExists(PDO $pdo, string $tableName, string $columnName): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$tableName, $columnName]);
    return (int) $stmt->fetchColumn() > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'seed_demo');

    if ($action === 'fix_db') {
        seedDemoRunStep('Create admin_menu_assignments table', function () use ($pdo): void {
            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS admin_menu_assignments (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    admin_id INT UNSIGNED NOT NULL,
                    menu_id INT UNSIGNED NOT NULL,
                    created_at DATETIME NOT NULL,
                    UNIQUE KEY uniq_admin_menu (admin_id, menu_id),
                    CONSTRAINT fk_admin_menu_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
                    CONSTRAINT fk_admin_menu_menu FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        }, $steps);

        seedDemoRunStep('Add admins.role column', function () use ($pdo): void {
            if (!seedDemoColumnExists($pdo, 'admins', 'role')) {
                $pdo->exec("ALTER TABLE admins ADD COLUMN role ENUM('super_admin','admin') NOT NULL DEFAULT 'admin' AFTER password");
            }
        }, $steps);

        seedDemoRunStep('Set admin user as super_admin', function () use ($pdo): void {
            $stmt = $pdo->prepare("UPDATE admins SET role = 'super_admin' WHERE username = 'admin'");
            $stmt->execute();
        }, $steps);

        seedDemoRunStep('Normalize empty roles', function () use ($pdo): void {
            $stmt = $pdo->prepare("UPDATE admins SET role = 'admin' WHERE role IS NULL OR role = ''");
            $stmt->execute();
        }, $steps);

        seedDemoRunStep('Add menus.menu_type column', function () use ($pdo): void {
            if (!seedDemoColumnExists($pdo, 'menus', 'menu_type')) {
                $pdo->exec("ALTER TABLE menus ADD COLUMN menu_type VARCHAR(30) NOT NULL DEFAULT 'other' AFTER description");
            }
        }, $steps);

        $hasError = false;
        foreach ($steps as $step) {
            if (!$step['ok']) {
                $hasError = true;
                break;
            }
        }
        $messageColor = $hasError ? '#dc2626' : '#059669';
        $message = $hasError ? 'Some DB fixes failed. Check update log below.' : 'DB requirements updated successfully.';
        setFlashMessage($message, $hasError ? 'error' : 'success');
        header('Location: seed_demo.php');
        exit;
    } else {
    $pdo->beginTransaction();
    try {
        $menuStmt = $pdo->prepare('SELECT id FROM menus WHERE menu_code = ? LIMIT 1');
        $menuStmt->execute([$menuCode]);
        $menuId = (int) ($menuStmt->fetchColumn() ?: 0);

        if ($menuId === 0) {
            $insertMenu = $pdo->prepare(
                    'INSERT INTO menus (name, menu_code, description, menu_type, logo_image, banner_image, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())'
            );
            $insertMenu->execute([
                'Demo Restaurant',
                $menuCode,
                'Fresh flavors, premium ingredients, and a modern dining experience.',
                    'restaurant',
                null,
                null,
            ]);
            $menuId = (int) $pdo->lastInsertId();
        }

        $catMap = [];
        $categoryData = [
            ['Starters', 1],
            ['Main Course', 2],
            ['Desserts', 3],
            ['Drinks', 4],
                ['Garden & Bowls', 5],
                ['Chef Sides', 6],
                ['Brunch', 7],
        ];

        $findCategory = $pdo->prepare('SELECT id FROM categories WHERE menu_id = ? AND name = ? LIMIT 1');
        $insertCategory = $pdo->prepare('INSERT INTO categories (menu_id, name, sort_order, created_at) VALUES (?, ?, ?, NOW())');

        foreach ($categoryData as [$catName, $sort]) {
            $findCategory->execute([$menuId, $catName]);
            $catId = (int) ($findCategory->fetchColumn() ?: 0);
            if ($catId === 0) {
                $insertCategory->execute([$menuId, $catName, $sort]);
                $catId = (int) $pdo->lastInsertId();
            }
            $catMap[$catName] = $catId;
        }

            /**
             * Unsplash CDN (allowed hotlinking). w= quality-sized for menu cards.
             * @var list<array{name:string,desc:string,price:float,cat:string,sort:int,img:string}>
             */
        $items = [
                // Starters (5)
                ['name' => 'Truffle Fries', 'desc' => 'Crispy fries, truffle oil, parmesan, and fresh herbs.', 'price' => 6.5, 'cat' => 'Starters', 'sort' => 1, 'img' => 'https://images.unsplash.com/photo-1573080496219-bb080dd4dcee?auto=format&fit=crop&w=800&q=80'],
                ['name' => 'Bruschetta Trio', 'desc' => 'Tomato basil, whipped ricotta, and olive tapenade on grilled sourdough.', 'price' => 7.2, 'cat' => 'Starters', 'sort' => 2, 'img' => 'https://images.unsplash.com/photo-1572695157199-89b34b1728d8?auto=format&fit=crop&w=800&q=80'],
                ['name' => 'Crispy Calamari', 'desc' => 'Lemon aioli, pickled chili, and micro cilantro.', 'price' => 9.5, 'cat' => 'Starters', 'sort' => 3, 'img' => 'https://images.unsplash.com/photo-1599487488170-d11ec9c172f0?auto=format&fit=crop&w=800&q=80'],
                ['name' => 'Korean BBQ Wings', 'desc' => 'Gochujang glaze, sesame, scallion, and cucumber ribbons.', 'price' => 8.9, 'cat' => 'Starters', 'sort' => 4, 'img' => 'https://images.unsplash.com/photo-1527477396000-e27137b194f3?auto=format&fit=crop&w=800&q=80'],
                ['name' => 'Charred Edamame', 'desc' => 'Tossed in garlic butter, togarashi, and flaky sea salt.', 'price' => 5.4, 'cat' => 'Starters', 'sort' => 5, 'img' => 'https://images.unsplash.com/photo-1540420773420-3366772f4999?auto=format&fit=crop&w=800&q=80'],
                // Garden & Bowls (5)
                ['name' => 'Mediterranean Grain Bowl', 'desc' => 'Farro, chickpeas, feta, olives, cucumber, and lemon tahini.', 'price' => 12.5, 'cat' => 'Garden & Bowls', 'sort' => 1, 'img' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=800&q=80'],
                ['name' => 'Thai Crunch Salad', 'desc' => 'Cabbage slaw, peanut dressing, mango, and crispy shallots.', 'price' => 11.25, 'cat' => 'Garden & Bowls', 'sort' => 2, 'img' => 'https://images.unsplash.com/photo-1546793665-c74683f339c1?auto=format&fit=crop&w=800&q=80'],
                ['name' => 'Roasted Beet & Citrus', 'desc' => 'Arugula, goat cheese mousse, pistachio, and blood orange.', 'price' => 10.8, 'cat' => 'Garden & Bowls', 'sort' => 3, 'img' => 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?auto=format&fit=crop&w=800&q=80'],
                ['name' => 'Avocado Caesar', 'desc' => 'Little gem, white anchovy, parmesan crisp, and lime caesar.', 'price' => 10.2, 'cat' => 'Garden & Bowls', 'sort' => 4, 'img' => 'https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?auto=format&fit=crop&w=800&q=80'],
                ['name' => 'Smoked Salmon Poke', 'desc' => 'Sushi rice, edamame, avocado, ponzu, and crispy nori.', 'price' => 13.9, 'cat' => 'Garden & Bowls', 'sort' => 5, 'img' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=800&q=80'],
                // Main Course (6)
                ['name' => 'Grilled Salmon', 'desc' => 'Herbed rice, charred lemon, brown butter capers.', 'price' => 18.5, 'cat' => 'Main Course', 'sort' => 1, 'img' => 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?auto=format&fit=crop&w=800&q=80'],
                ['name' => 'Beef Burger Deluxe', 'desc' => 'Angus patty, aged cheddar, caramelized onion, brioche, fries.', 'price' => 14.5, 'cat' => 'Main Course', 'sort' => 2, 'img' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=800&q=80'],
                ['name' => 'Miso Black Cod', 'desc' => 'Overnight marinade, bok choy, ginger dashi, and jasmine rice.', 'price' => 22.0, 'cat' => 'Main Course', 'sort' => 3, 'img' => 'https://images.unsplash.com/photo-1580476262798-bddd9f4b7369?auto=format&fit=crop&w=800&q=80'],
                ['name' => 'Wild Mushroom Pappardelle', 'desc' => 'Porcini cream, truffle oil, pecorino, and crispy sage.', 'price' => 16.25, 'cat' => 'Main Course', 'sort' => 4, 'img' => 'https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9?auto=format&fit=crop&w=800&q=80'],
                ['name' => 'Herb-Crusted Lamb Rack', 'desc' => 'Roasted garlic jus, fondant potato, and spring peas.', 'price' => 28.0, 'cat' => 'Main Course', 'sort' => 5, 'img' => 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=800&q=80'],
                ['name' => 'Smoked Brisket Plate', 'desc' => 'House BBQ, pickles, slaw, and buttered cornbread.', 'price' => 19.75, 'cat' => 'Main Course', 'sort' => 6, 'img' => 'https://images.unsplash.com/photo-1529193591184-b1d58069ecdd?auto=format&fit=crop&w=800&q=80'],
                // Chef Sides (4)
                ['name' => 'Truffle Parmesan Fries', 'desc' => 'Double-fried, black truffle salt, pecorino snow.', 'price' => 6.0, 'cat' => 'Chef Sides', 'sort' => 1, 'img' => 'https://images.unsplash.com/photo-1630384060881-c525f580c310?auto=format&fit=crop&w=800&q=80'],
                ['name' => 'Maple Roasted Carrots', 'desc' => 'Harissa yogurt, dukkah, and mint.', 'price' => 5.5, 'cat' => 'Chef Sides', 'sort' => 2, 'img' => 'https://images.unsplash.com/photo-1511690743698-d9d85f2fbf38?auto=format&fit=crop&w=800&q=80'],
                ['name' => 'Charred Broccolini', 'desc' => 'Calabrian chili, lemon zest, and toasted almonds.', 'price' => 5.25, 'cat' => 'Chef Sides', 'sort' => 3, 'img' => 'https://images.unsplash.com/photo-1584270354949-c26b0d5b4a0c?auto=format&fit=crop&w=800&q=80'],
                ['name' => 'Creamy Polenta', 'desc' => 'Fontina, roasted mushrooms, and chive oil.', 'price' => 6.75, 'cat' => 'Chef Sides', 'sort' => 4, 'img' => 'https://images.unsplash.com/photo-1476124369491-e7f408a3753d?auto=format&fit=crop&w=800&q=80'],
                // Desserts (5)
                ['name' => 'Chocolate Lava Cake', 'desc' => 'Molten center, salted caramel, and crème fraîche.', 'price' => 7.9, 'cat' => 'Desserts', 'sort' => 1, 'img' => 'https://images.unsplash.com/photo-1606313564200-e75d5e39b904?auto=format&fit=crop&w=800&q=80'],
                ['name' => 'Classic Tiramisu', 'desc' => 'Espresso-soaked ladyfingers, mascarpone, cocoa dust.', 'price' => 7.2, 'cat' => 'Desserts', 'sort' => 2, 'img' => 'https://images.unsplash.com/photo-1571877227200-a0d98ea607e9?auto=format&fit=crop&w=800&q=80'],
                ['name' => 'Yuzu Lemon Tart', 'desc' => 'Italian meringue, almond shell, and candied zest.', 'price' => 6.8, 'cat' => 'Desserts', 'sort' => 3, 'img' => 'https://images.unsplash.com/photo-1565958011703-44f9829ba187?auto=format&fit=crop&w=800&q=80'],
                ['name' => 'Matcha Basque Cheesecake', 'desc' => 'Burnt top, white chocolate crémeux, and red bean.', 'price' => 8.25, 'cat' => 'Desserts', 'sort' => 4, 'img' => 'https://images.unsplash.com/photo-1533134242443-d4fd215305ad?auto=format&fit=crop&w=800&q=80'],
                ['name' => 'Seasonal Fruit Sorbet', 'desc' => 'Three scoops, mint, and almond tuile.', 'price' => 5.9, 'cat' => 'Desserts', 'sort' => 5, 'img' => 'https://images.unsplash.com/photo-1497034825429-c86d4636745c?auto=format&fit=crop&w=800&q=80'],
                // Drinks (5)
                ['name' => 'Iced Latte', 'desc' => 'Double espresso, oat milk, and vanilla bean.', 'price' => 4.5, 'cat' => 'Drinks', 'sort' => 1, 'img' => 'https://images.unsplash.com/photo-1517701604599-bb29b565ddc9?auto=format&fit=crop&w=800&q=80'],
                ['name' => 'Fresh Orange Juice', 'desc' => 'Cold-pressed Valencia, no added sugar.', 'price' => 3.95, 'cat' => 'Drinks', 'sort' => 2, 'img' => 'https://images.unsplash.com/photo-1600271886742-f049cd451bba?auto=format&fit=crop&w=800&q=80'],
                ['name' => 'Sparkling Yuzu Cooler', 'desc' => 'Yuzu, elderflower, soda, and cucumber ribbon.', 'price' => 4.25, 'cat' => 'Drinks', 'sort' => 3, 'img' => 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=800&q=80'],
                ['name' => 'Cold Brew Float', 'desc' => 'Vanilla bean ice cream and 18-hour cold brew.', 'price' => 5.1, 'cat' => 'Drinks', 'sort' => 4, 'img' => 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=800&q=80'],
                ['name' => 'Mango Lassi Spritz', 'desc' => 'House yogurt lassi, sparkling water, cardamom.', 'price' => 4.75, 'cat' => 'Drinks', 'sort' => 5, 'img' => 'https://images.unsplash.com/photo-1546171753-97d7676e4602?auto=format&fit=crop&w=800&q=80'],
                // Brunch (4)
                ['name' => 'Smoked Salmon Benedict', 'desc' => 'Brioche, hollandaise, capers, and everything spice.', 'price' => 13.5, 'cat' => 'Brunch', 'sort' => 1, 'img' => 'https://images.unsplash.com/photo-1525351484163-7529414344d8?auto=format&fit=crop&w=800&q=80'],
                ['name' => 'Ricotta Hotcakes', 'desc' => 'Blueberry compote, maple butter, and lemon zest.', 'price' => 11.9, 'cat' => 'Brunch', 'sort' => 2, 'img' => 'https://images.unsplash.com/photo-1506086679524-493c00c17acd?auto=format&fit=crop&w=800&q=80'],
                ['name' => 'Shakshuka Skillet', 'desc' => 'Spiced tomato, feta, baked eggs, and grilled sourdough.', 'price' => 12.25, 'cat' => 'Brunch', 'sort' => 3, 'img' => 'https://images.unsplash.com/photo-1596797038530-2c107229654b?auto=format&fit=crop&w=800&q=80'],
                ['name' => 'Avocado Toast Deluxe', 'desc' => 'Smashed avocado, poached eggs, chili crunch, radish.', 'price' => 10.5, 'cat' => 'Brunch', 'sort' => 4, 'img' => 'https://images.unsplash.com/photo-1541519227354-08fa5d50c44d?auto=format&fit=crop&w=800&q=80'],
        ];

        $findItem = $pdo->prepare('SELECT id FROM items WHERE menu_id = ? AND name = ? LIMIT 1');
        $insertItem = $pdo->prepare(
            'INSERT INTO items (menu_id, category_id, name, description, price, image, sort_order, is_visible, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())'
        );

            foreach ($items as $row) {
                $name = $row['name'];
                $desc = $row['desc'];
                $price = $row['price'];
                $catName = $row['cat'];
                $sort = $row['sort'];
                $img = $row['img'];
            $findItem->execute([$menuId, $name]);
            $exists = (int) ($findItem->fetchColumn() ?: 0);
            if ($exists === 0 && isset($catMap[$catName])) {
                    $insertItem->execute([$menuId, $catMap[$catName], $name, $desc, $price, $img, $sort]);
                }
            }

            $syncDemoItem = $pdo->prepare(
                'UPDATE items SET description = ?, price = ?, image = ?, sort_order = ?, category_id = ? WHERE menu_id = ? AND name = ?'
            );
            foreach ($items as $row) {
                if (!isset($catMap[$row['cat']])) {
                    continue;
                }
                $syncDemoItem->execute([
                    $row['desc'],
                    $row['price'],
                    $row['img'],
                    $row['sort'],
                    $catMap[$row['cat']],
                    $menuId,
                    $row['name'],
                ]);
            }

            $demoBanner =
                'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=2000&q=85';
            $demoLogo =
                'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=400&h=400&q=85';
            $pdo->prepare('UPDATE menus SET banner_image = ?, logo_image = ? WHERE id = ? AND menu_code = ?')->execute([
                $demoBanner,
                $demoLogo,
                $menuId,
                $menuCode,
            ]);

        $qrStmt = $pdo->prepare('SELECT id FROM qr_codes WHERE menu_id = ? LIMIT 1');
        $qrStmt->execute([$menuId]);
        $hasQr = (int) ($qrStmt->fetchColumn() ?: 0);
        if ($hasQr === 0) {
            $qrPath = generateMenuQr($menuCode);
            if ($qrPath) {
                $pdo->prepare('INSERT INTO qr_codes (menu_id, qr_path, qr_url, created_at) VALUES (?, ?, ?, NOW())')
                    ->execute([$menuId, $qrPath, baseUrl() . '/menu.php?id=' . $menuCode]);
            }
        }

        $pdo->commit();
        $message = 'Demo data inserted successfully. Preview: ../menu.php?id=' . $menuCode;
            $messageColor = '#059669';
            setFlashMessage($message);
            header('Location: seed_demo.php');
            exit;
    } catch (Throwable $e) {
        $pdo->rollBack();
            $message = 'Failed to insert demo data. Please run "Fix / Update DB" and try again.';
            $messageColor = '#dc2626';
            setFlashMessage($message, 'error');
            header('Location: seed_demo.php');
            exit;
        }
    }
}
?>
<div class="card">
    <h2 style="margin-top:0;">Insert Demo Data</h2>
    <p class="muted">Creates or updates the demo menu (<code>demo2026</code>): categories, many sample dishes with Unsplash photos, banner/logo, and a QR code. Re-run to refresh copy and images for seeded dish names; new rows are only added when the name is new.</p>
    <p class="muted" style="margin-top:-4px;">If schema changed (new tables/columns), run Fix / Update DB first.</p>
    <?php if ($message): ?>
        <p style="color:<?= e($messageColor) ?>;"><?= e($message) ?></p>
    <?php endif; ?>
    <form method="post">
        <input type="hidden" name="action" value="seed_demo">
        <button class="btn" type="submit">Insert Demo Data</button>
        <button class="btn btn-outline" type="submit" name="action" value="fix_db">Fix / Update DB</button>
        <a class="btn btn-outline" href="../menu.php?id=demo2026" target="_blank">Open Demo Menu</a>
    </form>
</div>
<?php if (!empty($steps)): ?>
    <div class="card">
        <h3 style="margin-top:0;">DB Update Log</h3>
        <ul style="margin:0;padding-left:18px;">
            <?php foreach ($steps as $step): ?>
                <li style="margin:6px 0;color:<?= $step['ok'] ? '#059669' : '#dc2626' ?>;">
                    <?= e($step['label']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
