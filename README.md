# Barbershop ni Jiji Management System

A Laravel 12 mini-capstone application for managing customers, barbers, services, conflict-safe appointments, partial payments, receipts, reports, user accounts, and business settings.

## Requirements

- PHP 8.2 or newer with PDO MySQL
- Composer
- Node.js and NPM
- MySQL (Laragon is recommended on Windows)

## Laragon / MySQL setup

1. Start Apache/Nginx and MySQL in Laragon.
2. Create a database named `barbershop_management` in HeidiSQL or phpMyAdmin.
3. Copy `.env.example` to `.env` and confirm these values:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=barbershop_management
DB_USERNAME=root
DB_PASSWORD=
```

4. Install and initialize the application:

```bash
composer install
npm install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run build
```

5. Start it with `php artisan serve`, then open `http://127.0.0.1:8000`.

For active frontend development, run `npm run dev` in a second terminal.

## Development credentials

| Role | Email | Password |
|---|---|---|
| Administrator | `admin@barbershop.test` | `password` |
| Barber | `miguel@barbershop.test` | `password` |
| Barber | `carlo@barbershop.test` | `password` |
| Barber | `paolo@barbershop.test` | `password` |
| Barber | `rafael@barbershop.test` | `password` |
| Barber | `luis@barbershop.test` | `password` |

These accounts are for development only. Change every seeded password before deployment.

## Main behavior

- Administrators manage all operational and financial modules.
- Barbers see only their assigned appointments and can move them to **In Progress** and then **Completed**.
- Appointment end times use the service-duration snapshot. Transactions lock the barber row and re-check overlaps before saving.
- Inactive barbers, unavailable services, closed days, and times outside business hours cannot be booked.
- Each appointment has one payment ledger and any number of immutable partial-payment transactions. Overpayments are rejected.
- Customers and services with history are deactivated instead of being permanently deleted. Financial transactions are never deleted through the UI.
- Revenue reports sum actual payment transactions, not appointment prices or unpaid balances.

## Verification

The automated suite uses a separate in-memory SQLite database, so it never touches development data:

```bash
php artisan test
```

The current suite covers authentication, role authorization, CRUD modules, conflict prevention, rescheduling, status transitions, partial payments, overpayment prevention, revenue reporting, and rendering every administrator screen.

## Local demonstration database

This workspace currently uses SQLite in `.env` so the application can run immediately without relying on a running MySQL service. The production-ready MySQL values are already provided in `.env.example`; switch `.env` to those values and run `php artisan migrate --seed` for the Laragon demonstration database.
