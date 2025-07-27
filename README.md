# 🛍️ E-Commerce API

A modern e-commerce API system built with Laravel 12 + Vue.js 3, featuring multi-country pricing, cart management, order system, and admin panel.

## ✨ Features

### 🔐 User Authentication
- User registration/login (Laravel Sanctum)
- User profile management
- Multi-country support (Malaysia/Singapore)

### 🛒 Shopping Features
- Product browsing and search
- Category filtering
- Multi-country price display
- Cart management (add/update/delete)
- Real-time cart updates

### 📦 Order System
- Complete checkout process
- Shipping address collection
- Order history viewing
- Order status management
- Order soft deletion (hidden from users, recoverable by admin)

### 👨‍💼 Admin Panel (Filament)
- User management (search, permission control)
- Order management (including deleted orders)
- Product and category management
- Shipping address viewing
- Access control (admin-only access)

### 📖 API Documentation
- **Scribe-generated documentation** with interactive interface
- Complete endpoint reference with request/response examples
- Built-in API testing interface
- Auto-generated from code annotations

## 🛠️ Tech Stack

**Backend:**
- **Laravel 12** - PHP Framework
- **Laravel Sanctum** - API Authentication
- **Filament 3** - Admin Panel
- **Scribe** - API Documentation
- **MySQL** - Database

**Frontend:**
- **Vue.js 3** - Frontend Framework
- **Vue Router** - Routing
- **Axios** - HTTP Client
- **Bootstrap 5** - UI Framework
- **FontAwesome** - Icons

**Development Tools:**
- **Vite** - Frontend Build Tool
- **Composer** - PHP Dependency Manager
- **NPM** - Frontend Dependency Manager

## 📋 System Requirements

- **PHP** >= 8.2
- **Node.js** >= 18
- **MySQL** >= 8.0
- **Composer** >= 2.0

## 🚀 Quick Start

### 1. Clone the Project
```bash
git clone https://github.com/april-EXO/ecommerce-api.git
cd ecommerce-api
```

### 2. Install Backend Dependencies
```bash
composer install
```

### 3. Install Frontend Dependencies
```bash
npm install
```

### 4. Environment Setup
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 5. Database Configuration
Edit `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce_api
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 6. Database Migration and Seeding
```bash
# Run migrations
php artisan migrate

# Run seeders (creates sample products, categories, and test users)
php artisan db:seed
```

**🔑 Test Accounts** (automatically created by seeder):
- **Regular User**: `user@example.com` | Password: `password123`
- **Admin**: `admin@example.com` | Password: `admin123`

### 7. Generate API Documentation
```bash
# Generate Scribe API documentation
php artisan scribe:generate
```

### 8. (Optional) Create Custom Admin User
```bash
# If you need additional admin users
php artisan make:filament-user

# Set user as admin (replace with your email)
php artisan tinker
> App\Models\User::where('email', 'your-email@example.com')->update(['is_admin' => true]);
> exit
```

### 9. Build Frontend Assets
```bash
# Development
npm run dev

# Production
npm run build
```

### 10. Start the Server
```bash
php artisan serve
```

## 🌐 Access URLs

- **Main Site**: http://localhost:8000
- **Admin Panel**: http://localhost:8000/admin
- **API Documentation**: http://localhost:8000/docs

### 🔐 **Quick Login**
- **User**: `user@example.com` / `password123`
- **Admin**: `admin@example.com` / `admin123`

## 📖 API Documentation

This project uses **Scribe** to generate comprehensive API documentation with the following features:

### 🚀 **Interactive Documentation**
- **Live API Testing**: Test endpoints directly from the documentation
- **Request/Response Examples**: See actual examples for each endpoint
- **Authentication Testing**: Test protected routes with real tokens
- **Parameter Validation**: See required/optional parameters with types

### 📚 **Documentation Features**
- **Auto-generated**: Documentation is generated from code annotations
- **Always Up-to-date**: Regenerate docs when API changes
- **Multiple Formats**: Available as web interface and exportable formats
- **Grouped Endpoints**: Organized by functionality (Auth, Products, Cart, Orders)

### 🔧 **Generating Documentation**
```bash
# Generate/update API documentation
php artisan scribe:generate

# Generate with specific config
php artisan scribe:generate --force
```

### 📝 **Accessing Documentation**
Visit http://localhost:8000/docs to access the interactive API documentation where you can:
- Browse all available endpoints
- See detailed request/response schemas
- Test API calls with authentication
- Download Postman collections

## 🚀 **Postman API Testing**

Test the API directly with our ready-to-use Postman collection.

### 📱 **Access Postman Collection**

🌐 **Join Team Workspace**: [Accept Invite & Open in Postman](https://app.getpostman.com/join-team?invite_code=7e7e2ac2e6b966463b875248441c66cc5ac441526b2cafbf5cdb51b035462372&target_code=0ee0a2dc75592fcd48ce94ca5669838e)

**Features**:
- ✅ All endpoints pre-configured
- ✅ Sample request data included
- ✅ Authentication examples
- ✅ Ready to test with your local server

**Quick Start**:
1. Start your server: `php artisan serve`
2. Open the Postman workspace
3. Test the endpoints directly

*Powered by [Scribe](https://scribe.knuckles.wtf/) - Auto-generated API documentation*

## 📱 Usage Guide

### 🔑 **Quick Test Accounts**

No registration needed - use these test accounts directly:

| Account Type | Email | Password | Purpose |
|-------------|-------|----------|---------|
| Regular User | `user@example.com` | `password123` | Test shopping cart, orders |
| Admin | `admin@example.com` | `admin123` | Access admin panel |

### 🛍️ User Features

1. **Registration/Login**
   - Visit homepage and click "Login/Register"
   - Select country during registration (MY/SG)
   - Or use test accounts above for quick access

2. **Browse Products**
   - Homepage displays all products
   - Filter by category
   - Price range and search support

3. **Shopping Cart**
   - Click "Add to Cart" on products
   - View cart icon (top right)
   - Manage items in cart page

4. **Place Orders**
   - Fill shipping address in cart page
   - Click "Proceed to Checkout"
   - View order history

5. **Order Management**
   - View all orders in "Orders" page
   - Cancel pending orders
   - Delete cancelled orders

### 👨‍💼 Admin Panel

1. **Login to Admin Panel**
   - Visit `/admin`
   - Login with admin account (`admin@example.com` / `admin123`)

2. **User Management**
   - View all users
   - Search by name/email
   - Set admin permissions

3. **Order Management**
   - View all orders (including soft deleted)
   - Filter by status/country
   - View complete shipping addresses
   - Restore deleted orders

## 🔧 API Endpoints

### Authentication
```
POST   /api/register          # User registration
POST   /api/login             # User login
GET    /api/user              # Get user info
POST   /api/logout            # Logout
PUT    /api/user/country      # Update user country
```

### Products
```
GET    /api/products          # Get product list
GET    /api/products/{id}     # Get product details
GET    /api/categories        # Get categories
```

### Cart (Authentication Required)
```
GET    /api/cart              # Get cart
POST   /api/cart/items        # Add item to cart
PUT    /api/cart/items/{id}   # Update cart item
DELETE /api/cart/items/{id}   # Remove cart item
GET    /api/cart/count        # Get cart count
```

### Orders (Authentication Required)
```
GET    /api/orders            # Get order list
POST   /api/orders            # Create order
GET    /api/orders/{id}       # Get order details
PUT    /api/orders/{id}/cancel # Cancel order
DELETE /api/orders/{id}       # Soft delete order
```

> 💡 **Tip**: Visit http://localhost:8000/docs for complete interactive API documentation with request/response examples and testing interface.

## 🗄️ Database Structure

### Main Tables

- **users** - User information (includes is_admin field)
- **products** - Product information
- **product_prices** - Multi-country pricing
- **categories** - Product categories
- **countries** - Country information
- **carts** - Shopping carts
- **cart_items** - Cart items
- **orders** - Orders (includes shipping_address JSON)
- **order_items** - Order items

## 🔧 Development Commands

### Laravel Commands
```bash
# Clear cache
php artisan optimize:clear

# View routes
php artisan route:list

# Reset database
php artisan migrate:fresh --seed

# Generate API docs
php artisan scribe:generate

# Create new Filament resource
php artisan make:filament-resource ModelName
```

### Frontend Commands
```bash
# Development mode (hot reload)
npm run dev

# Watch file changes
npm run watch

# Production build
npm run build
```

## 🚨 Troubleshooting

### Common Issues

1. **500 Error**
   ```bash
   # Check logs
   tail -f storage/logs/laravel.log
   
   # Clear cache
   php artisan optimize:clear
   ```

2. **Frontend Assets 404**
   ```bash
   # Rebuild frontend
   npm run build
   ```

3. **Database Connection Error**
   - Check `.env` database configuration
   - Ensure database service is running

4. **Permission Errors**
   ```bash
   # Linux/Mac permission fix
   sudo chown -R www-data:www-data storage/
   sudo chmod -R 775 storage/
   ```

5. **Filament Access Issues**
   - Ensure user has `is_admin = true`
   - Clear application cache

6. **API Documentation Not Loading**
   ```bash
   # Regenerate documentation
   php artisan scribe:generate --force
   
   # Clear cache
   php artisan optimize:clear
   ```

## 📝 Notes

- **Country Support**: Currently supports Malaysia (MY) and Singapore (SG)
- **Currency**: MY uses MYR, SG uses SGD
- **Soft Deletes**: User-deleted orders are hidden from users but visible to admins
- **Access Control**: Only users with `is_admin = true` can access admin panel
- **Test Accounts**: Seeder creates test users automatically - remove or change passwords in production
- **API Documentation**: Auto-generated with Scribe - regenerate after API changes

## 🤝 Contributing

1. Fork the project
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -am 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Create a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

If you have issues:
1. Check the troubleshooting section in this documentation
2. Review existing [Issues](https://github.com/your-username/ecommerce-api/issues)
3. Create a new Issue

For API-related questions, refer to the interactive documentation at `/docs`.

---

**Developer**: April  
**Version**: 1.0.0  
**Last Updated**: January 2025
