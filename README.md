# Deployment Steps for Laravel 11 Project

## Prerequisites
- PHP 8.2 or higher
- Composer
- Memcached installed and running on the server
- SQLite database setup with the path: `database.sqlite`

## Setup Steps

1. **Clone the repository**
   git clone https://github.com/daniialHZ/Blog.git cd Blog

2. **Install dependencies using Composer**
   composer install --optimize-autoloader --no-dev

3. **Set up the .env file**
   Copy `.env.example` to `.env`:
   cp .env.example .env

- Ensure the following values in the `.env` file:
    - `APP_KEY`: Run `php artisan key:generate` if not already set.
    - `DB_CONNECTION=sqlite`
    - `DB_DATABASE=database.sqlite` (Make sure the `database.sqlite` file exists)
    - `CACHE_DRIVER=memcached`
    - `MEMCACHED_HOST=127.0.0.1`
    - Configure other values as needed (email, queue, etc.)

4. **Generate application key**
   If not already set, generate the application key:
   php artisan key:generate

5. **Migrate the database**
   Since you're using SQLite, ensure the `database.sqlite` file is created and accessible.
   php artisan migrate

6. **Set up Memcached**
    - Ensure Memcached is installed and running on the server.
    - Configure the `MEMCACHED_HOST` in the `.env` file (default: `127.0.0.1`).

7. **Set up the cache**
   Ensure the cache driver is configured to use Memcached:
   CACHE_DRIVER=memcached

8. **Set up the session**
    - Make sure the session is set to use the database:
      ```
      SESSION_DRIVER=database
      ```

9. **Set up the queue**
    - Ensure the queue connection is set to `database`:
      ```
      QUEUE_CONNECTION=database
      ```

10. **Set up mail configuration**
    - If using Mail locally, set:
      ```
      MAIL_MAILER=log
      ```
    - Otherwise, configure SMTP settings.

11. **Configure the web server**
    - Point the web server's document root to the `public` directory of the Laravel project.
    - For example, for Apache, use the following `.htaccess` in the `public` directory.
    - For Nginx, ensure the `root` points to the `public` directory and rewrite rules are set.

12. **Set file permissions**
    - Ensure that the necessary directories have the correct permissions:
      ```
      sudo chown -R www-data:www-data storage bootstrap/cache
      sudo chmod -R 775 storage bootstrap/cache
      ```

13. **Run tests (optional)**
    If you want to run tests using Pest:
     ```
     php artisan test
     ```

14. **Optimizations**
    - Cache the config, routes, and views for production:
      ```
      php artisan config:cache
      php artisan route:cache
      php artisan view:cache
      ```

15. **Restart the web server (if necessary)**
     ```
     sudo service apache2 restart   # Apache
     sudo service nginx restart     # Nginx
     ```

16. **Monitor Logs**
    Check logs for any errors:
     ```
     tail -f storage/logs/laravel.log
     ```

---

## Important Notes
    - Make sure Memcached is up and running.
    - Verify your SQLite database file has the proper read/write permissions.
    - Ensure that any email configurations (SMTP or mailers) are correctly set for production.

Enjoy your Laravel application deployment!





