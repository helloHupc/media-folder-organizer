# Media Folder Organizer 安装与接入操作手册

本文档适用于 `Media Folder Organizer 1.0.0`。按照本文操作即可将插件安装到现有 WordPress 站点。

## 1. 安装前结论

插件只使用 WordPress 自带的插件、媒体库、用户权限、REST API 和数据库能力，不需要单独部署后端服务。

必须满足：

- WordPress 6.2 或更高版本。
- PHP 7.4 或更高版本。
- 能安装和启用 WordPress 插件的管理员账号，或能写入 `wp-content/plugins` 的主机、FTP/SFTP 权限。
- 实际使用插件的 WordPress 用户具有 `upload_files` 权限。
- 管理后台所用浏览器已启用 JavaScript。

不需要安装：

- Node.js、npm 或前端构建工具。
- Composer 或额外 PHP 包。
- Redis、Elasticsearch 或其他缓存/搜索服务。
- 外部数据库。
- 外部 API 密钥。
- 云存储账号。
- WordPress 以外的常驻进程。

插件不会移动 `wp-content/uploads` 中的物理文件，不会更改附件 URL，也不会修改文章中已有的媒体地址。

## 2. 安装前检查与条件处理

### 2.1 检查 WordPress 版本

在 WordPress 后台打开：

```text
仪表盘 > 更新
```

页面会显示当前 WordPress 版本。也可以打开：

```text
仪表盘 > 概况
```

如果低于 WordPress 6.2：

1. 先备份站点数据库和整个 `wp-content` 目录。
2. 确认当前主题和其他插件支持准备升级到的 WordPress 版本。
3. 在 **仪表盘 > 更新** 中升级 WordPress。
4. 升级完成后先确认站点前台和后台可正常访问，再安装本插件。

有 WP-CLI 时可在 WordPress 根目录执行：

```bash
wp core version
wp core update
```

生产站点建议先在预发布或测试站点完成 WordPress 升级验证。

### 2.2 检查 PHP 版本

在 WordPress 后台打开：

```text
工具 > 站点健康 > 信息 > 服务器
```

查看 **PHP 版本**。也可以在服务器终端执行：

```bash
php -v
```

如果低于 PHP 7.4，应通过主机控制面板或服务器运维流程升级。常见入口名称包括：

- PHP Version
- MultiPHP Manager
- PHP Selector
- 运行环境
- 网站设置 > PHP 版本

升级前确认主题和其他插件兼容目标 PHP 版本。共享主机无法自行切换时，需要联系主机服务商。插件本身不能替站点升级 PHP。

注意：终端中 `php -v` 的版本可能与网站实际使用的 PHP-FPM 版本不同，应以 WordPress **站点健康** 页面显示的版本为准。

### 2.3 检查安装权限

推荐使用 WordPress 管理员账号安装。后台应能看到：

```text
插件 > 安装插件
```

如果没有该菜单：

- 确认当前账号是站点管理员。
- 多站点环境中改用超级管理员账号。
- 检查 `wp-config.php` 是否设置了 `DISALLOW_FILE_MODS`。
- 联系站点运维人员，通过 FTP/SFTP、主机文件管理器或 WP-CLI 安装。

若存在以下配置，后台插件上传和安装可能被禁用：

```php
define( 'DISALLOW_FILE_MODS', true );
```

不要为了安装插件直接修改生产配置。由站点管理员按安全规范临时处理，或使用文件部署方式安装。

### 2.4 检查媒体权限

显示文件夹界面和管理文件夹需要 WordPress 的：

```text
upload_files
```

管理员、编辑和作者通常具备该权限，但角色编辑插件可能改变默认权限。

检查方式：

1. 使用目标账号登录后台。
2. 确认能够打开 **媒体 > 媒体库**。
3. 确认能够上传一个普通测试文件。

如果无法上传，使用角色权限管理插件为对应角色补充 `upload_files`，或由管理员调整角色。移动已有附件时，用户还必须具有编辑相应附件的权限。

### 2.5 检查浏览器

使用仍在安全支持期内的 Chrome、Edge、Firefox 或 Safari，并启用 JavaScript。插件的文件夹树、拖放和媒体弹窗筛选依赖 WordPress 管理后台 JavaScript。

若浏览器安装了脚本拦截扩展，应允许当前 WordPress 后台域名执行脚本。

### 2.6 备份

安装和激活不会创建自定义数据库表，但会在现有 WordPress taxonomy 表中写入文件夹和附件关联数据。

生产安装前至少备份：

- WordPress 数据库。
- `wp-content` 目录。

可使用主机快照、控制面板备份、现有备份插件或运维备份流程。恢复前应同时确认数据库备份和文件备份来自同一时间点。

## 3. 推荐安装方式：后台上传 ZIP

使用交付文件：

```text
media-folder-organizer-1.0.0.zip
```

操作步骤：

1. 使用管理员账号登录 WordPress。
2. 打开 **插件 > 安装插件**。
3. 点击页面顶部的 **上传插件**。
4. 选择 `media-folder-organizer-1.0.0.zip`。
5. 点击 **立即安装**。
6. 安装完成后点击 **启用插件**。
7. 打开 **媒体 > 媒体库**。
8. 确认页面中出现文件夹侧栏。

正确安装后，服务器上的目录结构应为：

```text
wp-content/
└── plugins/
    └── media-folder-organizer/
        ├── media-folder-organizer.php
        ├── includes/
        ├── assets/
        └── uninstall.php
```

如果目录变成：

```text
wp-content/plugins/media-folder-organizer/media-folder-organizer/
```

说明多套了一层目录。应删除错误安装的目录，并重新上传交付 ZIP。

## 4. 其他安装方式

### 4.1 FTP、SFTP 或主机文件管理器

1. 在本地解压 `media-folder-organizer-1.0.0.zip`。
2. 得到完整目录 `media-folder-organizer`。
3. 将该目录上传到站点的：

   ```text
   wp-content/plugins/
   ```

4. 确认主插件文件最终路径为：

   ```text
   wp-content/plugins/media-folder-organizer/media-folder-organizer.php
   ```

5. 登录 WordPress 后台。
6. 打开 **插件 > 已安装插件**。
7. 找到 **Media Folder Organizer** 并点击 **启用**。

文件权限应遵循当前 WordPress 站点规则。常见默认值是目录 `755`、文件 `644`，不要设置为 `777`。如果站点使用不同的 PHP-FPM 用户或容器部署，应保持文件所有者与现有插件一致。

### 4.2 WP-CLI

将 ZIP 上传到服务器后，在 WordPress 根目录执行：

```bash
wp plugin install /绝对路径/media-folder-organizer-1.0.0.zip --activate
wp plugin status media-folder-organizer
```

预期状态为：

```text
Status: Active
```

如果文件目录已经部署到 `wp-content/plugins/media-folder-organizer`，只需执行：

```bash
wp plugin activate media-folder-organizer
```

多站点全网启用使用：

```bash
wp plugin activate media-folder-organizer --network
```

仅在确实需要所有子站点统一启用时使用 `--network`。

### 4.3 WordPress 多站点

- 只有超级管理员可以从后台安装插件。
- 可在单个子站点启用，也可全网启用。
- 文件夹数据保存在各子站点自己的 WordPress 数据表中。
- 各子站点的文件夹和附件关联彼此独立。
- 启用前应在一个子站点完成首次验证，再决定是否全网启用。

## 5. 激活后的首次验证

不要直接用重要生产媒体做首次操作。建议上传 2 至 3 个测试图片，然后按顺序检查：

1. 打开 **媒体 > 媒体库**，确认文件夹侧栏出现。
2. 新建一个顶层文件夹。
3. 在该文件夹下新建一个子文件夹。
4. 重命名子文件夹。
5. 拖动文件夹，改变同级顺序。
6. 将一个测试图片移动到文件夹。
7. 分别在媒体库网格模式和列表模式筛选该文件夹。
8. 编辑一篇测试文章，打开 **插入媒体** 弹窗，确认可以按文件夹筛选。
9. 打开特色图片选择弹窗，确认可以按文件夹筛选。
10. 先选择目标文件夹，再上传一个测试图片，确认上传后已归入该文件夹。
11. 删除测试文件夹，确认图片文件仍存在，并显示为未分类。

插件后端逻辑和语法已完成交付前检查；浏览器端界面请在目标 WordPress 环境按以上清单验证，因为主题、媒体插件、安全插件和后台优化配置会影响实际界面行为。

## 6. 日常使用

### 6.1 创建和管理文件夹

在 **媒体 > 媒体库** 中：

- 点击文件夹侧栏的加号创建顶层文件夹。
- 点击某个文件夹行上的加号创建子文件夹。
- 点击编辑图标重命名。
- 拖动文件夹调整顺序。
- 将文件夹拖到另一个文件夹上可改变父级。
- 将文件夹拖到顶层放置区可移除父级。
- 点击删除图标会递归删除该文件夹及其子文件夹。

删除文件夹不会删除附件或物理文件。原来属于这些文件夹的附件会变为未分类。

### 6.2 移动已有媒体

网格模式：

1. 选择一个或多个媒体。
2. 将已选择的媒体拖到目标文件夹；或先选择目标文件夹，再点击 **Move selected media here**。

列表模式：

1. 使用每行复选框选择媒体。
2. 在侧栏选择目标文件夹。
3. 点击 **Move selected media here**。

一个附件同时只属于一个 Media Folder Organizer 文件夹。再次移动会替换原来的文件夹关联。

### 6.3 筛选媒体

- **All media**：显示全部媒体。
- **Uncategorized**：显示未分配文件夹的媒体。
- 选择具体文件夹：显示该文件夹及其全部后代文件夹中的媒体。

媒体库列表模式、插入媒体弹窗和特色图片弹窗中也可以使用文件夹筛选。

### 6.4 上传到指定文件夹

1. 打开 **媒体 > 添加新媒体文件**，或媒体弹窗中的 **上传文件**。
2. 在上传前选择 **Upload to folder**。
3. 选择文件并上传。

上传完成后，插件通过 WordPress 附件关联将媒体归入所选虚拟文件夹，不改变文件系统路径。

## 7. 数据存储与数据库条件

插件不创建自定义表，不需要执行 SQL，也不需要单独创建数据库账号。

使用的 taxonomy 名称为：

```text
mfo_media_folder
```

数据保存在当前站点已有的 WordPress 表中：

- `wp_terms`：文件夹名称和 slug。
- `wp_term_taxonomy`：父子层级。
- `wp_term_relationships`：附件与文件夹关联。
- `wp_termmeta`：`_mfo_order` 同级排序值。
- `wp_options`：插件版本。

实际表前缀可能不是 `wp_`。插件使用 WordPress API 自动识别，无需修改配置。

数据库账号只需具备当前 WordPress 正常运行所需的权限。若 WordPress 本身能创建分类、上传媒体和更新选项，通常无需额外授权。

## 8. REST API、安全与缓存配置

插件使用登录态 WordPress REST nonce，并要求用户具备 `upload_files`。使用的接口路径为：

```text
/wp-json/mfo/v1/
```

安全插件、WAF 或反向代理必须允许已登录管理员访问该路径。不要将此接口开放为匿名写入，也不要关闭 WordPress nonce 校验。

如果后台优化插件合并、延迟或改写管理端资源，将以下文件加入排除列表：

```text
media-folder-organizer/assets/js/admin.js
media-folder-organizer/assets/css/admin.css
```

修改配置后清理：

- WordPress 页面缓存。
- 对象缓存。
- CDN 缓存。
- 浏览器缓存。

通常不应对 `/wp-admin/` 和已登录用户页面启用整页缓存。

## 9. 上传限制和服务器条件

上传大小限制不是本插件依赖，但会影响 WordPress 媒体上传。可在 **媒体 > 添加新媒体文件** 页面查看当前最大上传文件大小。

常见 PHP 配置：

```ini
upload_max_filesize = 64M
post_max_size = 64M
memory_limit = 256M
max_execution_time = 120
```

这些值只是示例，应按站点需求和主机资源设置。`post_max_size` 不应小于 `upload_max_filesize`。Nginx 还可能需要调整：

```nginx
client_max_body_size 64m;
```

Apache、CDN、WAF 和主机控制面板也可能有独立限制。修改服务器配置后按当前环境要求重载 PHP-FPM 或 Web 服务器。共享主机用户应通过控制面板设置或联系服务商。

如果普通 WordPress 上传本身失败，应先修复 WordPress/服务器上传问题，再排查文件夹归类。

## 10. 常见问题处理

### 10.1 上传 ZIP 时提示文件过大

- 提高 PHP 和 Web 服务器上传限制。
- 改用 FTP/SFTP、主机文件管理器或 WP-CLI 安装。

### 10.2 提示“目标文件夹已存在”

1. 先停用旧版本。
2. 备份旧插件目录。
3. 使用后台提供的“替换当前版本”功能；如果当前 WordPress 未提供替换入口，则通过 FTP/SFTP 覆盖同名插件目录。
4. 确认没有形成双层 `media-folder-organizer/media-folder-organizer` 目录。

不要删除插件来完成普通升级，因为后台“删除插件”会运行 `uninstall.php` 并删除文件夹数据。

### 10.3 插件无法启用或出现 PHP 版本错误

- 在 **工具 > 站点健康 > 信息 > 服务器** 确认网站实际 PHP 版本。
- 确认 WordPress 不低于 6.2、PHP 不低于 7.4。
- 检查 ZIP 是否完整、主插件路径是否正确。
- 查看 WordPress 调试日志和 PHP 错误日志。

需要临时记录错误时，可由管理员在非生产环境配置：

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

日志通常位于：

```text
wp-content/debug.log
```

排查完成后恢复站点原有调试配置，避免长期记录敏感信息。

### 10.4 文件夹侧栏不显示

- 确认插件状态为已启用。
- 确认当前用户有 `upload_files`。
- 确认打开的是 WordPress 后台 **媒体 > 媒体库**。
- 强制刷新浏览器并清理后台优化缓存。
- 临时停用可能改写媒体库的插件，逐个恢复以定位冲突。
- 检查浏览器控制台是否有其他后台插件产生的 JavaScript 错误。

### 10.5 创建、重命名或移动文件夹返回 401/403

- 退出 WordPress 后重新登录，以刷新 nonce。
- 确认当前账号具有 `upload_files`。
- 打开 `/wp-json/`，确认 WordPress REST API 未被整体关闭。
- 检查安全插件、WAF 和代理是否拦截 `/wp-json/mfo/v1/`。
- 检查站点 URL 与后台实际访问 URL 的协议和域名是否一致，避免 Cookie/nonce 不匹配。

### 10.6 筛选不到媒体

- 刷新页面一次。
- 确认附件已移动到预期文件夹。
- 检查当前选择的是具体文件夹、未分类还是全部媒体。
- 临时停用其他媒体库文件夹或 taxonomy 查询插件，排查查询冲突。

选择父文件夹时会包含全部后代文件夹中的媒体，这是预期行为。

### 10.7 上传成功但媒体仍为未分类

- 必须在开始上传前选择目标文件夹。
- 清理后台脚本优化缓存。
- 确认其他插件没有替换 WordPress 原生上传器。
- 在浏览器网络面板检查上传请求是否带有 `mfo_folder`。
- 暂时停用其他媒体管理插件后复测。

### 10.8 拖放无反应

- 确认浏览器 JavaScript 已启用。
- 使用最新稳定版 Chrome、Edge、Firefox 或 Safari 重试。
- 关闭可能拦截鼠标或脚本的浏览器扩展。
- 将插件的 JS/CSS 加入后台优化排除列表。
- 查看浏览器控制台最先出现的 JavaScript 错误，最早的错误通常来自真正的冲突源。

## 11. 升级

升级前备份数据库和旧插件目录。

推荐步骤：

1. 保持当前插件数据不动。
2. 在后台上传新 ZIP。
3. WordPress 提示同名插件已存在时，选择替换当前版本。
4. 如后台不支持替换，通过 FTP/SFTP 或部署系统覆盖：

   ```text
   wp-content/plugins/media-folder-organizer
   ```

5. 返回 **插件 > 已安装插件**，确认插件仍为启用状态。
6. 清理后台资源缓存。
7. 按“激活后的首次验证”检查创建、移动、筛选和上传。

普通升级不要先点击“删除”，否则会执行卸载清理。

## 12. 停用、卸载、回滚与恢复

### 12.1 停用

在 **插件 > 已安装插件** 点击 **停用**：

- 文件夹界面停止加载。
- 文件夹和附件关联数据保留。
- 媒体物理文件和 URL 不受影响。
- 再次启用后可继续使用原数据。

### 12.2 卸载

在后台停用后点击 **删除**，WordPress 会运行 `uninstall.php`，删除：

- 所有 `mfo_media_folder` 文件夹。
- 文件夹与附件的关联。
- `_mfo_order` 排序元数据。
- `mfo_version` 选项。

不会删除：

- WordPress 附件记录。
- `wp-content/uploads` 中的物理文件。
- 文章中的媒体 URL。

卸载造成的文件夹结构和关联删除不可通过重新安装自动恢复。需要保留这些数据时，卸载前必须备份数据库。

### 12.3 代码回滚

若新版本出现问题：

1. 只停用插件，不要删除。
2. 将 `wp-content/plugins/media-folder-organizer` 替换为备份的旧版本目录。
3. 重新启用插件。
4. 清理后台缓存并检查媒体库。

若不同版本包含数据结构变更，应同时阅读对应版本升级说明。仅恢复插件代码不能撤销数据库变更。

### 12.4 数据恢复

若已经执行卸载并需要恢复文件夹：

1. 先备份当前站点。
2. 使用卸载前的数据库备份恢复相关数据，或恢复整个同时间点数据库。
3. 确保恢复方式不会覆盖卸载后新增的重要内容。
4. 恢复后重新安装并启用相同或兼容版本插件。

生产数据恢复应由熟悉 WordPress 数据库的管理员执行。没有数据库备份时，已卸载的文件夹结构和附件关联无法自动重建。

## 13. 交付验收清单

安装人员可使用以下清单记录结果：

- [ ] WordPress 版本不低于 6.2。
- [ ] PHP 版本不低于 7.4。
- [ ] 已完成数据库和 `wp-content` 备份。
- [ ] 插件目录只有一层 `media-folder-organizer`。
- [ ] 插件可以启用。
- [ ] 目标用户具有 `upload_files`。
- [ ] 媒体库显示文件夹侧栏。
- [ ] 可以创建、重命名、嵌套和排序文件夹。
- [ ] 可以将已有媒体移动到文件夹。
- [ ] 网格和列表模式筛选正常。
- [ ] 插入媒体和特色图片弹窗筛选正常。
- [ ] 上传前选择文件夹后可以正确归类。
- [ ] 删除测试文件夹不会删除媒体文件。
- [ ] 安全插件未拦截 `/wp-json/mfo/v1/`。
- [ ] 后台优化插件已排除本插件 JS/CSS，或确认无需排除。
- [ ] 已清理测试数据并记录安装版本。

