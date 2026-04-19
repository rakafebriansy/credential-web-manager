<?php
error_reporting(0);
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Create table
$sql = "CREATE TABLE IF NOT EXISTS omg_notes (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    content LONGTEXT,
    color VARCHAR(20) DEFAULT 'default',
    is_pinned TINYINT(1) DEFAULT 0,
    is_archived TINYINT(1) DEFAULT 0,
    created_by INT(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
@mysqli_query($conn, $sql);

// Handle AJAX requests
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

if ($action) {
    header('Content-Type: application/json');
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
    
    switch ($action) {
        case 'get_notes':
            $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
            $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
            
            $where = "1=1";
            if ($filter == 'pinned') {
                $where .= " AND is_pinned = 1 AND is_archived = 0";
            } elseif ($filter == 'archived') {
                $where .= " AND is_archived = 1";
            } else {
                $where .= " AND is_archived = 0";
            }
            
            if ($search) {
                $where .= " AND (title LIKE '%$search%' OR content LIKE '%$search%')";
            }
            
            $sql = "SELECT n.*, u.full_name as author FROM omg_notes n 
                    LEFT JOIN users u ON n.created_by = u.id 
                    WHERE $where ORDER BY is_pinned DESC, updated_at DESC";
            $result = mysqli_query($conn, $sql);
            $notes = array();
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $notes[] = $row;
                }
            }
            echo json_encode(array('success' => true, 'notes' => $notes));
            exit;
            
        case 'save_note':
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $title = isset($_POST['title']) ? mysqli_real_escape_string($conn, $_POST['title']) : '';
            $content = isset($_POST['content']) ? mysqli_real_escape_string($conn, $_POST['content']) : '';
            $color = isset($_POST['color']) ? mysqli_real_escape_string($conn, $_POST['color']) : 'default';
            
            if (empty($title) && empty($content)) {
                echo json_encode(array('success' => false, 'message' => 'Empty note'));
                exit;
            }
            
            if ($id > 0) {
                $sql = "UPDATE omg_notes SET title='$title', content='$content', color='$color' WHERE id=$id";
            } else {
                $sql = "INSERT INTO omg_notes (title, content, color, created_by) VALUES ('$title', '$content', '$color', $user_id)";
            }
            
            $success = mysqli_query($conn, $sql);
            $newId = $id > 0 ? $id : mysqli_insert_id($conn);
            echo json_encode(array('success' => $success, 'id' => $newId));
            exit;
            
        case 'update_color':
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $color = isset($_POST['color']) ? mysqli_real_escape_string($conn, $_POST['color']) : 'default';
            $success = mysqli_query($conn, "UPDATE omg_notes SET color='$color' WHERE id=$id");
            echo json_encode(array('success' => $success));
            exit;
            
        case 'toggle_pin':
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $success = mysqli_query($conn, "UPDATE omg_notes SET is_pinned = NOT is_pinned WHERE id=$id");
            echo json_encode(array('success' => $success));
            exit;
            
        case 'toggle_archive':
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $success = mysqli_query($conn, "UPDATE omg_notes SET is_archived = NOT is_archived WHERE id=$id");
            echo json_encode(array('success' => $success));
            exit;
            
        case 'delete_note':
            $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
            $success = mysqli_query($conn, "DELETE FROM omg_notes WHERE id=$id");
            echo json_encode(array('success' => $success));
            exit;
            
        case 'get_stats':
            $total = 0; $pinned = 0; $archived = 0;
            $r1 = mysqli_query($conn, "SELECT COUNT(*) as c FROM omg_notes WHERE is_archived=0");
            if ($r1) { $row = mysqli_fetch_assoc($r1); $total = $row['c']; }
            $r2 = mysqli_query($conn, "SELECT COUNT(*) as c FROM omg_notes WHERE is_pinned=1 AND is_archived=0");
            if ($r2) { $row = mysqli_fetch_assoc($r2); $pinned = $row['c']; }
            $r3 = mysqli_query($conn, "SELECT COUNT(*) as c FROM omg_notes WHERE is_archived=1");
            if ($r3) { $row = mysqli_fetch_assoc($r3); $archived = $row['c']; }
            echo json_encode(array('success' => true, 'total' => $total, 'pinned' => $pinned, 'archived' => $archived));
            exit;
            
        default:
            echo json_encode(array('success' => false, 'message' => 'Invalid action'));
            exit;
    }
}

$page_title = 'OMG Notes';
$current_page = 'omg-notes.php';
include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="main-content">
    <div class="content-header">
        <div class="header-left">
            <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
            <h1><i class="fas fa-sticky-note"></i> OMG Notes</h1>
        </div>
        <div class="header-right">
            <div class="search-box-notes">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Cari catatan...">
            </div>
        </div>
    </div>
    <div class="notes-stats">
        <div class="note-stat-card"><div class="note-stat-icon total"><i class="fas fa-file-alt"></i></div><div class="note-stat-info"><span class="note-stat-value" id="totalNotes">0</span><span class="note-stat-label">Total Catatan</span></div></div>
        <div class="note-stat-card"><div class="note-stat-icon pinned"><i class="fas fa-thumbtack"></i></div><div class="note-stat-info"><span class="note-stat-value" id="pinnedNotes">0</span><span class="note-stat-label">Dipasangi Pin</span></div></div>
        <div class="note-stat-card"><div class="note-stat-icon archived"><i class="fas fa-archive"></i></div><div class="note-stat-info"><span class="note-stat-value" id="archivedNotes">0</span><span class="note-stat-label">Diarsipkan</span></div></div>
    </div>
    <div class="notes-filter-tabs">
        <button class="filter-tab active" onclick="setFilter('all', this)"><i class="fas fa-th-large"></i> Semua</button>
        <button class="filter-tab" onclick="setFilter('pinned', this)"><i class="fas fa-thumbtack"></i> Dipasangi Pin</button>
        <button class="filter-tab" onclick="setFilter('archived', this)"><i class="fas fa-archive"></i> Arsip</button>
    </div>
    <div class="note-composer" id="noteComposer">
        <div class="composer-collapsed" onclick="expandComposer()"><i class="fas fa-plus-circle"></i><span>Buat catatan baru...</span></div>
        <div class="composer-expanded">
            <input type="text" class="composer-title" id="newTitle" placeholder="Judul">
            <textarea class="composer-content" id="newContent" placeholder="Tulis catatan..."></textarea>
            <div class="composer-toolbar">
                <div class="color-palette" id="newColorPalette"></div>
                <div class="composer-actions">
                    <button type="button" class="btn-composer" onclick="collapseComposer()">Batal</button>
                    <button type="button" class="btn-composer btn-save" onclick="saveNewNote()"><i class="fas fa-check"></i> Simpan</button>
                </div>
            </div>
        </div>
    </div>
    <div class="notes-container">
        <div class="pinned-section" id="pinnedSection" style="display:none;"><h3 class="section-title"><i class="fas fa-thumbtack"></i> Dipasangi Pin</h3><div class="notes-grid" id="pinnedGrid"></div></div>
        <div class="other-section" id="otherSection"><h3 class="section-title" id="otherTitle" style="display:none;"><i class="fas fa-sticky-note"></i> Lainnya</h3><div class="notes-grid" id="notesGrid"></div></div>
        <div class="empty-state-notes" id="emptyState" style="display:none;"><i class="fas fa-lightbulb"></i><h3>Belum ada catatan</h3><p>Klik "Buat catatan baru" untuk memulai</p></div>
    </div>
</main>
<div class="modal-overlay" id="editModal">
    <div class="modal-note" id="editModalContent">
        <input type="hidden" id="editNoteId">
        <input type="text" class="modal-title" id="editTitle" placeholder="Judul">
        <textarea class="modal-content-area" id="editContent" placeholder="Catatan..."></textarea>
        <div class="modal-toolbar">
            <div class="color-palette" id="editColorPalette"></div>
            <div class="modal-actions">
                <button type="button" class="btn-icon" onclick="togglePin()" title="Pin"><i class="fas fa-thumbtack"></i></button>
                <button type="button" class="btn-icon" onclick="toggleArchive()" title="Arsip"><i class="fas fa-archive"></i></button>
                <button type="button" class="btn-icon btn-danger" onclick="deleteNote()" title="Hapus"><i class="fas fa-trash"></i></button>
                <button type="button" class="btn-close-modal" onclick="closeEditModal()">Tutup</button>
            </div>
        </div>
    </div>
</div>
<style>
.content-header{display:flex;justify-content:space-between;align-items:center;padding:20px 25px;background:var(--card-bg);border-bottom:1px solid var(--border-color);flex-wrap:wrap;gap:15px}
.header-left{display:flex;align-items:center;gap:15px}.header-left h1{margin:0;font-size:1.5rem;color:var(--text-primary);display:flex;align-items:center;gap:10px}.header-left h1 i{color:#f59e0b}
.search-box-notes{position:relative;width:300px}.search-box-notes i{position:absolute;left:15px;top:50%;transform:translateY(-50%);color:var(--text-secondary)}
.search-box-notes input{width:100%;padding:12px 15px 12px 45px;border:2px solid var(--border-color);border-radius:25px;background:var(--bg-primary);color:var(--text-primary);font-size:14px}
.search-box-notes input:focus{outline:none;border-color:#667eea}
.notes-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;padding:25px}
.note-stat-card{background:var(--card-bg);border-radius:16px;padding:20px;display:flex;align-items:center;gap:15px;box-shadow:0 4px 15px rgba(0,0,0,0.05);border:1px solid var(--border-color)}
.note-stat-icon{width:55px;height:55px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#fff}
.note-stat-icon.total{background:linear-gradient(135deg,#667eea,#764ba2)}.note-stat-icon.pinned{background:linear-gradient(135deg,#f59e0b,#d97706)}.note-stat-icon.archived{background:linear-gradient(135deg,#6b7280,#4b5563)}
.note-stat-info{display:flex;flex-direction:column}.note-stat-value{font-size:1.8rem;font-weight:700;color:var(--text-primary)}.note-stat-label{font-size:.85rem;color:var(--text-secondary)}
.notes-filter-tabs{display:flex;gap:10px;padding:0 25px 20px;flex-wrap:wrap}
.filter-tab{padding:10px 20px;border:2px solid var(--border-color);border-radius:25px;background:var(--card-bg);color:var(--text-secondary);font-size:14px;font-weight:500;cursor:pointer;display:flex;align-items:center;gap:8px}
.filter-tab:hover{border-color:#667eea;color:#667eea}.filter-tab.active{background:linear-gradient(135deg,#667eea,#764ba2);border-color:transparent;color:#fff}
.note-composer{max-width:650px;margin:0 auto 30px;padding:0 25px}
.composer-collapsed{background:var(--card-bg);border:2px dashed var(--border-color);border-radius:12px;padding:18px 20px;cursor:pointer;display:flex;align-items:center;gap:12px;color:var(--text-secondary)}
.composer-collapsed:hover{border-color:#667eea;color:#667eea}
.composer-expanded{display:none;background:var(--card-bg);border:1px solid var(--border-color);border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,0.12);overflow:hidden}
.note-composer.expanded .composer-collapsed{display:none}.note-composer.expanded .composer-expanded{display:block}
.composer-title,.modal-title{width:100%;padding:18px 20px 10px;border:none;background:transparent;font-size:1.1rem;font-weight:600;color:var(--text-primary)}
.composer-content,.modal-content-area{width:100%;min-height:120px;padding:10px 20px 15px;border:none;background:transparent;font-size:14px;color:var(--text-primary);resize:none;font-family:inherit;line-height:1.6}
.composer-title:focus,.composer-content:focus,.modal-title:focus,.modal-content-area:focus{outline:none}
.composer-toolbar,.modal-toolbar{display:flex;justify-content:space-between;align-items:center;padding:12px 15px;border-top:1px solid var(--border-color);flex-wrap:wrap;gap:10px}
.color-palette{display:flex;gap:6px;flex-wrap:wrap}
.color-dot{width:28px;height:28px;border-radius:50%;cursor:pointer;border:2px solid transparent;transition:all .2s}
.color-dot:hover{transform:scale(1.15)}.color-dot.selected{border-color:var(--text-primary);box-shadow:0 0 0 2px var(--card-bg)}
.color-dot.c-default{background:var(--card-bg);border:2px solid var(--border-color)!important}
.color-dot.c-red{background:#f28b82}.color-dot.c-orange{background:#fbbc04}.color-dot.c-yellow{background:#fff475}.color-dot.c-green{background:#ccff90}.color-dot.c-teal{background:#a7ffeb}.color-dot.c-blue{background:#cbf0f8}.color-dot.c-purple{background:#d7aefb}.color-dot.c-pink{background:#fdcfe8}
.composer-actions,.modal-actions{display:flex;gap:10px;align-items:center}
.btn-composer{padding:8px 18px;border:none;border-radius:6px;font-size:14px;cursor:pointer;background:var(--bg-primary);color:var(--text-secondary)}
.btn-composer:hover{background:var(--border-color)}.btn-composer.btn-save{background:linear-gradient(135deg,#667eea,#764ba2);color:#fff}

.notes-container{padding:0 25px 30px}.section-title{font-size:12px;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:1px;margin-bottom:15px;display:flex;align-items:center;gap:8px}.pinned-section{margin-bottom:30px}
.notes-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px}
.note-card{background:var(--card-bg);border:1px solid var(--border-color);border-radius:12px;padding:16px;cursor:pointer;transition:all .3s;position:relative;min-height:120px}
.note-card:hover{box-shadow:0 8px 25px rgba(0,0,0,0.1);transform:translateY(-2px)}.note-card:hover .card-actions{opacity:1}.note-card:hover .pin-btn{opacity:1}
.note-card.bg-default{background:var(--card-bg)}.note-card.bg-red{background:#f28b82;border-color:#f28b82;color:#000}.note-card.bg-orange{background:#fbbc04;border-color:#fbbc04;color:#000}.note-card.bg-yellow{background:#fff475;border-color:#fff475;color:#000}.note-card.bg-green{background:#ccff90;border-color:#ccff90;color:#000}.note-card.bg-teal{background:#a7ffeb;border-color:#a7ffeb;color:#000}.note-card.bg-blue{background:#cbf0f8;border-color:#cbf0f8;color:#000}.note-card.bg-purple{background:#d7aefb;border-color:#d7aefb;color:#000}.note-card.bg-pink{background:#fdcfe8;border-color:#fdcfe8;color:#000}
.pin-btn{position:absolute;top:10px;right:10px;width:32px;height:32px;border-radius:50%;border:none;background:rgba(0,0,0,0.1);color:inherit;cursor:pointer;opacity:0;transition:all .2s}.pin-btn:hover{background:rgba(0,0,0,0.2)}.pin-btn.pinned{opacity:1;color:#f59e0b}
.note-title{font-size:1rem;font-weight:600;margin-bottom:8px;padding-right:35px;word-break:break-word}
.note-content{font-size:13px;line-height:1.5;word-break:break-word;white-space:pre-wrap;max-height:200px;overflow:hidden}
.note-content pre{background:rgba(0,0,0,0.15);border-radius:6px;padding:10px;margin:8px 0;overflow-x:auto;font-family:monospace;font-size:12px}
.note-content code{font-family:monospace;background:rgba(0,0,0,0.1);padding:2px 6px;border-radius:4px}
.note-meta{display:flex;justify-content:space-between;align-items:center;margin-top:12px;padding-top:10px;border-top:1px solid rgba(0,0,0,0.1);font-size:11px;opacity:.7}
.card-actions{display:flex;gap:5px;opacity:0;transition:opacity .2s}
.card-action-btn{width:28px;height:28px;border-radius:50%;border:none;background:rgba(0,0,0,0.1);color:inherit;cursor:pointer;font-size:12px}.card-action-btn:hover{background:rgba(0,0,0,0.2)}
.modal-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;padding:20px}.modal-overlay.show{display:flex}
.modal-note{background:var(--card-bg);border-radius:12px;width:100%;max-width:600px;max-height:80vh;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.3)}
.modal-note.bg-red{background:#f28b82}.modal-note.bg-orange{background:#fbbc04}.modal-note.bg-yellow{background:#fff475}.modal-note.bg-green{background:#ccff90}.modal-note.bg-teal{background:#a7ffeb}.modal-note.bg-blue{background:#cbf0f8}.modal-note.bg-purple{background:#d7aefb}.modal-note.bg-pink{background:#fdcfe8}
.modal-content-area{min-height:200px;max-height:50vh;overflow-y:auto}
.btn-icon{width:36px;height:36px;border-radius:50%;border:none;background:transparent;color:var(--text-secondary);cursor:pointer;font-size:14px}.btn-icon:hover{background:var(--border-color);color:var(--text-primary)}.btn-icon.btn-danger:hover{background:#fee2e2;color:#dc2626}
.btn-close-modal{padding:8px 20px;border:none;border-radius:6px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;font-weight:500;cursor:pointer;margin-left:auto}
.empty-state-notes{text-align:center;padding:60px 20px;color:var(--text-secondary)}.empty-state-notes i{font-size:4rem;opacity:.2;margin-bottom:20px;color:#f59e0b;display:block}.empty-state-notes h3{margin-bottom:8px;color:var(--text-primary)}
@media(max-width:768px){.notes-stats{grid-template-columns:1fr}.search-box-notes{width:100%}.content-header{flex-direction:column;align-items:stretch}.notes-grid{grid-template-columns:repeat(auto-fill,minmax(200px,1fr))}}
</style>
