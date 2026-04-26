package models

import (
	"time"
)

type User struct {
	ID        uint      `json:"id"`
	Username  string    `json:"username"`
	Email     string    `json:"email"`
	Password  string    `json:"-"`
	CreatedAt time.Time `json:"created_at"`
	UpdatedAt time.Time `json:"updated_at"`
}

type Book struct {
	ID           uint      `json:"id"`
	Title        string    `json:"title"`
	Author       string    `json:"author"`
	Price        float64   `json:"price"`
	Description  string    `json:"description"`
	Cover        string    `json:"cover"`
	Rating       float64   `json:"rating"`
	LikeCount    int       `json:"like_count"`
	CommentCount int       `json:"comment_count"`
	CreatedAt    time.Time `json:"created_at"`
	UpdatedAt    time.Time `json:"updated_at"`
}

type Comment struct {
	ID        uint      `json:"id"`
	UserID    uint      `json:"user_id"`
	BookID    uint      `json:"book_id"`
	Content   string    `json:"content"`
	CreatedAt time.Time `json:"created_at"`
	UpdatedAt time.Time `json:"updated_at"`
	User      User      `json:"user"`
}

type Like struct {
	ID        uint      `json:"id"`
	UserID    uint      `json:"user_id"`
	BookID    uint      `json:"book_id"`
	CreatedAt time.Time `json:"created_at"`
}

type Recommendation struct {
	ID        uint      `json:"id"`
	UserID    uint      `json:"user_id"`
	BookID    uint      `json:"book_id"`
	Reason    string    `json:"reason"`
	CreatedAt time.Time `json:"created_at"`
	Book      Book      `json:"-"`
}
