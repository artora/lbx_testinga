<?php
require_once 'config.php';
session_start();

function scanDirectory($dir) {
    $animations = [];
    if (is_dir($dir)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isFile() && (
                strtolower($file->getExtension()) == 'json' || 
                strtolower($file->getExtension()) == 'lottie'
            )) {
                $animations[] = [
                    'name' => $file->getBasename(),
                    'file_path' => str_replace('\\', '/', $file->getPathname()),
                    'tags' => '',
                    'category' => 'Uncategorized'
                ];
            }
        }
    }
    return $animations;
}

// Fetch animations with categories and tags
$stmt = $pdo->query("
    SELECT DISTINCT a.*, 
           GROUP_CONCAT(DISTINCT c.name) as category_names,
           GROUP_CONCAT(DISTINCT c.id) as category_ids
    FROM animations a 
    LEFT JOIN animation_categories ac ON a.id = ac.animation_id
    LEFT JOIN categories c ON ac.category_id = c.id 
    GROUP BY a.id
    ORDER BY a.created_at DESC
");
$dbAnimations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY display_order");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique animations
$existingPaths = array_column($dbAnimations, 'file_path');
$directoryAnimations = array_filter(scanDirectory('uploads'), function($anim) use ($existingPaths) {
    return !in_array($anim['file_path'], $existingPaths);
});

$allAnimations = array_merge($dbAnimations, $directoryAnimations);
?>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lottie Animation Gallery</title>
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
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
            padding: 0;
        }

        .header {
            background: var(--secondary-bg);
            padding: 20px;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .search-container {
            max-width: 600px;
            margin: 0 auto;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 12px 20px;
            border: none;
            border-radius: 25px;
            background: var(--primary-bg);
            color: var(--text-color);
            font-size: 16px;
        }

        .category-nav {
            display: flex;
            gap: 10px;
            padding: 20px;
            overflow-x: auto;
            margin-top: 80px;
            background: var(--secondary-bg);
        }

        .category-btn {
            padding: 8px 16px;
            border-radius: 20px;
            border: none;
            background: var(--primary-bg);
            color: var(--text-color);
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.3s;
        }

        .category-btn.active {
            background: var(--accent-color);
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px;
            margin-top: 140px;
        }

        .animation-card {
            background: var(--secondary-bg);
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            transition: transform 0.3s;
        }

        .animation-card:hover {
            transform: translateY(-5px);
        }

        .card-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 10px;
            background: rgba(0,0,0,0.7);
            display: flex;
            justify-content: space-between;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .animation-card:hover .card-overlay {
            opacity: 1;
        }

        .action-btn {
            padding: 8px;
            border-radius: 50%;
            border: none;
            background: var(--accent-color);
            color: white;
            cursor: pointer;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.9);
            z-index: 1000;
        }

        .modal-content {
            position: relative;
            width: 90%;
            max-width: 1200px;
            height: 90vh;
            margin: 5vh auto;
            background: var(--secondary-bg);
            border-radius: 12px;
            display: grid;
            grid-template-columns: 1fr 350px;
            overflow: hidden;
        }

        .modal-left {
            background: var(--primary-bg);
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .modal-right {
            padding: 24px;
            overflow-y: auto;
        }

        .player-container {
            width: 100%;
            max-width: 500px;
            max-height: 500px;
            aspect-ratio: 1;
            margin: 0 auto;
        }

        #modalPlayer {
            width: 100%;
            height: 100%;
            max-width: 500px;
            max-height: 500px;
        }

        .modal-title {
            font-size: 24px;
            margin: 0 0 20px;
            word-break: break-word;
        }

        .modal-section {
            margin-bottom: 24px;
            padding-bottom: 24px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .modal-section h3 {
            font-size: 16px;
            margin: 0 0 12px;
            color: #888;
        }

        .renderer-switch {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
            background: var(--primary-bg);
            padding: 4px;
            border-radius: 8px;
        }

        .renderer-option {
            flex: 1;
            padding: 8px;
            text-align: center;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .renderer-option.active {
            background: var(--accent-color);
        }

        .color-presets {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin: 10px 0;
        }

        .color-preset {
            width: 100%;
            aspect-ratio: 1;
            border: 2px solid transparent;
            border-radius: 8px;
            cursor: pointer;
        }

        .color-preset.active {
            border-color: var(--accent-color);
        }

        .tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin: 15px 0;
        }

        .tag {
            padding: 6px 12px;
            background: var(--accent-color);
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
        }

        .modal-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-top: 20px;
        }

        .modal-btn {
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: var(--accent-color);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .modal-btn.delete-btn {
            background: #dc3545;
        }

        .close-modal {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0,0,0,0.5);
            border: none;
            color: var(--text-color);
            font-size: 24px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1001;
        }

        @media (max-width: 768px) {
            .modal-content {
                grid-template-columns: 1fr;
                height: auto;
                max-height: 90vh;
            }
            
            .modal-right {
                max-height: 40vh;
                overflow-y: auto;
            }
        }
    </style>
</head>







<body>
    <div class="header">
        <div class="search-container">
            <input type="text" class="search-input" placeholder="Search animations by name, tag, or category...">
        </div>
    </div>

    <div class="category-nav">
        <button class="category-btn active" data-category="all">All</button>
        <?php foreach ($categories as $category): ?>
            <button class="category-btn" data-category="<?= htmlspecialchars($category['name']) ?>">
                <?= htmlspecialchars($category['name']) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="gallery-grid">
        <?php foreach ($allAnimations as $animation): ?>
            <div class="animation-card" 
                data-id="<?= htmlspecialchars($animation['id'] ?? '') ?>"
                data-category="<?= htmlspecialchars($animation['category_name'] ?? 'Uncategorized') ?>"
                data-name="<?= htmlspecialchars($animation['name']) ?>"
                data-tags="<?= htmlspecialchars($animation['tags'] ?? '') ?>">
                <lottie-player
                    src="<?= htmlspecialchars($animation['file_path'] ?? $animation['url']) ?>"
                    background="transparent"
                    speed="1"
                    hover
                    style="width: 100%; height: 200px;">
                </lottie-player>
                <div class="card-overlay">
                    <button class="action-btn copy-btn" title="Copy Embed Code">
                        <i class="fas fa-code"></i>
                    </button>
                    <button class="action-btn download-btn" title="Download">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<div class="modal" id="animationModal">
    <div class="modal-content">
        <button class="close-modal">&times;</button>
        <div class="modal-left">
            <div class="player-container">
                <lottie-player 
                    id="modalPlayer" 
                    background="transparent" 
                    speed="1" 
                    loop 
                    autoplay>
                </lottie-player>
            </div>
            <div class="player-controls">
                <div class="renderer-switch">
                    <div class="renderer-option active" data-renderer="svg">SVG</div>
                    <div class="renderer-option" data-renderer="canvas">Canvas</div>
                </div>
                <div class="color-picker-container">
                    <span>Background:</span>
                    <div class="color-presets">
                        <div class="color-preset active" style="background: #ffffff" data-color="#ffffff"></div>
                        <div class="color-preset" style="background: #000000" data-color="#000000"></div>
                        <div class="color-preset" style="background: #808080" data-color="#808080"></div>
                        <div class="custom-color">
                            <input type="color" id="customColor" value="#ffffff">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-right">
            <h2 class="modal-title" id="modalTitle"></h2>
            <div class="modal-section">
                <h3>Animation Details</h3>
                <div id="modalTags" class="tags-container"></div>
            </div>
            <div class="modal-actions">
                <button class="modal-btn" onclick="copyJsonUrl()">
                    <i class="fas fa-link"></i> Copy URL
                </button>
                <button class="modal-btn" onclick="copyEmbedCode()">
                    <i class="fas fa-code"></i> Copy Embed
                </button>
                <button class="modal-btn" onclick="downloadAnimation()">
                    <i class="fas fa-download"></i> Download
                </button>
                <?php if (isset($_SESSION['admin'])): ?>
                <button class="modal-btn edit-btn" onclick="editAnimation()">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="modal-btn delete-btn" onclick="deleteAnimation()">
                    <i class="fas fa-trash"></i> Delete
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
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

    // Search functionality
    document.querySelector('.search-input').addEventListener('input', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        document.querySelectorAll('.animation-card').forEach(card => {
            const name = card.dataset.name.toLowerCase();
            const tags = card.dataset.tags.toLowerCase();
            const category = card.dataset.category.toLowerCase();
            const visible = name.includes(searchTerm) || 
                          tags.includes(searchTerm) || 
                          category.includes(searchTerm);
            card.style.display = visible ? 'block' : 'none';
        });
    });

    // Category filtering
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const category = this.dataset.category;
            
            document.querySelectorAll('.animation-card').forEach(card => {
                const categories = card.dataset.category.split(',');
                if (category === 'all' || categories.includes(category)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // Modal handling
    const modal = document.getElementById('animationModal');
    const modalPlayer = document.getElementById('modalPlayer');

    document.querySelectorAll('.animation-card').forEach(card => {
        card.addEventListener('click', function(e) {
            if (!e.target.closest('.action-btn')) {
                const animation = {
                    id: this.dataset.id,
                    name: this.dataset.name,
                    src: this.querySelector('lottie-player').src,
                    tags: this.dataset.tags
                };
                openModal(animation);
            }
        });
    });

    function openModal(animation) {
        modalPlayer.load(animation.src);
        modalPlayer.dataset.animationId = animation.id;
        document.getElementById('modalTitle').textContent = animation.name;
        const tags = animation.tags || '';
        document.getElementById('modalTags').innerHTML = tags
            .split(',')
            .filter(tag => tag.trim())
            .map(tag => `<span class="tag">${tag.trim()}</span>`)
            .join('');
        modal.style.display = 'block';
        modalPlayer.play();
    }

    // Close modal
    document.querySelector('.close-modal').onclick = () => {
        modal.style.display = 'none';
    };

    // Renderer switching
    document.querySelectorAll('.renderer-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.renderer-option').forEach(opt => opt.classList.remove('active'));
            this.classList.add('active');
            modalPlayer.renderer = this.dataset.renderer;
        });
    });

    // Background color handling
    document.querySelectorAll('.color-preset').forEach(preset => {
        preset.addEventListener('click', function() {
            document.querySelectorAll('.color-preset').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            modalPlayer.background = this.dataset.color;
        });
    });

    document.getElementById('customColor').addEventListener('input', function(e) {
        modalPlayer.background = e.target.value;
        document.querySelectorAll('.color-preset').forEach(p => p.classList.remove('active'));
    });

    // Utility functions
    function copyJsonUrl() {
        navigator.clipboard.writeText(modalPlayer.src);
        showToast('JSON URL copied to clipboard!');
    }

    function copyEmbedCode() {
        const embedCode = `<lottie-player src="${modalPlayer.src}" background="transparent" speed="1" style="width: 300px; height: 300px;" hover></lottie-player>`;
        navigator.clipboard.writeText(embedCode);
        showToast('Embed code copied to clipboard!');
    }

    function downloadAnimation() {
        const link = document.createElement('a');
        link.href = modalPlayer.src;
        link.download = document.getElementById('modalTitle').textContent + '.json';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        showToast('Download started!');
    }

    // Edit and Delete functions for admin
    function editAnimation() {
        window.location.href = `admin.php?edit=${modalPlayer.dataset.animationId}`;
    }

    function deleteAnimation() {
        if (confirm('Are you sure you want to delete this animation?')) {
            fetch('delete_animation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: modalPlayer.dataset.animationId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Animation deleted successfully');
                    modal.style.display = 'none';
                    location.reload();
                } else {
                    showToast(data.message, 'error');
                }
            });
        }
    }

    // Button handlers
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const player = this.closest('.animation-card').querySelector('lottie-player');
            const embedCode = `<lottie-player src="${player.src}" background="transparent" speed="1" style="width: 300px; height: 300px;" hover></lottie-player>`;
            navigator.clipboard.writeText(embedCode);
            showToast('Embed code copied to clipboard!');
        });
    });

    document.querySelectorAll('.download-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const player = this.closest('.animation-card').querySelector('lottie-player');
            const name = this.closest('.animation-card').dataset.name;
            const link = document.createElement('a');
            link.href = player.src;
            link.download = name + '.json';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            showToast('Download started!');
        });
    });
</script>
</body>
</html>
