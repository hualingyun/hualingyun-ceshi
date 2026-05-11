package models

import (
	"encoding/json"
	"os"
	"sync"
	"time"
)

type User struct {
	Username string `json:"username"`
	Password string `json:"password"`
	Role     string `json:"role"` // "admin" or "student"
	Name     string `json:"name"`
}

type Schedule struct {
	ID        string    `json:"id"`
	Date      string    `json:"date"`
	Shift     string    `json:"shift"` // "morning" or "evening"
	Username  string    `json:"username"`
	Name      string    `json:"name"`
	CreatedAt time.Time `json:"created_at"`
}

type HandoverLog struct {
	ID           string    `json:"id"`
	ScheduleID   string    `json:"schedule_id"`
	Username     string    `json:"username"`
	Name         string    `json:"name"`
	Date         string    `json:"date"`
	Shift        string    `json:"shift"`
	HandoverTime time.Time `json:"handover_time"`
	Remark       string    `json:"remark"`
	Type         string    `json:"type"` // "clock_in" 接班, "clock_out" 交班
}

var (
	users     = make(map[string]User)
	schedules = make(map[string]Schedule)
	handoverLogs = make(map[string]HandoverLog)
	mu        sync.RWMutex
)

func LoadUsers() error {
	mu.Lock()
	defer mu.Unlock()

	data, err := os.ReadFile("data/users.json")
	if os.IsNotExist(err) {
		users["admin"] = User{
			Username: "admin",
			Password: "Admin123_",
			Role:     "admin",
			Name:     "管理员",
		}
		saveUsersNoLock()
		return nil
	}
	if err != nil {
		return err
	}

	var userList []User
	if err := json.Unmarshal(data, &userList); err != nil {
		return err
	}

	for _, u := range userList {
		users[u.Username] = u
	}
	return nil
}

func saveUsersNoLock() error {
	var userList []User
	for _, u := range users {
		userList = append(userList, u)
	}

	data, err := json.MarshalIndent(userList, "", "  ")
	if err != nil {
		return err
	}

	os.MkdirAll("data", 0755)
	return os.WriteFile("data/users.json", data, 0644)
}

func SaveUsers() error {
	mu.RLock()
	defer mu.RUnlock()

	var userList []User
	for _, u := range users {
		userList = append(userList, u)
	}

	data, err := json.MarshalIndent(userList, "", "  ")
	if err != nil {
		return err
	}

	os.MkdirAll("data", 0755)
	return os.WriteFile("data/users.json", data, 0644)
}

func GetUser(username string) (User, bool) {
	mu.RLock()
	defer mu.RUnlock()
	u, ok := users[username]
	return u, ok
}

func AddUser(u User) {
	mu.Lock()
	defer mu.Unlock()
	users[u.Username] = u
	saveUsersNoLock()
}

func DeleteUser(username string) {
	mu.Lock()
	defer mu.Unlock()
	delete(users, username)
	saveUsersNoLock()
}

func GetAllUsers() []User {
	mu.RLock()
	defer mu.RUnlock()
	var list []User
	for _, u := range users {
		list = append(list, u)
	}
	return list
}

func LoadSchedules() error {
	mu.Lock()
	defer mu.Unlock()

	data, err := os.ReadFile("data/schedules.json")
	if os.IsNotExist(err) {
		return nil
	}
	if err != nil {
		return err
	}

	var list []Schedule
	if err := json.Unmarshal(data, &list); err != nil {
		return err
	}

	for _, s := range list {
		schedules[s.ID] = s
	}
	return nil
}

func saveSchedulesNoLock() error {
	var list []Schedule
	for _, s := range schedules {
		list = append(list, s)
	}

	data, err := json.MarshalIndent(list, "", "  ")
	if err != nil {
		return err
	}

	os.MkdirAll("data", 0755)
	return os.WriteFile("data/schedules.json", data, 0644)
}

func SaveSchedules() error {
	mu.RLock()
	defer mu.RUnlock()
	return saveSchedulesNoLock()
}

func AddSchedule(s Schedule) {
	mu.Lock()
	defer mu.Unlock()
	schedules[s.ID] = s
	saveSchedulesNoLock()
}

func UpdateSchedule(s Schedule) {
	mu.Lock()
	defer mu.Unlock()
	schedules[s.ID] = s
	saveSchedulesNoLock()
}

func DeleteSchedule(id string) {
	mu.Lock()
	defer mu.Unlock()
	delete(schedules, id)
	saveSchedulesNoLock()
}

func GetAllSchedules() []Schedule {
	mu.RLock()
	defer mu.RUnlock()
	var list []Schedule
	for _, s := range schedules {
		list = append(list, s)
	}
	return list
}

func GetSchedulesByUsername(username string) []Schedule {
	mu.RLock()
	defer mu.RUnlock()
	var list []Schedule
	for _, s := range schedules {
		if s.Username == username {
			list = append(list, s)
		}
	}
	return list
}

func LoadHandoverLogs() error {
	mu.Lock()
	defer mu.Unlock()

	data, err := os.ReadFile("data/handover_logs.json")
	if os.IsNotExist(err) {
		return nil
	}
	if err != nil {
		return err
	}

	var list []HandoverLog
	if err := json.Unmarshal(data, &list); err != nil {
		return err
	}

	for _, h := range list {
		handoverLogs[h.ID] = h
	}
	return nil
}

func saveHandoverLogsNoLock() error {
	var list []HandoverLog
	for _, h := range handoverLogs {
		list = append(list, h)
	}

	data, err := json.MarshalIndent(list, "", "  ")
	if err != nil {
		return err
	}

	os.MkdirAll("data", 0755)
	return os.WriteFile("data/handover_logs.json", data, 0644)
}

func SaveHandoverLogs() error {
	mu.RLock()
	defer mu.RUnlock()
	return saveHandoverLogsNoLock()
}

func AddHandoverLog(h HandoverLog) {
	mu.Lock()
	defer mu.Unlock()
	handoverLogs[h.ID] = h
	saveHandoverLogsNoLock()
}

func GetAllHandoverLogs() []HandoverLog {
	mu.RLock()
	defer mu.RUnlock()
	var list []HandoverLog
	for _, h := range handoverLogs {
		list = append(list, h)
	}
	return list
}

func GetHandoverLogsBySchedule(scheduleID string) []HandoverLog {
	mu.RLock()
	defer mu.RUnlock()
	var list []HandoverLog
	for _, h := range handoverLogs {
		if h.ScheduleID == scheduleID {
			list = append(list, h)
		}
	}
	return list
}
