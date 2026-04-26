package routes

import (
	"book-recommendation/controllers"
	"book-recommendation/middlewares"

	"github.com/gin-gonic/gin"
)

func SetupRouter() *gin.Engine {
	router := gin.Default()

	router.Static("/static", "./static")
	router.LoadHTMLGlob("templates/*")

	router.GET("/", func(c *gin.Context) {
		c.HTML(200, "index.html", nil)
	})

	api := router.Group("/api")
	{
		api.POST("/register", controllers.Register)
		api.POST("/login", controllers.Login)

		api.GET("/books", controllers.GetBooks)
		api.GET("/books/recommended", controllers.GetRecommendedBooks)
		api.GET("/books/recommended/details", controllers.GetRecommendationDetails)
		api.GET("/books/:id", controllers.GetBookByID)
		api.GET("/books/:id/comments", controllers.GetBookComments)
		api.GET("/books/:id/recommendations", controllers.GetBookRecommendations)

		auth := api.Group("")
		auth.Use(middlewares.AuthMiddleware())
		{
			auth.GET("/user", controllers.GetCurrentUser)
			auth.POST("/books/:id/comments", controllers.AddComment)
			auth.POST("/books/:id/like", controllers.ToggleLike)
			auth.POST("/books/:id/recommend", controllers.AddRecommendation)
		}
	}

	return router
}
