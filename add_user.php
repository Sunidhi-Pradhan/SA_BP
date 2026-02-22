<?php
// --------------------
// SHOW ERRORS (DEV ONLY)
// --------------------
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --------------------
// DB CONNECTION
// --------------------
require "config.php";

// --------------------
// PHPMailer
// --------------------
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require "PHPMailer/src/Exception.php";
require "PHPMailer/src/PHPMailer.php";
require "PHPMailer/src/SMTP.php";

// --------------------
// CHECK REQUEST
// --------------------
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: user.php");
    exit;
}

// --------------------
// GET & SANITIZE FORM DATA
// --------------------
$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$role     = trim($_POST['role'] ?? '');
$site_code = trim($_POST['site_code'] ?? '');


// --------------------
// VALIDATION
// --------------------
if (
    empty($name) ||
    empty($email) ||
    empty($password) ||
    empty($role) ||
    empty($site_code)
) {
    die("All fields are required");
}

// --------------------
// CHECK DUPLICATE EMAIL
// --------------------
$check = $pdo->prepare("SELECT id FROM user WHERE email = ?");
$check->execute([$email]);

if ($check->rowCount() > 0) {
    die("Email already exists");
}

// --------------------
// HASH PASSWORD
// --------------------
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// --------------------
// INSERT USER (AUTO_INCREMENT ID)
// --------------------
$stmt = $pdo->prepare(
        "INSERT INTO user (name, email, password, role, site_code)
        VALUES (?, ?, ?, ?, ?)"

);

$stmt->execute([
    $name,
    $email,
    $hashedPassword,
    $role,
    $site_code
]);

// GET GENERATED USER ID
$userId = $pdo->lastInsertId();

// Fetch Site Name
$siteStmt = $pdo->prepare("SELECT SiteName FROM site_master WHERE SiteCode = ?");
$siteStmt->execute([$site_code]);
$siteRow = $siteStmt->fetch(PDO::FETCH_ASSOC);
$siteName = $siteRow['SiteName'] ?? '';

// --------------------
// SEND EMAIL
// --------------------
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = "smtp.gmail.com";
    $mail->SMTPAuth   = true;
    $mail->Username   = "test.work3589@gmail.com";   // sender
    $mail->Password   = "qfwizcluoelbfxuh";          // app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom("test.work3589@gmail.com", "Admin Panel");
    $mail->addAddress($email, $name);

    $mail->isHTML(true);
    $mail->Subject = "Your Account Has Been Created";

    $mail->Body = "
        <h3>Hello $name 👋</h3>
        
        <p>You are <b>successfully added</b> to our system.</p>
        <p><b>Your User ID:</b> $userId</p>
        <p><b>Email:</b> $email</p>
        <p><b>Role:</b> $role</p>
        <p><b>Password:</b> $password</p>
        <p><b>Area:</b> $siteName</p>
        <br>
        <p>Regards,<br>Admin Panel</p>
    ";

    //     <table cellpadding='6'>
    //         <tr><td><b>User ID:</b></td><td>$userId</td></tr>
    //         <tr><td><b>Email:</b></td><td>$email</td></tr>
    //         <tr><td><b>Role:</b></td><td>$role</td></tr>
    //         <tr><td><b>Site:</b></td><td>$site</td></tr>
    //     </table>

    //     <p><b>⚠️ Please change your password after first login.</b></p>

    //     <br>
    //     <p>Regards,<br>Admin Panel</p>
    // ";

    $mail->send();
        echo "User added and Email sent successfully.";

} catch (Exception $e) {
    echo "User added successfully but Email failed";
    // Email failure should NOT block user creation
}
header("Location: user.php?success=1");
?>
<!-- // --------------------
// REDIRECT BACK WITH SUCCESS
// --------------------
header("Location: user.php?success=1");
exit; -->
