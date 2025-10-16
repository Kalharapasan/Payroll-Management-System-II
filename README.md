" 💼 Payroll Management System II

A comprehensive, modern payroll management system with beautiful UI, automatic calculations, and complete employee data management.
<img width="1366" height="619" alt="Capture" src="https://github.com/user-attachments/assets/2d433e3d-875a-4cfe-8718-34448ba3823c" />

## ✨ Features

- 💰 **Automatic Salary Calculations** - Real-time gross, net, and deduction calculations
- 👥 **Complete Employee Management** - Add, edit, view, and delete employees with all details
- 🔍 **Advanced Search** - Search by name, reference number, postcode, or NI number
- 📊 **Interactive Dashboard** - Real-time statistics with total employees, gross, net, and deductions
- 📱 **Fully Responsive Design** - Works perfectly on desktop, tablet, and mobile devices
- 🎨 **Modern UI with Icons** - Beautiful gradient design with Font Awesome icons
- 📝 **Comprehensive Data Capture** - 27 fields including personal, employment, tax, and NI information
- 🔐 **Data Integrity** - Unique constraints and NULL handling for optional fields
- 🎯 **Organized Forms** - Sections for Personal Info, Salary, Employment, and Tax details

## 🚀 Installation

### Prerequisites
- XAMPP (or LAMP/WAMP) with PHP 7.4+ and MySQL 5.7+
- Web browser (Chrome, Firefox, Edge, or Safari)

### Step-by-Step Setup

1. **Clone or Download Repository**
   ```bash
   # Navigate to your htdocs folder
   cd C:\xampp\htdocs
   
   # Clone the repository
   git clone https://github.com/Kalharapasan/Payroll-Management-System-II.git PMS_II
   
   # Or download and extract to C:\xampp\htdocs\PMS_II
   ```

2. **Create the Database**
   - Start XAMPP and enable Apache & MySQL
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Click "Import" tab
   - Choose file: `Data_Bases.sql`
   - Click "Go" to import

3. **Configure Database Connection**
   - Open `config.php` in a text editor
   - Update credentials if needed (default works for standard XAMPP):
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'psII');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     ```

4. **Run Database Migration (Important!)**
   - Visit: http://localhost/PMS_II/migrate.php
   - Click "🚀 Run Migration Now"
   - This fixes the unique constraint for reference numbers
   - Wait for success message

5. **Access the System**
   - Visit: http://localhost/PMS_II/
   - Start adding employees! 🎉

## 📋 Employee Data Fields

The system captures comprehensive employee information organized in sections:

### 👤 Personal Information
- Employee Name (required)
- Reference Number (optional, must be unique if provided)
- Gender (Male/Female)
- Pay Date
- Home Address
- Postcode

### 💼 Employment Details
- Employer Name
- Employer Address

### 💰 Salary Components
- Inner City Allowance
- Basic Salary
- Overtime Pay

### 🧾 Tax & National Insurance
- Tax Code
- Tax Period
- Tax to Date
- NI Code
- NI Number
- Pension to Date
- Student Loan Reference

### 📊 Auto-Calculated Fields
- Gross Pay
- Taxable Pay (9% of gross)
- Pensionable Pay (5.5% of gross)
- Student Loan (2.5% of gross)
- NI Payment (2.3% of gross)
- Total Deductions
- Net Pay

### 📝 Additional
- Reference Notes

## 🔧 Common Issues & Solutions

### Issue: "Duplicate entry '' for key 'reference_no'" Error

**Cause**: Multiple employees with empty reference numbers (unique constraint violation)

**Solution 1 - Web Interface (Recommended)**:
1. Visit http://localhost/PMS_II/migrate.php
2. Click "🚀 Run Migration Now"
3. Wait for success confirmation

**Solution 2 - Manual SQL**:
- Run the SQL commands from `fix_unique_constraint.sql` in phpMyAdmin

**How it works**: Converts empty reference numbers to NULL, which allows multiple employees without reference numbers while keeping uniqueness for non-empty values.

### Issue: Modal Not Showing Bottom Section

**Solution**: Already fixed! The modal now has proper scrolling with `modal-dialog-scrollable` class.

### Issue: Data Not Showing in View Mode

**Solution**: Already fixed! All 27 fields are now displayed in the form and view mode shows complete breakdown.

## 🧮 Salary Calculation Formula

The system performs real-time automatic calculations:

### Calculation Breakdown
```
Gross Pay = Inner City Allowance + Basic Salary + Overtime

Deductions:
├── Taxable Pay       = Gross × 9%    (Tax)
├── Pensionable Pay   = Gross × 5.5%  (Pension)
├── Student Loan      = Gross × 2.5%  (Student Loan)
└── NI Payment        = Gross × 2.3%  (National Insurance)

Total Deductions = Taxable + Pensionable + Student Loan + NI

Net Pay = Gross Pay - Total Deductions
```

### Example Calculation
```
Salary Components:
- Inner City: $500.00
- Basic Salary: $3,000.00
- Overtime: $200.00

Calculations:
- Gross Pay: $3,700.00
- Taxable Pay (9%): $333.00
- Pensionable Pay (5.5%): $203.50
- Student Loan (2.5%): $92.50
- NI Payment (2.3%): $85.10
- Total Deductions: $714.10
- Net Pay: $2,985.90
```

**Live Calculations**: Values update in real-time as you type!

## File Structure

```
PMS_II/
├── index.php              # Main application file
├── config.php             # Database configuration
├── styles.css             # Custom styles
├── js.js                  # JavaScript functionality
├── Data_Bases.sql         # Database schema
├── migrate.php            # Migration tool
├── fix_unique_constraint.sql  # SQL migration script
└── README.md              # This file
```

## 💻 Technologies Used

### Backend
- **PHP 7.4+** - Server-side logic and database operations
- **MySQL/MariaDB** - Database management
- **PDO** - Database abstraction layer with prepared statements

### Frontend
- **Bootstrap 5.3** - Responsive UI framework
- **Font Awesome 6.4** - Icon library
- **Vanilla JavaScript** - Client-side functionality and calculations
- **Custom CSS** - Beautiful gradient design and animations

### Security Features
- PDO Prepared Statements (SQL injection prevention)
- Input validation and sanitization
- CSRF protection with form tokens
- XSS prevention with htmlspecialchars()

## 🌐 Browser Support

| Browser | Version | Status |
|---------|---------|--------|
| Chrome | Latest | ✅ Fully Supported |
| Firefox | Latest | ✅ Fully Supported |
| Edge | Latest | ✅ Fully Supported |
| Safari | Latest | ✅ Fully Supported |
| Opera | Latest | ✅ Fully Supported |

## 🎨 Design Features

- **Gradient Theme**: Purple/Blue gradient background
- **Modern Cards**: Shadow effects and hover animations
- **Responsive Layout**: Mobile-first design approach
- **Icon Integration**: Font Awesome icons throughout
- **Live Calculations**: Real-time updates as you type
- **Smooth Animations**: Fade-in effects and transitions
- **Organized Forms**: Sectioned layout for better UX

## 📊 Dashboard Features

The main dashboard displays:
- 👥 **Total Employees** - Count of all employees
- 📈 **Total Gross** - Sum of all gross pay
- 💰 **Total Net** - Sum of all net pay
- 🧾 **Total Deductions** - Sum of all deductions

All statistics update automatically when employees are added, edited, or deleted.

## 🔒 Data Validation

### Required Fields
- Employee Name (cannot be empty)

### Optional but Unique
- Reference Number (must be unique if provided, can be left empty)

### Automatic Handling
- Empty strings converted to NULL for optional fields
- Gender validation (only 'm' or 'f' accepted)
- Numeric validation for salary components
- Date validation for pay date

## 🐛 Debugging & Troubleshooting

### Enable Error Display (Development Only)
Add to `config.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Check Database Connection
Visit `index.php` - if there's a connection error, it will display immediately.

### Clear Browser Cache
If changes aren't showing:
1. Press `Ctrl + Shift + Delete` (Chrome/Firefox)
2. Clear cached images and files
3. Refresh the page

### Additional Help Files
- `FIX_REFERENCE_NO_ERROR.md` - Reference number issue solutions
- `COMPLETE_DATA_DISPLAY_FIX.md` - Field mapping and data display info

## 📈 Version History

### Version 2.0 (Current)
- ✅ Added all 27 employee data fields
- ✅ Fixed reference number unique constraint issue
- ✅ Implemented database migration tool
- ✅ Enhanced modal with scrolling support
- ✅ Added organized form sections
- ✅ Improved view mode with detailed breakdown
- ✅ Added modern UI with gradients and icons
- ✅ Implemented real-time calculations
- ✅ Added comprehensive search functionality

## 🤝 Contributing

Contributions are welcome! Feel free to:
- Report bugs
- Suggest new features
- Submit pull requests

## 📄 License

MIT License - Free to use for personal and commercial projects

## 👨‍💻 Author

**Kalharapasan**
- GitHub: [@Kalharapasan](https://github.com/Kalharapasan)
- Repository: [Payroll-Management-System-II](https://github.com/Kalharapasan/Payroll-Management-System-II)

## 💬 Support

Need help? 

1. Check the documentation files:
   - `README.md` (this file)
   - `FIX_REFERENCE_NO_ERROR.md`
   - `COMPLETE_DATA_DISPLAY_FIX.md`

2. Review the code comments in:
   - `index.php` - Main application logic
   - `js.js` - JavaScript functionality
   - `migrate.php` - Migration tool

3. Open an issue on GitHub with:
   - Clear description of the problem
   - Steps to reproduce
   - Expected vs actual behavior
   - Screenshots if applicable

---

**Made with ❤️ for efficient payroll management**

" 
