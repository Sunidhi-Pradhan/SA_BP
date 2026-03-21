<?php
/**
 * generate_lpc.php
 * Core LPC (Labour Payment Certificate) generation logic.
 * Called automatically after SDHOD final approval of monthly attendance.
 *
 * Formula per designation per site:
 *   PF           = Basic VDA × 12%
 *   CMPS         = Basic VDA × 7%
 *   Bonus        = Basic VDA × 8.33%  (Security Guard only)
 *   ESI          = Basic VDA × 4%     (Security Guard only)
 *   PF Admin     = (PF × 2) × 3%
 *   Total        = Basic VDA + PF + CMPS + Bonus + ESI + PF Admin
 *   Service Chg  = Total × 12.5%
 *   Per Day Rate = Total + Service Charge
 *   Payable Days = Present + Leave + Overtime
 *   Net Pay      = Payable Days × Per Day Rate
 *   GST          = Net Pay × 18%
 *   Gross Total  = Net Pay + GST
 */

/**
 * Generate LPC record for a given site, month, and year.
 * Aggregates attendance, applies formula, stores into lpc_master.
 *
 * @param PDO    $pdo
 * @param string $siteCode
 * @param int    $month
 * @param int    $year
 * @return bool  true if generated, false if already exists or no data
 */
function generateLpcForSite(PDO $pdo, string $siteCode, int $month, int $year): bool
{
    // ── 1. Check if LPC already exists for this site/month/year ──
    $chk = $pdo->prepare("SELECT lpc_id FROM lpc_master WHERE lpc_area_code = ? AND lpc_month = ? AND lpc_year = ?");
    $chk->execute([$siteCode, $month, $year]);
    if ($chk->fetch()) {
        return false; // already generated
    }

    // ── 2. Fetch site name ──
    $stmtSite = $pdo->prepare("SELECT SiteName FROM site_master WHERE SiteCode = ?");
    $stmtSite->execute([$siteCode]);
    $siteRow = $stmtSite->fetch(PDO::FETCH_ASSOC);
    $siteName = $siteRow ? $siteRow['SiteName'] : $siteCode;

    // ── 3. Get employees at this site with their ranks ──
    $stmtEmp = $pdo->prepare("SELECT esic_no, rank FROM employee_master WHERE site_code = ?");
    $stmtEmp->execute([$siteCode]);
    $employees = $stmtEmp->fetchAll(PDO::FETCH_ASSOC);

    if (empty($employees)) {
        return false; // no employees at this site
    }

    // Build a map: esic_no => rank
    $empRankMap = [];
    foreach ($employees as $emp) {
        $empRankMap[$emp['esic_no']] = $emp['rank'];
    }

    // ── 4. Fetch attendance data for these employees ──
    $esicList = array_keys($empRankMap);
    $placeholders = implode(',', array_fill(0, count($esicList), '?'));
    $params = array_merge($esicList, [$year, $month]);

    $stmtAtt = $pdo->prepare("
        SELECT esic_no, attendance_json 
        FROM attendance 
        WHERE esic_no IN ($placeholders)
          AND attendance_year = ?
          AND attendance_month = ?
    ");
    $stmtAtt->execute($params);
    $attendanceRows = $stmtAtt->fetchAll(PDO::FETCH_ASSOC);

    // ── 5. Aggregate attendance by rank ──
    // Initialize rank stats
    $rankStats = [];
    
    // Count employees per rank (from employee_master)
    foreach ($empRankMap as $esic => $rank) {
        $normRank = strtoupper(trim($rank));
        if (!isset($rankStats[$normRank])) {
            $rankStats[$normRank] = [
                'rank'      => $rank,
                'employees' => 0,
                'present'   => 0,
                'absent'    => 0,
                'leave'     => 0,
                'overtime'  => 0,
            ];
        }
        $rankStats[$normRank]['employees']++;
    }

    // Count attendance statuses
    foreach ($attendanceRows as $attRow) {
        $esic = $attRow['esic_no'];
        $rank = $empRankMap[$esic] ?? 'UNKNOWN';
        $normRank = strtoupper(trim($rank));

        $attJson = json_decode($attRow['attendance_json'], true) ?? [];

        foreach ($attJson as $dateKey => $entry) {
            $status = $entry['status'] ?? '';
            switch ($status) {
                case 'P':
                    $rankStats[$normRank]['present']++;
                    break;
                case 'A':
                    $rankStats[$normRank]['absent']++;
                    break;
                case 'L':
                    $rankStats[$normRank]['leave']++;
                    break;
                case 'PP':
                    $rankStats[$normRank]['overtime']++;
                    break;
            }
        }
    }

    // ── 6. Fetch basic_vda per rank from emp_grade ──
    // Priority: site-specific rate > 'All' rate
    $rankBasicVda = [];
    foreach (array_keys($rankStats) as $normRank) {
        // Try site-specific first
        $stmtGrade = $pdo->prepare("
            SELECT basic_vda FROM emp_grade 
            WHERE UPPER(TRIM(designation)) = ? AND SiteCode = ?
            LIMIT 1
        ");
        $stmtGrade->execute([$normRank, $siteCode]);
        $gradeRow = $stmtGrade->fetch(PDO::FETCH_ASSOC);

        if (!$gradeRow) {
            // Fall back to 'All'
            $stmtGrade->execute([$normRank, 'All']);
            $gradeRow = $stmtGrade->fetch(PDO::FETCH_ASSOC);
        }

        $rankBasicVda[$normRank] = $gradeRow ? (float)$gradeRow['basic_vda'] : 0;
    }

    // ── 7. Apply LPC formula per rank ──
    $ranks = [];
    $siteTotalNetPay  = 0;
    $siteTotalGst     = 0;
    $siteTotalGross   = 0;
    $siteTotalPresent = 0;
    $siteTotalAbsent  = 0;
    $siteTotalLeave   = 0;
    $siteTotalOvertime = 0;
    $siteTotalEmp     = 0;

    foreach ($rankStats as $normRank => $stats) {
        $basicVda = $rankBasicVda[$normRank] ?? 0;
        $isGuard  = (strpos($normRank, 'SECURITY GUARD') !== false);

        // Formula calculations
        $pf       = round($basicVda * 0.12, 2);
        $cmps     = round($basicVda * 0.07, 2);
        $bonus    = $isGuard ? round($basicVda * 0.0833, 2) : 0;
        $esi      = $isGuard ? round($basicVda * 0.04, 2)   : 0;
        $pfAdmin  = round(($pf * 2) * 0.03, 2);
        $total    = round($basicVda + $pf + $cmps + $bonus + $esi + $pfAdmin, 2);
        $sc       = round($total * 0.125, 2);
        $perDay   = round($total + $sc, 2);

        // Payable days = present + leave + overtime
        $payableDays = $stats['present'] + $stats['leave'] + $stats['overtime'];
        $netPay      = round($payableDays * $perDay, 2);
        $gstAmount   = round($netPay * 0.18, 2);
        $grossTotal  = round($netPay + $gstAmount, 2);

        $ranks[] = [
            'rank'        => $stats['rank'],
            'present'     => $stats['present'],
            'absent'      => $stats['absent'],
            'leave'       => $stats['leave'],
            'overtime'    => $stats['overtime'],
            'employees'   => $stats['employees'],
            'basic_pay'   => $basicVda,
            'pf'          => $pf,
            'cmps'        => $cmps,
            'bonus'       => $bonus,
            'esi'         => $esi,
            'pf_admin'    => $pfAdmin,
            'total_allowance' => $total,
            'service_charge'  => $sc,
            'perday_rate' => $perDay,
            'payable_days' => $payableDays,
            'netpay'      => $netPay,
            'gst_amount'  => $gstAmount,
            'gross_total' => $grossTotal,
        ];

        $siteTotalNetPay  += $netPay;
        $siteTotalGst     += $gstAmount;
        $siteTotalGross   += $grossTotal;
        $siteTotalPresent += $stats['present'];
        $siteTotalAbsent  += $stats['absent'];
        $siteTotalLeave   += $stats['leave'];
        $siteTotalOvertime += $stats['overtime'];
        $siteTotalEmp     += $stats['employees'];
    }

    // ── 8. Build lpc_summary JSON ──
    $lpcSummary = json_encode([[
        'siteCode'     => $siteCode,
        'siteName'     => $siteName,
        'present'      => $siteTotalPresent,
        'absent'       => $siteTotalAbsent,
        'leave'        => $siteTotalLeave,
        'overtime'     => $siteTotalOvertime,
        'employees'    => $siteTotalEmp,
        'total_netpay' => $siteTotalNetPay,
        'gst_amount'   => $siteTotalGst,
        'grand_total'  => $siteTotalGross,
        'ranks'        => $ranks,
    ]]);

    // ── 9. Build lpc_workflow JSON ──
    $lpcWorkflow = json_encode([
        'current_step'    => 'SDHOD',
        'current_step_id' => 1,
        'steps' => [
            [
                'id'       => 1,
                'Code'     => 'SDHOD',
                'status'   => 'pending',
                'comment'  => null,
                'acted_by' => null,
                'acted_at' => null,
            ],
            [
                'id'       => 2,
                'Code'     => 'FINANCE',
                'status'   => 'pending',
                'comment'  => null,
                'acted_by' => null,
                'acted_at' => null,
            ],
        ],
    ]);

    // ── 10. Generate identifiers ──
    $secNo  = '12345678/' . str_pad($month, 2, '0', STR_PAD_LEFT) . date('Y');
    $dakNo  = 'SB' . rand(100000, 999999);

    // ── 11. Insert into lpc_master ──
    $stmtInsert = $pdo->prepare("
        INSERT INTO lpc_master 
        (lpc_area_code, sec_no, dak_no, lpc_month, lpc_year, created_lpc_date, lpc_summary, lpc_workflow)
        VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)
    ");
    $stmtInsert->execute([
        $siteCode,
        $secNo,
        $dakNo,
        $month,
        $year,
        $lpcSummary,
        $lpcWorkflow,
    ]);

    return true;
}

/**
 * Generate LPC for ALL sites that have been SDHOD-approved for a given month/year
 * but don't yet have an LPC record. Used by the MySQL event safety net.
 */
function generateAllPendingLpc(PDO $pdo, int $month, int $year): int
{
    $count = 0;

    // Find all attendance_approval rows where SDHOD approved (current_step = 'COMPLETE')
    $stmt = $pdo->prepare("
        SELECT area_code 
        FROM attendance_approval 
        WHERE attendance_month = ?
          AND attendance_year  = ?
          AND JSON_UNQUOTE(JSON_EXTRACT(attendance_workflow, '$.current_step')) = 'COMPLETE'
    ");
    $stmt->execute([$month, $year]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        if (generateLpcForSite($pdo, $row['area_code'], $month, $year)) {
            $count++;
        }
    }

    return $count;
}
