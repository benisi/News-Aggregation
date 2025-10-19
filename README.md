# News Aggregator API

This is the backend for a comprehensive news aggregator platform, built with Laravel. It is designed to fetch articles from various external sources, store them in a local database, and serve them to a frontend application via a clean, RESTful API.

The system is built with a focus on SOLID principles, scalability, and maintainability, featuring a robust queue-based system for data aggregation and a flexible API for content delivery.

---

## Features

- **Multi-Source Data Aggregation**: Fetches articles from multiple news APIs (NewsAPI, The Guardian, New York Times).
- **Queued Background Jobs**: Article fetching is handled by background jobs for better performance and reliability.
- **Scheduled Updates**: A command is scheduled to run periodically to keep the news content fresh.
- **RESTful API**: A full suite of API endpoints to serve articles, sources, categories, and authors.
- **Advanced Filtering**: The articles API supports filtering by search keyword, date range, category, source, and author.
- **User Personalization**: Endpoints for users to register, log in, and save their preferred news sources, categories, and authors.
- **Interactive API Documentation**: Comes with a complete, interactive Swagger/OpenAPI documentation.

---

## Installation and Setup

Follow these steps to get the project up and running on your local machine.

### Prerequisites

- PHP >= 8.2
- Composer
- A database (MySQL, PostgreSQL, etc.)
- (Optional) Docker for running with Laravel Sail

### 1. Clone the Repository

```bash
git clone https://github.com/benisi/News-Aggregation.git
cd News-Aggregation
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Environment Configuration
Copy the example environment file and generate your application key.

```bash
cp .env.example .env
php artisan key:generate
```

#### Database Connection
open the .env file and configure the following variables:

```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=news_aggregator
DB_USERNAME=root
DB_PASSWORD=
```

#### API Keys for News Sources:
You will need to get API keys from the respective services and add them to your .env file.

```bash
NEWSAPI_KEY="your_newsapi_key"
GUARDIAN_API_KEY="your_guardianapi_key"
NYTIMES_API_KEY="your_nytimes_api_key"
```

### 4. Database Setup
First, run the database migrations to create all the necessary tables.

```bash
php artisan migrate
```

Next, seed the database with the initial list of whitelisted news sources and their categories. This step is mandatory for the aggregator to know which sources to pull from.

```bash
php artisan db:seed --class=SourceSeeder
```

## Running the Application
You can run the application manually or using Laravel Sail.

### Manual Setup
1. Start the Laravel Development Server:

```bash
php artisan serve
```
2. Start the Queue Worker: The data aggregation happens in the background, so you must have a queue worker running. Open a new terminal window and run:

```bash
php artisan queue:listen
```

### Using Laravel Sail (Docker)
If you have Docker installed, you can use Laravel Sail to run the entire application stack, including the server and queue worker.

1. Start the Sail Containers:

```bash
./vendor/bin/sail build or ./vendor/bin/sail build --no-cache

./vendor/bin/sail up -d

./vendor/bin/sail artisan key:generate
```
This command starts the web server, database, and all other configured services in the background. Sail automatically runs a queue worker for you.

sail may default to this url [http://news-aggregation-laravel.test:8080\api](http://news-aggregation-laravel.test\api) depending on your project folder and docs will be in [http://news-aggregation-laravel.test:8080/swagger](http://news-aggregation-laravel.test:8080/swagger)

Note: When you run sail, all services is setup for, all you need to do is to add the `api keys` of your data source.
the queues, the schedule and an initial data poll is done everytime you run `./vendor/bin/sail up -d`

## Data Aggregation
The core of this project is the data aggregator, which is handled by an Artisan command.

### Running the Command Manually
You can trigger the aggregation process at any time by running the following command. This will dispatch jobs to the queue for all configured data sources.

```bash
php artisan articles:aggregate
```

You can also specify a single source to aggregate:

```bash
php artisan articles:aggregate newsapi
```
Available sources are `newsapi`, `guardian`, and `nytimes`.

## Scheduling the Command
To ensure the news data stays up-to-date, the `articles:aggregate` command is scheduled to run automatically. This is defined in routes/console:

```php
<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('articles:aggregate')->hourly();
```

For this to work on a production server, you would need to add a single cron job to your server's crontab that runs every minute. Laravel will then handle running the scheduled tasks at their defined interval.

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

On local use the command below

```bash
php artisan schedule:work
```
for sail use

```bash
vendor/bin/sail artisan schedule:work
```

## API Documentation
This project includes interactive API documentation powered by OpenAPI (Swagger). It allows you to view and test every endpoint directly from your browser.

- URL: [http://localhost:8000/swagger](http://localhost:8000/swagger)

The server URL used in the documentation is automatically linked to the APP_URL you set in your .env file, making it easy to use across different environments.
