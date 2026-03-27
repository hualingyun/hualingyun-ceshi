<?php
/**
 * 工单数据存储类
 * 支持文件存储和Redis存储两种方式
 */

require_once __DIR__ . '/config.php';

class WorkorderStorage {
    private $storageType;
    private $redis = null;
    
    public function __construct() {
        $this->storageType = STORAGE_TYPE;
        
        if ($this->storageType === 'redis') {
            $this->initRedis();
        }
    }
    
    /**
     * 初始化Redis连接
     */
    private function initRedis() {
        try {
            $this->redis = new Redis();
            $this->redis->connect(REDIS_HOST, REDIS_PORT);
            $this->redis->select(REDIS_DB);
        } catch (Exception $e) {
            // Redis连接失败，回退到文件存储
            $this->storageType = 'file';
            error_log('Redis连接失败，已切换到文件存储: ' . $e->getMessage());
        }
    }
    
    /**
     * 获取所有工单
     */
    public function getAll() {
        if ($this->storageType === 'redis') {
            return $this->getAllFromRedis();
        }
        return $this->getAllFromFile();
    }
    
    /**
     * 根据ID获取工单
     */
    public function getById($id) {
        $workorders = $this->getAll();
        foreach ($workorders as $workorder) {
            if ($workorder['id'] === $id) {
                return $workorder;
            }
        }
        return null;
    }
    
    /**
     * 添加工单
     */
    public function add($data) {
        $workorders = $this->getAll();
        
        // 生成工单编号
        $data['id'] = $this->generateId();
        $data['workorder_no'] = 'WO' . date('Ymd') . str_pad(count($workorders) + 1, 4, '0', STR_PAD_LEFT);
        $data['status'] = 'pending';
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $workorders[] = $data;
        
        if ($this->storageType === 'redis') {
            return $this->saveAllToRedis($workorders);
        }
        return $this->saveAllToFile($workorders);
    }
    
    /**
     * 更新工单
     */
    public function update($id, $data) {
        $workorders = $this->getAll();
        
        foreach ($workorders as &$workorder) {
            if ($workorder['id'] === $id) {
                $workorder = array_merge($workorder, $data);
                $workorder['updated_at'] = date('Y-m-d H:i:s');
                
                if ($this->storageType === 'redis') {
                    return $this->saveAllToRedis($workorders);
                }
                return $this->saveAllToFile($workorders);
            }
        }
        
        return false;
    }
    
    /**
     * 删除工单
     */
    public function delete($id) {
        $workorders = $this->getAll();
        
        foreach ($workorders as $key => $workorder) {
            if ($workorder['id'] === $id) {
                unset($workorders[$key]);
                
                if ($this->storageType === 'redis') {
                    return $this->saveAllToRedis(array_values($workorders));
                }
                return $this->saveAllToFile(array_values($workorders));
            }
        }
        
        return false;
    }
    
    /**
     * 从文件获取所有工单
     */
    private function getAllFromFile() {
        $content = file_get_contents(WORKORDER_FILE);
        $data = json_decode($content, true);
        return $data ?: [];
    }
    
    /**
     * 保存所有工单到文件
     */
    private function saveAllToFile($workorders) {
        return file_put_contents(WORKORDER_FILE, json_encode($workorders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
    }
    
    /**
     * 从Redis获取所有工单
     */
    private function getAllFromRedis() {
        $keys = $this->redis->keys(REDIS_KEY_PREFIX . '*');
        $workorders = [];
        
        foreach ($keys as $key) {
            $data = $this->redis->get($key);
            if ($data) {
                $workorders[] = json_decode($data, true);
            }
        }
        
        // 按创建时间排序
        usort($workorders, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return $workorders;
    }
    
    /**
     * 保存所有工单到Redis
     */
    private function saveAllToRedis($workorders) {
        try {
            // 清除现有数据
            $keys = $this->redis->keys(REDIS_KEY_PREFIX . '*');
            if (!empty($keys)) {
                $this->redis->del($keys);
            }
            
            // 保存新数据
            foreach ($workorders as $workorder) {
                $key = REDIS_KEY_PREFIX . $workorder['id'];
                $this->redis->set($key, json_encode($workorder, JSON_UNESCAPED_UNICODE));
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Redis保存失败: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 生成唯一ID
     */
    private function generateId() {
        return uniqid('wo_', true);
    }
}
