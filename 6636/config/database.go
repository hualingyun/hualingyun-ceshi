package config

import (
	"book-recommendation/models"
	"fmt"
	"log"
	"os"

	"github.com/jinzhu/gorm"
	_ "github.com/jinzhu/gorm/dialects/sqlite"
	"github.com/joho/godotenv"
)

var DB *gorm.DB

func InitDatabase() {
	err := godotenv.Load()
	if err != nil {
		log.Println("警告: 未找到 .env 文件，使用默认配置")
	}

	dbPath := os.Getenv("DB_PATH")
	if dbPath == "" {
		dbPath = "book.db"
	}

	DB, err = gorm.Open("sqlite3", dbPath)
	if err != nil {
		log.Fatal("无法连接到数据库:", err)
	}

	fmt.Println("数据库连接成功!")

	DB.AutoMigrate(&models.User{}, &models.Book{}, &models.Comment{}, &models.Like{}, &models.Recommendation{})
	fmt.Println("数据库迁移完成!")

	initBooksData()
}

func initBooksData() {
	var count int
	DB.Model(&models.Book{}).Count(&count)
	if count > 0 {
		return
	}

	books := []models.Book{
		{
			Title:       "活着",
			Author:      "余华",
			Price:       39.80,
			Description: "《活着》讲述了农村人福贵悲惨的人生遭遇。福贵本是个阔少爷，可他嗜赌如命，终于赌光了家业，一贫如洗。他的父亲被他活活气死，母亲则在穷困中患了重病，福贵前去求药，却在途中被国民党抓去当壮丁，后被解放军俘虏，回到家中，才知道母亲早已去世，妻子家珍含辛茹苦地养大两个儿女。此后，更加悲惨的命运一次又一次降临到福贵身上，他的妻子、儿女和孙子相继死去，最后只剩福贵和一头老牛相依为命，但老人依旧活着，仿佛比往日更加洒脱与坚强。",
			Cover:       "https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=Chinese%20novel%20book%20cover%20art%20emotional%20drama%20traditional%20style&image_size=square",
			Rating:      9.4,
		},
		{
			Title:       "百年孤独",
			Author:      "加西亚·马尔克斯",
			Price:       49.50,
			Description: "《百年孤独》是魔幻现实主义文学的代表作，描写了布恩迪亚家族七代人的传奇故事，以及加勒比海沿岸小镇马孔多的百年兴衰，反映了拉丁美洲一个世纪以来风云变幻的历史。作品融入神话传说、民间故事、宗教典故等因素，巧妙地糅合了现实与虚幻，展现出一个瑰丽的想象世界。",
			Cover:       "https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=magical%20realism%20book%20cover%20colorful%20fantasy%20island%20village&image_size=square",
			Rating:      9.3,
		},
		{
			Title:       "三体",
			Author:      "刘慈欣",
			Price:       93.00,
			Description: "《三体》是刘慈欣创作的系列长篇科幻小说，由《三体》、《三体Ⅱ·黑暗森林》、《三体Ⅲ·死神永生》组成，第一部于2006年5月起在《科幻世界》杂志上连载，讲述了地球人类文明和三体文明的信息交流、生死搏杀及两个文明在宇宙中的兴衰历程。",
			Cover:       "https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=science%20fiction%20book%20cover%20space%20stars%20three%20body%20problem&image_size=square",
			Rating:      9.4,
		},
		{
			Title:       "解忧杂货店",
			Author:      "东野圭吾",
			Price:       39.50,
			Description: "僻静的街道旁有一家特别的杂货店，只要写下烦恼投进店前门卷帘门的投信口，第二天就会在店后的牛奶箱里得到回答。因男友身患绝症，年轻女孩静子在爱情与梦想间徘徊；克郎为了音乐梦想离家漂泊，却在现实中举步维艰；少年浩介面临家庭巨变，不知该何去何从……他们将困惑写成信投进杂货店，奇妙的事情随即不断发生。",
			Cover:       "https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=Japanese%20mystery%20book%20cover%20grocery%20store%20warm%20light%20night&image_size=square",
			Rating:      8.5,
		},
		{
			Title:       "人类简史",
			Author:      "尤瓦尔·赫拉利",
			Price:       68.00,
			Description: "《人类简史》以独特的视角审视人类历史，从石器时代到人工智能时代，讲述我们如何登上食物链顶端，成为地球的主宰者。作者尤瓦尔·赫拉利用一种全新的方式审视人类的历史，重新思考我们是谁，我们从哪里来，我们要到哪里去。",
			Cover:       "https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=history%20book%20cover%20human%20evolution%20timeline%20artistic&image_size=square",
			Rating:      9.1,
		},
		{
			Title:       "小王子",
			Author:      "安托万·德·圣-埃克苏佩里",
			Price:       32.00,
			Description: "《小王子》是法国作家安托万·德·圣埃克苏佩里于1942年写成的著名儿童文学短篇小说。本书的主人公是来自外星球的小王子。书中以一位飞行员作为故事叙述者，讲述了小王子从自己星球出发前往地球的过程中，所经历的各种历险。作者以小王子的孩子式的眼光，透视出成人的空虚、盲目，愚妄和死板教条，用浅显天真的语言写出了人类的孤独寂寞、没有根基随风流浪的命运。",
			Cover:       "https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=little%20prince%20book%20cover%20starry%20sky%20rose%20fox%20magical&image_size=square",
			Rating:      9.0,
		},
		{
			Title:       "追风筝的人",
			Author:      "卡勒德·胡赛尼",
			Price:       45.00,
			Description: "12岁的阿富汗富家少爷阿米尔与他父亲仆人儿子哈桑之间的友情故事，作者并没有很华丽的文笔，她仅仅是用那淡柔的文字细腻的勾勒了家庭与友谊，背叛与救赎，给我们一幅心灵的画卷。当罪行导致善行，那就是真正的获救。",
			Cover:       "https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=kite%20flying%20book%20cover%20afghanistan%20sunset%20emotional&image_size=square",
			Rating:      8.9,
		},
		{
			Title:       "围城",
			Author:      "钱钟书",
			Price:       36.00,
			Description: "《围城》是钱钟书所著的长篇小说，是中国现代文学史上一部风格独特的讽刺小说。被誉为\"新儒林外史\"。第一版于1947年由上海晨光出版公司出版。故事主要写抗战初期知识分子的群相。",
			Cover:       "https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=Chinese%20literature%20book%20cover%20walled%20city%20metaphor%20artistic&image_size=square",
			Rating:      8.9,
		},
		{
			Title:       "平凡的世界",
			Author:      "路遥",
			Price:       98.00,
			Description: "《平凡的世界》是中国作家路遥创作的一部百万字的小说。这是一部全景式地表现中国当代城乡社会生活的长篇小说，全书共三部。该书以中国70年代中期到80年代中期十年间为背景，以孙少安和孙少平两兄弟为中心，通过复杂的矛盾纠葛，刻画了当时社会各阶层众多普通人的形象。",
			Cover:       "https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=ordinary%20world%20book%20cover%20rural%20china%20sunrise%20hope&image_size=square",
			Rating:      9.0,
		},
		{
			Title:       "嫌疑人X的献身",
			Author:      "东野圭吾",
			Price:       39.50,
			Description: "百年一遇的数学天才石神，每天唯一的乐趣，便是去固定的便当店买午餐，只为看一眼在便当店做事的邻居靖子。靖子与女儿相依为命，失手杀了前来纠缠的前夫。石神提出由他料理善后。石神设了一个匪夷所思的局，令警方始终只能在外围敲敲打打，根本无法与案子沾边。石神究竟使用了什么手法？",
			Cover:       "https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=mystery%20suspense%20book%20cover%20mathematical%20genius%20dark%20atmosphere&image_size=square",
			Rating:      8.9,
		},
	}

	for _, book := range books {
		DB.Create(&book)
	}

	fmt.Println("初始化图书数据完成!")
}
