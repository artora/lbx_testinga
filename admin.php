<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit();
}

// Fetch categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY display_order");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Lottie Gallery</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
    <style>
        :root {
            --primary-bg: #1a1a1a;
            --secondary-bg: #2d2d2d;
            --accent-color: #007bff;
            --text-color: #ffffff;
        }

        body {
            background: var(--primary-bg);
            color: var(--text-color);
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 20px;
        }

        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .admin-nav {
            display: flex;
            gap: 20px;
        }

        .nav-link {
            color: var(--text-color);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            background: var(--secondary-bg);
        }

        .panel {
            background: var(--secondary-bg);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .upload-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .tab {
            padding: 10px 20px;
            background: var(--primary-bg);
            border-radius: 6px;
            cursor: pointer;
        }

        .tab.active {
            background: var(--accent-color);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        input, select, button {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 6px;
            background: var(--primary-bg);
            color: var(--text-color);
            margin-bottom: 10px;
        }

        button {
            background: var(--accent-color);
            cursor: pointer;
            transition: opacity 0.3s;
        }

        button:hover {
            opacity: 0.9;
        }

        .category-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 10px 0;
        }

        .category-chip {
            padding: 8px 16px;
            background: var(--primary-bg);
            border-radius: 20px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .category-chip.selected {
            background: var(--accent-color);
        }

        .settings-section {
            margin-top: 20px;
        }

        .password-change {
            max-width: 400px;
        }

        .theme-settings {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .color-picker {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .color-picker input[type="color"] {
            width: 50px;
            height: 50px;
            padding: 0;
            border: none;
        }

        .tagify {
            background: var(--primary-bg);
            border-radius: 6px;
            --tags-border-color: transparent;
            --tags-hover-border-color: var(--accent-color);
            --tags-focus-border-color: var(--accent-color);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Admin Panel</h1>
            <div class="admin-nav">
                <a href="index.php" class="nav-link">View Gallery</a>
                <a href="#" class="nav-link" id="settingsBtn">Settings</a>
                <a href="logout.php" class="nav-link">Logout</a>
            </div>
        </div>

        <!-- Upload Panel -->
        <div class="panel">
            <h2>Upload Animation</h2>
            <div class="upload-tabs">
                <div class="tab active" data-tab="file">File Upload</div>
                <div class="tab" data-tab="url">URL Import</div>
            </div>

            <form action="upload.php" method="post" enctype="multipart/form-data" id="uploadForm">
                <div class="upload-section active" id="file-upload">
                    <div class="form-group">
                        <label>Choose Lottie File</label>
                        <input type="file" name="lottieFile" accept=".json,.lottie">
                    </div>
                </div>

                <div class="upload-section" id="url-upload" style="display: none;">
                    <div class="form-group">
                        <label>Lottie URL</label>
                        <input type="text" name="url" placeholder="Enter Lottie animation URL">
                    </div>
                </div>

                <div class="form-group">
                    <label>Animation Name</label>
                    <input type="text" name="name" required>
                </div>

                <div class="form-group">
                    <label>Tags</label>
                    <input type="text" name="tags" class="tags-input" placeholder="Add tags...">
                </div>

                <div class="form-group">
                    <label>Background Color</label>
                    <input type="color" name="background" value="#ffffff">
                </div>

                <div class="form-group">
                    <label>Categories</label>
                    <div class="category-chips">
                        <?php foreach ($categories as $category): ?>
                            <div class="category-chip" data-id="<?= $category['id'] ?>">
                                <?= htmlspecialchars($category['name']) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="categories" id="selectedCategories">
                </div>

                <button type="submit">Upload Animation</button>
            </form>
        </div>

        <!-- Category Management -->
        <div class="panel">
            <h2>Manage Categories</h2>
            <form action="add_category.php" method="post">
                <div class="form-group">
                    <label>Category Name</label>
                    <input type="text" name="category_name" required>
                </div>
                <div class="form-group">
                    <label>Display Order</label>
                    <input type="number" name="display_order" value="0">
                </div>
                <button type="submit">Add Category</button>
            </form>
        </div>

        <!-- Settings Panel -->
        <div class="panel settings-section" style="display: none;">
            <h2>Settings</h2>
            <div class="theme-settings">
                <div class="form-group">
                    <label>Primary Background</label>
                    <input type="color" id="primaryBg" value="#1a1a1a">
                </div>
                <div class="form-group">
                    <label>Secondary Background</label>
                    <input type="color" id="secondaryBg" value="#2d2d2d">
                </div>
                <div class="form-group">
                    <label>Accent Color</label>
                    <input type="color" id="accentColor" value="#007bff">
                </div>
            </div>
            <button onclick="saveTheme()">Save Theme</button>

            <div class="password-change">
                <h3>Change Password</h3>
                <form action="change_password.php" method="post">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                    <button type="submit">Change Password</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Initialize Tagify
        new Tagify(document.querySelector('.tags-input'));

        // Toast notification function
        function showToast(message, type = 'success') {
            Toastify({
                text: message,
                duration: 3000,
                gravity: "top",
                position: "right",
                backgroundColor: type === 'success' ? "#4CAF50" : "#f44336"
            }).showToast();
        }

        // Tab switching
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.upload-section').forEach(s => s.style.display = 'none');
                
                this.classList.add('active');
                document.getElementById(this.dataset.tab + '-upload').style.display = 'block';
            });
        });

        // Category chip selection
        document.querySelectorAll('.category-chip').forEach(chip => {
            chip.addEventListener('click', function() {
                this.classList.toggle('selected');
                updateSelectedCategories();
            });
        });

        function updateSelectedCategories() {
            const selected = Array.from(document.querySelectorAll('.category-chip.selected'))
                .map(chip => chip.dataset.id);
            document.getElementById('selectedCategories').value = selected.join(',');
        }

        // Settings panel toggle
        document.getElementById('settingsBtn').addEventListener('click', function(e) {
            e.preventDefault();
            const settingsPanel = document.querySelector('.settings-section');
            settingsPanel.style.display = settingsPanel.style.display === 'none' ? 'block' : 'none';
        });

        // Theme handling
        function saveTheme() {
            const theme = {
                primaryBg: document.getElementById('primaryBg').value,
                secondaryBg: document.getElementById('secondaryBg').value,
                accentColor: document.getElementById('accentColor').value
            };
            
            localStorage.setItem('siteTheme', JSON.stringify(theme));
            applyTheme(theme);
            showToast('Theme saved successfully');
        }

        function applyTheme(theme) {
            document.documentElement.style.setProperty('--primary-bg', theme.primaryBg);
            document.documentElement.style.setProperty('--secondary-bg', theme.secondaryBg);
            document.documentElement.style.setProperty('--accent-color', theme.accentColor);
        }

        // Load saved theme
        const savedTheme = localStorage.getItem('siteTheme');
        if (savedTheme) {
            const theme = JSON.parse(savedTheme);
            document.getElementById('primaryBg').value = theme.primaryBg;
            document.getElementById('secondaryBg').value = theme.secondaryBg;
            document.getElementById('accentColor').value = theme.accentColor;
            applyTheme(theme);
        }

        // Form submission
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('upload.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                try {
                    const result = JSON.parse(data);
                    if (result.success) {
                        showToast('Animation uploaded successfully');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast(result.message || 'Upload failed', 'error');
                    }
                } catch(e) {
                    if (data.includes('success')) {
                        showToast('Animation uploaded successfully');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('Upload failed', 'error');
                    }
                }
            })
            .catch(error => {
                showToast('Upload failed', 'error');
            });
        });

        // File name handling
        document.querySelector('input[type="file"]').addEventListener('change', function() {
            if (this.files[0]) {
                const fileName = this.files[0].name.replace(/\.[^/.]+$/, "");
                document.querySelector('input[name="name"]').value = fileName;
            }
        });
    </script>
</body>
</html>
