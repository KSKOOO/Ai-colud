# Uni-App 干净重编译步骤

## 1. 清理旧产物

在项目根目录执行：

```bat
gpustack-uniapp\clean_unpackage.bat
```

该脚本会删除：

- `gpustack-uniapp/unpackage/dist`
- `gpustack-uniapp/unpackage/cache`
- `gpustack-uniapp/unpackage/release`

会保留：

- `gpustack-uniapp/unpackage/res`

## 2. 重新生成开发产物

如果你使用 HBuilderX：

1. 关闭正在运行的手机调试
2. 执行一次 `clean_unpackage.bat`
3. 重新运行“发行”或“运行到手机/模拟器”

如果你使用命令行：

```bat
cd gpustack-uniapp
npm install
npm run dev:app
```

或正式构建：

```bat
cd gpustack-uniapp
npm install
npm run build:app
```

## 3. 手机上仍显示旧资源时

按顺序处理：

1. 彻底关闭手机上当前运行的旧 app
2. 卸载旧安装包
3. 重新运行上面的重编译步骤
4. 再安装新包

## 4. 快速验证点

重编译后优先检查：

1. 底部 tabbar 图标不再是字母占位
2. 底部文案为“首页 / 聊天 / 智能体 / 我的”
3. 创建智能体页面模型列表支持搜索、按提供商分组
4. 创建智能体页面图标选择器显示正常 emoji 图标
