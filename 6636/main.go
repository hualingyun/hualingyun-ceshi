package main

import (
	"book-recommendation/routes"
	"book-recommendation/store"
	"fmt"
	"os"
)

func main() {
	store.InitStore()
	fmt.Println("内存存储初始化完成!")

	router := routes.SetupRouter()

	port := os.Getenv("PORT")
	if port == "" {
		port = "8080"
	}

	fmt.Printf("服务器运行在 http://localhost:%s\n", port)
	router.Run(":" + port)
}
