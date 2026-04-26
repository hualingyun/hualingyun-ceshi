package main

import (
	"blog-system/internal/handlers"
	"blog-system/internal/storage"
	"log"
	"net/http"
	"os"

	"github.com/gin-gonic/gin"
)

func main() {
	// 初始化存储
	blogStore := storage.NewBlogStorage("data/blogs.json")
	categoryStore := storage.NewCategoryStorage("data/categories.json")

	// 确保数据目录存在
	os.MkdirAll("data", 0755)

	// 初始化默认类别
	if len(categoryStore.GetAll()) == 0 {
		categoryStore.Create(storage.Category{Name: "技术"})
		categoryStore.Create(storage.Category{Name: "生活"})
		categoryStore.Create(storage.Category{Name: "随笔"})
	}

	// 初始化处理器
	blogHandler := handlers.NewBlogHandler(blogStore, categoryStore)
	categoryHandler := handlers.NewCategoryHandler(categoryStore, blogStore)

	// 创建Gin引擎
	r := gin.Default()

	// 静态文件服务
	r.Static("/static", "./static")

	// API路由
	api := r.Group("/api")
	{
		// 博客相关API
		api.GET("/blogs", blogHandler.GetAll)
		api.GET("/blogs/:id", blogHandler.GetByID)
		api.POST("/blogs", blogHandler.Create)
		api.PUT("/blogs/:id", blogHandler.Update)
		api.DELETE("/blogs/:id", blogHandler.Delete)

		// 类别相关API
		api.GET("/categories", categoryHandler.GetAll)
		api.POST("/categories", categoryHandler.Create)
		api.PUT("/categories/:id", categoryHandler.Update)
		api.DELETE("/categories/:id", categoryHandler.Delete)
	}

	// 页面路由
	r.GET("/", func(c *gin.Context) {
		c.File("./static/index.html")
	})

	r.GET("/add", func(c *gin.Context) {
		c.File("./static/add.html")
	})

	r.GET("/edit/:id", func(c *gin.Context) {
		c.File("./static/edit.html")
	})

	r.GET("/categories", func(c *gin.Context) {
		c.File("./static/categories.html")
	})

	// 启动服务器
	port := ":8080"
	log.Printf("服务器启动在 %s 端口", port)
	log.Printf("请访问 http://localhost%s", port)

	if err := http.ListenAndServe(port, r); err != nil {
		log.Fatalf("服务器启动失败: %v", err)
	}
}
