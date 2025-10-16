<?php 

require_once __DIR__ . '/config.php';
try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo "<h1>Database connection failed</h1><p>".htmlspecialchars($e->getMessage())."</p>";
    exit;
}

function calc_from_components($inner_city, $basic_salary, $overtime) {
    $inner_city = (float)$inner_city;
    $basic_salary = (float)$basic_salary;
    $overtime = (float)$overtime;

    $gross = $inner_city + $basic_salary + $overtime;
    $taxable = ($gross * 9) / 100.0; 
    $pension = ($gross * 5.5) / 100.0; 
    $student = ($gross * 2.5) / 100.0; 
    $ni = ($gross * 2.3) / 100.0; 
    $deductions = $taxable + $pension + $student + $ni;
    $net = $gross - $deductions;

    return [
        'gross_pay' => round($gross, 2),
        'taxable_pay' => round($taxable, 2),
        'pensionable_pay' => round($pension, 2),
        'student_loan' => round($student, 2),
        'ni_payment' => round($ni, 2),
        'deduction' => round($deductions, 2),
        'net_pay' => round($net, 2),
    ];
}

function s($v) { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

$errors = [];
$messages = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'update') {
        $data = [];
        $fields = [
            'employee_name','address','postcode','gender','reference_no','employer','emp_address','tax_period','tax_code','pay_date',
            'inner_city','basic_salary','overtime','tax_todate','pension_todate','student_ref','ni_code','ni_number','ref_note'
        ];
        foreach ($fields as $f) {
            $data[$f] = $_POST[$f] ?? null;
        }

        if (empty(trim($data['employee_name'] ?? ''))) $errors[] = 'Employee name is required.';
        if (!in_array($data['gender'], ['m','f','M','F'])) $data['gender'] = null;
        
        // Convert empty reference_no to NULL to avoid unique constraint issues
        if (empty(trim($data['reference_no'] ?? ''))) $data['reference_no'] = null;
        
        // Convert empty strings to NULL for other optional fields
        if (empty(trim($data['ni_number'] ?? ''))) $data['ni_number'] = null;
        if (empty(trim($data['student_ref'] ?? ''))) $data['student_ref'] = null;
        
        $data['inner_city'] = str_replace(',', '', $data['inner_city'] ?: 0);
        $data['basic_salary'] = str_replace(',', '', $data['basic_salary'] ?: 0);
        $data['overtime'] = str_replace(',', '', $data['overtime'] ?: 0);

        if (empty($errors)) {
            $calc = calc_from_components($data['inner_city'], $data['basic_salary'], $data['overtime']);

            try {
                if ($action === 'add') {
                    $sql = "INSERT INTO employees
                        (employee_name,address,postcode,gender,reference_no,employer,emp_address,tax_period,tax_code,pay_date,
                        inner_city,basic_salary,overtime,gross_pay,taxable_pay,pensionable_pay,student_loan,ni_payment,deduction,net_pay,
                        tax_todate,pension_todate,student_ref,ni_code,ni_number,ref_note)
                        VALUES
                        (:employee_name,:address,:postcode,:gender,:reference_no,:employer,:emp_address,:tax_period,:tax_code,:pay_date,
                        :inner_city,:basic_salary,:overtime,:gross_pay,:taxable_pay,:pensionable_pay,:student_loan,:ni_payment,:deduction,:net_pay,
                        :tax_todate,:pension_todate,:student_ref,:ni_code,:ni_number,:ref_note)
                    ";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(array_merge($data, $calc));
                    $messages[] = 'Employee added successfully.';
                } else {
                    $id = (int)($_POST['id'] ?? 0);
                    if ($id <= 0) throw new Exception('Invalid employee id for update.');
                    $sql = "UPDATE employees SET
                        employee_name=:employee_name,address=:address,postcode=:postcode,gender=:gender,reference_no=:reference_no,employer=:employer,emp_address=:emp_address,tax_period=:tax_period,tax_code=:tax_code,pay_date=:pay_date,
                        inner_city=:inner_city,basic_salary=:basic_salary,overtime=:overtime,gross_pay=:gross_pay,taxable_pay=:taxable_pay,pensionable_pay=:pensionable_pay,student_loan=:student_loan,ni_payment=:ni_payment,deduction=:deduction,net_pay=:net_pay,
                        tax_todate=:tax_todate,pension_todate=:pension_todate,student_ref=:student_ref,ni_code=:ni_code,ni_number=:ni_number,ref_note=:ref_note
                        WHERE id = :id
                    ";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(array_merge($data, $calc, ['id' => $id]));
                    $messages[] = 'Employee updated successfully.';
                }
            } catch (PDOException $e) {
                // Handle duplicate entry error specifically
                if ($e->getCode() == 23000) {
                    if (strpos($e->getMessage(), 'reference_no') !== false) {
                        $errors[] = 'The reference number "' . htmlspecialchars($data['reference_no']) . '" is already in use. Please use a unique reference number or leave it empty.';
                    } else {
                        $errors[] = 'Duplicate entry error: ' . $e->getMessage();
                    }
                } else {
                    $errors[] = 'Database error: ' . $e->getMessage();
                }
            } catch (Exception $e) {
                $errors[] = 'Error: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare('DELETE FROM employees WHERE id = :id');
                $stmt->execute(['id' => $id]);
                $messages[] = 'Employee deleted.';
            } catch (Exception $e) {
                $errors[] = 'Delete failed: ' . $e->getMessage();
            }
        } else {
            $errors[] = 'Invalid id for deletion.';
        }
    }
}

$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10; 
$offset = ($page - 1) * $per_page;

$params = [];
$where = '';
if ($search !== '') {
    $where = "WHERE employee_name LIKE :q OR reference_no LIKE :q OR postcode LIKE :q OR ni_number LIKE :q";
    $params[':q'] = "%$search%";
}

$totalsStmt = $pdo->prepare("SELECT COUNT(*) as total_count, IFNULL(SUM(gross_pay),0) as total_gross, IFNULL(SUM(net_pay),0) as total_net, IFNULL(SUM(deduction),0) as total_ded FROM employees $where");
$totalsStmt->execute($params);
$totals = $totalsStmt->fetch();

$listSql = "SELECT * FROM employees $where ORDER BY id DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($listSql);
foreach ($params as $k=>$v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', (int)$per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$countSql = "SELECT COUNT(*) FROM employees $where";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total_records = (int)$countStmt->fetchColumn();
$total_pages = max(1, ceil($total_records / $per_page));

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Payroll Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <div class="page-header d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-money-check-alt"></i> Payroll Management System</h2>
            <div>
                <button class="btn btn-primary" id="btnNew">
                    <i class="fas fa-user-plus"></i> Add Employee
                </button>
            </div>
        </div>
        
        <?php if (!empty($messages)): ?>
        <?php foreach ($messages as $m): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= s($m) ?></div>
        <?php endforeach; ?>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $e): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= s($e) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        
        
        <div class="stats-card">
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-item">
                            <i class="fas fa-users icon-employees"></i>
                            <div class="stat-label">Total Employees</div>
                            <div class="stat-value"><?= (int)$totals['total_count'] ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-item">
                            <i class="fas fa-chart-line icon-gross"></i>
                            <div class="stat-label">Total Gross</div>
                            <div class="stat-value monos">$<?= number_format((float)$totals['total_gross'],2) ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-item">
                            <i class="fas fa-wallet icon-net"></i>
                            <div class="stat-label">Total Net</div>
                            <div class="stat-value monos">$<?= number_format((float)$totals['total_net'],2) ?></div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="stat-item">
                            <i class="fas fa-receipt icon-deductions"></i>
                            <div class="stat-label">Total Deductions</div>
                            <div class="stat-value monos">$<?= number_format((float)$totals['total_ded'],2) ?></div>
                        </div>
                    </div>
                </div>
                <form class="search-wrapper" method="get">
                    <input name="q" value="<?= s($search) ?>" class="form-control" placeholder="🔍 Search by name, reference, postcode, or NI number...">
                    <button class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                </form>
            </div>
        </div>

        
        <div class="table-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th><i class="fas fa-hashtag"></i> ID</th>
                        <th><i class="fas fa-user"></i> Name</th>
                        <th><i class="fas fa-id-card"></i> Reference</th>
                        <th><i class="fas fa-dollar-sign"></i> Gross</th>
                        <th><i class="fas fa-money-bill-wave"></i> Net</th>
                        <th><i class="fas fa-minus-circle"></i> Deductions</th>
                        <th><i class="fas fa-calendar-alt"></i> Pay Date</th>
                        <th><i class="fas fa-cogs"></i> Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="8" class="text-center py-4">No records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td class="nowrap"><?= (int)$r['id'] ?></td>
                                <td><i class="fas fa-user-circle"></i> <?= s($r['employee_name']) ?></td>
                                <td><?= s($r['reference_no']) ?></td>
                                <td class="monos">$<?= number_format((float)$r['gross_pay'],2) ?></td>
                                <td class="monos">$<?= number_format((float)$r['net_pay'],2) ?></td>
                                <td class="monos">$<?= number_format((float)$r['deduction'],2) ?></td>
                                <td><?= s($r['pay_date']) ?></td>
                                <td class="nowrap">
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-info btnView" data-row='<?= json_encode($r, JSON_HEX_APOS|JSON_HEX_QUOT) ?>'>
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <button class="btn btn-sm btn-warning btnEdit" data-row='<?= json_encode($r, JSON_HEX_APOS|JSON_HEX_QUOT) ?>'>
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form method="post" style="display:inline" onsubmit="return confirm('⚠️ Are you sure you want to delete this employee?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                            <button class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <nav class="mt-3" aria-label="pagination">
            <ul class="pagination">
                <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?q=<?= urlencode($search) ?>&page=<?= $p ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>

    </div>

    <div class="modal fade" id="modalRow" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
            <form id="frmRow" method="post">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-user-edit"></i> Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="formId" value="0">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-user"></i> Name</label>
                        <input name="employee_name" id="employee_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-id-badge"></i> Reference No</label>
                        <input name="reference_no" id="reference_no" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-venus-mars"></i> Gender</label>
                        <select name="gender" id="gender" class="form-select">
                            <option value="">Select Gender</option>
                            <option value="m">Male</option>
                            <option value="f">Female</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="fas fa-calendar"></i> Pay Date</label>
                        <input name="pay_date" id="pay_date" class="form-control" type="date">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label"><i class="fas fa-building"></i> Inner City</label>
                        <input name="inner_city" id="inner_city" class="form-control" type="number" step="0.01" placeholder="0.00">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><i class="fas fa-hand-holding-usd"></i> Basic Salary</label>
                        <input name="basic_salary" id="basic_salary" class="form-control" type="number" step="0.01" placeholder="0.00">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label"><i class="fas fa-clock"></i> Overtime</label>
                        <input name="overtime" id="overtime" class="form-control" type="number" step="0.01" placeholder="0.00">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label"><i class="fas fa-briefcase"></i> Employer</label>
                        <input name="employer" id="employer" class="form-control">
                    </div>

                    <div class="col-12">
                        <div class="calc-display">
                            <small class="text-muted d-block mb-2"><i class="fas fa-calculator"></i> <strong>Calculated Values (Live):</strong></small>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="calc-item">
                                        <div class="calc-label"><i class="fas fa-chart-line"></i> Gross Pay</div>
                                        <div class="calc-value">$<span id="calc_gross">0.00</span></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="calc-item">
                                        <div class="calc-label"><i class="fas fa-minus-circle"></i> Deductions</div>
                                        <div class="calc-value">$<span id="calc_ded">0.00</span></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="calc-item">
                                        <div class="calc-label"><i class="fas fa-money-bill-wave"></i> Net Pay</div>
                                        <div class="calc-value">$<span id="calc_net">0.00</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label"><i class="fas fa-sticky-note"></i> Reference Note</label>
                        <textarea name="ref_note" id="ref_note" class="form-control" rows="3" placeholder="Add any notes or comments..."></textarea>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
                <button type="submit" class="btn btn-primary" id="modalSave">
                    <i class="fas fa-save"></i> Save
                </button>
            </div>
            </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js.js"></script> 

</body>
</html>