package handlers

import (
	"blog-system/internal/storage"
	"net/http"
	"sort"
	"strconv"

	"github.com/gin-gonic/gin"
)

// BlogHandler 博客处理器
type BlogHandler struct {
	blogStore     *storage.BlogStorage
	categoryStore *storage.CategoryStorage
}

// NewBlogHandler 创建博客处理器
func NewBlogHandler(blogStore *storage.BlogStorage, categoryStore *storage.CategoryStorage) *BlogHandler {
	return &BlogHandler{
		blogStore:     blogStore,
		categoryStore: categoryStore,
	}
}

// BlogResponse 博客响应
type BlogResponse struct {
	ID           int    `json:"id"`
	Title        string `json:"title"`
	CategoryID   int    `json:"category_id"`
	CategoryName string `json:"category_name"`
	Content      string `json:"content"`
	IsRecommend  bool   `json:"is_recommend"`
	IsTop        bool   `json:"is_top"`
	CreatedAt    string `json:"created_at"`
	UpdatedAt    string `json:"updated_at"`
}

// GetAll 获取所有博客
func (h *BlogHandler) GetAll(c *gin.Context) {
	blogs := h.blogStore.GetAll()
	categories := h.categoryStore.GetAll()

	// 构建类别ID到名称的映射
	categoryMap := make(map[int]string)
	for _, cat := range categories {
		categoryMap[cat.ID] = cat.Name
	}

	// 转换为响应格式
	var response []BlogResponse
	for _, blog := range blogs {
		response = append(response, BlogResponse{
			ID:           blog.ID,
			Title:        blog.Title,
			CategoryID:   blog.CategoryID,
			CategoryName: categoryMap[blog.CategoryID],
			Content:      blog.Content,
			IsRecommend:  blog.IsRecommend,
			IsTop:        blog.IsTop,
			CreatedAt:    blog.CreatedAt.Format("2006-01-02 15:04:05"),
			UpdatedAt:    blog.UpdatedAt.Format("2006-01-02 15:04:05"),
		})
	}

	// 排序：置顶的博客排在前面，然后按创建时间倒序
	sort.Slice(response, func(i, j int) bool {
		if response[i].IsTop != response[j].IsTop {
			return response[i].IsTop
		}
		// 如果都是置顶或都不是置顶，按创建时间倒序
		return response[i].CreatedAt > response[j].CreatedAt
	})

	c.JSON(http.StatusOK, response)
}

// GetByID 根据ID获取博客
func (h *BlogHandler) GetByID(c *gin.Context) {
	idStr := c.Param("id")
	id, err := strconv.Atoi(idStr)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "无效的ID"})
		return
	}

	blog, err := h.blogStore.GetByID(id)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{"error": err.Error()})
		return
	}

	categories := h.categoryStore.GetAll()
	categoryMap := make(map[int]string)
	for _, cat := range categories {
		categoryMap[cat.ID] = cat.Name
	}

	response := BlogResponse{
		ID:           blog.ID,
		Title:        blog.Title,
		CategoryID:   blog.CategoryID,
		CategoryName: categoryMap[blog.CategoryID],
		Content:      blog.Content,
		IsRecommend:  blog.IsRecommend,
		IsTop:        blog.IsTop,
		CreatedAt:    blog.CreatedAt.Format("2006-01-02 15:04:05"),
		UpdatedAt:    blog.UpdatedAt.Format("2006-01-02 15:04:05"),
	}

	c.JSON(http.StatusOK, response)
}

// Create 创建博客
func (h *BlogHandler) Create(c *gin.Context) {
	var blog storage.Blog
	if err := c.ShouldBindJSON(&blog); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// 验证数据
	if blog.Title == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "博客标题不能为空"})
		return
	}

	if blog.CategoryID == 0 {
		c.JSON(http.StatusBadRequest, gin.H{"error": "请选择博客类别"})
		return
	}

	if blog.Content == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "博客内容不能为空"})
		return
	}

	// 检查类别是否存在
	if _, err := h.categoryStore.GetByID(blog.CategoryID); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "选择的类别不存在"})
		return
	}

	createdBlog, err := h.blogStore.Create(blog)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusCreated, createdBlog)
}

// Update 更新博客
func (h *BlogHandler) Update(c *gin.Context) {
	idStr := c.Param("id")
	id, err := strconv.Atoi(idStr)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "无效的ID"})
		return
	}

	var blog storage.Blog
	if err := c.ShouldBindJSON(&blog); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// 验证数据
	if blog.Title == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "博客标题不能为空"})
		return
	}

	if blog.CategoryID == 0 {
		c.JSON(http.StatusBadRequest, gin.H{"error": "请选择博客类别"})
		return
	}

	if blog.Content == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "博客内容不能为空"})
		return
	}

	// 检查类别是否存在
	if _, err := h.categoryStore.GetByID(blog.CategoryID); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "选择的类别不存在"})
		return
	}

	updatedBlog, err := h.blogStore.Update(id, blog)
	if err != nil {
		if err.Error() == "博客不存在" {
			c.JSON(http.StatusNotFound, gin.H{"error": err.Error()})
		} else {
			c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		}
		return
	}

	c.JSON(http.StatusOK, updatedBlog)
}

// Delete 删除博客
func (h *BlogHandler) Delete(c *gin.Context) {
	idStr := c.Param("id")
	id, err := strconv.Atoi(idStr)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "无效的ID"})
		return
	}

	if err := h.blogStore.Delete(id); err != nil {
		if err.Error() == "博客不存在" {
			c.JSON(http.StatusNotFound, gin.H{"error": err.Error()})
		} else {
			c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		}
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "删除成功"})
}
