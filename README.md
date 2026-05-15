# Uptotime

The Uptotime Platform.

## Tech Stack

- PHP 8.4+
- Laravel 13+
- PostgreSQL 17+ / MySQL 8+ / SQLite 3+
- Node.js 24+

## Installation

Run the following commands to setup this platform:

```shell
# Clone the repository
git clone git@github.com:devhammed/uptotime.git

# Change directory
cd uptotime

# Run the setup script
composer run setup
```

This will setup the project, create a `.env` file, generate an application key, and create a SQLite database file if it
doesn't exist.

This project has been tested with PostgreSQL, MySQL, and SQLite so you can update the `.env` file to use the one you
prefer and don't forget to run migrations after.

## Development

To start the development server, run:

```shell
composer run dev
```

This will start all the processes required for development, e.g., server, queue, scheduler, logs.

You can visit the API docs at [http://localhost:8000/docs/api](http://localhost:8000/docs/api) or the health check
at [http://localhost:8000/up](http://localhost:8000/up).

## Testing

Run the test suite:

```shell
composer test
```

## Credits

- [Hammed Oyedele](https://github.com/devhammed)

## License

This package is open-source software released under the MIT License.

See [LICENSE.md](LICENSE.md) for details.
