<?php
session_start();
require_once 'config.php';

$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year  = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$site  = isset($_GET['site']) ? trim($_GET['site']) : '';

$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

$siteCondition = '';
if ($site !== '') {
    $siteEsc = $conn->real_escape_string($site);
    $siteCondition = " AND em.site_code = '$siteEsc'";
}

$sql = "
    SELECT 
        a.esic_no,
        a.attendance_json,
        em.employee_name,
        em.site_code
    FROM attendance a
    LEFT JOIN employee_master em 
        ON a.esic_no = em.esic_no
    WHERE a.attendance_month = $month
    AND a.attendance_year = $year
    $siteCondition
    ORDER BY em.employee_name ASC
";

$result = $conn->query($sql);

$employees = [];

while ($row = $result->fetch_assoc()) {

    $attendanceData = json_decode($row['attendance_json'], true);
    if (!is_array($attendanceData)) continue;

    $dailyStatus = array_fill(1, $daysInMonth, '');
    $totals = ['present'=>0,'absent'=>0,'leave'=>0,'overtime'=>0];

    foreach ($attendanceData as $item) {
        if (!isset($item['created_at'])) continue;

        $day = (int)date('j', strtotime($item['created_at']));
        $status = strtolower($item['status'] ?? '');

        $short = strtoupper(substr($status,0,1));
        $dailyStatus[$day] = $short;

        if (isset($totals[$status])) {
            $totals[$status]++;
        }
    }

    $employees[] = [
        'empId'=>$row['esic_no'],
        'name'=>$row['employee_name'],
        'days'=>$dailyStatus,
        'totals'=>$totals
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Monthly Attendance</title>

<style>
body {
    font-family: Arial, sans-serif;
    background:#f4f6f9;
    padding:20px;
}

h2 {
    margin-bottom:20px;
}

.filters {
    margin-bottom:20px;
}

select, button {
    padding:6px 10px;
    font-size:14px;
}

table {
    border-collapse: collapse;
    width:100%;
    background:white;
    font-size:13px;
}

th, td {
    border:1px solid #ddd;
    padding:6px;
    text-align:center;
}

th {
    background:#0f766e;
    color:white;
    position:sticky;
    top:0;
}

td.name {
    text-align:left;
    font-weight:bold;
}

td.p { background:#d1fae5; }
td.a { background:#fee2e2; }
td.l { background:#fef3c7; }
td.o { background:#dbeafe; }

tfoot td {
    font-weight:bold;
    background:#f3f4f6;
}
</style>
</head>

<body>

<h2>Monthly Attendance</h2>

<div class="filters">
    <form method="GET">
        <select name="month">
            <?php for($m=1;$m<=12;$m++): ?>
                <option value="<?= $m ?>" <?= $m==$month?'selected':'' ?>>
                    <?= date('F', mktime(0,0,0,$m,1)) ?>
                </option>
            <?php endfor; ?>
        </select>

        <select name="year">
            <?php for($y=date('Y')-2;$y<=date('Y')+1;$y++): ?>
                <option value="<?= $y ?>" <?= $y==$year?'selected':'' ?>>
                    <?= $y ?>
                </option>
            <?php endfor; ?>
        </select>

        <button type="submit">Load</button>
    </form>
</div>

<div style="overflow:auto; max-height:600px;">
<table>
<thead>
<tr>
    <th>EMP ID</th>
    <th>NAME</th>
    <?php for($d=1;$d<=$daysInMonth;$d++): ?>
        <th><?= $d ?></th>
    <?php endfor; ?>
    <th>P</th>
    <th>A</th>
    <th>L</th>
    <th>OT</th>
</tr>
</thead>

<tbody>

<?php foreach($employees as $emp): ?>
<tr>
    <td><?= htmlspecialchars($emp['empId']) ?></td>
    <td class="name"><?= htmlspecialchars($emp['name']) ?></td>

    <?php for($d=1;$d<=$daysInMonth;$d++): 
        $val = strtolower($emp['days'][$d]);
        $class = '';
        if($val=='p') $class='p';
        if($val=='a') $class='a';
        if($val=='l') $class='l';
        if($val=='o') $class='o';
    ?>
        <td class="<?= $class ?>"><?= strtoupper($val) ?></td>
    <?php endfor; ?>

    <td><?= $emp['totals']['present'] ?></td>
    <td><?= $emp['totals']['absent'] ?></td>
    <td><?= $emp['totals']['leave'] ?></td>
    <td><?= $emp['totals']['overtime'] ?></td>
</tr>
<?php endforeach; ?>

</tbody>
</table>
</div>

</body>
</html>
