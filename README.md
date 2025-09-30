## GUVI Task - Auth + Profile (PHP + Bootstrap)

Minimal login/register/profile app with a Bootstrap UI, Mongo snapshot, and Redis sessions.

### Features
- Register, login, logout
- View profile and update age / DOB / contact
- Clean Bootstrap UI with toasts for feedback
- Client + server validation (username, email, password, DOB, contact)
- Sessions in Redis; MySQL primary storage; optional Mongo backup

### Prerequisites
- PHP 8+
- MySQL (or MariaDB)
- Redis
- Composer (already vendor/ committed)

### Quick start
1) Create a MySQL database and table (example)
```sql
CREATE DATABASE guvi_intern;
USE guvi_intern;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(30) NOT NULL UNIQUE,
  email VARCHAR(254) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  age INT NULL,
  dob DATE NULL,
  contact VARCHAR(32) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

2) Configure environment (optional; sensible defaults exist)
- DB_HOST, DB_NAME, DB_USER, DB_PASS
- REDIS_HOST, REDIS_PORT, REDIS_TTL
- MONGO_URI, MONGO_DB

3) Run locally (from project root)
```bash
php -S 127.0.0.1:8000
```
Open `http://127.0.0.1:8000/index.html`.

### Project structure (high level)
- `index.html`, `login.html`, `register.html`, `profile.html`
- `js/script.js` – UI logic, validation, toasts
- `php/` – API endpoints and helpers

### Validation (high level)
- Username: 3–30 chars, letters/numbers/underscore/dot, must include at least one letter
- Email: valid format, ≤ 254 chars
- Password: 8–128 chars, confirm match on register
- Age: 1–120 (optional)
- DOB: ISO date, must be at least 10 years ago
- Contact: 7–20 chars, digits and `+ ( ) -` and spaces

Client-side validation prevents obvious issues; server re-validates everything.

### Endpoints
- `php/register.php`
  - Body: `{ username, email, password, age?, dob?, contact? }`
  - Returns: `{ success, message }`

- `php/login.php`
  - Body: `{ identifier, password }` (identifier = username or email)
  - Returns: `{ success, token, user }`

- `php/get_profile.php`
  - Headers: `Authorization: Bearer <token>` or Body `{ token }`
  - Returns: `{ success, user }`

- `php/update_profile.php`
  - Body: `{ token, age?, dob?, contact? }`
  - Returns: `{ success, message }`

- `php/logout.php`
  - Body: `{ token }`
  - Returns: `{ success, message }`

### Notes
- Bootstrap toasts are used for warnings/errors/success feedback.
- If Mongo is unavailable, MySQL remains the source of truth.
- Keep your `vendor/` folder present or run `composer install` if needed.


