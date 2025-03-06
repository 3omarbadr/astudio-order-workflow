Let me provide you with basic setup instructions and brief API documentation for the order management system you shared.

### Basic Setup Instructions

1. **Prerequisites**
- PHP 8.1+
- Composer
- Laravel 10.x+
- MySQL/PostgreSQL database
- Laravel authentication setup (for Auth::id())

2. **Installation Steps**
```bash
# Clone the repository
git clone https://github.com/3omarbadr/astudio-order-workflow
cd astudio-order-workflow

# Install dependencies
composer install

# Copy environment file and configure
cp .env.example .env
php artisan key:generate

# Configure database in .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Run migrations
php artisan migrate

# Optional: Seed database with test data
php artisan db:seed
```

3. **Project Structure Assumptions**
- Models: `Order`, `OrderHistory`, `User`
- Enums: `OrderStatus`
- Resources: `OrderResource`
- Requests: `CreateOrderRequest`
- Services: `OrderService`, `ApprovalService`

4. **Running the Application**
```bash
php artisan serve
# Application will be available at http://localhost:8000
```

5. **Required Configuration**
- Set up Laravel authentication (Sanctum/Passport)
- Configure middleware for API routes
- Ensure proper relationships in models (Order hasMany Items, History)

### Brief API Documentation

#### Base URL
```
http://localhost:8000/api
```

#### Authentication
- Requires authentication token (Bearer Token)
- All endpoints assume authenticated user context

#### Endpoints

1. **Orders**
```
GET /orders
```
- Description: List all orders with pagination
- Query Params:
    - `page` (optional): Page number
- Response: 200 OK
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "order_number": "ORD-123",
        "status": "pending",
        "items": []
      }
    ],
    "current_page": 1,
    "total": 15
  }
}
```

```
POST /orders
```
- Description: Create new order
- Body:
```json
{
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    }
  ]
}
```
- Response: 201 Created
```json
{
  "success": true,
  "message": "Order created successfully",
  "data": {
    "order_number": "ORD-123",
    "status": "draft",
    "items": []
  }
}
```

```
GET /orders/{orderNumber}
```
- Description: Get specific order details
- Response: 200 OK
```json
{
  "success": true,
  "data": {
    "order_number": "ORD-123",
    "status": "pending",
    "items": [],
    "history": []
  }
}
```

```
POST /orders/{orderNumber}/submit
```
- Description: Submit order for approval
- Response: 200 OK
```json
{
  "success": true,
  "message": "Order submitted for approval",
  "data": {
    "order_number": "ORD-123",
    "status": "pending"
  }
}
```

```
GET /orders/{orderNumber}/history
```
- Description: Get order history
- Response: 200 OK
```json
{
  "success": true,
  "data": [
    {
      "status": "draft",
      "new_status": "pending",
      "note": "Order submitted",
      "changed_by": 1,
      "created_at": "2025-03-06T12:00:00Z"
    }
  ]
}
```

2. **Approval**
```
POST /orders/{orderNumber}/approve
```
- Description: Approve a pending order
- Response: 200 OK
```json
{
  "success": true,
  "message": "Order approved successfully",
  "data": {
    "order_number": "ORD-123",
    "status": "approved",
    "approved_by": 1
  }
}
```

```
POST /orders/{orderNumber}/reject
```
- Description: Reject a pending order
- Body:
```json
{
  "reason": "Invalid items"
}
```
- Response: 200 OK
```json
{
  "success": true,
  "message": "Order rejected successfully",
  "data": {
    "order_number": "ORD-123",
    "status": "rejected"
  }
}
```

#### Error Responses
- 404 Not Found
```json
{
  "success": false,
  "message": "Order not found"
}
```
- 500 Internal Server Error
```json
{
  "success": false,
  "message": "Failed to [action] order",
  "error": "Detailed error message"
}
```

### Testing

# Configure database in .env
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

# Run migrations
```
php artisan migrate
php artisan migrate --env=testing
```

# Optional: Seed database with test data
```
php artisan db:seed
php artisan db:seed --env=testing
```

4. **Running Tests**
```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test --filter=ApprovalServiceTest

```

---

#### Testing
- Unit tests are available in `tests/Unit/`
- Run tests using `php artisan test`
- Tests cover:
    - ApprovalService core functionality
    - Order status transitions
    - History recording
    - Validation rules
- Test database is configured separately from production

#### Notes
- All responses are in JSON format
- Order numbers are unique strings
- Status values come from OrderStatus enum
- History entries are created automatically
