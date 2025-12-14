# IYUU 种子推送插件

基于 IYUU API 开发的 NexusPHP 插件，用于查询其他站点相同的种子并实现一键推送功能。

## ✨ 功能特性

- 🔍 基于 IYUU API 查询跨站点相同种子
- ⚙️ 后台管理配置界面
- 🔄 实时状态反馈

## 📦 安装方法

### 通过 Composer 安装

#### 1.8.x 版本
```bash
composer config repositories.betting vcs git@github.com:xqw8889/np-iyuu-push-torrent.git
composer require xqw8889/np-iyuu-push-torrent:1.8.x-dev
php artisan plugin install xqw8889/np-iyuu-push-torrent
```

#### 1.9.x 版本
```bash
omposer config repositories.betting vcs git@github.com:xqw8889/np-iyuu-push-torrent.git
composer require xqw8889/np-iyuu-push-torrent:1.9.x-dev
php artisan plugin install xqw8889/np-iyuu-push-torrent
```

### 方式二：通过 Artisan 安装
```bash
php artisan plugin install xqw8889/np-iyuu-push-torrent
```

## ⚙️ 配置说明

### 1. 获取认证信息
安装完成后，使用**主管账户**登录后台管理界面：

1. 访问：`http://localhost/nexusphp/settings` 页面
2. 找到 `IUYY辅助数据` 插件设置项

### 2. 申请认证密钥
需要获取以下两个关键信息：

- **sign_key**：API 签名密钥
- **site_name**：站点名称

请通过以下方式联系获取：
- **QQ 群**：[爱语飞飞对接群](http://qm.qq.com/cgi-bin/qm/qr?_wv=1027&k=FQ52EMjotpIpZyP-dUA5RbDoF_POU7qi&authKey=ehvWC%2F0MJ%2FvLMmem4jswLFxKfsf2GVEym2M6GQ0F2KpTgXw4EJPeeHbBdW4ha5vp&noverify=0&group_code=964806790)
- **群号**：964806790

### 3. 配置步骤
1. 登录 NexusPHP 后台管理
2. 导航至插件设置页面
3. 找到 "IYUU 种子推送" 插件
4. 输入获取到的 `sign_key` 和 `site_name`
5. 保存配置

## 🚀 使用方法

### 📌 手动添加代码挂钩
如果您希望在种子详情页显示其他站点查重信息，请在 `public/details.php` 文件中找到：

```php
tr($lang_details['row_basic_info'], $size_info.$type_info.$taxonomyRendered, 1);
```

在该行代码下面添加：

```php
//iyuu
do_action('IyuuPushTorren_torrent', $id);
```

## 🔧 技术特性

- **兼容性**：支持 NexusPHP 1.8.x 和 1.9.x 版本
- **安全性**：使用 API 签名验证
- **稳定性**：错误处理和重试机制
- **性能**：异步处理提升用户体验

## 📝 注意事项

1. **权限要求**：需要主管账户权限进行配置
2. **API 限制**：请遵守 IYUU API 的使用规范
3. **网络要求**：确保服务器能够访问 IYUU API 服务
4. **版本兼容**：选择与您的 NexusPHP 版本对应的插件版本

## 🆘 支持与帮助

### 常见问题
- **Q**: 插件安装后不显示？
    - **A**: 请检查是否使用主管账户登录，并清除浏览器缓存

- **Q**: 推送失败？
    - **A**: 请检查 `sign_key` 和 `site_name` 是否正确，或联系群内管理员

- **Q**: 如何确认 API 连接正常？
    - **A**: 后台配置页面会显示连接状态

### 获取帮助
- **官方 QQ 群**：964806790
- **问题反馈**：请在群内联系管理员
- **功能建议**：欢迎在群内提出宝贵建议

## 🔄 更新日志

### v1.9.x
- 支持 NexusPHP 1.9.x 版本

### v1.8.x
- 初始版本发布

## 📄 许可证

本项目基于 MIT 许可证开源。

## 🤝 贡献指南

欢迎提交 Issue 和 Pull Request！

---

**温馨提示**：使用本插件前，请确保您已获得相关站点的授权，并遵守各站点的规则和政策。