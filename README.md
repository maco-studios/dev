# Magento

This is a development module for the magento project that provides database seeding and factory capabilities for testing and development environments.

## Features

- Database Seeding System
- Factory Pattern for Test Data Generation
- Support for Categories, Products, Customers, and Orders
- Integration with FakerPHP for realistic test data

## Requirements

- PHP (compatible with Magento)
- Composer
- FakerPHP/Faker
- Maco Studios Console Package

## Installation

1. Add this repository to your project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/maco-studios/console.git"
        }
    ]
}
```

2. Install via Composer:

```bash
composer require maco-studios/dev --dev
```

## Usage

### Database Seeding

The module includes several seeders for populating your database with test data:

- ProductSeeder
- CustomerSeeder
- CategorySeeder
- OrderSeeder

To run all seeders:

```php
$seeder = new Dev_Database_Seeder_DatabaseSeeder();
$seeder->run();
```

### Factories

The module provides factories for generating test data:

- CategoryFactory
- CustomerFactory
- ProductFactory
- OrderFactory

Each factory extends the `FactoryAbstract` class and uses FakerPHP to generate realistic test data.

## Development

### Code Style

This project uses PHP-CS-Fixer for maintaining code style. To fix code style:

```bash
composer cs:fix
```

### Composer Scripts

- `composer cs:fix`: Run PHP-CS-Fixer
- `composer normalize`: Normalize composer.json (runs automatically post-install and post-update)
