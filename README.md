# 工单管理系统

## 项目简介
这是一个基于PHP和JavaScript的工单管理系统，用于创建、编辑和管理工单信息。数据存储在JSON文件中，无需数据库。

## 功能特性

1. **工单列表页面**
   - 展示工单编号、工单主题、类别、工单状态、创建日期和创建人
   - 支持编辑和删除操作

2. **添加工单功能**
   - 工单主题
   - 类别选择（日常工单、事件工单、问题工单、变更工单）
   - 问题描述
   - 计划开始时间
   - 执行人
   - 计划结束时间

3. **编辑工单功能**
   - 修改工单信息
   - 保存后更新列表

4. **数据存储**
   - 使用JSON文件存储工单数据
   - 无需数据库

## 文件结构

```
.
├── index.html          # 主页面
├── style.css           # 样式文件
├── script.js           # 前端JavaScript
├── api.php             # 后端API接口
└── tickets.json        # 工单数据存储文件（自动生成）
```

## 使用方法

### 环境要求
- PHP 7.0 或更高版本
- 现代浏览器

### 运行方式

1. **使用PHP内置服务器**
```bash
php -S localhost:8000
```

2. **访问系统**
在浏览器中打开：`http://localhost:8000`

### 功能说明

1. **添加工单**
   - 点击页面左上方的"添加工单"按钮
   - 填写工单表单信息
   - 点击"保存"按钮

2. **编辑工单**
   - 在工单列表中找到要编辑的工单
   - 点击"编辑"按钮
   - 修改工单信息
   - 点击"保存"按钮

3. **删除工单**
   - 在工单列表中找到要删除的工单
   - 点击"删除"按钮
   - 确认删除操作

## API接口

### 获取工单列表
```
GET /api.php?action=list
```

### 添加工单
```
POST /api.php?action=add
Content-Type: application/json

{
    "subject": "工单主题",
    "category": "日常工单",
    "description": "问题描述",
    "plannedStart": "2023-01-01T00:00",
    "executor": "执行人",
    "plannedEnd": "2023-01-02T00:00"
}
```

### 更新工单
```
POST /api.php?action=update
Content-Type: application/json

{
    "id": "工单ID",
    "subject": "工单主题",
    "category": "日常工单",
    "description": "问题描述",
    "plannedStart": "2023-01-01T00:00",
    "executor": "执行人",
    "plannedEnd": "2023-01-02T00:00"
}
```

### 删除工单
```
GET /api.php?action=delete&id=工单ID
```

### 获取单个工单
```
GET /api.php?action=get&id=工单ID
```

## 数据结构

工单数据结构：

```json
{
    "id": "TICKET-123456",
    "subject": "工单主题",
    "category": "日常工单",
    "description": "问题描述",
    "plannedStart": "2023-01-01T00:00",
    "executor": "执行人",
    "plannedEnd": "2023-01-02T00:00",
    "status": "待处理",
    "createDate": "2023-01-01",
    "creator": "管理员"
}
```

## 注意事项

1. 首次运行时，系统会自动创建`tickets.json`文件
2. 确保PHP对当前目录有写入权限
3. 建议定期备份`tickets.json`文件
4. 如需使用Redis存储，可以修改`api.php`中的数据存储部分

## 技术栈

- **前端**: HTML5, CSS3, JavaScript
- **后端**: PHP
- **数据存储**: JSON文件
- **UI**: 响应式设计，支持移动端

## 扩展功能建议

1. 添加用户认证功能
2. 实现工单状态流转
3. 添加附件上传功能
4. 实现工单搜索和筛选
5. 添加邮件通知功能
6. 集成Redis缓存
7. 导出工单数据
8. 添加统计报表

## 许可证

MIT License