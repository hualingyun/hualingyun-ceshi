<?php
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$message = '';
$messageType = '';
$editContent = null;

$search = trim($_GET['search'] ?? '');
$page = intval($_GET['page'] ?? 1);
$perPage = 5;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $title = trim($_POST['title'] ?? '');
        $content = $_POST['content'] ?? '';
        $type = $_POST['type'] ?? 'article';
        $order = intval($_POST['order'] ?? 0);
        
        if (empty($title) || empty($content)) {
            $message = '请填写标题和内容';
            $messageType = 'error';
        } else {
            $result = addContent($title, $content, $type, $order);
            if ($result['success']) {
                $message = $result['message'];
                $messageType = 'success';
            } else {
                $message = $result['message'];
                $messageType = 'error';
            }
        }
    } elseif ($action === 'update' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        $title = trim($_POST['title'] ?? '');
        $content = $_POST['content'] ?? '';
        $type = $_POST['type'] ?? 'article';
        $order = intval($_POST['order'] ?? 0);
        
        if (empty($title) || empty($content)) {
            $message = '请填写标题和内容';
            $messageType = 'error';
        } else {
            $result = updateContent($id, $title, $content, $type, $order);
            if ($result['success']) {
                $message = $result['message'];
                $messageType = 'success';
            } else {
                $message = $result['message'];
                $messageType = 'error';
            }
        }
    } elseif ($action === 'delete' && isset($_POST['id'])) {
        $result = deleteContent(intval($_POST['id']));
        if ($result['success']) {
            $message = $result['message'];
            $messageType = 'success';
        } else {
            $message = $result['message'];
            $messageType = 'error';
        }
    }
}

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $editContent = getContentById(intval($_GET['edit']));
}

$allContents = getSortedContents();

if ($search) {
    $filteredContents = [];
    foreach ($allContents as $content) {
        if (stripos($content['title'], $search) !== false) {
            $filteredContents[] = $content;
        }
    }
    $allContents = $filteredContents;
}

$totalCount = count($allContents);
$totalPages = ceil($totalCount / $perPage);
if ($page < 1) $page = 1;
if ($page > $totalPages && $totalPages > 0) $page = $totalPages;
$offset = ($page - 1) * $perPage;
$contents = array_slice($allContents, $offset, $perPage);

function buildUrl($params = []) {
    $query = array_merge($_GET, $params);
    return '?' . http_build_query($query);
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>内容管理 - 培训管理系统</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .search-bar input {
            flex: 1;
            max-width: 400px;
        }
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .pagination a,
        .pagination span {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .pagination a {
            background: #f0f2f5;
            color: #333;
        }
        .pagination a:hover {
            background: #667eea;
            color: white;
        }
        .pagination .current {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: bold;
        }
        .pagination .disabled {
            background: #f5f5f5;
            color: #ccc;
            cursor: not-allowed;
        }
        .pagination-info {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="page-header">
                <h1>内容管理</h1>
                <p>发布和管理培训学习内容</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <div class="card">
                <h2 style="margin-bottom: 20px;">
                    <?php echo $editContent ? '编辑内容' : '发布新内容'; ?>
                </h2>
                <form method="POST" action="contents.php">
                    <input type="hidden" name="action" value="<?php echo $editContent ? 'update' : 'add'; ?>">
                    <?php if ($editContent): ?>
                        <input type="hidden" name="id" value="<?php echo $editContent['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>内容标题</label>
                            <input type="text" name="title" placeholder="请输入内容标题" 
                                   value="<?php echo htmlspecialchars($editContent['title'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>内容类型</label>
                            <select name="type">
                                <option value="article" <?php echo ($editContent['type'] ?? '') === 'article' ? 'selected' : ''; ?>>文章</option>
                                <option value="video" <?php echo ($editContent['type'] ?? '') === 'video' ? 'selected' : ''; ?>>视频</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>学习顺序</label>
                            <input type="number" name="order" placeholder="学习顺序" 
                                   value="<?php echo htmlspecialchars($editContent['order'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>内容</label>
                        <textarea name="content" placeholder="请输入内容（文章正文或视频URL）" required><?php echo htmlspecialchars($editContent['content'] ?? ''); ?></textarea>
                        <small style="color: #666; font-size: 12px;">
                            提示：视频类型请输入视频URL（如YouTube链接或直接视频文件地址），文章类型输入正文内容。
                        </small>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-success">
                            <?php echo $editContent ? '更新内容' : '发布内容'; ?>
                        </button>
                        <?php if ($editContent): ?>
                            <a href="contents.php" class="btn btn-secondary">取消编辑</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="margin: 0;">
                        内容列表
                        <?php if ($search): ?>
                            <span style="font-size: 14px; color: #666; font-weight: normal;">
                                （搜索："<?php echo htmlspecialchars($search); ?>"，找到 <?php echo $totalCount; ?> 条）
                            </span>
                        <?php else: ?>
                            <span style="font-size: 14px; color: #666; font-weight: normal;">
                                （共 <?php echo $totalCount; ?> 条）
                            </span>
                        <?php endif; ?>
                    </h2>
                </div>
                
                <form method="GET" action="" class="search-bar">
                    <input type="text" name="search" placeholder="按标题搜索..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary" style="width: auto;">🔍 搜索</button>
                    <?php if ($search): ?>
                        <a href="contents.php" class="btn btn-secondary" style="width: auto;">清除搜索</a>
                    <?php endif; ?>
                </form>
                
                <?php if (empty($allContents)): ?>
                    <div class="empty-state">
                        <div class="icon">📚</div>
                        <h3><?php echo $search ? '未找到匹配内容' : '暂无内容'; ?></h3>
                        <p><?php echo $search ? '请尝试其他搜索关键词' : '请在上方发布您的第一条培训内容'; ?></p>
                    </div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>顺序</th>
                                <th>标题</th>
                                <th>类型</th>
                                <th>更新时间</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contents as $content): ?>
                                <tr>
                                    <td><?php echo $content['order']; ?></td>
                                    <td><?php echo htmlspecialchars($content['title']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $content['type'] === 'article' ? 'badge-article' : 'badge-video'; ?>">
                                            <?php echo $content['type'] === 'article' ? '文章' : '视频'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($content['updated_at']); ?></td>
                                    <td>
                                        <a href="contents.php?edit=<?php echo $content['id']; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>&page=<?php echo $page; ?>" class="btn btn-warning btn-sm">编辑</a>
                                        <form method="POST" action="" style="display: inline;" onsubmit="return confirm('确定要删除该内容吗？');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $content['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">删除</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="<?php echo buildUrl(['page' => $page - 1]); ?>">« 上一页</a>
                            <?php else: ?>
                                <span class="disabled">« 上一页</span>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php if ($i === $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php elseif ($i === 1 || $i === $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                    <a href="<?php echo buildUrl(['page' => $i]); ?>"><?php echo $i; ?></a>
                                <?php elseif ($i === $page - 3 || $i === $page + 3): ?>
                                    <span>...</span>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="<?php echo buildUrl(['page' => $page + 1]); ?>">下一页 »</a>
                            <?php else: ?>
                                <span class="disabled">下一页 »</span>
                            <?php endif; ?>
                        </div>
                        <div class="pagination-info">
                            第 <?php echo $page; ?> / <?php echo $totalPages; ?> 页，显示 <?php echo $offset + 1; ?> - <?php echo min($offset + $perPage, $totalCount); ?> 条
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
