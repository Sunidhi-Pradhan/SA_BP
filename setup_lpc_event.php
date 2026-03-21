<?php
/**
 * setup_lpc_event.php
 * Run this once to create the MySQL scheduled event for LPC generation.
 * This is a safety-net event that runs daily to catch any LPC records
 * missed by the PHP trigger in monthlyatt.php.
 */
require "config.php";

try {
    // Drop old broken event
    $pdo->exec("DROP EVENT IF EXISTS evt_generate_monthly_lpp");
    echo "Dropped old event: evt_generate_monthly_lpp\n";

    // Create new LPC generation event
    $sql = "
    CREATE EVENT IF NOT EXISTS generate_monthly_lpc
    ON SCHEDULE EVERY 1 DAY
    STARTS CURRENT_TIMESTAMP
    ON COMPLETION PRESERVE
    ENABLE
    DO
    BEGIN
        -- For each SDHOD-approved attendance that has no matching LPC record
        INSERT INTO lpc_master (lpc_area_code, sec_no, dak_no, lpc_month, lpc_year, created_lpc_date, lpc_summary, lpc_workflow)
        SELECT 
            aa.area_code,
            CONCAT('12345678/', LPAD(aa.attendance_month, 2, '0'), aa.attendance_year),
            CONCAT('SB', FLOOR(100000 + RAND() * 900000)),
            aa.attendance_month,
            aa.attendance_year,
            NOW(),
            -- Basic lpc_summary placeholder (detailed calculation done by PHP)
            JSON_ARRAY(JSON_OBJECT(
                'siteCode', aa.area_code,
                'siteName', COALESCE(sm.SiteName, aa.area_code),
                'present', 0,
                'absent', 0,
                'leave', 0,
                'overtime', 0,
                'employees', 0,
                'total_netpay', 0,
                'gst_amount', 0,
                'grand_total', 0,
                'ranks', JSON_ARRAY()
            )),
            JSON_OBJECT(
                'current_step', 'SDHOD',
                'current_step_id', 1,
                'steps', JSON_ARRAY(
                    JSON_OBJECT('id', 1, 'Code', 'SDHOD', 'status', 'pending', 'comment', NULL, 'acted_by', NULL, 'acted_at', NULL),
                    JSON_OBJECT('id', 2, 'Code', 'FINANCE', 'status', 'pending', 'comment', NULL, 'acted_by', NULL, 'acted_at', NULL)
                )
            )
        FROM attendance_approval aa
        LEFT JOIN site_master sm ON sm.SiteCode = aa.area_code
        WHERE JSON_UNQUOTE(JSON_EXTRACT(aa.attendance_workflow, '$.current_step')) = 'COMPLETE'
          AND NOT EXISTS (
              SELECT 1 FROM lpc_master lm 
              WHERE lm.lpc_area_code = aa.area_code 
                AND lm.lpc_month = aa.attendance_month 
                AND lm.lpc_year = aa.attendance_year
          );
    END
    ";

    $pdo->exec($sql);
    echo "Created event: generate_monthly_lpc (ENABLED, DAILY)\n";
    echo "\nDone! The event will run daily as a safety net.\n";
    echo "Primary LPC generation happens via PHP trigger in monthlyatt.php.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
