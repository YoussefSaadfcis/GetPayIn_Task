## Install the repository

1. Clone the repo and enter directory:

```bash
git clone https://github.com/YoussefSaadfcis/GetPayIn_Task.git
cd GetPayIn_Task
```

2. Install PHP dependencies:

```bash
composer install
```

3. Copy environment example and generate the app key:

```bash
cp .env.example .env
php artisan key:generate
```

4. Edit `.env` and set your database credentials (see Configuration below).

## Configuration

Edit the `.env` file to configure your environment. Minimum variables to set for local development:

```
APP_NAME=GetPayIn_Task
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
```

Notes:
- The project uses MySQL as the database.
- Cache driver is `file` by default (no external cache service required).

## Database migration

Run migrations to create the database schema:

```bash
php artisan migrate
```

If you need a fresh start (drops all tables then migrates):

```bash
php artisan migrate:fresh
```

## Run scheduled worker

This project uses Laravel scheduled tasks. To run the schedule in the foreground for development:

```bash
php artisan schedule:work
```

For production you should run the scheduler as a daemon (systemd, supervisor, or a process manager). Example (in background):

```bash
# run in background (development/test only)
nohup php artisan schedule:work > /dev/null 2>&1 &
```

## Optional: Seed database

There is an optional seeder that creates sample product data. Run the seeder (adjust class name if different):

```bash
php artisan db:seed --class=ProductSeeder
```

Or run all seeders:

```bash
php artisan db:seed
```

This step is optional but useful to populate the DB with example products.

## Run tests

Execute the test suite using Artisan:

```bash
php artisan test
```

Or directly with PHPUnit if required:

```bash
vendor/bin/phpunit
```

## Cache and logging

- Cache driver: file (configured via `CACHE_DRIVER=file` in .env).
- Logs: all application logs are written to the default Laravel log file:

```
storage\logs\laravel.log
```

(Adjust path separator for your OS; on Unix-like systems it is `storage/logs/laravel.log`.)

If you need more readable logs, consider configuring `config/logging.php` to use a daily log channel or a custom formatter (see Features below).

## Features / Enhancements

The repository includes the following enhancements:

1. Improved log readability and clarity

2. Unified API responses via a Resource

3. Add a service layer for all business logic in code, not only for WebHock logic
