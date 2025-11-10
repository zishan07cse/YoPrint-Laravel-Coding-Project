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

