# Mifania Sustainable Fashion Line (SFL)

Mifania is a high-end, sustainable fashion e-commerce platform built with Symfony 7, API Platform, and Tailwind CSS. It features a robust reward system, real-time notifications via Socket.io, and a comprehensive Admin/Staff dashboard.

---

## Getting Started

### Prerequisites
- PHP 8.2+ & Composer
- Node.js 18+ & npm
- Docker & Docker Compose
- MySQL (via Docker or local)

### Installation Guide

1. **Clone the Repository**
   ```bash
   git clone <your-repo-url>
   cd Mifania
   ```

2. **Install PHP Dependencies**
   ```bash
   composer install
   ```

3. **Install Frontend Dependencies**
   ```bash
   npm install
   ```

4. **Environment Setup**
   Copy `.env` to `.env.local` and configure your database and JWT keys:
   ```bash
   cp .env .env.local
   # Generate JWT Keys
   php bin/console lexik:jwt:generate-keypair
   ```

5. **Start Infrastructure (MySQL)**
   ```bash
   docker-compose up -d
   ```

6. **Database Setup**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   php bin/console doctrine:fixtures:load  # Optional: for testing data
   ```

7. **Start Frontend Assets**
   ```bash
   npm run dev
   ```

8. **Run the Symfony Server**
   ```bash
   symfony serve -d
   ```

---

## 📡 Real-Time Synchronization (Socket.io)

The project uses a dedicated Node.js server to handle real-time synchronization between the Web Dashboard and the Mobile API.

### How to Run:
1. Navigate to the socket server directory:
   ```bash
   cd socket-server
   ```
2. Install dependencies:
   ```bash
   npm install
   ```
3. Start the server:
   ```bash
   node server.js
   ```
*The Socket server runs on port 3001 (WebSockets) and port 3000 (Internal API for Symfony).*

---

## 🛠 Customer API Documentation

The API follows REST standards and returns standardized JSON-LD/Hydra responses.

### Base URL: `http://localhost:8000/api`

| Endpoint | Method | Description | Auth Required |
| :--- | :--- | :--- | :--- |
| `/login` | `POST` | Get JWT Token (email, password) | No |
| `/products` | `GET` | Fetch all sustainable products | No |
| `/categories` | `GET` | List all product categories | No |
| `/orders` | `POST` | Place a new order | Yes (JWT) |
| `/orders` | `GET` | List authenticated user's orders | Yes (JWT) |
| `/customers` | `GET` | Get customer profile & rewards | Yes (JWT) |
| `/wallets` | `GET` | Check wallet balance & credits | Yes (JWT) |

### Sample Order Request (`POST /api/orders`)
```json
{
  "totalAmount": "1500.00",
  "status": "pending",
  "orderItems": [
    {
      "product": "/api/products/1",
      "quantity": 2,
      "price": "750.00"
    }
  ]
}
```

---

## 🔐 Security & Roles

- **Admin:** Full system control, stock management, and user auditing.
- **Staff:** Manage orders, products, and customer redemptions.
- **Customer:** Browse products, earn rewards, and track orders via Web or Mobile.

API routes are protected using **LexikJWTAuthenticationBundle**. Use the `Authorization: Bearer <token>` header for protected requests.

---

## 🧪 Testing

Run the test suite to ensure stability:
```bash
php bin/phpunit
```

---

## 🌿 Sustainability Commitment
Mifania tracks the carbon footprint and ethical sourcing of every garment. Use the **QR Tag** feature to view the journey of your clothing.
