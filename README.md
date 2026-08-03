# Silo

*Grain by grain.*

Silo is a self-hosted RSS/Atom feed reader. Subscribe to feeds, read articles in one place, organize them with categories and tags, search across everything, and optionally let AI summarize articles you don't have time to read in full.

Built with Laravel, Inertia.js, and Vue 3.

## Features

- **Feeds**: subscribe by URL, organize into categories, import/export OPML
- **Reading**: unread/read tracking, starring, a responsive reader UI
- **Organization & search**: tags, saved searches, full-text search
- **AI summaries**: opt in per feed to have new articles summarized automatically (via [laravel/ai](https://github.com/laravel/ai))
- **Background processing**: feed fetching and summarization run on queued jobs via Horizon, on a schedule

## Requirements

- [Docker](https://www.docker.com/) (for [Laravel Sail](https://laravel.com/docs/sail))

## Getting started

```bash
composer install
cp .env.example .env
php artisan key:generate

./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

The app will be available at the URL configured by `APP_PORT` in `.env` (defaults to `http://localhost`).

To fetch feeds in the background, run Horizon:

```bash
./vendor/bin/sail artisan horizon
```

Feed refreshes are scheduled every 5 minutes (`routes/console.php`); Laravel's scheduler needs to be running (`sail artisan schedule:work` in development, or a cron entry calling `schedule:run` in production) for that to fire automatically.

### AI summaries

Summaries are opt-in per feed and off by default. To enable them, configure a provider in `.env` — the default is Anthropic:

```
AI_DEFAULT_PROVIDER=anthropic
ANTHROPIC_API_KEY=
```

See `config/ai.php` for the full list of supported providers.

### Email

Password resets and other transactional mail default to the `log` driver locally, so they work without any external account. To send real mail through [Resend](https://resend.com):

```
MAIL_MAILER=resend
RESEND_API_KEY=
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

`RESEND_API_KEY` maps to `services.resend.key` — see `config/services.php` and `config/mail.php`.

## Running tests

```bash
./vendor/bin/sail artisan test
```

## Code style

```bash
./vendor/bin/sail php vendor/bin/pint
```
