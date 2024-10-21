CloudflareAccountMerge


# CloudflareAccountMerge | Cloudflare 跨账户域名迁移工具

CloudflareAccountMerge 简称CAM, 这个工具的用途是帮助你跨账户迁移网站Zone资源,并且可以同步迁移DNS记录和CDN加速状态

主要解决的问题
1. 多个Cloudflare账户下的网站 合并到一个Cloudflare账户下
2. Cloudflare A邮箱账户下的网站 合并到B邮箱Cloudflare账户下

配置方式

使用文本编辑器 打开 `CF_config.php` 文件,然后配置新账户Email和旧账户email 以及对应KEY


使用方法
1. php CF.php get_domains 获取旧Cloudflare账户域名列表 , 【会写入 zone 文件夹下，文件名称为域名，文件内容为旧账户下的ZoneID]
2. php CF.php list_domains 检查域名列表是不是正确,  读取列表
3. php CF.php export_record 获取所有域名的DNS解析记录和Cloudflare提供的加速服务状态 是开启还是关闭的 , 【写入 record文件夹】
4. php CF.php add_domain 把旧帐户导出的域名 添加到新账户里 
5. php CF.php delete_domain 把旧帐户导出的域名 在新账户里删除掉 （例如你突发奇想不想转移或者合并了...）
6. php CF.php import_record 把旧帐户导出的域名记录 添加到新账户里
7. php CF.php clear 清理 zone 和 record 的缓存文件 

完成 

迁移后请更新域名的NS服务器 然后


## Help

```shell

php CF.php
Command: php CF.php
Available Command:
  get_domains             Export OLD Cloudflare Account Domain Lists
  list_domains            Check OLD Cloudflare Account Domain list
  export_record           Export OLD Cloudflare Account Domain DNS Record
  add_domain              Add Domain to New Cloudflare Account
  import_record           Import Domain to New Cloudflare Account
  clear                   Delete All Cache Data

```



