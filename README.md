# Installation

Junior is a Laravel artisan command that writes code for you

1. Install the package:
```sh
composer require mmartinjoo/junior
```

2. Publish the configuration file:
```sh
php artisan vendor:publish --provider="Mmartinjoo\Junior\JuniorServiceProvider"
```

3. Register at [Junior.dev](https://junior.dev)
4. Create an API key
5. Add the API key to your `.env` file:
```sh
JUNIOR_API_KEY=your-api-key
```
# Usage

Junior can generate you basic CRUD code, including:
- Models
- Factories
- Migrations
- Controllers
- Resources
- Requests
- Tests

You need to run the following command:
```sh
php artisan junior make:crud
```

If you only need a model with migrations and factories, you can run:
```sh
php artisan junior make:model
```

If you only need a factory for an existing model, you can run:
```sh
php artisan junior make:factory
```
