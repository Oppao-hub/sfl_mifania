# Mifania Sustainable Fashion Line (SFL) - Project Context

Mifania is a sustainable fashion e-commerce platform and management system built with Symfony. It focuses on eco-friendly fashion, ethical production, and features a rewards/loyalty system.

## Project Overview

- **Purpose:** E-commerce for sustainable fashion, including a dashboard for admin/staff to manage products, stock, categories, and customer rewards.
- **Core Domain:**
    - **E-commerce:** Products, Categories, SubCategories, Stock, Orders, Cart.
    - **Sustainability:** Story (brand narrative), QR Tags for product transparency.
    - **Loyalty/Gamification:** Rewards, Wallet, Transactions, Redemptions.
    - **User Management:** RBAC with Admin, Staff, and Customer roles.
- **Main Technologies:**
    - **Backend:** Symfony 7.3 (PHP 8.2+), Doctrine ORM, Symfony Security (JWT & Google OAuth).
    - **Frontend:** Twig, Tailwind CSS (v4), Alpine.js (for interactivity), Stimulus (Symfony UX).
    - **API:** API Platform (RESTful resources).
    - **Real-time:** Symfony Mercure (for notifications/broadcasts).
    - **Infrastructure:** Docker (MySQL, Mercure, PHPMyAdmin).

## Building and Running

### Prerequisites
- PHP 8.2+
- Composer
- Node.js & npm/yarn
- Docker & Docker Compose
- Symfony CLI (recommended)

### Installation
1. **Clone and Install PHP dependencies:**
   ```bash
   composer install
   ```
2. **Install JS dependencies:**
   ```bash
   npm install
   ```
3. **Start Infrastructure (MySQL, Mercure):**
   ```bash
   docker-compose up -d
   ```
4. **Environment Configuration:**
   Copy `.env` to `.env.local` and configure your `DATABASE_URL`, `MERCURE_URL`, etc.

### Database Setup
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load  # Optional: load sample data
```

### Frontend Assets
- **Development:** `npm run watch` or `npm run dev`
- **Production:** `npm run build`

### Running the Server
```bash
symfony serve -d
```

### Testing
```bash
php bin/phpunit
```

## Development Conventions

- **Architecture:** Standard Symfony directory structure (`src/`, `config/`, `templates/`, `assets/`).
- **Backend:**
    - Use **Entities** for database mapping and **Repositories** for custom queries.
    - **Services** should contain business logic.
    - **Controllers** are split into `Authentication`, `Dashboard` (Admin/Staff), and `Frontend` (Customer-facing).
    - **API Resources** are located in `src/ApiResource/`.
- **Frontend:**
    - **Tailwind CSS:** Preferred for styling. Custom theme colors are defined in `assets/styles/app.css` using `@theme`.
    - **Alpine.js:** Used for "sprinkles" of interactivity (modals, carousels, mobile menus).
    - **Stimulus:** Used for more complex frontend logic (e.g., DataTables initialization, specific UI controllers).
    - **Icons:** Powered by `symfony/ux-icons`.
- **Naming Conventions:**
    - PHP: PSR-12, CamelCase for classes/methods.
    - Twig: snake_case for variables, kebab-case for component names.
    - CSS: Tailwind utility-first approach.
- **Modifying Files:**
    - Always check if a change affects both the customer-facing frontend and the admin dashboard.
    - When adding Alpine.js components, ensure the base layouts (`base.html.twig` or `auth.html.twig`) include the necessary scripts.
