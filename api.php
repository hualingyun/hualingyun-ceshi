<?php
/**
 * 工单管理API接口
 */

require_once __DIR__ . '/WorkorderStorage.php';
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

// 获取请求方法和参数
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

$storage = new WorkorderStorage();

// 处理跨域请求
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    switch ($action) {
        case 'list':
            // 获取工单列表
            $workorders = $storage->getAll();
            echo json_encode([
                'code' => 200,
                'message' => 'success',
                'data' => $workorders
            ]);
            break;
            
        case 'get':
            // 获取单个工单
            $id = isset($_GET['id']) ? $_GET['id'] : '';
            if (empty($id)) {
                throw new Exception('工单ID不能为空');
            }
            
            $workorder = $storage->getById($id);
            if (!$workorder) {
                throw new Exception('工单不存在');
            }
            
            echo json_encode([
                'code' => 200,
                'message' => 'success',
                'data' => $workorder
            ]);
            break;
            
        case 'add':
            // 添加工单
            if ($method !== 'POST') {
                throw new Exception('请求方法错误');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            // 验证必填字段
            if (empty($input['subject'])) {
                throw new Exception('工单主题不能为空');
            }
            if (empty($input['category'])) {
                throw new Exception('工单类别不能为空');
            }
            
            // 设置创建人（实际项目中应从登录会话获取）
            $input['creator'] = isset($input['creator']) ? $input['creator'] : '管理员';
            
            $result = $storage->add($input);
            if ($result) {
                echo json_encode([
                    'code' => 200,
                    'message' => '工单添加成功',
                    'data' => $result
                ]);
            } else {
                throw new Exception('工单添加失败');
            }
            break;
            
        case 'update':
            // 更新工单
            if ($method !== 'POST' && $method !== 'PUT') {
                throw new Exception('请求方法错误');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $id = isset($input['id']) ? $input['id'] : '';
            
            if (empty($id)) {
                throw new Exception('工单ID不能为空');
            }
            
            // 移除id字段，不作为更新内容
            unset($input['id']);
            unset($input['workorder_no']);
            unset($input['created_at']);
            
            $result = $storage->update($id, $input);
            if ($result) {
                echo json_encode([
                    'code' => 200,
                    'message' => '工单更新成功',
                    'data' => $result
                ]);
            } else {
                throw new Exception('工单更新失败或工单不存在');
            }
            break;
            
        case 'delete':
            // 删除工单
            if ($method !== 'POST' && $method !== 'DELETE') {
                throw new Exception('请求方法错误');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $id = isset($input['id']) ? $input['id'] : (isset($_GET['id']) ? $_GET['id'] : '');
            
            if (empty($id)) {
                throw new Exception('工单ID不能为空');
            }
            
            $result = $storage->delete($id);
            if ($result) {
                echo json_encode([
                    'code' => 200,
                    'message' => '工单删除成功',
                    'data' => $result
                ]);
            } else {
                throw new Exception('工单删除失败或工单不存在');
            }
            break;
            
        case 'categories':
            // 获取工单类别
            global $WORKORDER_CATEGORIES;
            echo json_encode([
                'code' => 200,
                'message' => 'success',
                'data' => $WORKORDER_CATEGORIES
            ]);
            break;
            
        case 'status':
            // 获取工单状态
            global $WORKORDER_STATUS;
            echo json_encode([
                'code' => 200,
                'message' => 'success',
                'data' => $WORKORDER_STATUS
            ]);
            break;
            
        default:
            throw new Exception('未知的操作类型');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'code' => 400,
        'message' => $e->getMessage(),
        'data' => null
    ]);
}
