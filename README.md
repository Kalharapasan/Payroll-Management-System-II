"# Payroll Management System II

A comprehensive payroll management system with modern UI, automatic calculations, and employee management features.

## Features

- 💰 Automatic salary calculations (gross, net, deductions)
- 👥 Employee management (add, edit, view, delete)
- 🔍 Search functionality
- 📊 Dashboard with statistics
- 📱 Responsive design
- 🎨 Modern UI with icons

## Installation

1. **Clone or download this repository** to your `htdocs` folder:
   ```
   C:\xampp\htdocs\PMS_II
   ```

2. **Create the database**:
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Import `Data_Bases.sql` file

3. **Configure database connection**:
   - Open `config.php`
   - Update database credentials if needed

4. **Fix Reference Number Issue (Important!)**:
   - Visit: http://localhost/PMS_II/migrate.php
   - Click "Run Migration Now"
   - This fixes the "Duplicate entry for reference_no" error

5. **Access the system**:
   - Visit: http://localhost/PMS_II/

## Common Issues

### "Duplicate entry '' for key 'reference_no'" Error

**Solution**: Run the migration script:
1. Visit http://localhost/PMS_II/migrate.php
2. Click "Run Migration Now"

Or manually run the SQL from `fix_unique_constraint.sql`

## Salary Calculations

The system automatically calculates:
- **Gross Pay** = Inner City + Basic Salary + Overtime
- **Taxable Pay** = 9% of Gross
- **Pensionable Pay** = 5.5% of Gross
- **Student Loan** = 2.5% of Gross
- **NI Payment** = 2.3% of Gross
- **Net Pay** = Gross Pay - Total Deductions

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

## Technologies Used

- PHP 7.4+
- MySQL/MariaDB
- Bootstrap 5.3
- Font Awesome 6.4
- JavaScript (Vanilla)

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Edge (latest)
- Safari (latest)

## License

MIT License

## Support

If you encounter any issues, check the `FIX_REFERENCE_NO_ERROR.md` file for detailed troubleshooting.

" 
