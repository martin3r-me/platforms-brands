# Truncate-Reihenfolge für Brands & Integrations Tabellen

## Problem

Beim Truncaten von Tabellen mit Foreign Key Constraints muss die richtige Reihenfolge eingehalten werden, um Constraint-Verletzungen zu vermeiden.

## Foreign Key Abhängigkeiten

```
integrations_facebook_pages
  ├── integrations_instagram_accounts (facebook_page_id → SET NULL)
  └── brands_facebook_posts (facebook_page_id → CASCADE)

integrations_instagram_accounts
  ├── brands_instagram_media (instagram_account_id → CASCADE)
  └── brands_instagram_account_insights (instagram_account_id → CASCADE)
```

## Korrekte Truncate-Reihenfolge

**WICHTIG:** Truncate in dieser exakten Reihenfolge ausführen:

1. `brands_instagram_media` (abhängig von integrations_instagram_accounts)
2. `brands_instagram_account_insights` (abhängig von integrations_instagram_accounts)
3. `brands_facebook_posts` (abhängig von integrations_facebook_pages)
4. `integrations_instagram_accounts` (abhängig von integrations_facebook_pages)
5. `integrations_facebook_pages` (Basis-Tabelle)

## SQL-Befehl (MySQL/MariaDB)

```sql
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE brands_instagram_media;
TRUNCATE TABLE brands_instagram_account_insights;
TRUNCATE TABLE brands_facebook_posts;
TRUNCATE TABLE integrations_instagram_accounts;
TRUNCATE TABLE integrations_facebook_pages;

SET FOREIGN_KEY_CHECKS = 1;
```

## Laravel Artisan Command

```php
// In einem Artisan Command oder Tinker:
DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

DB::table('brands_instagram_media')->truncate();
DB::table('brands_instagram_account_insights')->truncate();
DB::table('brands_facebook_posts')->truncate();
DB::table('integrations_instagram_accounts')->truncate();
DB::table('integrations_facebook_pages')->truncate();

DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
```

## Alternative: Foreign Keys temporär deaktivieren

Wenn du alle Brands-Daten löschen willst:

```php
Schema::disableForeignKeyConstraints();

DB::table('brands_instagram_media')->truncate();
DB::table('brands_instagram_account_insights')->truncate();
DB::table('brands_facebook_posts')->truncate();
DB::table('integrations_instagram_accounts')->truncate();
DB::table('integrations_facebook_pages')->truncate();

Schema::enableForeignKeyConstraints();
```

## Hinweis

- **CASCADE** bedeutet: Wenn die Parent-Tabelle gelöscht wird, werden auch die Child-Daten gelöscht
- **SET NULL** bedeutet: Wenn die Parent-Tabelle gelöscht wird, wird der Foreign Key auf NULL gesetzt
- Beim Truncaten werden Foreign Key Constraints trotzdem geprüft, daher die richtige Reihenfolge wichtig
