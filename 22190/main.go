package main

import (
	"duty-system/handlers"
	"fmt"
	"log"
	"net/http"
)

func main() {
	fs := http.FileServer(http.Dir("./static"))
	http.Handle("/static/", http.StripPrefix("/static/", fs))

	http.HandleFunc("/", handlers.LoginHandler)
	http.HandleFunc("/login", handlers.LoginHandler)
	http.HandleFunc("/register", handlers.RegisterHandler)
	http.HandleFunc("/logout", handlers.LogoutHandler)
	http.HandleFunc("/admin", handlers.AdminHandler)
	http.HandleFunc("/student", handlers.StudentHandler)
	http.HandleFunc("/users", handlers.UsersHandler)
	http.HandleFunc("/schedule", handlers.ScheduleHandler)
	http.HandleFunc("/handover", handlers.HandoverHandler)
	http.HandleFunc("/handover-log", handlers.HandoverLogHandler)
	http.HandleFunc("/api/users", handlers.UsersAPIHandler)
	http.HandleFunc("/api/schedule", handlers.ScheduleAPIHandler)
	http.HandleFunc("/api/handover", handlers.HandoverAPIHandler)
	http.HandleFunc("/api/current-duty", handlers.CurrentDutyAPIHandler)

	fmt.Println("Server starting at http://localhost:8080")
	log.Fatal(http.ListenAndServe(":8080", nil))
}
