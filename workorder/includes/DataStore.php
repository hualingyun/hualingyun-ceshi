<?php

class DataStore {
    private $storageType;
    private $filePath;
    private $redis;
    
    public function __construct($storageType = 'file') {
        $this->storageType = $storageType;
        $this->filePath = __DIR__ . '/../storage/workorders.json';
        
        if ($storageType === 'redis') {
            $this->initRedis();
        }
    }
    
    private function initRedis() {
        try {
            $this->redis = new Redis();
            $this->redis->connect('127.0.0.1', 6379);
        } catch (Exception $e) {
            $this->storageType = 'file';
        }
    }
    
    public function getAllWorkOrders() {
        if ($this->storageType === 'redis' && $this->redis) {
            $data = $this->redis->get('workorders');
            return $data ? json_decode($data, true) : [];
        } else {
            if (!file_exists($this->filePath)) {
                return [];
            }
            $content = file_get_contents($this->filePath);
            return json_decode($content, true) ?: [];
        }
    }
    
    public function saveWorkOrder($workOrder) {
        $workOrders = $this->getAllWorkOrders();
        
        if (isset($workOrder['id'])) {
            $found = false;
            foreach ($workOrders as &$wo) {
                if ($wo['id'] === $workOrder['id']) {
                    $wo = $workOrder;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $workOrders[] = $workOrder;
            }
        } else {
            $workOrder['id'] = $this->generateId();
            $workOrder['created_at'] = date('Y-m-d H:i:s');
            $workOrders[] = $workOrder;
        }
        
        return $this->saveAllWorkOrders($workOrders);
    }
    
    public function getWorkOrderById($id) {
        $workOrders = $this->getAllWorkOrders();
        foreach ($workOrders as $workOrder) {
            if ($workOrder['id'] === $id) {
                return $workOrder;
            }
        }
        return null;
    }
    
    public function deleteWorkOrder($id) {
        $workOrders = $this->getAllWorkOrders();
        $workOrders = array_filter($workOrders, function($wo) use ($id) {
            return $wo['id'] !== $id;
        });
        return $this->saveAllWorkOrders(array_values($workOrders));
    }
    
    private function saveAllWorkOrders($workOrders) {
        if ($this->storageType === 'redis' && $this->redis) {
            return $this->redis->set('workorders', json_encode($workOrders));
        } else {
            $dir = dirname($this->filePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            return file_put_contents($this->filePath, json_encode($workOrders, JSON_PRETTY_PRINT)) !== false;
        }
    }
    
    private function generateId() {
        return 'WO' . date('Ymd') . strtoupper(substr(uniqid(), -6));
    }
}
