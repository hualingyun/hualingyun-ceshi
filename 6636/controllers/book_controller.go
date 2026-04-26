package controllers

import (
	"book-recommendation/models"
	"book-recommendation/store"
	"math"
	"net/http"
	"sort"
	"strconv"
	"strings"

	"github.com/gin-gonic/gin"
)

const PageSize = 8

func GetBooks(c *gin.Context) {
	page, _ := strconv.Atoi(c.DefaultQuery("page", "1"))
	search := c.Query("search")

	var books []models.Book
	if search != "" {
		books = store.Store.SearchBooks(search)
	} else {
		books = store.Store.GetAllBooks()
	}

	sort.Slice(books, func(i, j int) bool {
		if books[i].Rating != books[j].Rating {
			return books[i].Rating > books[j].Rating
		}
		return books[i].CreatedAt.After(books[j].CreatedAt)
	})

	total := len(books)
	totalPages := int(math.Ceil(float64(total) / float64(PageSize)))

	start := (page - 1) * PageSize
	end := start + PageSize
	if end > total {
		end = total
	}

	var pageBooks []models.Book
	if start < total {
		pageBooks = books[start:end]
	} else {
		pageBooks = []models.Book{}
	}

	c.JSON(http.StatusOK, gin.H{
		"books":       pageBooks,
		"currentPage": page,
		"totalPages":  totalPages,
		"total":       total,
		"pageSize":    PageSize,
	})
}

func GetBookByID(c *gin.Context) {
	idStr := c.Param("id")
	id, err := strconv.ParseUint(idStr, 10, 32)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "无效的图书ID"})
		return
	}

	book, exists := store.Store.GetBookByID(uint(id))
	if !exists {
		c.JSON(http.StatusNotFound, gin.H{"error": "图书不存在"})
		return
	}

	userID, exists := c.Get("user_id")
	var isLiked bool
	var hasRecommended bool

	if exists {
		isLiked = store.Store.HasUserLiked(userID.(uint), uint(id))
		hasRecommended = store.Store.HasUserRecommended(userID.(uint), uint(id))
	}

	c.JSON(http.StatusOK, gin.H{
		"book":           book,
		"isLiked":        isLiked,
		"hasRecommended": hasRecommended,
	})
}

func GetBookComments(c *gin.Context) {
	idStr := c.Param("id")
	id, err := strconv.ParseUint(idStr, 10, 32)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "无效的图书ID"})
		return
	}

	comments := store.Store.GetCommentsByBookID(uint(id))

	sort.Slice(comments, func(i, j int) bool {
		return comments[i].CreatedAt.After(comments[j].CreatedAt)
	})

	result := make([]gin.H, len(comments))
	for i, comment := range comments {
		result[i] = gin.H{
			"id":         comment.ID,
			"user_id":    comment.UserID,
			"book_id":    comment.BookID,
			"content":    comment.Content,
			"created_at": comment.CreatedAt,
			"user": gin.H{
				"id":       comment.User.ID,
				"username": comment.User.Username,
			},
		}
	}

	c.JSON(http.StatusOK, gin.H{"comments": result})
}

func AddComment(c *gin.Context) {
	userID := c.GetUint("user_id")
	idStr := c.Param("id")
	bookID, err := strconv.ParseUint(idStr, 10, 32)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "无效的图书ID"})
		return
	}

	var req struct {
		Content string `json:"content" binding:"required"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "请求参数错误"})
		return
	}

	book, exists := store.Store.GetBookByID(uint(bookID))
	if !exists {
		c.JSON(http.StatusNotFound, gin.H{"error": "图书不存在"})
		return
	}

	content := strings.TrimSpace(req.Content)
	if content == "" {
		c.JSON(http.StatusBadRequest, gin.H{"error": "评论内容不能为空"})
		return
	}

	comment := models.Comment{
		UserID:  userID,
		BookID:  book.ID,
		Content: content,
	}

	if err := store.Store.CreateComment(&comment); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "评论创建失败"})
		return
	}

	user, _ := store.Store.GetUserByID(userID)

	c.JSON(http.StatusOK, gin.H{
		"message": "评论成功",
		"comment": gin.H{
			"id":         comment.ID,
			"user_id":    comment.UserID,
			"book_id":    comment.BookID,
			"content":    comment.Content,
			"created_at": comment.CreatedAt,
			"user": gin.H{
				"id":       user.ID,
				"username": user.Username,
			},
		},
	})
}

func ToggleLike(c *gin.Context) {
	userID := c.GetUint("user_id")
	idStr := c.Param("id")
	bookID, err := strconv.ParseUint(idStr, 10, 32)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "无效的图书ID"})
		return
	}

	_, exists := store.Store.GetBookByID(uint(bookID))
	if !exists {
		c.JSON(http.StatusNotFound, gin.H{"error": "图书不存在"})
		return
	}

	isLiked, likeCount := store.Store.ToggleLike(userID, uint(bookID))

	if isLiked {
		c.JSON(http.StatusOK, gin.H{
			"message":   "点赞成功",
			"isLiked":   true,
			"likeCount": likeCount,
		})
	} else {
		c.JSON(http.StatusOK, gin.H{
			"message":   "取消点赞",
			"isLiked":   false,
			"likeCount": likeCount,
		})
	}
}

func AddRecommendation(c *gin.Context) {
	userID := c.GetUint("user_id")
	idStr := c.Param("id")
	bookID, err := strconv.ParseUint(idStr, 10, 32)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "无效的图书ID"})
		return
	}

	var req struct {
		Reason string `json:"reason"`
	}

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "请求参数错误"})
		return
	}

	_, exists := store.Store.GetBookByID(uint(bookID))
	if !exists {
		c.JSON(http.StatusNotFound, gin.H{"error": "图书不存在"})
		return
	}

	if store.Store.HasUserRecommended(userID, uint(bookID)) {
		c.JSON(http.StatusBadRequest, gin.H{"error": "您已经推荐过这本书了"})
		return
	}

	if err := store.Store.AddRecommendation(userID, uint(bookID), req.Reason); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": "推荐失败"})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"message":        "推荐成功",
		"hasRecommended": true,
	})
}

func GetRecommendedBooks(c *gin.Context) {
	books := store.Store.GetRecommendedBooks()

	c.JSON(http.StatusOK, gin.H{
		"books": books,
	})
}

func GetRecommendationDetails(c *gin.Context) {
	details := store.Store.GetRecommendationDetails()

	c.JSON(http.StatusOK, gin.H{
		"recommendations": details,
	})
}

func GetBookRecommendations(c *gin.Context) {
	idStr := c.Param("id")
	id, err := strconv.ParseUint(idStr, 10, 32)
	if err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": "无效的图书ID"})
		return
	}

	details := store.Store.GetBookRecommendations(uint(id))

	c.JSON(http.StatusOK, gin.H{
		"recommendations": details,
	})
}
