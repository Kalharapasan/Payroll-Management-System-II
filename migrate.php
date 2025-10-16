<?php

require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - Fix Reference No</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 40px; background: #f5f5f5; }
        .container { max-width: 800px; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Database Migration</h1>
        <p class="lead">Fix for "Duplicate entry '' for key 'reference_no'" error</p>
        <hr>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
    try {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        echo "<h3>Running Migration...</h3>";
        echo "<div class='alert alert-info'>Starting database migration</div>";

        echo "<p>Step 1: Converting empty reference numbers to NULL...</p>";
        $stmt = $pdo->exec("UPDATE employees SET reference_no = NULL WHERE reference_no = '' OR reference_no IS NULL");
        echo "<p class='success'>✓ Updated {$stmt} rows</p>";
        echo "<p>Step 2: Removing old unique constraint...</p>";
        try {
            $pdo->exec("ALTER TABLE employees DROP INDEX reference_no");
            echo "<p class='success'>✓ Old constraint removed</p>";
        } catch (PDOException $e) {
            if ($e->getCode() == '42000' && strpos($e->getMessage(), "check that column/key exists") !== false) {
                echo "<p class='text-warning'>⚠ Constraint already removed or doesn't exist</p>";
            } else {
                throw $e;
            }
        }
        echo "<p>Step 3: Modifying reference_no column...</p>";
        $pdo->exec("ALTER TABLE employees MODIFY COLUMN reference_no VARCHAR(50) DEFAULT NULL");
        echo "<p class='success'>✓ Column modified successfully</p>";

        echo "<p>Step 4: Adding new unique constraint...</p>";
        try {
            $pdo->exec("ALTER TABLE employees ADD UNIQUE KEY unique_reference_no (reference_no)");
            echo "<p class='success'>✓ New constraint added</p>";
        } catch (PDOException $e) {
            if ($e->getCode() == '42000' && strpos($e->getMessage(), "Duplicate key name") !== false) {
                echo "<p class='text-warning'>⚠ Constraint already exists</p>";
            } else {
                throw $e;
            }
        }

        echo "<p>Step 5: Fixing other optional fields...</p>";
        $pdo->exec("UPDATE employees SET ni_number = NULL WHERE ni_number = ''");
        $pdo->exec("ALTER TABLE employees MODIFY COLUMN ni_number VARCHAR(50) DEFAULT NULL");
        $pdo->exec("UPDATE employees SET student_ref = NULL WHERE student_ref = ''");
        $pdo->exec("ALTER TABLE employees MODIFY COLUMN student_ref VARCHAR(50) DEFAULT NULL");
        echo "<p class='success'>✓ Other fields updated</p>";

        echo "<div class='alert alert-success mt-4'>";
        echo "<h4>✅ Migration Completed Successfully!</h4>";
        echo "<p>Your database has been updated. You can now:</p>";
        echo "<ul>";
        echo "<li>Add employees without reference numbers</li>";
        echo "<li>Add employees with unique reference numbers</li>";
        echo "<li>The duplicate entry error is fixed</li>";
        echo "</ul>";
        echo "<p><a href='index.php' class='btn btn-primary'>Go to Payroll System</a></p>";
        echo "</div>";

        $count = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
        $nullCount = $pdo->query("SELECT COUNT(*) FROM employees WHERE reference_no IS NULL")->fetchColumn();
        
        echo "<h4>Current Database Status:</h4>";
        echo "<ul>";
        echo "<li>Total employees: <strong>{$count}</strong></li>";
        echo "<li>Employees without reference number: <strong>{$nullCount}</strong></li>";
        echo "<li>Employees with reference number: <strong>" . ($count - $nullCount) . "</strong></li>";
        echo "</ul>";

    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>";
        echo "<h4>❌ Migration Failed</h4>";
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>Code: " . htmlspecialchars($e->getCode()) . "</p>";
        echo "</div>";
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>";
        echo "<h4>❌ Error</h4>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</div>";
    }
} else {
  
    ?>
        <div class="alert alert-warning">
            <h4>⚠️ Before You Begin</h4>
            <p>This migration will:</p>
            <ul>
                <li>Fix the "Duplicate entry '' for key 'reference_no'" error</li>
                <li>Convert empty reference numbers to NULL</li>
                <li>Allow multiple employees without reference numbers</li>
                <li>Keep the unique constraint for non-empty reference numbers</li>
            </ul>
            <p><strong>This operation is safe and will not delete any data.</strong></p>
        </div>

        <h3>Current Database Info:</h3>
        <?php
        try {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            
            $count = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
            echo "<p>Total employees in database: <strong>{$count}</strong></p>";
            

            $emptyRefCount = $pdo->query("SELECT COUNT(*) FROM employees WHERE reference_no = ''")->fetchColumn();
            if ($emptyRefCount > 1) {
                echo "<p class='error'>⚠️ Found {$emptyRefCount} employees with empty reference numbers - this causes the error!</p>";
            } else {
                echo "<p class='success'>✓ Database appears to be okay, but you can still run the migration to ensure proper configuration.</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>Could not check database: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>

        <form method="POST" class="mt-4">
            <button type="submit" name="run_migration" class="btn btn-primary btn-lg">
                🚀 Run Migration Now
            </button>
            <a href="index.php" class="btn btn-secondary btn-lg">Cancel</a>
        </form>

        <hr class="mt-5">
        <h4>Manual Alternative</h4>
        <p>If you prefer to run the migration manually, execute this SQL:</p>
        <pre>UPDATE employees SET reference_no = NULL WHERE reference_no = '' OR reference_no IS NULL;
ALTER TABLE employees DROP INDEX reference_no;
ALTER TABLE employees MODIFY COLUMN reference_no VARCHAR(50) DEFAULT NULL;
ALTER TABLE employees ADD UNIQUE KEY unique_reference_no (reference_no);
UPDATE employees SET ni_number = NULL WHERE ni_number = '';
ALTER TABLE employees MODIFY COLUMN ni_number VARCHAR(50) DEFAULT NULL;
UPDATE employees SET student_ref = NULL WHERE student_ref = '';
ALTER TABLE employees MODIFY COLUMN student_ref VARCHAR(50) DEFAULT NULL;</pre>
    <?php
}
?>
    </div>
</body>
</html>
