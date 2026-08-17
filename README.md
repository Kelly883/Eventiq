# Eventiq

Eventiq is a full-stack event management and ticketing platform. It provides event discovery, ticketing, QR-based check-in, analytics, pricing windows, and organizer dashboards.

## Tech Stack

- **Backend**: Laravel 11, PHP 8.2+, MySQL/SQLite, Laravel Sanctum, Queues, Events, Observers
- **Frontend**: React 18, Vite, Tailwind CSS, React Router
- **Infrastructure**: Vercel (frontend), containerized backend

## Features

- Event browsing and discovery
- Ticket tiers with early bird pricing
- Pricing windows with dynamic pricing
- QR code ticketing and venue scanning
- Sales analytics and reporting
- Organizer and user dashboards
- Admin controls and email notifications
- Push notifications (FCM)

## Project Structure

```
.
├── backend/          # Laravel API and business logic
│   ├── app/
│   ├── database/
│   └── routes/
├── frontend/         # React SPA
│   ├── src/
│   │   ├── features/ # Feature modules
│   │   ├── lib/      # Shared utilities
│   │   └── App.jsx   # Router and layout
│   ├── index.html
│   └── vite.config.js
└── vercel.json       # SPA rewrite rules
```

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- npm or yarn
- MySQL or SQLite

### Backend Setup

```bash
cd backend
cp .env.example .env
php artisan key:generate
php artisan migrate --force
composer install
php artisan serve
```

### Frontend Setup

```bash
cd frontend
npm install
npm run dev
```

### Running Tests

```bash
cd backend
php artisan test
```

## Environment Variables

### Backend

| Variable | Description |
|---|---|
| `APP_KEY` | Laravel application key |
| `DB_CONNECTION` | Database driver (mysql, sqlite) |
| `DB_DATABASE` | Database name or path |
| `SANCTUM_STATEFUL_DOMAINS` | Frontend domain for session auth |

### Frontend

| Variable | Description |
|---|---|
| `VITE_API_BASE_URL` | Backend API URL |

## Deployment

- **Frontend**: Deployed on Vercel with root directory set to `frontend` and output `dist`.
- **Backend**: Deploy the Laravel app to your preferred PHP hosting.

## Contributing

1. Create a feature branch from `main`
2. Make changes and run tests
3. Open a pull request

## License

Proprietary
