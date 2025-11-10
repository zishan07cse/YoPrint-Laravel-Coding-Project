# Laravel CSV Uploader Project

This project provides a **CSV file uploader** with background processing, idempotent uploads, UTF-8 cleaning, and dynamic table updates. Products in the CSV are **upserted** into the database.

---

## Table of Contents

1. [Requirements](#requirements)
2. [Setup Instructions](#setup-instructions)
3. [Environment Variables (`.env`)](#environment-variables-env)
4. [Database Migrations](#database-migrations)
5. [Running the Queue Worker](#running-the-queue-worker)
6. [Testing the Project](#testing-the-project)
7. [Project Features and How Requirements are Fulfilled](#project-features-and-how-requirements-are-fulfilled)
8. [Front-end](#front-end)
9. [Notes](#notes)

---

## Requirements

- PHP >= 8.1
- MySQL or compatible database
- Composer
- Laravel 10+
- Node.js & npm (optional if using frontend assets)

---

## Setup Instructions

1. Clone the repository:

```bash

git clone <your-repo-url>
cd <your-project-folder>
composer install

# 3. Install Node.js dependencies
npm install

# 4. Build frontend assets (if applicable)
npm run dev   # or 'npm run build' for production

# 5. Setup environment file
cp .env.example .env

# 6. Generate application key
php artisan key:generate

# 7. Update .env with your database credentials (manual step)
# Example:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=your_database
# DB_USERNAME=root
# DB_PASSWORD=secret

# 8. Run migrations and seed database
php artisan migrate
php artisan db:seed

# 9. Start the development server
php artisan serve

Update your .env file with the following settings:

APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:<generated-key>
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yo_print
DB_USERNAME=root
DB_PASSWORD=password

SESSION_DRIVER=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public

BROADCAST_DRIVER=log
CACHE_STORE=database

MAIL_MAILER=log

Running the Queue Worker
php artisan queue:work

Testing the Project
http://localhost

Upload a CSV file using the drag-and-drop area or file selector.

Check the Upload Status Table:

pending → file uploaded, queued

processing → background job running

completed → successfully processed

failed → file processing failed

Verify product entries in the database (products table) are upserted correctly.

Upload the same CSV file multiple times:

No duplicate products are created.

Upload table shows new entries if desired.

Background job re-processes the CSV safely.
