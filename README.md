# TCG Inventory Platform

TCG Inventory Platform is a web-based inventory and e-commerce system designed for local Trading Card Game (TCG) stores.

The project focuses on simplifying the management and sale of TCG products such as **single cards, sealed products, deck boxes, sleeves, playmats, and other accessories**, while providing a storefront where customers can browse available inventory and place orders for in-store pickup.

The platform is currently under active development and is being built incrementally, starting with the administration and inventory management features before implementing the public storefront.

## Project Goals

The main goal of the project is to build a practical inventory platform tailored to the needs of small and local TCG stores.

The system is designed around a simple inventory principle:

> If two items require independent stock management, they are treated as separate products.

For example, different card rarities, printings, conditions, or languages are stored as independent products instead of product variants. The same principle applies to accessories such as deck boxes of different colors.

This approach keeps inventory management straightforward while allowing the storefront to organize and present related products in a user-friendly way.

## Main Features

The planned V1 includes:

- User and customer management.
- Role-based administration.
- TCG management.
- Product type management.
- Product catalog management.
- Specialized metadata for single cards.
- Product image management.
- Inventory tracking and movement history.
- Shopping cart.
- Customer orders.
- In-store pickup workflow. (Version 1 focuses exclusively on **in-store pickup**.)
- External TCG API integrations.
- Card search and metadata import.

The first external integration will focus on **Yu-Gi-Oh!**, allowing administrators to search for cards and use external card information when registering inventory.

Shipping, customer addresses, product variants, and multi-store inventory are intentionally outside the scope of V1.

## Technology Stack

### Backend

- PHP
- Laravel
- REST API
- MySQL

### Frontend

- React
- JavaScript / TypeScript
- Tailwind CSS

### Development

- Git
- GitHub
- GitHub Projects
- Composer
- Node.js / npm

Additional tools and libraries may be introduced as development progresses.

## Project Structure

Project documentation is stored under the `docs/` directory.

```text
docs/
└── database/
    ├── schema.dbml
    ├── schema.png
    └── data-dictionary.md
```

The database documentation includes the editable DBML schema, a visual representation of the database, and the data dictionary describing the purpose and constraints of each entity.


## Installation

> Installation instructions are provisional and may change while the project is under development.

### Requirements

Make sure the following tools are installed:

- PHP
- Composer
- Node.js
- npm
- MySQL
- Git

### 1. Clone the repository

```bash
git clone <repository-url>
cd tcg-inventory-platform
```

### 2. Install backend dependencies

```bash
composer install
```

### 3. Install frontend dependencies

```bash
npm install
```

### 4. Configure the environment

Create your local environment file:

```bash
cp .env.example .env
```

Generate the Laravel application key:

```bash
php artisan key:generate
```

Configure the database connection in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tcg_inventory
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Initialize the database

```bash
php artisan migrate
```

When development seeders become available:

```bash
php artisan db:seed
```

### 6. Start the application

Start Laravel:

```bash
php artisan serve
```

Start the frontend development server:

```bash
npm run dev
```

The application should now be available in your local development environment.

## Development Status

🚧 **Currently under active development.**

Development is organized incrementally through GitHub Issues and milestones. The initial development stages focus on:

1. Project foundation.
2. Administration and authentication.
3. Product catalog.
4. TCG card integration.
5. Inventory management.
6. Storefront and checkout.
7. In-store pickup.

The scope may evolve as the platform is tested against real TCG store workflows.

## Documentation

Technical documentation will continue to be maintained under `docs/` as the project evolves.

Database changes should be reflected in both the database schema and its corresponding documentation.

## License

License information will be defined as the project approaches its first public release.
