package handlers

import (
	"blog-system/internal/storage"
	"net/http"
	"strconv"

	"github.com/gin-gonic/gin"
)

// CategoryHandler 类别处理器
type CategoryHandler struct {
	categoryStore *storage.CategoryStorage
	blogStore     *storage.BlogStorage
}

// NewCategoryHandler 创建类别处理器
func NewCategoryHandler(categoryStore *storage.CategoryStorage, blogStore *storage.BlogStorage) *CategoryHandler {
	return &CategoryHandler{
		categoryStore: categoryStore,
		blogStore:     blogStore,
	}
}

// GetAll 获取所有类别
func (h *CategoryHandler) GetAll(c *gin.Context) {
	categories := h.categoryStore.GetAll()
	c.JSON(http.StatusOK, categories)
}

// Create 创建类别
func (h *CategoryHandler) Create(c *gin.Context) {
	var category storage.Category
	if err := c.ShouldBindJSON(&category); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// 验证数据
	if category.Name == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "类别名称不能为空"})
		return
	}

	createdCategory, err := h.categoryStore.Create(category)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusCreated, createdCategory)
}

// Update 更新类别
func (h *CategoryHandler) Update(c *gin.Context) {
	idStr := c.Param("id")
	id, err := strconv.Atoi(idStr)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "无效的ID"})
		return
	}

	var category storage.Category
	if err := c.ShouldBindJSON(&category); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	// 验证数据
	if category.Name == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "类别名称不能为空"})
		return
	}

	updatedCategory, err := h.categoryStore.Update(id, category)
	if err != nil {
		if err.Error() == "类别不存在" {
			c.JSON(http.StatusNotFound, gin.H{"error": err.Error()})
		} else {
			c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		}
		return
	}

	c.JSON(http.StatusOK, updatedCategory)
}

// Delete 删除类别
func (h *CategoryHandler) Delete(c *gin.Context) {
	idStr := c.Param("id")
	id, err := strconv.Atoi(idStr)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "无效的ID"})
		return
	}

	// 检查是否有博客使用该类别
	if h.blogStore.ExistsByCategoryID(id) {
		c.JSON(http.StatusBadRequest, gin.H{"error": "该类别已被博客使用，不能删除"})
		return
	}

	if err := h.categoryStore.Delete(id); err != nil {
		if err.Error() == "类别不存在" {
			c.JSON(http.StatusNotFound, gin.H{"error": err.Error()})
		} else {
			c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		}
		return
	}

	c.JSON(http.StatusOK, gin.H{"message": "删除成功"})
}
