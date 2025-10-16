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
            } catch (Exception $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
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
    <title>Payroll — Clean Rebuilt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 18px; }
        .card-stats { gap: .75rem; display:flex; }
        .monos { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        td.nowrap { white-space:nowrap; }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2>Payroll — Clean Rebuilt</h2>
            <div>
                <button class="btn btn-primary" id="btnNew">+ Add Employee</button>
            </div>
        </div>
        
        <?php if (!empty($messages)): ?>
        <?php foreach ($messages as $m): ?>
            <div class="alert alert-success"><?= s($m) ?></div>
        <?php endforeach; ?>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $e): ?>
                <div class="alert alert-danger"><?= s($e) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        
        
        <div class="card mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div class="card-stats">
                    <div>
                        <small class="text-muted">Total Employees</small>
                        <div class="h4"><?= (int)$totals['total_count'] ?></div>
                    </div>
                    <div class="ms-4">
                        <small class="text-muted">Total Gross</small>
                        <div class="h5 monos"><?= number_format((float)$totals['total_gross'],2) ?></div>
                    </div>
                    <div class="ms-4">
                        <small class="text-muted">Total Net</small>
                        <div class="h5 monos"><?= number_format((float)$totals['total_net'],2) ?></div>
                    </div>
                    <div class="ms-4">
                        <small class="text-muted">Total Deductions</small>
                        <div class="h5 monos"><?= number_format((float)$totals['total_ded'],2) ?></div>
                    </div>
                </div>
                <form class="d-flex" method="get" style="gap:.5rem;">
                    <input name="q" value="<?= s($search) ?>" class="form-control" placeholder="Search name, ref, postcode, NI">
                    <button class="btn btn-outline-secondary">Search</button>
                </form>
            </div>
        </div>

        
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Reference</th>
                        <th>Gross</th>
                        <th>Net</th>
                        <th>Deductions</th>
                        <th>Pay Date</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="8" class="text-center py-4">No records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td class="nowrap"><?= (int)$r['id'] ?></td>
                                <td><?= s($r['employee_name']) ?></td>
                                <td><?= s($r['reference_no']) ?></td>
                                <td class="monos"><?= number_format((float)$r['gross_pay'],2) ?></td>
                                <td class="monos"><?= number_format((float)$r['net_pay'],2) ?></td>
                                <td class="monos"><?= number_format((float)$r['deduction'],2) ?></td>
                                <td><?= s($r['pay_date']) ?></td>
                                <td class="nowrap">
                                    <button class="btn btn-sm btn-info btnView" data-row='<?= json_encode($r, JSON_HEX_APOS|JSON_HEX_QUOT) ?>'>View</button>
                                    <button class="btn btn-sm btn-warning btnEdit" data-row='<?= json_encode($r, JSON_HEX_APOS|JSON_HEX_QUOT) ?>'>Edit</button>
                                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this employee?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <button class="btn btn-sm btn-danger">Delete</button>
                                    </form>
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
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
            <form id="frmRow" method="post">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="formId" value="0">

                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input name="employee_name" id="employee_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Reference No</label>
                        <input name="reference_no" id="reference_no" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Gender</label>
                        <select name="gender" id="gender" class="form-select"><option value="">-</option><option value="m">Male</option><option value="f">Female</option></select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Pay Date</label>
                        <input name="pay_date" id="pay_date" class="form-control" type="date">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Inner City</label>
                        <input name="inner_city" id="inner_city" class="form-control" type="number" step="0.01">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Basic Salary</label>
                        <input name="basic_salary" id="basic_salary" class="form-control" type="number" step="0.01">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Overtime</label>
                        <input name="overtime" id="overtime" class="form-control" type="number" step="0.01">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Employer</label>
                        <input name="employer" id="employer" class="form-control">
                    </div>

                    <div class="col-12 mt-2">
                        <small class="text-muted">Calculated values (live):</small>
                        <div class="d-flex gap-3 mt-1">
                            <div class="border p-2 rounded">Gross: <span id="calc_gross">0.00</span></div>
                            <div class="border p-2 rounded">Deductions: <span id="calc_ded">0.00</span></div>
                            <div class="border p-2 rounded">Net: <span id="calc_net">0.00</span></div>
                        </div>
                    </div>

                    <div class="col-12 mt-2">
                        <label class="form-label">Reference Note</label>
                        <textarea name="ref_note" id="ref_note" class="form-control" rows="3"></textarea>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary" id="modalSave">Save</button>
            </div>
            </form>
            </div>
        </div>
    </div>




</body>
</html>