# Library Management API

## Overview
The **Library Management API** is a RESTful API built using Laravel that provides functionality to manage books, borrowing, and user roles. This project implements role-based authentication using **JWT (JSON Web Token)** and includes event-driven features like sending emails when a book is borrowed.

## Features
- Role-based access control (Admin & User)
- JWT authentication for secure API access
- Book borrowing and returning functionality
- Borrow history tracking
- Role & permission management
- Automated email notifications on book borrowing

## Roles & Permissions
The system includes two primary roles:
- **Admin**: Can manage users, roles, books, and view borrowing history.
- **User**: Can register, log in, borrow, and return books.

## API Endpoints

### User Endpoints
| Method | Endpoint | Description |
|--------|-------------|-------------|
| POST   | `/api/user/register` | Register a new user |
| POST   | `/api/login` | Login and get JWT token |
| GET    | `/api/me` | Get logged-in user details |
| POST   | `/api/refresh` | Refresh JWT token |
| POST   | `/api/logout` | Logout user |
| POST   | `/api/borrow-book` | Borrow a book |
| POST   | `/api/return-book` | Return a borrowed book |
| GET    | `/api/get-borrowed-list` | View borrowing history |

### Admin Endpoints
| Method | Endpoint | Description |
|--------|-------------|-------------|
| GET    | `/api/roles` | Get all roles |
| GET    | `/api/roles/{id}` | Get details of a specific role |
| POST   | `/api/roles` | Create a new role |
| PUT    | `/api/roles/{id}` | Update an existing role |
| DELETE | `/api/roles/{id}` | Delete a role |
| GET    | `/api/books` | Get all books |
| GET    | `/api/books/{id}` | Get book details |
| POST   | `/api/books` | Add a new book |
| PUT    | `/api/books/{id}` | Update a book |
| DELETE | `/api/books/{id}` | Delete a book |
| GET    | `/api/get-bookwise-borrow-list/{bookId}` | Get borrow details for a specific book |

## Installation & Setup
Follow these steps to set up the project:

### 1. Clone the Repository
```sh
git clone <repository-url>
cd <project-folder>
```

### 2. Install Dependencies
```sh
composer install
```

### 3. Configure Environment Variables
Copy the example environment file and configure database settings:
```sh
cp .env.example .env
```
Edit `.env` to set up your database connection.

### 4. Run Migrations
```sh
php artisan migrate
```

### 5. Seed Database
```sh
php artisan db:seed
```

### 6. Start the Application
```sh
php artisan serve
```

## API Documentation
Once the server is running, you can test the API via Postman or the built-in API documentation:
[API Documentation](http://127.0.0.1:8000/api/documentation)

## License
This project is licensed under the MIT License.

## Contributing
Contributions are welcome! Feel free to submit a pull request or report issues.

## MYSQL task also added
You can check MYSQL test task in mysql.md file