package store

import (
	"book-recommendation/models"
	"sort"
	"sync"
	"time"
)

type MemoryStore struct {
	users           map[uint]models.User
	usersByUsername map[string]models.User
	usersByEmail    map[string]models.User
	books           map[uint]models.Book
	comments        map[uint]models.Comment
	commentsByBook  map[uint][]models.Comment
	likes           map[uint]models.Like
	likesByBook     map[uint]map[uint]bool
	recommendations map[uint]models.Recommendation
	recsByBook      map[uint]map[uint]bool

	userCounter uint
	bookCounter uint
	commentCounter uint
	likeCounter uint
	recCounter uint

	mu sync.RWMutex
}

var Store *MemoryStore

func InitStore() {
	Store = &MemoryStore{
		users:           make(map[uint]models.User),
		usersByUsername: make(map[string]models.User),
		usersByEmail:    make(map[string]models.User),
		books:           make(map[uint]models.Book),
		comments:        make(map[uint]models.Comment),
		commentsByBook:  make(map[uint][]models.Comment),
		likes:           make(map[uint]models.Like),
		likesByBook:     make(map[uint]map[uint]bool),
		recommendations: make(map[uint]models.Recommendation),
		recsByBook:      make(map[uint]map[uint]bool),
		userCounter:     1,
		bookCounter:     1,
		commentCounter:  1,
		likeCounter:     1,
		recCounter:      1,
	}

	initBooksData()
}

func initBooksData() {
	books := []models.Book{
		{
			Title:       "活着",
			Author:      "余华",
			Price:       39.80,
			Description: "《活着》讲述了农村人福贵悲惨的人生遭遇。福贵本是个阔少爷，可他嗜赌如命，终于赌光了家业，一贫如洗。他的父亲被他活活气死，母亲则在穷困中患了重病，福贵前去求药，却在途中被国民党抓去当壮丁，后被解放军俘虏，回到家中，才知道母亲早已去世，妻子家珍含辛茹苦地养大两个儿女。此后，更加悲惨的命运一次又一次降临到福贵身上，他的妻子、儿女和孙子相继死去，最后只剩福贵和一头老牛相依为命，但老人依旧活着，仿佛比往日更加洒脱与坚强。",
			Cover:       "https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=Chinese%20novel%20book%20cover%20art%20emotional%20drama%20traditional%20style&image_size=square",
			Rating:      9.4,
			LikeCount:   0,
			CommentCount: 0,
		},
		{
			Title:       "百年孤独",
			Author:      "加西亚·马尔克斯",
			Price:       49.50,
			Description: "《百年孤独》是魔幻现实主义文学的代表作，描写了布恩迪亚家族七代人的传奇故事，以及加勒比海沿岸小镇马孔多的百年兴衰，反映了拉丁美洲一个世纪以来风云变幻的历史。作品融入神话传说、民间故事、宗教典故等因素，巧妙地糅合了现实与虚幻，展现出一个瑰丽的想象世界。",
			Cover:       "https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=magical%20realism%20book%20cover%20colorful%20fantasy%20island%20village&image_size=square",
			Rating:      9.3,
			LikeCount:   0,
			CommentCount: 0,
		},
		{
			Title:       "三体",
			Author:      "刘慈欣",
			Price:       93.00,
			Description: "《三体》是刘慈欣创作的系列长篇科幻小说，由《三体》、《三体Ⅱ·黑暗森林》、《三体Ⅲ·死神永生》组成，第一部于2006年5月起在《科幻世界》杂志上连载，讲述了地球人类文明和三体文明的信息交流、生死搏杀及两个文明在宇宙中的兴衰历程。",
			Cover:       "https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=science%20fiction%20book%20cover%20space%20stars%20three%20body%20problem&image_size=square",
			Rating:      9.4,
			LikeCount:   0,
			CommentCount: 0,
		},
		{
			Title:       "解忧杂货店",
			Author:      "东野圭吾",
			Price:       39.50,
			Description: "僻静的街道旁有一家特别的杂货店，只要写下烦恼投进店前门卷帘门的投信口，第二天就会在店后的牛奶箱里得到回答。因男友身患绝症，年轻女孩静子在爱情与梦想间徘徊；克郎为了音乐梦想离家漂泊，却在现实中举步维艰；少年浩介面临家庭巨变，不知该何去何从……他们将困惑写成信投进杂货店，奇妙的事情随即不断发生。",
			Cover:       "https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=Japanese%20mystery%20book%20cover%20grocery%20store%20warm%20light%20night&image_size=square",
			Rating:      8.5,
			LikeCount:   0,
			CommentCount: 0,
		},
		{
			Title:       "人类简史",
			Author:      "尤瓦尔·赫拉利",
			Price:       68.00,
			Description: "《人类简史》以独特的视角审视人类历史，从石器时代到人工智能时代，讲述我们如何登上食物链顶端，成为地球的主宰者。作者尤瓦尔·赫拉利用一种全新的方式审视人类的历史，重新思考我们是谁，我们从哪里来，我们要到哪里去。",
			Cover:       "https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=history%20book%20cover%20human%20evolution%20timeline%20artistic&image_size=square",
			Rating:      9.1,
			LikeCount:   0,
			CommentCount: 0,
		},
		{
			Title:       "小王子",
			Author:      "安托万·德·圣-埃克苏佩里",
			Price:       32.00,
			Description: "《小王子》是法国作家安托万·德·圣埃克苏佩里于1942年写成的著名儿童文学短篇小说。本书的主人公是来自外星球的小王子。书中以一位飞行员作为故事叙述者，讲述了小王子从自己星球出发前往地球的过程中，所经历的各种历险。作者以小王子的孩子式的眼光，透视出成人的空虚、盲目，愚妄和死板教条，用浅显天真的语言写出了人类的孤独寂寞、没有根基随风流浪的命运。",
			Cover:       "https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=little%20prince%20book%20cover%20starry%20sky%20rose%20fox%20magical&image_size=square",
			Rating:      9.0,
			LikeCount:   0,
			CommentCount: 0,
		},
		{
			Title:       "追风筝的人",
			Author:      "卡勒德·胡赛尼",
			Price:       45.00,
			Description: "12岁的阿富汗富家少爷阿米尔与他父亲仆人儿子哈桑之间的友情故事，作者并没有很华丽的文笔，她仅仅是用那淡柔的文字细腻的勾勒了家庭与友谊，背叛与救赎，给我们一幅心灵的画卷。当罪行导致善行，那就是真正的获救。",
			Cover:       "https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=kite%20flying%20book%20cover%20afghanistan%20sunset%20emotional&image_size=square",
			Rating:      8.9,
			LikeCount:   0,
			CommentCount: 0,
		},
		{
			Title:       "围城",
			Author:      "钱钟书",
			Price:       36.00,
			Description: "《围城》是钱钟书所著的长篇小说，是中国现代文学史上一部风格独特的讽刺小说。被誉为\"新儒林外史\"。第一版于1947年由上海晨光出版公司出版。故事主要写抗战初期知识分子的群相。",
			Cover:       "https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=Chinese%20literature%20book%20cover%20walled%20city%20metaphor%20artistic&image_size=square",
			Rating:      8.9,
			LikeCount:   0,
			CommentCount: 0,
		},
		{
			Title:       "平凡的世界",
			Author:      "路遥",
			Price:       98.00,
			Description: "《平凡的世界》是中国作家路遥创作的一部百万字的小说。这是一部全景式地表现中国当代城乡社会生活的长篇小说，全书共三部。该书以中国70年代中期到80年代中期十年间为背景，以孙少安和孙少平两兄弟为中心，通过复杂的矛盾纠葛，刻画了当时社会各阶层众多普通人的形象。",
			Cover:       "https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=ordinary%20world%20book%20cover%20rural%20china%20sunrise%20hope&image_size=square",
			Rating:      9.0,
			LikeCount:   0,
			CommentCount: 0,
		},
		{
			Title:       "嫌疑人X的献身",
			Author:      "东野圭吾",
			Price:       39.50,
			Description: "百年一遇的数学天才石神，每天唯一的乐趣，便是去固定的便当店买午餐，只为看一眼在便当店做事的邻居靖子。靖子与女儿相依为命，失手杀了前来纠缠的前夫。石神提出由他料理善后。石神设了一个匪夷所思的局，令警方始终只能在外围敲敲打打，根本无法与案子沾边。石神究竟使用了什么手法？",
			Cover:       "https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=mystery%20suspense%20book%20cover%20mathematical%20genius%20dark%20atmosphere&image_size=square",
			Rating:      8.9,
			LikeCount:   0,
			CommentCount: 0,
		},
	}

	for _, book := range books {
		Store.CreateBook(&book)
	}
}

func (s *MemoryStore) CreateUser(user *models.User) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	user.ID = s.userCounter
	user.CreatedAt = time.Now()
	user.UpdatedAt = time.Now()
	s.users[user.ID] = *user
	s.usersByUsername[user.Username] = *user
	s.usersByEmail[user.Email] = *user
	s.userCounter++
	return nil
}

func (s *MemoryStore) GetUserByUsername(username string) (*models.User, bool) {
	s.mu.RLock()
	defer s.mu.RUnlock()
	user, exists := s.usersByUsername[username]
	return &user, exists
}

func (s *MemoryStore) GetUserByEmail(email string) (*models.User, bool) {
	s.mu.RLock()
	defer s.mu.RUnlock()
	user, exists := s.usersByEmail[email]
	return &user, exists
}

func (s *MemoryStore) GetUserByID(id uint) (*models.User, bool) {
	s.mu.RLock()
	defer s.mu.RUnlock()
	user, exists := s.users[id]
	return &user, exists
}

func (s *MemoryStore) CreateBook(book *models.Book) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	book.ID = s.bookCounter
	book.CreatedAt = time.Now()
	book.UpdatedAt = time.Now()
	s.books[book.ID] = *book
	s.likesByBook[book.ID] = make(map[uint]bool)
	s.recsByBook[book.ID] = make(map[uint]bool)
	s.bookCounter++
	return nil
}

func (s *MemoryStore) GetBookByID(id uint) (*models.Book, bool) {
	s.mu.RLock()
	defer s.mu.RUnlock()
	book, exists := s.books[id]
	return &book, exists
}

func (s *MemoryStore) UpdateBook(book *models.Book) error {
	s.mu.Lock()
	defer s.mu.Unlock()
	book.UpdatedAt = time.Now()
	s.books[book.ID] = *book
	return nil
}

func (s *MemoryStore) GetAllBooks() []models.Book {
	s.mu.RLock()
	defer s.mu.RUnlock()
	books := make([]models.Book, 0, len(s.books))
	for _, book := range s.books {
		books = append(books, book)
	}
	return books
}

func (s *MemoryStore) SearchBooks(query string) []models.Book {
	s.mu.RLock()
	defer s.mu.RUnlock()
	books := make([]models.Book, 0)
	for _, book := range s.books {
		if contains(book.Title, query) || contains(book.Author, query) {
			books = append(books, book)
		}
	}
	return books
}

func (s *MemoryStore) CreateComment(comment *models.Comment) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	comment.ID = s.commentCounter
	comment.CreatedAt = time.Now()
	comment.UpdatedAt = time.Now()
	s.comments[comment.ID] = *comment
	s.commentsByBook[comment.BookID] = append(s.commentsByBook[comment.BookID], *comment)
	s.commentCounter++

	if book, exists := s.books[comment.BookID]; exists {
		book.CommentCount++
		s.books[comment.BookID] = book
	}
	return nil
}

func (s *MemoryStore) GetCommentsByBookID(bookID uint) []models.Comment {
	s.mu.RLock()
	defer s.mu.RUnlock()
	comments := s.commentsByBook[bookID]
	result := make([]models.Comment, len(comments))
	for i := range comments {
		result[i] = comments[i]
		if user, exists := s.users[comments[i].UserID]; exists {
			result[i].User = user
		}
	}
	return result
}

func (s *MemoryStore) ToggleLike(userID, bookID uint) (bool, int) {
	s.mu.Lock()
	defer s.mu.Unlock()

	book, exists := s.books[bookID]
	if !exists {
		return false, 0
	}

	if s.likesByBook[bookID] == nil {
		s.likesByBook[bookID] = make(map[uint]bool)
	}

	if s.likesByBook[bookID][userID] {
		delete(s.likesByBook[bookID], userID)
		book.LikeCount--
		s.books[bookID] = book
		return false, book.LikeCount
	} else {
		like := models.Like{
			ID:        s.likeCounter,
			UserID:    userID,
			BookID:    bookID,
			CreatedAt: time.Now(),
		}
		s.likes[like.ID] = like
		s.likesByBook[bookID][userID] = true
		s.likeCounter++
		book.LikeCount++
		s.books[bookID] = book
		return true, book.LikeCount
	}
}

func (s *MemoryStore) HasUserLiked(userID, bookID uint) bool {
	s.mu.RLock()
	defer s.mu.RUnlock()
	return s.likesByBook[bookID] != nil && s.likesByBook[bookID][userID]
}

func (s *MemoryStore) AddRecommendation(userID, bookID uint, reason string) error {
	s.mu.Lock()
	defer s.mu.Unlock()

	if s.recsByBook[bookID] == nil {
		s.recsByBook[bookID] = make(map[uint]bool)
	}

	if s.recsByBook[bookID][userID] {
		return nil
	}

	rec := models.Recommendation{
		ID:        s.recCounter,
		UserID:    userID,
		BookID:    bookID,
		Reason:    reason,
		CreatedAt: time.Now(),
	}
	s.recommendations[rec.ID] = rec
	s.recsByBook[bookID][userID] = true
	s.recCounter++
	return nil
}

func (s *MemoryStore) HasUserRecommended(userID, bookID uint) bool {
	s.mu.RLock()
	defer s.mu.RUnlock()
	return s.recsByBook[bookID] != nil && s.recsByBook[bookID][userID]
}

func (s *MemoryStore) GetRecommendedBooks() []models.Book {
	s.mu.RLock()
	defer s.mu.RUnlock()

	recs := make([]models.Recommendation, 0, len(s.recommendations))
	for _, rec := range s.recommendations {
		recs = append(recs, rec)
	}

	seen := make(map[uint]bool)
	books := make([]models.Book, 0)
	for i := len(recs) - 1; i >= 0 && len(books) < 12; i-- {
		rec := recs[i]
		if !seen[rec.BookID] {
			if book, exists := s.books[rec.BookID]; exists {
				books = append(books, book)
				seen[rec.BookID] = true
			}
		}
	}
	return books
}

type RecommendationDetail struct {
	ID          uint       `json:"id"`
	BookID      uint       `json:"book_id"`
	UserID      uint       `json:"user_id"`
	Username    string     `json:"username"`
	Reason      string     `json:"reason"`
	CreatedAt   time.Time  `json:"created_at"`
	Book        models.Book `json:"book"`
}

func (s *MemoryStore) GetRecommendationDetails() []RecommendationDetail {
	s.mu.RLock()
	defer s.mu.RUnlock()

	recs := make([]models.Recommendation, 0, len(s.recommendations))
	for _, rec := range s.recommendations {
		recs = append(recs, rec)
	}

	result := make([]RecommendationDetail, 0)
	for i := len(recs) - 1; i >= 0; i-- {
		rec := recs[i]
		book, bookExists := s.books[rec.BookID]
		user, userExists := s.users[rec.UserID]
		
		if bookExists {
			detail := RecommendationDetail{
				ID:        rec.ID,
				BookID:    rec.BookID,
				UserID:    rec.UserID,
				Reason:    rec.Reason,
				CreatedAt: rec.CreatedAt,
				Book:      book,
			}
			if userExists {
				detail.Username = user.Username
			}
			result = append(result, detail)
		}
	}
	return result
}

func (s *MemoryStore) GetBookRecommendations(bookID uint) []RecommendationDetail {
	s.mu.RLock()
	defer s.mu.RUnlock()

	result := make([]RecommendationDetail, 0)
	for _, rec := range s.recommendations {
		if rec.BookID == bookID {
			book, bookExists := s.books[rec.BookID]
			user, userExists := s.users[rec.UserID]
			
			if bookExists {
				detail := RecommendationDetail{
					ID:        rec.ID,
					BookID:    rec.BookID,
					UserID:    rec.UserID,
					Reason:    rec.Reason,
					CreatedAt: rec.CreatedAt,
					Book:      book,
				}
				if userExists {
					detail.Username = user.Username
				}
				result = append(result, detail)
			}
		}
	}

	sort.Slice(result, func(i, j int) bool {
		return result[i].CreatedAt.After(result[j].CreatedAt)
	})
	return result
}

func contains(s, substr string) bool {
	if len(substr) == 0 {
		return true
	}
	for i := 0; i <= len(s)-len(substr); i++ {
		if s[i:i+len(substr)] == substr {
			return true
		}
	}
	return false
}
