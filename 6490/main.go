package main

import (
	"encoding/json"
	"fmt"
	"io/ioutil"
	"net/http"
	"os"
	"regexp"
	"sync"

	"github.com/gin-gonic/gin"
	"golang.org/x/crypto/bcrypt"
)

type User struct {
	ID       string `json:"id"`
	Username string `json:"username"`
	Password string `json:"password,omitempty"`
	Email    string `json:"email"`
	Role     string `json:"role"`
}

type DataStore struct {
	Users []User `json:"users"`
}

var (
	dataFile = "data.json"
	mu       sync.RWMutex
)

func init() {
	if _, err := os.Stat(dataFile); os.IsNotExist(err) {
		initialData := DataStore{
			Users: []User{},
		}
		saveData(initialData)
	}
}

func loadData() DataStore {
	mu.RLock()
	defer mu.RUnlock()
	
	file, err := ioutil.ReadFile(dataFile)
	if err != nil {
		return DataStore{Users: []User{}}
	}
	
	var data DataStore
	json.Unmarshal(file, &data)
	return data
}

func saveData(data DataStore) error {
	mu.Lock()
	defer mu.Unlock()
	
	file, err := json.MarshalIndent(data, "", "  ")
	if err != nil {
		return err
	}
	
	return ioutil.WriteFile(dataFile, file, 0644)
}

func hashPassword(password string) (string, error) {
	bytes, err := bcrypt.GenerateFromPassword([]byte(password), 14)
	return string(bytes), err
}

func checkPassword(password, hash string) bool {
	err := bcrypt.CompareHashAndPassword([]byte(hash), []byte(password))
	return err == nil
}

func validateUsername(username string) (bool, string) {
	if username == "" {
		return false, "用户名必填"
	}
	if len(username) < 3 || len(username) > 10 {
		return false, "用户名长度必须为3-10个字符"
	}
	matched, _ := regexp.MatchString(`^[a-zA-Z][a-zA-Z0-9_]*$`, username)
	if !matched {
		return false, "用户名必须以字母开头，且只能包含字母、数字和下划线"
	}
	return true, ""
}

func validatePassword(password string) (bool, string) {
	if password == "" {
		return false, "密码必填"
	}
	if len(password) < 6 || len(password) > 20 {
		return false, "密码长度必须为6-20个字符"
	}
	hasUpper, _ := regexp.MatchString(`[A-Z]`, password)
	hasLower, _ := regexp.MatchString(`[a-z]`, password)
	hasDigit, _ := regexp.MatchString(`[0-9]`, password)
	startsWithLetter, _ := regexp.MatchString(`^[a-zA-Z]`, password)
	
	if !startsWithLetter {
		return false, "密码必须以字母开头"
	}
	if !hasUpper || !hasLower || !hasDigit {
		return false, "密码必须包含大小写字母和数字"
	}
	return true, ""
}

func generateID() string {
	data := loadData()
	maxID := 0
	for _, user := range data.Users {
		var id int
		fmt.Sscanf(user.ID, "%d", &id)
		if id > maxID {
			maxID = id
		}
	}
	return fmt.Sprintf("%d", maxID+1)
}

func main() {
	r := gin.Default()

	r.Static("/static", "./static")
	r.StaticFile("/", "./static/index.html")
	r.StaticFile("/register", "./static/register.html")
	r.StaticFile("/login", "./static/login.html")
	r.StaticFile("/dashboard", "./static/dashboard.html")

	api := r.Group("/api")
	{
		api.POST("/register", func(c *gin.Context) {
			var req struct {
				Username        string `json:"username"`
				Password        string `json:"password"`
				ConfirmPassword string `json:"confirm_password"`
			}
			
			if err := c.ShouldBindJSON(&req); err != nil {
				c.JSON(http.StatusBadRequest, gin.H{"success": false, "message": "参数错误"})
				return
			}

			if valid, msg := validateUsername(req.Username); !valid {
				c.JSON(http.StatusBadRequest, gin.H{"success": false, "message": msg})
				return
			}

			if valid, msg := validatePassword(req.Password); !valid {
				c.JSON(http.StatusBadRequest, gin.H{"success": false, "message": msg})
				return
			}

			if req.Password != req.ConfirmPassword {
				c.JSON(http.StatusBadRequest, gin.H{"success": false, "message": "两次密码输入不一致"})
				return
			}

			data := loadData()
			for _, user := range data.Users {
				if user.Username == req.Username {
					c.JSON(http.StatusBadRequest, gin.H{"success": false, "message": "用户名已存在"})
					return
				}
			}

			hashedPassword, err := hashPassword(req.Password)
			if err != nil {
				c.JSON(http.StatusInternalServerError, gin.H{"success": false, "message": "密码加密失败"})
				return
			}

			newUser := User{
				ID:       generateID(),
				Username: req.Username,
				Password: hashedPassword,
				Role:     "user",
			}

			data.Users = append(data.Users, newUser)
			saveData(data)

			c.JSON(http.StatusOK, gin.H{"success": true, "message": "注册成功"})
		})

		api.POST("/login", func(c *gin.Context) {
			var req struct {
				Username string `json:"username"`
				Password string `json:"password"`
			}
			
			if err := c.ShouldBindJSON(&req); err != nil {
				c.JSON(http.StatusBadRequest, gin.H{"success": false, "message": "参数错误"})
				return
			}

			data := loadData()
			var foundUser *User
			for i := range data.Users {
				if data.Users[i].Username == req.Username {
					foundUser = &data.Users[i]
					break
				}
			}

			if foundUser == nil {
				c.JSON(http.StatusUnauthorized, gin.H{"success": false, "message": "用户名不存在"})
				return
			}

			if !checkPassword(req.Password, foundUser.Password) {
				c.JSON(http.StatusUnauthorized, gin.H{"success": false, "message": "密码错误"})
				return
			}

			c.JSON(http.StatusOK, gin.H{
				"success": true, 
				"message": "登录成功",
				"user": gin.H{
					"id":       foundUser.ID,
					"username": foundUser.Username,
					"role":     foundUser.Role,
				},
			})
		})

		api.GET("/users", func(c *gin.Context) {
			data := loadData()
			users := make([]User, len(data.Users))
			for i, user := range data.Users {
				users[i] = User{
					ID:       user.ID,
					Username: user.Username,
					Email:    user.Email,
					Role:     user.Role,
				}
			}
			c.JSON(http.StatusOK, gin.H{"success": true, "users": users})
		})

		api.GET("/users/:id", func(c *gin.Context) {
			id := c.Param("id")
			data := loadData()
			
			for _, user := range data.Users {
				if user.ID == id {
					c.JSON(http.StatusOK, gin.H{
						"success": true,
						"user": gin.H{
							"id":       user.ID,
							"username": user.Username,
							"email":    user.Email,
							"role":     user.Role,
						},
					})
					return
				}
			}
			
			c.JSON(http.StatusNotFound, gin.H{"success": false, "message": "用户不存在"})
		})

		api.POST("/users", func(c *gin.Context) {
			var req struct {
				Username string `json:"username"`
				Password string `json:"password"`
				Email    string `json:"email"`
				Role     string `json:"role"`
			}
			
			if err := c.ShouldBindJSON(&req); err != nil {
				c.JSON(http.StatusBadRequest, gin.H{"success": false, "message": "参数错误"})
				return
			}

			if valid, msg := validateUsername(req.Username); !valid {
				c.JSON(http.StatusBadRequest, gin.H{"success": false, "message": msg})
				return
			}

			if req.Password != "" {
				if valid, msg := validatePassword(req.Password); !valid {
					c.JSON(http.StatusBadRequest, gin.H{"success": false, "message": msg})
					return
				}
			}

			data := loadData()
			for _, user := range data.Users {
				if user.Username == req.Username {
					c.JSON(http.StatusBadRequest, gin.H{"success": false, "message": "用户名已存在"})
					return
				}
			}

			hashedPassword, err := hashPassword(req.Password)
			if err != nil {
				c.JSON(http.StatusInternalServerError, gin.H{"success": false, "message": "密码加密失败"})
				return
			}

			role := req.Role
			if role == "" {
				role = "user"
			}

			newUser := User{
				ID:       generateID(),
				Username: req.Username,
				Password: hashedPassword,
				Email:    req.Email,
				Role:     role,
			}

			data.Users = append(data.Users, newUser)
			saveData(data)

			c.JSON(http.StatusOK, gin.H{
				"success": true, 
				"message": "用户添加成功",
				"user": gin.H{
					"id":       newUser.ID,
					"username": newUser.Username,
					"email":    newUser.Email,
					"role":     newUser.Role,
				},
			})
		})

		api.PUT("/users/:id", func(c *gin.Context) {
			id := c.Param("id")
			var req struct {
				Username string `json:"username"`
				Password string `json:"password"`
				Email    string `json:"email"`
				Role     string `json:"role"`
			}
			
			if err := c.ShouldBindJSON(&req); err != nil {
				c.JSON(http.StatusBadRequest, gin.H{"success": false, "message": "参数错误"})
				return
			}

			data := loadData()
			found := false
			for i := range data.Users {
				if data.Users[i].ID == id {
					found = true
					
					if req.Username != "" && req.Username != data.Users[i].Username {
						if valid, msg := validateUsername(req.Username); !valid {
							c.JSON(http.StatusBadRequest, gin.H{"success": false, "message": msg})
							return
						}
						
						for _, u := range data.Users {
							if u.ID != id && u.Username == req.Username {
								c.JSON(http.StatusBadRequest, gin.H{"success": false, "message": "用户名已存在"})
								return
							}
						}
						data.Users[i].Username = req.Username
					}
					
					if req.Password != "" {
						if valid, msg := validatePassword(req.Password); !valid {
							c.JSON(http.StatusBadRequest, gin.H{"success": false, "message": msg})
							return
						}
						hashedPassword, err := hashPassword(req.Password)
						if err != nil {
							c.JSON(http.StatusInternalServerError, gin.H{"success": false, "message": "密码加密失败"})
							return
						}
						data.Users[i].Password = hashedPassword
					}
					
					if req.Email != "" {
						data.Users[i].Email = req.Email
					}
					if req.Role != "" {
						data.Users[i].Role = req.Role
					}
					break
				}
			}

			if !found {
				c.JSON(http.StatusNotFound, gin.H{"success": false, "message": "用户不存在"})
				return
			}

			saveData(data)
			c.JSON(http.StatusOK, gin.H{"success": true, "message": "用户更新成功"})
		})

		api.DELETE("/users/:id", func(c *gin.Context) {
			id := c.Param("id")
			data := loadData()
			
			found := false
			newUsers := []User{}
			for _, user := range data.Users {
				if user.ID == id {
					found = true
				} else {
					newUsers = append(newUsers, user)
				}
			}

			if !found {
				c.JSON(http.StatusNotFound, gin.H{"success": false, "message": "用户不存在"})
				return
			}

			data.Users = newUsers
			saveData(data)
			c.JSON(http.StatusOK, gin.H{"success": true, "message": "用户删除成功"})
		})
	}

	fmt.Println("服务器启动在 http://localhost:8080")
	r.Run(":8080")
}
