<p align="center">
    <a href="https://laravel.com" target="_blank">
        <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
    </a>
</p>

<p align="center">
    <a href="https://github.com/laravel/framework/actions">
        <img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status">
    </a>
    <a href="https://packagist.org/packages/laravel/framework">
        <img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads">
    </a>
    <a href="https://packagist.org/packages/laravel/framework">
        <img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version">
    </a>
    <a href="https://packagist.org/packages/laravel/framework">
        <img src="https://img.shields.io/packagist/l/laravel/framework" alt="License">
    </a>
</p>

---

# 📚 Assignment Schoox API

A RESTful API built with **Laravel 12**, running in **Docker** with **MySQL**, **Redis**, and **Laravel Passport** for secure user authentication. This application includes user management, queues, filtering endpoints, and more.

---

## 🚀 Tech Stack

- Laravel 12
- PHP 8.2+
- MySQL
- Redis
- Laravel Passport (OAuth2)
- Docker / Docker Compose

---

## 📦 Prerequisites

Ensure the following are installed:

- Docker & Docker Compose
- Git

---

## 🛠️ Installation & Setup

### 1. Clone the Repository

```bash
git clone https://github.com/TasosPapastavrou/Assignment-Schoox.git
cd Assignment-Schoox
cp .env.example .env


### 2. Configure Environment
Update the #.env file with:
```bash
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=Schoox
DB_USERNAME=Schoox
DB_PASSWORD=Schoox

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=redis
REDIS_PORT=6379


