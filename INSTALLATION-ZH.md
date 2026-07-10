# Media Folder Organizer 安装、使用与运维指南

本文档面向从 GitHub 获取并部署 Media Folder Organizer 的 WordPress 管理员、站点维护者和开发者。

项目地址：<https://github.com/helloHupc/media-folder-organizer>

## 环境要求

- WordPress 6.2 或更高版本。
- PHP 7.4 或更高版本。
- 实际使用者具有 `upload_files` 权限。
- 能安装插件，或能写入 `wp-content/plugins`。
- 管理后台浏览器已启用 JavaScript。

插件不需要 Composer、Node.js 构建、外部 API、Redis、云服务或独立数据库。

## 生产环境备份

安装或升级前建议备份：

- WordPress 数据库。
- 整个 `wp-content` 目录。

插件不创建自定义数据表，但会在 WordPress 原生 taxonomy 表中保存文件夹、层级、排序和附件关联。

## 从 GitHub Release 安装

1. 从 [GitHub Releases](https://github.com/helloHupc/media-folder-organizer/releases) 下载发布 ZIP。
2. 登录 WordPress 管理后台。
3. 打开 **插件 > 安装插件 > 上传插件**。
4. 上传 ZIP，完成安装并启用 **Media Folder Organizer**。
5. 打开 **媒体 > 媒体库**，确认左侧出现文件夹栏。

最终目录应为：

```text
wp-content/plugins/media-folder-organizer/
```

不要出现 `media-folder-organizer/media-folder-organizer` 双层目录。

## 从源码安装

在 WordPress 插件目录中克隆仓库：

```bash
cd /WordPress绝对路径/wp-content/plugins
git clone git@github.com:helloHupc/media-folder-organizer.git
```

然后在后台启用，或执行：

```bash
wp plugin activate media-folder-organizer
```

## 从源码制作安装包

在仓库上级目录执行：

```bash
zip -r media-folder-organizer.zip media-folder-organizer \
  -x 'media-folder-organizer/.git/*'
```

ZIP 的顶层必须是 `media-folder-organizer` 目录。

## 首次验证

建议使用测试图片依次验证：

1. 创建一级文件夹。
2. 创建子文件夹。
3. 重命名文件夹。
4. 将一个一级文件夹拖到另一个文件夹上，刷新后确认层级保留。
5. 将图片移动到文件夹。
6. 在媒体库网格模式筛选。
7. 在列表模式筛选。
8. 在插入媒体弹窗筛选。
9. 在特色图片弹窗筛选。
10. 选择目标文件夹后上传图片。
11. 删除测试文件夹，确认图片仍存在并变为未分类。

## 管理文件夹

打开 **媒体 > 媒体库**：

- 点击侧栏标题区的加号创建一级文件夹。
- 点击文件夹行的加号创建子文件夹。
- 使用编辑和删除按钮管理文件夹。
- 拖动文件夹调整同级顺序。
- 将文件夹拖到另一个文件夹行上，使其成为子文件夹。
- 将文件夹拖到“移动到顶层”区域，恢复为一级文件夹。

删除文件夹会同时删除其子文件夹，但不会删除 WordPress 附件或物理文件；相关媒体会变为未分类。

## 移动和筛选媒体

网格模式下，可选择一个或多个附件并拖到目标文件夹，也可以先选择文件夹，再点击 **Move selected media here**。

列表模式下，使用复选框选择附件，选择目标文件夹后执行移动。

每个附件最多属于一个本插件文件夹，再次移动会替换原关联。

选择父文件夹时，会同时显示所有后代文件夹中的媒体。

## 上传到指定文件夹

在以下位置开始上传前选择 **Upload to folder**：

- **媒体 > 添加新媒体文件**。
- WordPress 原生媒体弹窗的“上传文件”页面。

WordPress 创建附件后，插件会将其关联到所选虚拟文件夹，不会改变实际上传路径。

## 数据存储

插件注册私有层级 taxonomy：

```text
mfo_media_folder
```

数据使用 WordPress 原生表保存：

- `wp_terms`：文件夹名称。
- `wp_term_taxonomy`：父子层级。
- `wp_term_relationships`：附件关联。
- `wp_termmeta`：`_mfo_order` 同级排序。
- `wp_options`：插件版本。

实际表前缀可能不是 `wp_`。插件不会创建自定义表，也不会移动文件、修改附件 URL 或重写文章内容。

## 权限和 REST API

文件夹管理需要 `upload_files`。移动附件还要求当前用户能编辑对应附件。

插件使用登录态 WordPress REST nonce，请求路径为：

```text
/wp-json/mfo/v1/
```

安全插件、WAF 和反向代理应允许已登录管理员访问该路径，但不应开放匿名写入。

## 缓存与后台优化

后台页面通常不应启用整页缓存。如果优化插件会合并、延迟或改写管理端资源，请排除：

```text
media-folder-organizer/assets/js/admin.js
media-folder-organizer/assets/css/admin.css
```

升级后清理后台优化缓存，并对浏览器执行强制刷新。

## 升级

### ZIP 安装方式

1. 备份数据库和当前插件目录。
2. 上传新版本 ZIP。
3. 选择 WordPress 的“替换当前版本”。
4. 不要先删除旧插件；删除会执行卸载逻辑并清除文件夹数据。
5. 清理缓存并重新执行首次验证。

### Git 安装方式

在插件目录执行：

```bash
git pull --ff-only
```

跨主要版本升级前应先阅读 [CHANGELOG.md](CHANGELOG.md)。

## 常见问题

### 文件夹侧栏不显示

- 确认插件已启用。
- 确认当前用户具有 `upload_files`。
- 确认打开的是后台 **媒体 > 媒体库**。
- 强制刷新浏览器并清理后台优化缓存。
- 检查其他媒体插件产生的 JavaScript 错误。

### 分类筛选结果不正确

- 确认已启用最新版本。
- 升级后重新加载媒体库。
- 确认附件确实属于目标文件夹。
- 临时停用其他媒体文件夹或 taxonomy 查询插件排查冲突。

### 文件夹拖放无效

- 将文件夹行拖到目标文件夹，等待目标高亮后松开。
- 要恢复一级文件夹，请拖到顶层放置区域。
- 确认 JavaScript 已启用。
- 暂时关闭会修改鼠标行为的浏览器扩展。
- 将插件 JS/CSS 加入后台优化排除列表。

### REST 请求返回 401 或 403

- 退出 WordPress 后重新登录，刷新 nonce。
- 确认账号具有 `upload_files`。
- 确认 `/wp-json/` 未被整体关闭。
- 检查 `/wp-json/mfo/v1/` 的安全插件和 WAF 规则。

### 上传后仍为未分类

- 必须在上传开始前选择目标文件夹。
- 确认其他插件没有替换 WordPress 原生上传器。
- 在网络面板检查上传请求是否带有 `mfo_folder`。

## 停用与卸载

停用插件会保留所有文件夹和附件关联，再次启用后可以继续使用。

在 WordPress 后台删除插件会执行 `uninstall.php`，并删除：

- 所有 `mfo_media_folder` 文件夹。
- 文件夹与附件的关联。
- `_mfo_order` 排序元数据。
- `mfo_version` 选项。

卸载不会删除附件记录、物理文件或媒体 URL。若没有数据库备份，卸载后的文件夹结构无法自动恢复。

## 开源支持

- 问题和功能建议：[GitHub Issues](https://github.com/helloHupc/media-folder-organizer/issues)
- 贡献说明：[CONTRIBUTING.md](CONTRIBUTING.md)
- 安全问题：[SECURITY.md](SECURITY.md)
- 版本记录：[CHANGELOG.md](CHANGELOG.md)

项目采用 [MIT License](LICENSE)，作者为 `hupc`。
