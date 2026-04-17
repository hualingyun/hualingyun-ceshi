<?php

namespace App\Http\Controllers;

use App\Models\Ticket;

class TicketController
{
    private $ticketModel;

    public function __construct()
    {
        $this->ticketModel = new Ticket();
    }

    private function jsonResponse($data, $statusCode = 200)
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function index()
    {
        $tickets = $this->ticketModel->all();
        $this->jsonResponse(['success' => true, 'data' => $tickets]);
    }

    public function show($id)
    {
        $ticket = $this->ticketModel->find($id);
        
        if ($ticket) {
            $this->jsonResponse(['success' => true, 'data' => $ticket]);
        } else {
            $this->jsonResponse(['success' => false, 'message' => '工单不存在'], 404);
        }
    }

    public function store()
    {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        
        // 如果JSON解析失败，尝试处理URL编码的数据
        if (json_last_error() !== JSON_ERROR_NONE) {
            parse_str($rawInput, $data);
        }
        
        // 验证工单编号
        if (empty($data['ticket_no'])) {
            $this->jsonResponse(['success' => false, 'message' => '工单编号不能为空'], 422);
            return;
        }

        // 验证工单编号格式：6-20字符、必须同时包含大小写字母和数字、以字母开头
        $ticketNo = $data['ticket_no'];
        $validFormat = preg_match('/^[a-zA-Z][a-zA-Z0-9]{5,19}$/', $ticketNo);
        $hasUpperCase = preg_match('/[A-Z]/', $ticketNo);
        $hasLowerCase = preg_match('/[a-z]/', $ticketNo);
        $hasNumber = preg_match('/[0-9]/', $ticketNo);

        if (!$validFormat || !$hasUpperCase || !$hasLowerCase || !$hasNumber) {
            $this->jsonResponse(['success' => false, 'message' => '工单编号格式不正确，需6-20字符、以字母开头、必须同时包含大小写字母和数字'], 422);
            return;
        }

        // 验证工单主题
        if (empty($data['subject'])) {
            $this->jsonResponse(['success' => false, 'message' => '工单主题不能为空'], 422);
            return;
        }

        // 验证工单类别
        $validCategories = ['日常工单', '事件工单'];
        $category = isset($data['category']) ? trim($data['category']) : '';
        if (empty($category) || !in_array($category, $validCategories, true)) {
            $this->jsonResponse(['success' => false, 'message' => '工单类别必须是"日常工单"或"事件工单"'], 422);
            return;
        }
        $data['category'] = $category;

        // 检查工单编号是否已存在
        $tickets = $this->ticketModel->all();
        foreach ($tickets as $ticket) {
            if ($ticket['ticket_no'] === $data['ticket_no']) {
                $this->jsonResponse(['success' => false, 'message' => '工单编号已存在'], 422);
                return;
            }
        }

        $ticket = $this->ticketModel->create($data);
        $this->jsonResponse(['success' => true, 'message' => '工单创建成功', 'data' => $ticket]);
    }

    public function update($id)
    {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        
        // 如果JSON解析失败，尝试处理URL编码的数据
        if (json_last_error() !== JSON_ERROR_NONE) {
            parse_str($rawInput, $data);
        }
        
        // 验证工单是否存在
        $existingTicket = $this->ticketModel->find($id);
        if (!$existingTicket) {
            $this->jsonResponse(['success' => false, 'message' => '工单不存在'], 404);
            return;
        }

        // 验证工单编号
        if (empty($data['ticket_no'])) {
            $this->jsonResponse(['success' => false, 'message' => '工单编号不能为空'], 422);
            return;
        }

        // 验证工单编号格式：6-20字符、必须同时包含大小写字母和数字、以字母开头
        $ticketNo = $data['ticket_no'];
        $validFormat = preg_match('/^[a-zA-Z][a-zA-Z0-9]{5,19}$/', $ticketNo);
        $hasUpperCase = preg_match('/[A-Z]/', $ticketNo);
        $hasLowerCase = preg_match('/[a-z]/', $ticketNo);
        $hasNumber = preg_match('/[0-9]/', $ticketNo);

        if (!$validFormat || !$hasUpperCase || !$hasLowerCase || !$hasNumber) {
            $this->jsonResponse(['success' => false, 'message' => '工单编号格式不正确，需6-20字符、以字母开头、必须同时包含大小写字母和数字'], 422);
            return;
        }

        // 验证工单主题
        if (empty($data['subject'])) {
            $this->jsonResponse(['success' => false, 'message' => '工单主题不能为空'], 422);
            return;
        }

        // 验证工单类别
        $validCategories = ['日常工单', '事件工单'];
        $category = isset($data['category']) ? trim($data['category']) : '';
        if (empty($category) || !in_array($category, $validCategories, true)) {
            $this->jsonResponse(['success' => false, 'message' => '工单类别必须是"日常工单"或"事件工单"'], 422);
            return;
        }
        $data['category'] = $category;

        // 检查工单编号是否与其他工单重复
        $tickets = $this->ticketModel->all();
        foreach ($tickets as $ticket) {
            if ($ticket['ticket_no'] === $data['ticket_no'] && $ticket['id'] != $id) {
                $this->jsonResponse(['success' => false, 'message' => '工单编号已存在'], 422);
                return;
            }
        }

        $ticket = $this->ticketModel->update($id, $data);
        $this->jsonResponse(['success' => true, 'message' => '工单更新成功', 'data' => $ticket]);
    }

    public function destroy($id)
    {
        $result = $this->ticketModel->delete($id);
        
        if ($result) {
            $this->jsonResponse(['success' => true, 'message' => '工单删除成功']);
        } else {
            $this->jsonResponse(['success' => false, 'message' => '工单不存在'], 404);
        }
    }
}
