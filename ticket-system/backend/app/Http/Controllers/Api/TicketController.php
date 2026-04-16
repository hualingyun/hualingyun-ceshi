<?php

namespace App\Http\Controllers\Api;

use App\Models\Ticket;

class TicketController
{
    private $ticketModel;

    public function __construct()
    {
        $this->ticketModel = new Ticket();
    }

    public function index()
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        $tickets = $this->ticketModel->all();
        echo json_encode([
            'code' => 200,
            'message' => 'success',
            'data' => $tickets
        ]);
    }

    public function store()
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$this->validateTicketNo($input['ticket_no'] ?? '')) {
            http_response_code(400);
            echo json_encode([
                'code' => 400,
                'message' => '工单编号格式不正确，必须是6-20位，以字母开头，只能包含字母和数字'
            ]);
            return;
        }

        if (empty($input['subject'])) {
            http_response_code(400);
            echo json_encode([
                'code' => 400,
                'message' => '工单主题不能为空'
            ]);
            return;
        }

        if (empty($input['category'])) {
            http_response_code(400);
            echo json_encode([
                'code' => 400,
                'message' => '工单类别不能为空'
            ]);
            return;
        }

        $ticket = $this->ticketModel->create($input);
        
        echo json_encode([
            'code' => 200,
            'message' => '工单创建成功',
            'data' => $ticket
        ]);
    }

    public function show($id)
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        $ticket = $this->ticketModel->find($id);
        
        if (!$ticket) {
            http_response_code(404);
            echo json_encode([
                'code' => 404,
                'message' => '工单不存在'
            ]);
            return;
        }

        echo json_encode([
            'code' => 200,
            'message' => 'success',
            'data' => $ticket
        ]);
    }

    public function update($id)
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!empty($input['ticket_no']) && !$this->validateTicketNo($input['ticket_no'])) {
            http_response_code(400);
            echo json_encode([
                'code' => 400,
                'message' => '工单编号格式不正确，必须是6-20位，以字母开头，只能包含字母和数字'
            ]);
            return;
        }

        $ticket = $this->ticketModel->update($id, $input);
        
        if (!$ticket) {
            http_response_code(404);
            echo json_encode([
                'code' => 404,
                'message' => '工单不存在'
            ]);
            return;
        }

        echo json_encode([
            'code' => 200,
            'message' => '工单更新成功',
            'data' => $ticket
        ]);
    }

    public function destroy($id)
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        $result = $this->ticketModel->delete($id);
        
        if (!$result) {
            http_response_code(404);
            echo json_encode([
                'code' => 404,
                'message' => '工单不存在'
            ]);
            return;
        }

        echo json_encode([
            'code' => 200,
            'message' => '工单删除成功'
        ]);
    }

    private function validateTicketNo($ticketNo)
    {
        if (empty($ticketNo)) {
            return false;
        }
        
        if (strlen($ticketNo) < 6 || strlen($ticketNo) > 20) {
            return false;
        }
        
        // 必须以字母开头
        if (!preg_match('/^[a-zA-Z]/', $ticketNo)) {
            return false;
        }
        
        // 只能包含字母和数字
        if (!preg_match('/^[a-zA-Z0-9]+$/', $ticketNo)) {
            return false;
        }
        
        // 必须同时包含小写字母、大写字母和数字
        if (!preg_match('/[a-z]/', $ticketNo) || !preg_match('/[A-Z]/', $ticketNo) || !preg_match('/[0-9]/', $ticketNo)) {
            return false;
        }
        
        return true;
    }
}
