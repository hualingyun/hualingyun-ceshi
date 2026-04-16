<?php

namespace App\Models;

class Ticket
{
    private $storageFile;

    public function __construct()
    {
        $this->storageFile = __DIR__ . '/../../storage/tickets/tickets.json';
        $this->ensureStorageExists();
    }

    private function ensureStorageExists()
    {
        $dir = dirname($this->storageFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if (!file_exists($this->storageFile)) {
            file_put_contents($this->storageFile, json_encode([]));
        }
    }

    public function all()
    {
        $content = file_get_contents($this->storageFile);
        return json_decode($content, true) ?: [];
    }

    public function find($id)
    {
        $tickets = $this->all();
        foreach ($tickets as $ticket) {
            if ($ticket['id'] == $id) {
                return $ticket;
            }
        }
        return null;
    }

    public function create($data)
    {
        $tickets = $this->all();
        
        $ticket = [
            'id' => time(),
            'ticket_no' => $data['ticket_no'],
            'subject' => $data['subject'],
            'category' => $data['category'],
            'description' => $data['description'] ?? '',
            'status' => $data['status'] ?? '待处理',
            'planned_start_time' => $data['planned_start_time'] ?? null,
            'planned_end_time' => $data['planned_end_time'] ?? null,
            'assignee' => $data['assignee'] ?? '',
            'creator' => $data['creator'] ?? '管理员',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $tickets[] = $ticket;
        $this->save($tickets);

        return $ticket;
    }

    public function update($id, $data)
    {
        $tickets = $this->all();
        
        foreach ($tickets as &$ticket) {
            if ($ticket['id'] == $id) {
                $ticket['ticket_no'] = $data['ticket_no'];
                $ticket['subject'] = $data['subject'];
                $ticket['category'] = $data['category'];
                $ticket['description'] = $data['description'] ?? '';
                $ticket['planned_start_time'] = $data['planned_start_time'] ?? null;
                $ticket['planned_end_time'] = $data['planned_end_time'] ?? null;
                $ticket['assignee'] = $data['assignee'] ?? '';
                $ticket['status'] = $data['status'] ?? $ticket['status'];
                $ticket['updated_at'] = date('Y-m-d H:i:s');
                
                $this->save($tickets);
                return $ticket;
            }
        }

        return null;
    }

    public function delete($id)
    {
        $tickets = $this->all();
        $found = false;
        
        foreach ($tickets as $key => $ticket) {
            if ($ticket['id'] == $id) {
                unset($tickets[$key]);
                $found = true;
                break;
            }
        }

        if ($found) {
            $this->save(array_values($tickets));
            return true;
        }

        return false;
    }

    private function save($tickets)
    {
        file_put_contents($this->storageFile, json_encode($tickets, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
