
## CloudflareAccountMerge | Cloudflare Cross-Account Domain Migration Tool

CloudflareAccountMerge, abbreviated as CAM, is a tool designed to help you migrate website Zone resources across different Cloudflare accounts. It can also synchronize the migration of DNS records and CDN acceleration status.

## Key Problems Solved:

Consolidating websites: Merges websites from multiple Cloudflare accounts into a single Cloudflare account.
Migrating between email accounts: Migrates websites from a Cloudflare account associated with email A to a Cloudflare account associated with email B.


## How To Config

    mofify `CF_config.php` file,  update you cloudflare email and api key  ( use Global API Key )
    

## Usage:

```shell
php CF.php get_domains: Retrieves a list of domains from the old Cloudflare account and saves them in the zone folder. Each domain is saved in a separate file with the domain name as the filename and the ZoneID as the content.
php CF.php list_domains: Checks if the domain list is correct by reading the list.
php CF.php export_record: Retrieves DNS records and Cloudflare acceleration service status (enabled or disabled) for all domains and saves them in the record folder.
php CF.php add_domain: Adds domains exported from the old account to the new account.
php CF.php delete_domain: Deletes domains exported from the old account from the new account (in case you change your mind).
php CF.php import_record: Adds DNS records exported from the old account to the new account.
php CF.php clear: Clears the cache files in the zone and record folders.

```

Completion:
After migration, please update the New NS servers for your domains



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

