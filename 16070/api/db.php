<?php
require_once 'config.php';

class DB {
    private static $files = [
        'users' => 'users.json',
        'schedules' => 'schedules.json',
        'attendances' => 'attendances.json',
        'logs' => 'logs.json'
    ];

    private static function getFilePath($table) {
        if (!isset(self::$files[$table])) {
            throw new Exception('Invalid table');
        }
        return DATA_DIR . self::$files[$table];
    }

    public static function init() {
        foreach (self::$files as $table => $file) {
            $path = self::getFilePath($table);
            if (!file_exists($path)) {
                $data = [];
                if ($table === 'users') {
                    $data = [
                        [
                            'id' => 1,
                            'username' => DEFAULT_ADMIN_USER,
                            'password' => hash_password(DEFAULT_ADMIN_PASS),
                            'name' => '系统管理员',
                            'role' => 'admin',
                            'created_at' => date('Y-m-d H:i:s')
                        ]
                    ];
                }
                file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            }
        }
    }

    public static function read($table) {
        $path = self::getFilePath($table);
        if (!file_exists($path)) {
            return [];
        }
        $content = file_get_contents($path);
        return json_decode($content, true) ?: [];
    }

    public static function write($table, $data) {
        $path = self::getFilePath($table);
        file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    public static function insert($table, $record) {
        $data = self::read($table);
        $maxId = 0;
        foreach ($data as $item) {
            if ($item['id'] > $maxId) $maxId = $item['id'];
        }
        $record['id'] = $maxId + 1;
        $record['created_at'] = date('Y-m-d H:i:s');
        $data[] = $record;
        self::write($table, $data);
        return $record;
    }

    public static function update($table, $id, $updates) {
        $data = self::read($table);
        foreach ($data as &$item) {
            if ($item['id'] == $id) {
                $item = array_merge($item, $updates);
                $item['updated_at'] = date('Y-m-d H:i:s');
                self::write($table, $data);
                return $item;
            }
        }
        return null;
    }

    public static function delete($table, $id) {
        $data = self::read($table);
        $newData = [];
        $found = false;
        foreach ($data as $item) {
            if ($item['id'] == $id) {
                $found = true;
            } else {
                $newData[] = $item;
            }
        }
        if ($found) {
            self::write($table, $newData);
            return true;
        }
        return false;
    }

    public static function find($table, $id) {
        $data = self::read($table);
        foreach ($data as $item) {
            if ($item['id'] == $id) {
                return $item;
            }
        }
        return null;
    }

    public static function findBy($table, $key, $value) {
        $data = self::read($table);
        $results = [];
        foreach ($data as $item) {
            if ($item[$key] == $value) {
                $results[] = $item;
            }
        }
        return $results;
    }

    public static function findOneBy($table, $key, $value) {
        $data = self::read($table);
        foreach ($data as $item) {
            if ($item[$key] == $value) {
                return $item;
            }
        }
        return null;
    }
}

DB::init();
