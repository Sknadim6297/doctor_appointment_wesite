# MediForum Admin Panel

A modern, professional, and fully responsive Admin Panel Dashboard for Doctor Enrollment & Membership Management System built with Laravel 11 and Tailwind CSS.

## 🎨 Features

### ✅ Modern UI/UX
- Clean, professional SaaS-style dashboard
- Fully responsive design (Desktop, Tablet, Mobile)
- Smooth animations and transitions
- Premium quality design similar to AdminLTE and Metronic
- Gradient color schemes and modern card designs

### 🔐 Authentication & Authorization
- Secure login system
- Role-based access control (Super Admin & Admin)
- Password protection and session management
- Remember me functionality

### 👥 User Roles

#### Super Admin
- Full system access
- Manage all admins (Create, Read, Update, Delete)
- Reset admin passwords
- Access to all modules and features
- View all reports and analytics

#### Admin
- Limited access
- Manage doctors
- Manage enrollments
- Manage receipts
- View reports
- **Cannot** manage other admins

### 📊 Dashboard Components

#### Statistics Cards
- **Enrollment Doctors**: 954
- **Money Receipts**: 3,711
- **Doctor Cases**: 35
- **Lapse List**: 428
- **Premium Amount**: 777
- **Doctor Posts**: 2,653

#### Reports & Analytics
1. **Last 6 Month Report**
   - Enrollment tracking
   - Renewal statistics
   - Lapse monitoring

2. **Enrollment Comparison**
   - Year-over-year enrollment
   - Renewal comparisons

3. **Doctors Progress Report**
   - Progress bars for various metrics
   - Document completion tracking
   - Case assignments
   - Premium plan adoption
   - Photo uploads
   - Renewal status

4. **Doctor Plan Report**
   - Normal Plan statistics
   - High Plan statistics
   - Combo Plan statistics

5. **Payment Reports**
   - Current year payments
   - Previous year payments
   - All-time payment totals
   - Percentage changes

6. **Latest Enrolled Doctors**
   - Recently enrolled doctors list
   - Profile images
   - Enrollment dates

### 🧭 Navigation Structure

#### Sidebar Menu
- Dashboard
- Doctors
- Enrollment
- Money Receipts
- Doctor Cases
- Lapse List
- Premium Plans
- Doctor Posts
- Reports
- Admin Management (Super Admin only)
- Logout

#### Features
- Collapsible sidebar
- Mobile-friendly hamburger menu
- Active route highlighting
- Smooth hover effects
- Beautiful icons

## 🚀 Installation & Setup

### Prerequisites
- PHP 8.2 or higher
- Composer
- MySQL/MariaDB
- Node.js & NPM

### Installation Steps

1. **Clone the repository** (if applicable)
   ```bash
   cd c:\xampp\htdocs\mediforum_admin
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install NPM dependencies**
   ```bash
   npm install
   ```

4. **Environment Configuration**
   - Copy `.env.example` to `.env`
   - Configure your database settings in `.env`

5. **Generate Application Key**
   ```bash
   php artisan key:generate
   ```

6. **Run Migrations**
   ```bash
   php artisan migrate
   ```

7. **Seed Database** (Create default admin users)
   ```bash
   php artisan db:seed
   ```

8. **Build Assets**
   ```bash
   npm run build
   ```
   
   For development (with hot reload):
   ```bash
   npm run dev
   ```

9. **Start the Development Server**
   ```bash
   php artisan serve
   ```

10. **Access the Application**
    - URL: `http://localhost:8000`
    - Admin Login: `http://localhost:8000/admin/login`

## 🔑 Default Login Credentials

### Super Admin Account
- **Email**: `superadmin@mediforum.com`
- **Password**: `password`

### Regular Admin Account
- **Email**: `admin@mediforum.com`
- **Password**: `password`

> ⚠️ **Important**: Change these passwords immediately in production!

## 📱 Routes Structure

### Public Routes
```
/                           → Redirects to admin login
```

### Admin Routes
```
/admin/login               → Admin login page
/admin/dashboard           → Main dashboard (requires authentication)
/admin/doctors             → Doctors management
/admin/enrollment          → Enrollment management
/admin/receipts            → Money receipts
/admin/cases               → Doctor cases
/admin/lapse               → Lapse list
/admin/plans               → Premium plans
/admin/posts               → Doctor posts
/admin/reports             → Reports & analytics
/admin/admin-management    → Admin management (Super Admin only)
/admin/logout              → Logout
```

## 🎨 Technology Stack

- **Backend**: Laravel 11
- **Frontend**: Tailwind CSS 4.0
- **JavaScript**: Alpine.js
- **Icons**: Heroicons (SVG)
- **Build Tool**: Vite
- **Authentication**: Laravel's built-in Auth

## 📂 Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Admin/
│   │       ├── AdminAuthController.php      # Authentication
│   │       ├── DashboardController.php      # Dashboard
│   │       └── AdminManagementController.php # Admin CRUD
│   └── Middleware/
│       ├── AdminMiddleware.php              # Admin access
│       └── SuperAdminMiddleware.php         # Super admin access
│
resources/
└── views/
    └── admin/
        ├── auth/
        │   └── login.blade.php              # Login page
        ├── layouts/
        │   └── app.blade.php                # Main layout
        ├── dashboard.blade.php              # Dashboard
        ├── admin-management/                # Admin management views
        ├── doctors/                         # Doctors views
        ├── enrollment/                      # Enrollment views
        ├── receipts/                        # Receipts views
        ├── cases/                           # Cases views
        ├── lapse/                           # Lapse views
        ├── plans/                           # Plans views
        ├── posts/                           # Posts views
        └── reports/                         # Reports views
```

## 🔒 Security Features

- Password hashing using bcrypt
- CSRF protection on all forms
- XSS protection
- SQL injection prevention via Eloquent ORM
- Session management
- Role-based authorization
- Middleware protection on routes

## 🎯 Key Features Implementation

### 1. Responsive Design
- Mobile-first approach
- Breakpoints: sm (640px), md (768px), lg (1024px), xl (1280px)
- Collapsible sidebar on mobile
- Touch-friendly interface

### 2. Role-Based Access Control
- Middleware-based protection
- Route-level authorization
- UI elements conditionally rendered based on role

### 3. Admin Management (Super Admin Only)
- Create new admins
- Edit admin details
- Reset admin passwords
- Delete admins (except yourself)
- Toggle admin active status
- View admin list with pagination

### 4. Modern Dashboard
- Real-time statistics cards
- Color-coded metrics
- Progress bars and charts
- Latest activities feed
- Responsive grid layout

## 🔧 Customization

### Changing Colors
Edit `resources/css/app.css` or modify Tailwind classes directly in blade templates.

### Adding New Modules
1. Create controller in `app/Http/Controllers/Admin/`
2. Add routes in `routes/web.php`
3. Create views in `resources/views/admin/`
4. Add sidebar link in `resources/views/admin/layouts/app.blade.php`

### Modifying Dashboard Statistics
Edit `app/Http/Controllers/Admin/DashboardController.php` to fetch real data from your database.

## 📝 Development Notes

### Mock Data
Currently, the dashboard displays mock/sample data. To connect to real data:
1. Create database migrations for your tables
2. Create Eloquent models
3. Update the `DashboardController` to fetch real data
4. Implement CRUD operations in respective controllers

### Next Steps for Development
- Implement Doctors CRUD operations
- Create Enrollment system
- Build Money Receipts module
- Develop Doctor Cases management
- Create Premium Plans management
- Build Reports with real data
- Add data export functionality (PDF, Excel)
- Implement search and filtering
- Add pagination to all lists

## 🐛 Troubleshooting

### Issue: Styles not loading
```bash
npm run build
php artisan optimize:clear
```

### Issue: Database connection error
- Check `.env` database credentials
- Ensure MySQL/MariaDB is running
- Verify database exists

### Issue: Route not found
```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

### Issue: Permission denied
```bash
chmod -R 775 storage bootstrap/cache
```

## 📄 License

This project is proprietary software for MediForum.

## 👨‍💻 Support

For support, please contact the development team.

---

**Built with ❤️ using Laravel & Tailwind CSS**
