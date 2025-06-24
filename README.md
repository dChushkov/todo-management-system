# Laravel Todo Management System

A modern Todo Management System built with Laravel 12 and Blade. All actions (login, CRUD, filtering, marking complete) are performed via API and fetch() requests from Blade. No direct database access from Blade views.

## Setup
1. Clone the repository:
   ```bash
   git clone https://github.com/YOUR_USERNAME/todo-management-system.git
   cd todo-management-system
   ```
2. Install dependencies:
   ```bash
   composer install
   npm install && npm run build # if using frontend assets
   ```
3. Copy and configure .env:
   ```bash
   cp .env.example .env
   php artisan key:generate
   # Set your DB credentials
   ```
4. Run migrations and seeders:
   ```bash
   php artisan migrate --seed
   ```
5. Start the server:
   ```bash
   php artisan serve
   ```

