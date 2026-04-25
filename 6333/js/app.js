// 美食数据
const foodData = [
    {
        id: 1,
        name: '麻辣水煮鱼',
        category: '川菜',
        rating: 4.8,
        image: 'https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=delicious%20spicy%20boiled%20fish%20sichuan%20style%20food%20photography&image_size=square_hd',
        address: '上海市静安区南京西路123号',
        price: 58,
        description: '正宗四川风味，鱼肉鲜嫩，麻辣鲜香，回味无穷。'
    },
    {
        id: 2,
        name: '宫保鸡丁',
        category: '川菜',
        rating: 4.6,
        image: 'https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=kung%20pao%20chicken%20sichuan%20traditional%20food%20photography&image_size=square_hd',
        address: '上海市黄浦区外滩88号',
        price: 45,
        description: '经典川菜，鸡肉鲜嫩，花生香脆，口味独特。'
    },
    {
        id: 3,
        name: '麻婆豆腐',
        category: '川菜',
        rating: 4.7,
        image: 'https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=mapo%20tofu%20sichuan%20cuisine%20food%20photography&image_size=square_hd',
        address: '上海市徐汇区淮海中路567号',
        price: 38,
        description: '麻辣鲜香，豆腐嫩滑，正宗川味。'
    },
    {
        id: 4,
        name: '白切鸡',
        category: '粤菜',
        rating: 4.5,
        image: 'https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=cantonese%20white%20cut%20chicken%20food%20photography&image_size=square_hd',
        address: '上海市浦东新区陆家嘴环路100号',
        price: 52,
        description: '皮爽肉嫩，原汁原味，广东名菜。'
    },
    {
        id: 5,
        name: '烧鹅',
        category: '粤菜',
        rating: 4.9,
        image: 'https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=cantonese%20roast%20goose%20crispy%20skin%20food%20photography&image_size=square_hd',
        address: '上海市长宁区虹桥路200号',
        price: 68,
        description: '皮脆肉嫩，香气四溢，广东招牌菜。'
    },
    {
        id: 6,
        name: '剁椒鱼头',
        category: '湘菜',
        rating: 4.7,
        image: 'https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=hunan%20chopped%20chili%20fish%20head%20food%20photography&image_size=square_hd',
        address: '上海市普陀区长寿路300号',
        price: 78,
        description: '鲜嫩鱼头配剁椒，香辣可口，湖南名菜。'
    },
    {
        id: 7,
        name: '小炒黄牛肉',
        category: '湘菜',
        rating: 4.8,
        image: 'https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=hunan%20stir%20fried%20beef%20spicy%20food%20photography&image_size=square_hd',
        address: '上海市杨浦区邯郸路400号',
        price: 62,
        description: '牛肉鲜嫩，香辣入味，下饭神器。'
    },
    {
        id: 8,
        name: '寿司拼盘',
        category: '日料',
        rating: 4.6,
        image: 'https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=japanese%20sushi%20platter%20fresh%20salmon%20food%20photography&image_size=square_hd',
        address: '上海市虹口区四川北路500号',
        price: 88,
        description: '新鲜三文鱼、金枪鱼、北极贝等，美味寿司拼盘。'
    },
    {
        id: 9,
        name: '拉面',
        category: '日料',
        rating: 4.5,
        image: 'https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=japanese%20ramen%20noodle%20soup%20pork%20food%20photography&image_size=square_hd',
        address: '上海市闵行区龙茗路600号',
        price: 42,
        description: '浓郁汤底，劲道面条，日式经典美味。'
    },
    {
        id: 10,
        name: '牛排',
        category: '西餐',
        rating: 4.7,
        image: 'https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=western%20steak%20grilled%20medium%20rare%20food%20photography&image_size=square_hd',
        address: '上海市浦东新区世纪大道800号',
        price: 128,
        description: '澳洲进口牛肉，鲜嫩多汁，口感极佳。'
    },
    {
        id: 11,
        name: '意大利面',
        category: '西餐',
        rating: 4.4,
        image: 'https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=italian%20spaghetti%20bolognese%20tomato%20sauce%20food%20photography&image_size=square_hd',
        address: '上海市静安区愚园路700号',
        price: 58,
        description: '经典番茄肉酱意面，美味可口。'
    },
    {
        id: 12,
        name: '回锅肉',
        category: '川菜',
        rating: 4.6,
        image: 'https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=sichuan%20twice%20cooked%20pork%20food%20photography&image_size=square_hd',
        address: '上海市黄浦区福州路900号',
        price: 48,
        description: '肥而不腻，香辣可口，川菜经典。'
    }
];

// 美食专题数据
const topicsData = [
    {
        id: 1,
        title: '夏日消暑美食',
        description: '炎炎夏日，让我们一起探索清凉爽口的消暑美食，让味蕾享受清爽的感觉。',
        image: 'https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=summer%20cold%20noodles%20ice%20cream%20refreshing%20food%20photography&image_size=landscape_4_3',
        date: '2026-06-15',
        foods: ['凉皮', '冷面', '冰淇淋', '酸梅汤']
    },
    {
        id: 2,
        title: '冬季暖心火锅',
        description: '寒冷的冬天，没有什么比一顿热气腾腾的火锅更暖心了，各种火锅大盘点。',
        image: 'https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=winter%20hot%20pot%20spicy%20warm%20comfort%20food%20photography&image_size=landscape_4_3',
        date: '2026-11-20',
        foods: ['四川火锅', '潮汕牛肉火锅', '老北京涮羊肉', '日式寿喜烧']
    },
    {
        id: 3,
        title: '早餐营养指南',
        description: '一日之计在于晨，早餐是一天中最重要的一餐，让我们一起看看有哪些营养美味的早餐选择。',
        image: 'https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=healthy%20breakfast%20oatmeal%20eggs%20fresh%20food%20photography&image_size=landscape_4_3',
        date: '2026-03-10',
        foods: ['燕麦粥', '三明治', '豆浆油条', '西式早餐']
    },
    {
        id: 4,
        title: '深夜食堂',
        description: '夜猫子们的福利，各种深夜美食让你在夜晚也能享受美食的乐趣。',
        image: 'https://trae-api-cn.mchost.guru/api/ide/v1/text_to_image?prompt=midnight%20snack%20street%20food%20noodles%20food%20photography&image_size=landscape_4_3',
        date: '2026-08-05',
        foods: ['烧烤', '麻辣烫', '小龙虾', '深夜食堂料理']
    }
];

// 地图上的店铺位置数据（以上海为例）
const shopLocations = [
    {
        id: 1,
        name: '麻辣水煮鱼',
        shop: '川味轩',
        lat: 31.2304,
        lng: 121.4737,
        category: '川菜',
        address: '上海市静安区南京西路123号',
        rating: 4.8,
        price: 58,
        phone: '021-12345678'
    },
    {
        id: 2,
        name: '烧鹅',
        shop: '粤来粤好',
        lat: 31.2400,
        lng: 121.4800,
        category: '粤菜',
        address: '上海市长宁区虹桥路200号',
        rating: 4.9,
        price: 68,
        phone: '021-87654321'
    },
    {
        id: 3,
        name: '剁椒鱼头',
        shop: '湘菜馆',
        lat: 31.2200,
        lng: 121.4600,
        category: '湘菜',
        address: '上海市普陀区长寿路300号',
        rating: 4.7,
        price: 78,
        phone: '021-11112222'
    },
    {
        id: 4,
        name: '寿司拼盘',
        shop: '樱之味',
        lat: 31.2500,
        lng: 121.4900,
        category: '日料',
        address: '上海市虹口区四川北路500号',
        rating: 4.6,
        price: 88,
        phone: '021-33334444'
    },
    {
        id: 5,
        name: '牛排',
        shop: '西餐厅',
        lat: 31.2100,
        lng: 121.5000,
        category: '西餐',
        address: '上海市浦东新区世纪大道800号',
        rating: 4.7,
        price: 128,
        phone: '021-55556666'
    }
];

// 全局变量
let map;
let markers = [];

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    initNavigation();
    renderFoodList('all');
    renderTopics();
    initMap();
    initTabs();
});

// 初始化导航栏
function initNavigation() {
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');
    
    if (mobileMenuBtn && navLinks) {
        mobileMenuBtn.addEventListener('click', function() {
            navLinks.classList.toggle('active');
        });
        
        // 点击导航链接后关闭移动端菜单
        const links = navLinks.querySelectorAll('a');
        links.forEach(link => {
            link.addEventListener('click', function() {
                navLinks.classList.remove('active');
                
                // 更新活动状态
                links.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });
    }
}

// 初始化菜系标签
function initTabs() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // 移除所有活动状态
            tabBtns.forEach(b => b.classList.remove('active'));
            // 添加当前按钮活动状态
            this.classList.add('active');
            // 获取菜系分类
            const category = this.getAttribute('data-category');
            // 渲染对应的美食列表
            renderFoodList(category);
        });
    });
}

// 渲染美食列表
function renderFoodList(category) {
    const foodList = document.getElementById('foodList');
    let filteredFoods;
    
    if (category === 'all') {
        filteredFoods = foodData;
    } else {
        filteredFoods = foodData.filter(food => food.category === category);
    }
    
    if (filteredFoods.length === 0) {
        foodList.innerHTML = '<p style="text-align: center; color: #666; grid-column: 1/-1;">暂无该类别的美食</p>';
        return;
    }
    
    const html = filteredFoods.map(food => `
        <div class="food-card" data-id="${food.id}">
            <img src="${food.image}" alt="${food.name}" class="food-image" loading="lazy">
            <div class="food-content">
                <div class="food-header">
                    <h3 class="food-name">${food.name}</h3>
                    <div class="rating">
                        <span>★</span>
                        <span class="rating-value">${food.rating}</span>
                    </div>
                </div>
                <div class="food-info">
                    <div class="info-item">
                        <span class="info-label">菜系：</span>
                        <span>${food.category}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">地址：</span>
                        <span>${food.address}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">推荐：</span>
                        <span>${food.description}</span>
                    </div>
                </div>
                <div class="food-footer">
                    <div class="price">人均 ¥${food.price}</div>
                    <button class="btn" onclick="showOnMap(${food.id})">查看地图</button>
                </div>
            </div>
        </div>
    `).join('');
    
    foodList.innerHTML = html;
}

// 渲染美食专题
function renderTopics() {
    const topicsList = document.getElementById('topicsList');
    
    const html = topicsData.map(topic => `
        <div class="topic-card" data-id="${topic.id}">
            <img src="${topic.image}" alt="${topic.title}" class="topic-image" loading="lazy">
            <div class="topic-content">
                <h3 class="topic-title">${topic.title}</h3>
                <p class="topic-description">${topic.description}</p>
                <div class="topic-meta">
                    <span>发布日期: ${topic.date}</span>
                    <span>${topic.foods.length} 种美食</span>
                </div>
            </div>
        </div>
    `).join('');
    
    topicsList.innerHTML = html;
    
    // 添加点击事件
    const topicCards = document.querySelectorAll('.topic-card');
    topicCards.forEach(card => {
        card.addEventListener('click', function() {
            const id = parseInt(this.getAttribute('data-id'));
            showTopicDetail(id);
        });
    });
}

// 显示专题详情
function showTopicDetail(id) {
    const topic = topicsData.find(t => t.id === id);
    if (!topic) return;
    
    alert(`【${topic.title}】\n\n${topic.description}\n\n推荐美食：${topic.foods.join('、')}\n\n发布日期：${topic.date}`);
}

// 初始化地图
function initMap() {
    // 创建地图实例
    map = L.map('map').setView([31.2304, 121.4737], 12);
    
    // 添加OpenStreetMap图层
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);
    
    // 添加店铺标记
    addShopMarkers();
}

// 添加店铺标记
function addShopMarkers() {
    shopLocations.forEach(shop => {
        // 创建自定义图标
        const icon = L.divIcon({
            className: 'custom-marker',
            html: `<div style="background-color: #ff6b6b; color: white; padding: 8px 12px; border-radius: 5px; font-weight: bold; box-shadow: 0 2px 10px rgba(0,0,0,0.3);">${shop.name}</div>`,
            iconSize: [100, 40],
            iconAnchor: [50, 40]
        });
        
        // 添加标记
        const marker = L.marker([shop.lat, shop.lng], {icon: icon}).addTo(map);
        
        // 绑定点击事件
        marker.on('click', function() {
            showShopInfo(shop);
        });
        
        // 添加到标记数组
        markers.push({
            marker: marker,
            shop: shop
        });
    });
}

// 显示店铺信息
function showShopInfo(shop) {
    const shopInfo = document.getElementById('shopInfo');
    
    const html = `
        <div class="shop-detail">
            <h4>${shop.name}</h4>
            <p><strong>店铺名称：</strong>${shop.shop}</p>
            <p><strong>菜系：</strong>${shop.category}</p>
            <p><strong>地址：</strong>${shop.address}</p>
            <p><strong>评分：</strong>★ ${shop.rating}</p>
            <p><strong>人均消费：</strong>¥${shop.price}</p>
            <p><strong>联系电话：</strong>${shop.phone}</p>
            <p style="margin-top: 10px; font-size: 12px; color: #999;">
                <em>点击地图可查看周边环境，如需导航可复制地址到地图应用</em>
            </p>
        </div>
    `;
    
    shopInfo.innerHTML = html;
    
    // 平滑滚动到地图信息区域
    shopInfo.scrollIntoView({behavior: 'smooth', block: 'nearest'});
}

// 在地图上显示特定美食
function showOnMap(foodId) {
    const food = foodData.find(f => f.id === foodId);
    if (!food) return;
    
    // 查找对应的店铺位置
    const shop = shopLocations.find(s => s.name === food.name);
    if (!shop) {
        alert('该美食暂无地图信息');
        return;
    }
    
    // 滚动到地图区域
    document.getElementById('map').scrollIntoView({behavior: 'smooth'});
    
    // 延迟执行，等待滚动完成
    setTimeout(() => {
        // 定位到店铺位置
        map.setView([shop.lat, shop.lng], 15);
        
        // 显示店铺信息
        showShopInfo(shop);
        
        // 高亮对应的标记（通过模拟点击）
        const markerData = markers.find(m => m.shop.id === shop.id);
        if (markerData) {
            markerData.marker.openPopup();
        }
    }, 500);
}

// 平滑滚动导航
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        const targetElement = document.querySelector(targetId);
        
        if (targetElement) {
            targetElement.scrollIntoView({
                behavior: 'smooth'
            });
        }
    });
});
