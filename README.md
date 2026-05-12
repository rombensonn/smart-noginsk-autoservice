# Автосервис «Смарт» в Ногинске

Одностраничный PHP-лендинг для автосервиса «Смарт»: локальное SEO, адаптивный интерфейс, формы заявок, CSRF, honeypot, rate limit, JSONL-журнал заявок, email-уведомления и опциональная отправка в Telegram.

## Локальный запуск

```powershell
php -S 127.0.0.1:8088 router.php
```

Если PHP не добавлен в `PATH`, укажите полный путь к `php.exe`.

## Настройки

- Email для заявок: `config.php` → `mail.to`, затем `mail.enabled => true`.
- Telegram: `config.php` → `telegram.bot_token`, `telegram.chat_id`, `telegram.enabled => true`.
- amoCRM webhook: `config.php` → `amocrm.webhook_url`.
- Боевой домен: `config.php` → `site.site_url`, а также `robots.txt` и `sitemap.xml`.
- Юридические реквизиты: `docs/privacy-policy.html` и `docs/personal-data-consent.html`.

## Размещение

Проект рассчитан на обычный PHP-хостинг с PHP 8.1+. GitHub Pages используется только как статический предпросмотр интерфейса и не выполняет `submit.php`.
