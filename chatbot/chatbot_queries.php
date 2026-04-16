<?php
/**
 * SABP Chatbot — Live Data Queries
 * Executes safe, read-only DB queries based on user role.
 * Supports English + Hinglish query patterns.
 */

function handleDataQuery(PDO $pdo, string $message, string $role, string $userId): ?string
{
    $msg = mb_strtolower(trim($message));

    // ── Total employees ──────────────────────────────────────
    if (preg_match('/\b(how many|total|count|kitne|kitni)\b.*\b(employee|staff|worker|personnel|karmchari|log|employees)\b/i', $msg)
        || preg_match('/\bemployee.*(count|total|number|kitne)\b/i', $msg)) {

        if (!in_array($role, ['Admin','ASO','APM','GM','HQSO','SDHOD','Finance'])) {
            return "Sorry, you don't have permission to view employee counts.";
        }
        $count = (int)$pdo->query("SELECT COUNT(*) FROM employee_master")->fetchColumn();
        return "📊 There are currently <b>{$count}</b> employees registered in the system.";
    }

    // ── Total users ──────────────────────────────────────────
    if (preg_match('/\b(how many|total|count|kitne)\b.*\b(user|login|account|users)\b/i', $msg)
        || preg_match('/\buser.*(count|total|number|kitne)\b/i', $msg)) {

        if ($role !== 'Admin') {
            return "Sorry, only Admins can view user account counts.";
        }
        $count = (int)$pdo->query("SELECT COUNT(*) FROM user")->fetchColumn();
        return "👥 There are currently <b>{$count}</b> registered user accounts.";
    }

    // ── Today's attendance ───────────────────────────────────
    if (preg_match('/\b(today|todays|today\'s|aaj|aaj ka|aaj ki)\b.*\b(attendance|hajri|present|absent|upasthiti)\b/i', $msg)
        || preg_match('/\b(attendance|hajri|upasthiti)\b.*\b(today|aaj)\b/i', $msg)
        || $msg === "today's attendance" || $msg === 'aaj ki attendance') {

        $year  = (int)date('Y');
        $month = (int)date('n');
        $day   = (int)date('j');

        $stmt = $pdo->prepare("SELECT attendance_json FROM attendance WHERE attendance_year = ? AND attendance_month = ?");
        $stmt->execute([$year, $month]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $present = 0; $absent = 0; $leave = 0; $overtime = 0;
        foreach ($rows as $json) {
            $data = json_decode($json, true);
            if (!is_array($data)) continue;
            foreach ($data as $entry) {
                if ((int)($entry['day'] ?? 0) !== $day) continue;
                $s = $entry['status'] ?? '';
                if ($s === 'P')       $present++;
                elseif ($s === 'PP') { $present++; $overtime++; }
                elseif ($s === 'A')   $absent++;
                elseif ($s === 'L')   $leave++;
            }
        }
        $total = $present + $absent + $leave;
        $date  = date('d M Y');
        return "📅 <b>Today's Attendance</b> ({$date}):<br>"
             . "✅ Present: <b>{$present}</b><br>"
             . "❌ Absent: <b>{$absent}</b><br>"
             . "📋 Leave: <b>{$leave}</b><br>"
             . "⏰ Overtime: <b>{$overtime}</b><br>"
             . "📊 Total recorded: <b>{$total}</b>";
    }

    // ── Pending approvals ────────────────────────────────────
    if (preg_match('/\b(pending|waiting|unapproved|baaki)\b.*\b(approval|approve|report)\b/i', $msg)
        || preg_match('/\bapproval.*(pending|status|count|kitne)\b/i', $msg)) {

        if (!in_array($role, ['APM','GM','HQSO','SDHOD','Admin'])) {
            return "Approval tracking is available for APM, GM, HQSO, SDHOD, and Admin roles.";
        }

        $year  = (int)date('Y');
        $month = (int)date('n');
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_approval WHERE attendance_year = ? AND attendance_month = ?");
            $stmt->execute([$year, $month]);
            $total = (int)$stmt->fetchColumn();
            $monthName = date('F Y');
            return "📋 <b>Monthly Reports</b> ({$monthName}):<br>Total reports in system: <b>{$total}</b><br><br>Check the <b>Monthly Attendance</b> page for detailed approval status.";
        } catch (Exception $e) {
            return "📋 Approval data is not available at this time.";
        }
    }

    // ── Sites info ───────────────────────────────────────────
    if (preg_match('/\b(how many|total|count|list|kitne)\b.*\b(site|location|area|jagah)\b/i', $msg)
        || preg_match('/\bsite.*(count|total|number|list|kitne)\b/i', $msg)) {

        if (!in_array($role, ['Admin','ASO','APM','GM','HQSO','SDHOD','Finance'])) {
            return "Sorry, you don't have permission to view site information.";
        }
        try {
            $count = (int)$pdo->query("SELECT COUNT(*) FROM site_master")->fetchColumn();
            $sites = $pdo->query("SELECT SiteCode, SiteName FROM site_master ORDER BY SiteName LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
            $list = '';
            foreach ($sites as $s) {
                $list .= "• <b>{$s['SiteCode']}</b> — {$s['SiteName']}<br>";
            }
            $more = $count > 10 ? "<br><i>...and " . ($count - 10) . " more sites.</i>" : '';
            return "🏭 There are <b>{$count}</b> sites registered:<br><br>{$list}{$more}";
        } catch (Exception $e) {
            return "Site information is not available at this time.";
        }
    }

    // ── Upload stats (this month) ────────────────────────────
    if (preg_match('/\b(upload|uploaded)\b.*\b(attendance|status|count|this month)\b/i', $msg)
        || preg_match('/\battendance.*(upload|submitted)\b/i', $msg)) {

        $year  = (int)date('Y');
        $month = (int)date('n');
        $stmt  = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE attendance_year = ? AND attendance_month = ?");
        $stmt->execute([$year, $month]);
        $uploads = (int)$stmt->fetchColumn();
        $monthName = date('F Y');
        return "📤 <b>Attendance Uploads</b> for {$monthName}:<br>Total uploads: <b>{$uploads}</b> records.";
    }

    // ── LPC / LPP count info ─────────────────────────────────
    if (preg_match('/\b(lpc|lpp)\b.*\b(status|count|total|generated|how many|kitne)\b/i', $msg)
        || preg_match('/\b(how many|total|kitne)\b.*\b(lpc|lpp)\b/i', $msg)) {

        if (!in_array($role, ['SDHOD','Finance','Admin'])) {
            return "LPC/LPP information is available for SDHOD, Finance, and Admin roles.";
        }
        try {
            $count = (int)$pdo->query("SELECT COUNT(*) FROM lpp_records")->fetchColumn();
            $paid  = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM lpp_records WHERE status='paid'")->fetchColumn();
            return "💰 <b>LPC/LPP Summary</b>:<br>Total records: <b>{$count}</b><br>Total paid amount: <b>₹" . number_format($paid, 2) . "</b>";
        } catch (Exception $e) {
            return "LPC/LPP data is not available yet. The LPP module may not be fully set up.";
        }
    }

    // ── Dashboard navigation ─────────────────────────────────
    if (preg_match('/\b(dashboard|home|main page|mukhya|homepage)\b/i', $msg)
        && preg_match('/\b(go|open|navigate|take me|dikhao|khole|show)\b/i', $msg)) {
        return "🏠 You are on the <b>Dashboard</b>! It shows key metrics like attendance stats, employee counts, charts, and approval status.<br><br>Use the <b>sidebar menu</b> on the left to navigate to different modules.";
    }

    // No matching data query
    return null;
}
