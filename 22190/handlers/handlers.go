package handlers

import (
	"crypto/md5"
	"duty-system/models"
	"encoding/json"
	"fmt"
	"html/template"
	"net/http"
	"regexp"
	"sort"
	"time"
)

var tpl *template.Template

func init() {
	tpl = template.Must(template.ParseGlob("templates/*.html"))
	models.LoadUsers()
	models.LoadSchedules()
	models.LoadHandoverLogs()
}

func generateID() string {
	h := md5.New()
	h.Write([]byte(time.Now().String()))
	return fmt.Sprintf("%x", h.Sum(nil))
}

func validatePassword(password string) bool {
	if len(password) < 6 {
		return false
	}
	matched, _ := regexp.MatchString(`^[a-zA-Z0-9_]+$`, password)
	if !matched {
		return false
	}
	hasLetter, _ := regexp.MatchString(`[a-zA-Z]`, password)
	hasNumber, _ := regexp.MatchString(`[0-9]`, password)
	return hasLetter && hasNumber
}

func getCurrentUser(r *http.Request) (models.User, bool) {
	cookie, err := r.Cookie("user")
	if err != nil {
		return models.User{}, false
	}
	return models.GetUser(cookie.Value)
}

func LoginHandler(w http.ResponseWriter, r *http.Request) {
	if r.Method == "POST" {
		username := r.FormValue("username")
		password := r.FormValue("password")
		
		user, exists := models.GetUser(username)
		if !exists {
			tpl.ExecuteTemplate(w, "login.html", map[string]interface{}{"error": "用户不存在"})
			return
		}
		
		if user.Password != password {
			tpl.ExecuteTemplate(w, "login.html", map[string]interface{}{"error": "密码错误"})
			return
		}
		
		http.SetCookie(w, &http.Cookie{Name: "user", Value: username, Path: "/"})
		
		if user.Role == "admin" {
			http.Redirect(w, r, "/admin", http.StatusSeeOther)
		} else {
			http.Redirect(w, r, "/student", http.StatusSeeOther)
		}
		return
	}
	
	tpl.ExecuteTemplate(w, "login.html", nil)
}

func RegisterHandler(w http.ResponseWriter, r *http.Request) {
	if r.Method == "POST" {
		username := r.FormValue("username")
		password := r.FormValue("password")
		name := r.FormValue("name")
		role := r.FormValue("role")
		
		if _, exists := models.GetUser(username); exists {
			tpl.ExecuteTemplate(w, "register.html", map[string]interface{}{"error": "用户名已存在"})
			return
		}
		
		if !validatePassword(password) {
			tpl.ExecuteTemplate(w, "register.html", map[string]interface{}{"error": "密码需6位以上，包含字母、数字和下划线"})
			return
		}
		
		models.AddUser(models.User{
			Username: username,
			Password: password,
			Role:     role,
			Name:     name,
		})
		
		http.Redirect(w, r, "/login", http.StatusSeeOther)
		return
	}
	
	tpl.ExecuteTemplate(w, "register.html", nil)
}

func LogoutHandler(w http.ResponseWriter, r *http.Request) {
	http.SetCookie(w, &http.Cookie{Name: "user", Value: "", Path: "/", MaxAge: -1})
	http.Redirect(w, r, "/login", http.StatusSeeOther)
}

func AdminHandler(w http.ResponseWriter, r *http.Request) {
	user, ok := getCurrentUser(r)
	if !ok || user.Role != "admin" {
		http.Redirect(w, r, "/login", http.StatusSeeOther)
		return
	}
	
	tpl.ExecuteTemplate(w, "admin.html", map[string]interface{}{"user": user})
}

func StudentHandler(w http.ResponseWriter, r *http.Request) {
	user, ok := getCurrentUser(r)
	if !ok {
		http.Redirect(w, r, "/login", http.StatusSeeOther)
		return
	}
	
	schedules := models.GetSchedulesByUsername(user.Username)
	sort.Slice(schedules, func(i, j int) bool {
		return schedules[i].Date < schedules[j].Date
	})
	
	schedulesJSON, _ := json.Marshal(schedules)
	
	tpl.ExecuteTemplate(w, "student.html", map[string]interface{}{
		"user":         user,
		"schedules":    string(schedulesJSON),
	})
}

func UsersHandler(w http.ResponseWriter, r *http.Request) {
	user, ok := getCurrentUser(r)
	if !ok || user.Role != "admin" {
		http.Redirect(w, r, "/login", http.StatusSeeOther)
		return
	}
	
	users := models.GetAllUsers()
	tpl.ExecuteTemplate(w, "users.html", map[string]interface{}{"user": user, "users": users})
}

func ScheduleHandler(w http.ResponseWriter, r *http.Request) {
	user, ok := getCurrentUser(r)
	if !ok || user.Role != "admin" {
		http.Redirect(w, r, "/login", http.StatusSeeOther)
		return
	}
	
	schedules := models.GetAllSchedules()
	users := models.GetAllUsers()
	var students []models.User
	for _, u := range users {
		if u.Role == "student" {
			students = append(students, u)
		}
	}
	
	sort.Slice(schedules, func(i, j int) bool {
		return schedules[i].Date < schedules[j].Date
	})
	
	tpl.ExecuteTemplate(w, "schedule.html", map[string]interface{}{
		"user":      user,
		"schedules": schedules,
		"students":  students,
	})
}

func HandoverHandler(w http.ResponseWriter, r *http.Request) {
	user, ok := getCurrentUser(r)
	if !ok {
		http.Redirect(w, r, "/login", http.StatusSeeOther)
		return
	}
	
	tpl.ExecuteTemplate(w, "handover.html", map[string]interface{}{"user": user})
}

func HandoverLogHandler(w http.ResponseWriter, r *http.Request) {
	user, ok := getCurrentUser(r)
	if !ok || user.Role != "admin" {
		http.Redirect(w, r, "/login", http.StatusSeeOther)
		return
	}
	
	logs := models.GetAllHandoverLogs()
	sort.Slice(logs, func(i, j int) bool {
		return logs[i].HandoverTime.After(logs[j].HandoverTime)
	})
	
	tpl.ExecuteTemplate(w, "handover_log.html", map[string]interface{}{"user": user, "logs": logs})
}

func UsersAPIHandler(w http.ResponseWriter, r *http.Request) {
	user, ok := getCurrentUser(r)
	if !ok || user.Role != "admin" {
		http.Error(w, "Unauthorized", http.StatusUnauthorized)
		return
	}
	
	if r.Method == "DELETE" {
		username := r.URL.Query().Get("username")
		if username == "admin" {
			http.Error(w, "不能删除管理员", http.StatusBadRequest)
			return
		}
		models.DeleteUser(username)
		w.WriteHeader(http.StatusOK)
		return
	}
}

func ScheduleAPIHandler(w http.ResponseWriter, r *http.Request) {
	user, ok := getCurrentUser(r)
	if !ok || user.Role != "admin" {
		http.Error(w, "Unauthorized", http.StatusUnauthorized)
		return
	}
	
	if r.Method == "POST" {
		var req struct {
			Action    string `json:"action"`
			Date      string `json:"date"`
			Shift     string `json:"shift"`
			Username  string `json:"username"`
			Name      string `json:"name"`
			ID        string `json:"id"`
			StartDate string `json:"start_date"`
			EndDate   string `json:"end_date"`
			Mode      string `json:"mode"`
		}
		json.NewDecoder(r.Body).Decode(&req)
		
		if req.Action == "add" {
			s := models.Schedule{
				ID:        generateID(),
				Date:      req.Date,
				Shift:     req.Shift,
				Username:  req.Username,
				Name:      req.Name,
				CreatedAt: time.Now(),
			}
			models.AddSchedule(s)
		} else if req.Action == "update" {
			s := models.Schedule{
				ID:        req.ID,
				Date:      req.Date,
				Shift:     req.Shift,
				Username:  req.Username,
				Name:      req.Name,
			}
			models.UpdateSchedule(s)
		} else if req.Action == "delete" {
			models.DeleteSchedule(req.ID)
		} else if req.Action == "batch" {
			start, _ := time.Parse("2006-01-02", req.StartDate)
			end, _ := time.Parse("2006-01-02", req.EndDate)
			students := []models.User{}
			for _, u := range models.GetAllUsers() {
				if u.Role == "student" {
					students = append(students, u)
				}
			}
			if len(students) == 0 {
				json.NewEncoder(w).Encode(map[string]string{"status": "error", "msg": "没有学生用户"})
				return
			}
			
			mode := req.Mode
			if mode == "" {
				mode = "cycle"
			}
			
			if mode == "cycle" {
				idx := 0
				for d := start; !d.After(end); d = d.AddDate(0, 0, 1) {
					s := models.Schedule{
						ID:        generateID(),
						Date:      d.Format("2006-01-02"),
						Shift:     "morning",
						Username:  students[idx%len(students)].Username,
						Name:      students[idx%len(students)].Name,
						CreatedAt: time.Now(),
					}
					models.AddSchedule(s)
					idx++
					s2 := models.Schedule{
						ID:        generateID(),
						Date:      d.Format("2006-01-02"),
						Shift:     "evening",
						Username:  students[idx%len(students)].Username,
						Name:      students[idx%len(students)].Name,
						CreatedAt: time.Now(),
					}
					models.AddSchedule(s2)
					idx++
				}
			} else {
				fixedUser, _ := models.GetUser(req.Username)
				shiftOption := req.Shift
				if shiftOption == "" {
					shiftOption = "both"
				}
				
				for d := start; !d.After(end); d = d.AddDate(0, 0, 1) {
					if shiftOption == "both" || shiftOption == "morning" {
						s := models.Schedule{
							ID:        generateID(),
							Date:      d.Format("2006-01-02"),
							Shift:     "morning",
							Username:  fixedUser.Username,
							Name:      fixedUser.Name,
							CreatedAt: time.Now(),
						}
						models.AddSchedule(s)
					}
					if shiftOption == "both" || shiftOption == "evening" {
						s2 := models.Schedule{
							ID:        generateID(),
							Date:      d.Format("2006-01-02"),
							Shift:     "evening",
							Username:  fixedUser.Username,
							Name:      fixedUser.Name,
							CreatedAt: time.Now(),
						}
						models.AddSchedule(s2)
					}
				}
			}
		}
		json.NewEncoder(w).Encode(map[string]string{"status": "ok"})
	}
}

func HandoverAPIHandler(w http.ResponseWriter, r *http.Request) {
	user, ok := getCurrentUser(r)
	if !ok {
		http.Error(w, "Unauthorized", http.StatusUnauthorized)
		return
	}
	
	if r.Method == "POST" {
		var req struct {
			ScheduleID string `json:"schedule_id"`
			Remark     string `json:"remark"`
			Date       string `json:"date"`
			Shift      string `json:"shift"`
			Type       string `json:"type"`
		}
		json.NewDecoder(r.Body).Decode(&req)
		
		today := time.Now().Format("2006-01-02")
		now := time.Now()
		hour := now.Hour()
		
		currentShift := ""
		if hour >= 8 && hour < 18 {
			currentShift = "morning"
		} else if hour >= 18 || hour < 8 {
			currentShift = "evening"
		}
		
		if req.Date != today || req.Shift != currentShift {
			json.NewEncoder(w).Encode(map[string]string{"status": "error", "msg": "只能在自己的班次时间内打卡"})
			return
		}
		
		h := models.HandoverLog{
			ID:           generateID(),
			ScheduleID:   req.ScheduleID,
			Username:     user.Username,
			Name:         user.Name,
			Date:         req.Date,
			Shift:        req.Shift,
			HandoverTime: time.Now(),
			Remark:       req.Remark,
			Type:         req.Type,
		}
		models.AddHandoverLog(h)
		json.NewEncoder(w).Encode(map[string]string{"status": "ok"})
	}
}

func CurrentDutyAPIHandler(w http.ResponseWriter, r *http.Request) {
	user, ok := getCurrentUser(r)
	if !ok {
		http.Error(w, "Unauthorized", http.StatusUnauthorized)
		return
	}
	
	today := time.Now().Format("2006-01-02")
	now := time.Now()
	hour := now.Hour()
	
	currentShift := ""
	if hour >= 8 && hour < 18 {
		currentShift = "morning"
	} else if hour >= 18 || hour < 8 {
		currentShift = "evening"
	}
	
	schedules := models.GetSchedulesByUsername(user.Username)
	for _, s := range schedules {
		if s.Date == today && s.Shift == currentShift {
			logs := models.GetHandoverLogsBySchedule(s.ID)
			hasClockIn := false
			hasClockOut := false
			clockInTime := ""
			clockOutTime := ""
			
			for _, log := range logs {
				if log.Username == user.Username {
					if log.Type == "clock_in" {
						hasClockIn = true
						clockInTime = log.HandoverTime.Format("15:04:05")
					} else if log.Type == "clock_out" {
						hasClockOut = true
						clockOutTime = log.HandoverTime.Format("15:04:05")
					}
				}
			}
			
			json.NewEncoder(w).Encode(map[string]interface{}{
				"on_duty":        true,
				"schedule":       s,
				"has_clock_in":   hasClockIn,
				"has_clock_out":  hasClockOut,
				"clock_in_time":  clockInTime,
				"clock_out_time": clockOutTime,
			})
			return
		}
	}
	json.NewEncoder(w).Encode(map[string]interface{}{"on_duty": false})
}
