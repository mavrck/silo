# Silo

*Grain by grain.*

Silo is a self-hosted RSS/Atom feed reader. Subscribe to feeds, read articles in one place, organize them with categories and tags, search across everything, and optionally let AI summarize or translate articles you don't have time to read in full — with digest emails to catch you up on what's new.

Built with Laravel, Inertia.js, and Vue 3.

## Features

- **Feeds**: subscribe by URL (RSS & Atom, via [feed-io](https://github.com/alexdebril/feed-io)), organize into categories, import/export OPML
- **Reading**: unread/read tracking, starring, mark-all-read, full-text search, a responsive reader UI with dedicated per-feed pages and mobile drawer navigation
- **Podcasts**: episode metadata (enclosures, duration, episode/season numbers) is picked up automatically for audio feeds
- **Organization**: categories and tags
- **AI summaries**: opt in per feed to have new articles summarized automatically (via [laravel/ai](https://github.com/laravel/ai))
- **AI translation**: opt in per feed to have articles translated to a target language, with a global kill switch independent of any feed's setting
- **Email digests**: opt-in daily/weekly emails, both an account-wide digest and separate per-feed digests, covering unread items since the last successful send
- **API access**: personal access tokens (via [Sanctum](https://laravel.com/docs/sanctum)) and read-only JSON:API endpoints for feeds, entries, categories, and tags
- **Background processing**: feed fetching, summarization, translation, and digest sends all run on queued jobs via Horizon, on a schedule

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

Feed refreshes and digest emails run on a schedule (`routes/console.php`) — feeds every 5 minutes, digests daily and weekly at 6:00am Eastern. Laravel's scheduler needs to be running (`sail artisan schedule:work` in development, or a cron entry calling `schedule:run` in production) for that to fire automatically.

### AI summaries & translation

Summaries and translation are both opt-in per feed and off by default. To enable summaries, configure a provider in `.env` — the default is Anthropic:

```
AI_DEFAULT_PROVIDER=anthropic
ANTHROPIC_API_KEY=
```

See `config/ai.php` for the full list of supported providers. Translation has its own global kill switch, off by default even if a feed has a target language set:

```
TRANSLATION_ENABLED=true
```

See `config/translation.php` for the list of supported target languages.

### Email

Password resets and other transactional mail default to the `log` driver locally, so they work without any external account. To send real mail through [Resend](https://resend.com):

```
MAIL_MAILER=resend
RESEND_API_KEY=
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

`RESEND_API_KEY` maps to `services.resend.key` — see `config/services.php` and `config/mail.php`. `MAIL_FROM_ADDRESS` must be on a domain verified with Resend, or sends will fail silently into the queue's `failed_jobs` table.

Digest frequency (`off`/`daily`/`weekly`) is set per user in profile settings and per feed on the feeds page; each digest only includes items still unread since that user's or feed's last successful send.

## Running tests

```bash
./vendor/bin/sail artisan test
```

## Code style

```bash
./vendor/bin/sail php vendor/bin/pint
```
