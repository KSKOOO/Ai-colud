# 凌岳AI助手 - UniApp 移动端

凌岳科技AI辅助平台的跨平台移动端应用，支持iOS、Android、微信小程序等多端运行。

## 功能特性

- 🔐 用户登录/注册
- 💬 AI智能对话
- 🤖 智能体管理
- 📱 跨平台支持（iOS/Android/小程序/H5）

## 环境要求

- HBuilderX 3.8+
- Node.js 16+
- PHP后端服务已部署

## 快速开始

### 1. 配置服务器地址

编辑 `config/index.js` 文件，修改服务器配置：

```javascript
const SERVER_CONFIG = {
    // 服务器域名或IP地址
    host: 'tijo45376797.vicp.fun',  // 修改为实际域名或IP
    port: '80',                      // 端口号
    protocol: 'http'                 // 协议 http/https
}
```

**配置说明：**
- **开发环境**: 使用局域网IP（如 `192.168.1.100`）+ 端口 `8000`
- **生产环境**: 使用域名（如 `your-domain.com`）+ 端口 `80`
- **H5端**: 支持相对路径，自动适配当前域名

### 2. 安装依赖

```bash
npm install
```

### 3. 运行项目

**H5端（浏览器调试）：**
```bash
npm run dev:h5
```

**微信小程序：**
```bash
npm run dev:mp-weixin
```

**App端（需要连接手机或模拟器）：**
使用 HBuilderX 点击运行到手机或模拟器

### 4. 打包发布

**H5：**
```bash
npm run build:h5
```

**微信小程序：**
```bash
npm run build:mp-weixin
```

**Android App：**
使用 HBuilderX 云打包或本地打包

## 项目结构

```
gpustack-uniapp/
├── api/                    # API接口封装
│   ├── agents.js          # 智能体相关接口
│   ├── chat.js            # 聊天相关接口
│   └── user.js            # 用户相关接口
├── config/                 # 配置文件
│   └── index.js           # 服务器配置
├── pages/                  # 页面目录
│   ├── login/             # 登录页
│   ├── index/             # 首页
│   ├── chat/              # 聊天页
│   ├── agents/            # 智能体列表
│   ├── agent-detail/      # 智能体详情
│   ├── agent-create/      # 创建智能体
│   └── mine/              # 我的页面
├── static/                 # 静态资源
├── utils/                  # 工具函数
│   └── request.js         # HTTP请求封装
├── App.vue                # 应用入口
├── manifest.json          # 应用配置
└── pages.json             # 页面路由配置
```

## API接口说明

| 接口 | 说明 |
|------|------|
| `/api/api_handler.php` | AI聊天接口 |
| `/api/agents_handler.php` | 智能体管理接口 |
| `/api/user_handler.php` | 用户相关接口 |
| `/api/providers_handler.php` | AI提供商接口 |
| `/index.php?route=login` | 登录接口 |
| `/index.php?route=register` | 注册接口 |

## 常见问题

### 1. 启动后显示404错误

**原因**：服务器地址配置不正确

**解决方法**：
1. 确认后端服务已启动
2. 检查 `config/index.js` 中的 `host` 配置
3. 确保手机/模拟器与服务器在同一网络（开发环境）

### 2. 登录失败

**原因**：跨域或Session问题

**解决方法**：
- H5端：配置浏览器跨域插件
- App端：使用 `withCredentials: true` 已自动配置

### 3. 图片加载失败

项目已移除外部图片URL依赖，使用本地Emoji和CSS样式替代。

### 4. 智能体列表为空

**原因**：后端未创建智能体或接口404

**解决方法**：
1. 确认后端 `/api/agents_handler.php` 存在
2. 在Web端创建智能体后再查看

## 更新日志

### v1.0.0
- ✨ 初始版本发布
- ✅ 用户登录/注册
- ✅ AI智能对话
- ✅ 智能体列表/详情
- ✅ 多平台适配

## 开发团队

凌岳科技 © 2024

## 技术支持

如有问题，请联系技术支持或提交Issue。
