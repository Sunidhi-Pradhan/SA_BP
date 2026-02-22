<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require "PHPMailer/src/Exception.php";
require "PHPMailer/src/PHPMailer.php";
require "PHPMailer/src/SMTP.php";

$mail = new PHPMailer(true);

try {
    // SMTP settings
    $mail->isSMTP();
    $mail->Host = "smtp.gmail.com";
    $mail->SMTPAuth = true;
    $mail->Username = "test.work3589@gmail.com";   // YOUR GMAIL
    $mail->Password = "qfwi zclu oelb fxuh";    // 16-char app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Email details
    $mail->setFrom("test.work3589@gmail.com", "Test Mail");
    $mail->addAddress("sunidhipradhan16@gmail.com"); // send to yourself

    $mail->isHTML(true);
    $mail->Subject = "PHPMailer Test Email";
    $mail->Body = "
        <h2>Hello 👋</h2>
        <p>This is a test email.</p>
        <p>If you received this, Gmail SMTP is working successfully.</p>
    ";

    $mail->send();

    echo "✅ TEST EMAIL SENT SUCCESSFULLY";

} catch (Exception $e) {
    echo "❌ EMAIL FAILED: " . $mail->ErrorInfo;
}
