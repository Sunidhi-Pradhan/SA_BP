<?php
try {
    // Database connection
    $pdo = new PDO("mysql:host=localhost;dbname=demo;charset=utf8mb4", "root", "");

    // Set error mode
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // SQL query
    $sql = "SELECT site_name, bes_no, dar_no, amount, gst, gross_total, it_tds, sgst, cgst, retention, bonus, net_payment 
            FROM monthly_lpp_report";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $data = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data[] = [
            "site" => $row["site_name"],
            "bes" => $row["bes_no"],
            "dar" => $row["dar_no"],
            "amount" => $row["amount"],
            "gst" => $row["gst"],
            "gross" => $row["gross_total"],
            "ittds" => $row["it_tds"],
            "sgst" => $row["sgst"],
            "cgst" => $row["cgst"],
            "ret" => $row["retention"],
            "bonus" => $row["bonus"],
            "net" => $row["net_payment"]
        ];
    }

    // Return JSON
    header('Content-Type: application/json');
    echo json_encode($data);

} catch (PDOException $e) {
    echo json_encode([
        "error" => "Database connection failed",
        "message" => $e->getMessage()
    ]);
}
?>