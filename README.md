# GSU Alumni Payment Portal

GSU Alumni Payment Portal is a Laravel + React application for Gombe State University that handles graduating student payment requests, Paystack checkout, receipt generation, and admin payment monitoring.

The current product scope is intentionally focused on payment operations only:

- student biodata capture and payment request creation
- payment type management for alumni/admin officers
- Paystack payment initialization and server-side verification
- receipt generation for successful payments only
- admin payment records and dashboard monitoring
- academic master data management for program types, faculties, and departments

It is not currently intended to be a full alumni social platform.

## Current Roles

The system uses these user roles:

- `student`
- `alumni_admin`
- `super_admin`

Admin modules are available only to `alumni_admin` and `super_admin` users.

## Main Features

### Public student flow

- Open the public portal at `/`
- Fill in biodata and academic details
- Select program type
- Select faculty and department from seeded master data
- Select a payment type that matches the chosen program type
- Create a payment request with status `pending`
- Continue to Paystack from the payment request page
- Verify payment on the backend before marking it successful
- Generate and re-open a receipt only after successful verification

### Admin modules

- Dashboard summary for payment activity
- Payment type CRUD
- Program type CRUD
- Faculty CRUD
- Department CRUD
- Payment record list with search, filters, sort, pagination, and print views
- Payment record detail view with receipt access

## Payment Statuses

Payment requests currently use these statuses:

- `pending`
- `successful`
- `failed`
- `abandoned`

Receipts are issued only for `successful` payment requests.

## Tech Stack

- PHP 8.2+
- Laravel 12
- Inertia.js
- React 19
- TypeScript
- Tailwind CSS v4
- shadcn/ui and Radix UI
- Vite
- Paystack API

## Project Flow

1. Admin creates active payment types and assigns them to one or more program types.
2. Student opens the portal and submits biodata for a payment request.
3. The system stores the request and locks the amount from the selected payment type.
4. Student proceeds to Paystack.
5. The backend verifies the transaction through Paystack before updating status.
6. If successful, the system generates one official receipt for that payment request.
7. Admin users can monitor the payment record and re-open the receipt from the dashboard.

## Seeded Academic Data

The application seeds core academic master data for the public student form and admin CRUD modules:

- program types
- faculties
- departments under their faculties

Current seeded faculties include:

- Faculty of Basic Medical Sciences
- Faculty of Basic Clinical Sciences
- Faculty of Clinical Sciences
- Faculty of Arts and Social Sciences
- Faculty of Environmental Sciences
- Faculty of Sciences
- Faculty of Education
- Faculty of Law
- Faculty of Pharmaceutical Sciences

Current seeded program types include:

- Undergraduate
- Part-Time Undergraduate
- Diploma
- Certificate
- Pre-Degree
- Postgraduate Diploma
- Professional Programme
- Masters
- PhD

## Local Setup

### 1. Install dependencies

```bash
composer install
npm install
```

### 2. Create environment file

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Configure the database

The starter `.env.example` uses SQLite by default, but you can use MySQL if preferred.

Example SQLite setup:

```bash
touch database/database.sqlite
```

Then set:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite
```

Example MySQL setup:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gsu_alumni_payment_portal
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Configure Paystack

Set the following in `.env`:

```env
PAYSTACK_PUBLIC_KEY=pk_test_xxx
PAYSTACK_SECRET_KEY=sk_test_xxx
PAYSTACK_WEBHOOK_SECRET=sk_test_xxx
PAYSTACK_BASE_URL=https://api.paystack.co
PAYSTACK_CURRENCY=NGN
PAYSTACK_CALLBACK_URL=
PAYSTACK_TIMEOUT=15
```

Notes:

- `PAYSTACK_SECRET_KEY` must be your secret key, not your public key.
- If `PAYSTACK_WEBHOOK_SECRET` is empty, the app falls back to `PAYSTACK_SECRET_KEY`.
- The system never marks a payment successful from the frontend alone. Verification is always done server-side.

### 5. Run migrations and seeders

```bash
php artisan migrate --seed
```

Or reset everything:

```bash
php artisan migrate:fresh --seed
```

### 6. Start the application

You can run the full local stack with:

```bash
composer run dev
```

Or run services manually:

```bash
php artisan serve
npm run dev
```

Public portal:

- `http://localhost:8000/`

## Admin Access

The public portal does not show the login link, but admin authentication still exists through the standard Laravel auth routes.

Direct login route:

- `/login`

Important:

- No admin account is seeded by default.
- `DatabaseSeeder` only seeds academic master data and a local test user.
- The local test user is created only in the `local` environment with email `test@example.com`.
- The default factory password is `password`.
- That test user is created with the `student` role, not an admin role.

### Create a local admin user

You can create or promote an admin user with Tinker:

```bash
php artisan tinker --execute="\App\Models\User::updateOrCreate(['email' => 'admin@example.com'], ['name' => 'GSU Admin', 'password' => bcrypt('password'), 'email_verified_at' => now(), 'role' => \App\Enums\UserRole::SuperAdmin]);"
```

After that, log in with:

- email: `admin@example.com`
- password: `password`

## Important Routes

### Public

- `/` - student payment form
- `/payments` - create payment request
- `/payment-requests/{paymentRequest}` - payment request review page
- `/receipts/lookup` - receipt reprint lookup

### Admin

- `/dashboard` - signed-in dashboard
- `/admin/payment-types`
- `/admin/program-types`
- `/admin/faculties`
- `/admin/departments`
- `/admin/payment-records`

## Paystack Notes

Current Paystack support includes:

- backend transaction initialization
- popup-based student checkout flow
- callback handling
- server-side verification
- webhook handling
- idempotent payment update logic

Recommended Paystack webhook route:

- `/payments/paystack/webhook`

## Receipt Rules

- A receipt is created only for a verified successful payment.
- One successful payment request can have only one receipt.
- Re-opening a receipt reuses the existing receipt record.
- Receipt access is protected through signed URLs and lookup flow.

## Development Commands

```bash
php artisan route:list --except-vendor
php artisan migrate:fresh --seed
php artisan test
npm run types
npm run build
```

## Testing Note

If you run tests with SQLite, make sure the `pdo_sqlite` extension is enabled in PHP. Without it, migrations and tests that rely on SQLite will fail.

## High-Level Structure

```text
app/
  Enums/
  Http/Controllers/
  Http/Middleware/
  Http/Requests/
  Models/
  Services/

database/
  factories/
  migrations/
  seeders/

resources/
  css/
  js/
    components/
    layouts/
    pages/

routes/
  web.php
  admin.php
  auth.php
  settings.php
  student-payments.php
  student-receipts.php
```

## Current Scope Notes

Included now:

- payment operations
- academic master data management
- receipts
- admin monitoring

Not yet included:

- alumni activities or social features
- advanced analytics
- broad export/reporting suite
- full reconciliation tools

## License

This project is based on the Laravel ecosystem and remains licensed under the MIT license unless your organization applies a different project policy.
