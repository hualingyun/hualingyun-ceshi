package storage

import (
	"encoding/json"
	"fmt"
	"os"
	"sync"
	"time"
)

// Blog 博客结构体
type Blog struct {
	ID          int       `json:"id"`
	Title       string    `json:"title"`
	CategoryID  int       `json:"category_id"`
	Content     string    `json:"content"`
	IsRecommend bool      `json:"is_recommend"`
	IsTop       bool      `json:"is_top"`
	CreatedAt   time.Time `json:"created_at"`
	UpdatedAt   time.Time `json:"updated_at"`
}

// Category 博客类别结构体
type Category struct {
	ID   int    `json:"id"`
	Name string `json:"name"`
}

// BlogStorage 博客存储
type BlogStorage struct {
	filePath string
	mu       sync.RWMutex
}

// CategoryStorage 类别存储
type CategoryStorage struct {
	filePath string
	mu       sync.RWMutex
}

// NewBlogStorage 创建博客存储
func NewBlogStorage(filePath string) *BlogStorage {
	return &BlogStorage{
		filePath: filePath,
	}
}

// NewCategoryStorage 创建类别存储
func NewCategoryStorage(filePath string) *CategoryStorage {
	return &CategoryStorage{
		filePath: filePath,
	}
}

// 读取博客数据
func (s *BlogStorage) load() ([]Blog, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	data, err := os.ReadFile(s.filePath)
	if err != nil {
		if os.IsNotExist(err) {
			return []Blog{}, nil
		}
		return nil, err
	}

	if len(data) == 0 {
		return []Blog{}, nil
	}

	var blogs []Blog
	if err := json.Unmarshal(data, &blogs); err != nil {
		return nil, err
	}

	return blogs, nil
}

// 保存博客数据
func (s *BlogStorage) save(blogs []Blog) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	data, err := json.MarshalIndent(blogs, "", "  ")
	if err != nil {
		return err
	}

	return os.WriteFile(s.filePath, data, 0644)
}

// GetAll 获取所有博客
func (s *BlogStorage) GetAll() []Blog {
	blogs, _ := s.load()
	return blogs
}

// GetByID 根据ID获取博客
func (s *BlogStorage) GetByID(id int) (*Blog, error) {
	blogs, err := s.load()
	if err != nil {
		return nil, err
	}

	for i := range blogs {
		if blogs[i].ID == id {
			return &blogs[i], nil
		}
	}

	return nil, fmt.Errorf("博客不存在")
}

// Create 创建博客
func (s *BlogStorage) Create(blog Blog) (Blog, error) {
	blogs, err := s.load()
	if err != nil {
		return blog, err
	}

	// 生成ID
	maxID := 0
	for _, b := range blogs {
		if b.ID > maxID {
			maxID = b.ID
		}
	}
	blog.ID = maxID + 1
	blog.CreatedAt = time.Now()
	blog.UpdatedAt = time.Now()

	blogs = append(blogs, blog)

	if err := s.save(blogs); err != nil {
		return blog, err
	}

	return blog, nil
}

// Update 更新博客
func (s *BlogStorage) Update(id int, blog Blog) (Blog, error) {
	blogs, err := s.load()
	if err != nil {
		return blog, err
	}

	for i := range blogs {
		if blogs[i].ID == id {
			blogs[i].Title = blog.Title
			blogs[i].CategoryID = blog.CategoryID
			blogs[i].Content = blog.Content
			blogs[i].IsRecommend = blog.IsRecommend
			blogs[i].IsTop = blog.IsTop
			blogs[i].UpdatedAt = time.Now()

			if err := s.save(blogs); err != nil {
				return blog, err
			}

			return blogs[i], nil
		}
	}

	return blog, fmt.Errorf("博客不存在")
}

// Delete 删除博客
func (s *BlogStorage) Delete(id int) error {
	blogs, err := s.load()
	if err != nil {
		return err
	}

	for i := range blogs {
		if blogs[i].ID == id {
			blogs = append(blogs[:i], blogs[i+1:]...)
			return s.save(blogs)
		}
	}

	return fmt.Errorf("博客不存在")
}

// ExistsByCategoryID 检查是否有博客使用指定类别
func (s *BlogStorage) ExistsByCategoryID(categoryID int) bool {
	blogs, _ := s.load()

	for _, b := range blogs {
		if b.CategoryID == categoryID {
			return true
		}
	}

	return false
}

// 读取类别数据
func (s *CategoryStorage) load() ([]Category, error) {
	s.mu.RLock()
	defer s.mu.RUnlock()

	data, err := os.ReadFile(s.filePath)
	if err != nil {
		if os.IsNotExist(err) {
			return []Category{}, nil
		}
		return nil, err
	}

	if len(data) == 0 {
		return []Category{}, nil
	}

	var categories []Category
	if err := json.Unmarshal(data, &categories); err != nil {
		return nil, err
	}

	return categories, nil
}

// 保存类别数据
func (s *CategoryStorage) save(categories []Category) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	data, err := json.MarshalIndent(categories, "", "  ")
	if err != nil {
		return err
	}

	return os.WriteFile(s.filePath, data, 0644)
}

// GetAll 获取所有类别
func (s *CategoryStorage) GetAll() []Category {
	categories, _ := s.load()
	return categories
}

// GetByID 根据ID获取类别
func (s *CategoryStorage) GetByID(id int) (*Category, error) {
	categories, err := s.load()
	if err != nil {
		return nil, err
	}

	for i := range categories {
		if categories[i].ID == id {
			return &categories[i], nil
		}
	}

	return nil, fmt.Errorf("类别不存在")
}

// Create 创建类别
func (s *CategoryStorage) Create(category Category) (Category, error) {
	categories, err := s.load()
	if err != nil {
		return category, err
	}

	// 生成ID
	maxID := 0
	for _, c := range categories {
		if c.ID > maxID {
			maxID = c.ID
		}
	}
	category.ID = maxID + 1

	categories = append(categories, category)

	if err := s.save(categories); err != nil {
		return category, err
	}

	return category, nil
}

// Update 更新类别
func (s *CategoryStorage) Update(id int, category Category) (Category, error) {
	categories, err := s.load()
	if err != nil {
		return category, err
	}

	for i := range categories {
		if categories[i].ID == id {
			categories[i].Name = category.Name

			if err := s.save(categories); err != nil {
				return category, err
			}

			return categories[i], nil
		}
	}

	return category, fmt.Errorf("类别不存在")
}

// Delete 删除类别
func (s *CategoryStorage) Delete(id int) error {
	categories, err := s.load()
	if err != nil {
		return err
	}

	for i := range categories {
		if categories[i].ID == id {
			categories = append(categories[:i], categories[i+1:]...)
			return s.save(categories)
		}
	}

	return fmt.Errorf("类别不存在")
}
