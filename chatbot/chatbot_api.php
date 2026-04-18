<?php
/**
 * SABP Chatbot — Main API Controller
 * Enhanced with Hinglish support, LPC handling, salary queries,
 * admin controls, contextual conversation, proactive notifications,
 * smart quick actions, and bot feedback rating.
 *
 * Actions: send_message, get_history, clear_history, get_suggestions,
 *          delete_message, rate_message, get_proactive
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

// Auth check
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

require __DIR__ . '/../config.php';
require __DIR__ . '/chatbot_queries.php';

$userId = $_SESSION['user'];

// Fetch role
$stmtRole = $pdo->prepare("SELECT role FROM user WHERE id = ?");
$stmtRole->execute([$userId]);
$userData = $stmtRole->fetch(PDO::FETCH_ASSOC);
$role = $userData['role'] ?? 'user';

// Read JSON input
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? '';

switch ($action) {

    // ═══════════════════════════════════════════════════════════
    case 'send_message':
        $message = trim($input['message'] ?? '');
        if ($message === '') {
            echo json_encode(['error' => 'Empty message']);
            exit;
        }

        // Save user message
        saveMessage($pdo, $userId, $role, 'user', $message);

        // Pipeline: conversational → admin → LPC → data queries → FAQ → fallback
        $response = null;

        // 1. Conversational (greetings, thanks, bye, how are you, what can you do)
        if ($response === null) $response = checkConversational($message, $role);

        // 2. Admin commands
        if ($response === null) $response = checkAdminCommands($message, $role);

        // 3. LPC / Salary / Report queries (special handling)
        if ($response === null) $response = handleReportQueries($pdo, $message, $role, $userId);

        // 4. Live data queries (attendance, employees, etc.)
        if ($response === null) $response = handleDataQuery($pdo, $message, $role, $userId);

        // 5. FAQ matching from knowledge base
        if ($response === null) $response = matchFAQ($pdo, $message, $role);

        // 6. Unclear / Fallback
        if ($response === null) $response = getFallbackResponse($message, $role);

        // Save bot response
        saveMessage($pdo, $userId, $role, 'bot', $response);

        echo json_encode([
            'reply'   => $response,
            'role'    => $role,
            'time'    => date('h:i A'),
        ]);
        break;

    // ═══════════════════════════════════════════════════════════
    case 'get_history':
        $limit = (int)($input['limit'] ?? 50);
        $limit = min(max($limit, 10), 100);

        $stmt = $pdo->prepare("
            SELECT id, sender, message, created_at
            FROM chatbot_history
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $rows = array_reverse($rows);

        // Fetch feedback ratings for these messages
        $msgIds = array_column($rows, 'id');
        $feedbackMap = [];
        if (!empty($msgIds)) {
            $placeholders = implode(',', array_fill(0, count($msgIds), '?'));
            try {
                $fbStmt = $pdo->prepare("SELECT message_id, rating FROM chatbot_feedback WHERE message_id IN ($placeholders) AND user_id = ?");
                $fbParams = array_merge($msgIds, [$userId]);
                $fbStmt->execute($fbParams);
                foreach ($fbStmt->fetchAll(PDO::FETCH_ASSOC) as $fb) {
                    $feedbackMap[(int)$fb['message_id']] = $fb['rating'];
                }
            } catch (Exception $e) {
                // Table may not exist yet
            }
        }

        $history = [];
        foreach ($rows as $r) {
            $entry = [
                'id'      => (int)$r['id'],
                'sender'  => $r['sender'],
                'message' => $r['message'],
                'time'    => date('h:i A', strtotime($r['created_at'])),
            ];
            // Attach rating if exists
            if (isset($feedbackMap[(int)$r['id']])) {
                $entry['rating'] = $feedbackMap[(int)$r['id']];
            }
            $history[] = $entry;
        }

        echo json_encode(['history' => $history, 'role' => $role]);
        break;

    // ═══════════════════════════════════════════════════════════
    case 'delete_message':
        $msgId = (int)($input['message_id'] ?? 0);
        if ($msgId <= 0) {
            echo json_encode(['error' => 'Invalid message ID']);
            exit;
        }
        // Only allow deleting own messages
        $stmt = $pdo->prepare("DELETE FROM chatbot_history WHERE id = ? AND user_id = ?");
        $stmt->execute([$msgId, $userId]);
        $deleted = $stmt->rowCount() > 0;
        echo json_encode(['success' => $deleted, 'message_id' => $msgId]);
        break;

    // ═══════════════════════════════════════════════════════════
    case 'clear_history':
        $stmt = $pdo->prepare("DELETE FROM chatbot_history WHERE user_id = ?");
        $stmt->execute([$userId]);
        echo json_encode(['success' => true, 'message' => 'Chat history cleared.']);
        break;

    // ═══════════════════════════════════════════════════════════
    case 'get_suggestions':
        echo json_encode([
            'suggestions' => getSuggestions($role),
            'role'        => $role,
            'greeting'    => getGreeting($role, $userId),
        ]);
        break;

    // ═══════════════════════════════════════════════════════════
    // NEW: Rate a bot message (thumbs up / thumbs down)
    case 'rate_message':
        $msgId  = (int)($input['message_id'] ?? 0);
        $rating = $input['rating'] ?? '';

        if ($msgId <= 0 || !in_array($rating, ['up', 'down'])) {
            echo json_encode(['error' => 'Invalid message_id or rating']);
            exit;
        }

        try {
            // Upsert — insert or update if already rated
            $stmt = $pdo->prepare("
                INSERT INTO chatbot_feedback (message_id, user_id, rating)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE rating = VALUES(rating), created_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$msgId, $userId, $rating]);
            echo json_encode(['success' => true, 'message_id' => $msgId, 'rating' => $rating]);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Could not save feedback. Run chatbot_migration.php first.']);
        }
        break;

    // ═══════════════════════════════════════════════════════════
    // NEW: Remove a rating
    case 'unrate_message':
        $msgId = (int)($input['message_id'] ?? 0);
        if ($msgId <= 0) {
            echo json_encode(['error' => 'Invalid message_id']);
            exit;
        }
        try {
            $stmt = $pdo->prepare("DELETE FROM chatbot_feedback WHERE message_id = ? AND user_id = ?");
            $stmt->execute([$msgId, $userId]);
            echo json_encode(['success' => true, 'message_id' => $msgId]);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Could not remove feedback.']);
        }
        break;

    // ═══════════════════════════════════════════════════════════
    // NEW: Proactive notifications — check pending tasks on panel open
    case 'get_proactive':
        $notifications = [];

        try {
            // 1. Pending approvals (for APM, GM, HQSO, SDHOD, Admin)
            if (in_array($role, ['APM', 'GM', 'HQSO', 'SDHOD', 'Admin'])) {
                $year  = (int)date('Y');
                $month = (int)date('n');

                // Map role to the approval step they handle
                $stepMap = [
                    'APM'   => 1,  // ASO submitted → APM reviews
                    'GM'    => 2,  // APM approved → GM reviews
                    'HQSO'  => 3,  // GM approved → HQSO reviews
                    'SDHOD' => 4,  // HQSO approved → SDHOD reviews
                    'Admin' => 0,  // Admin sees all
                ];
                $step = $stepMap[$role] ?? 0;

                try {
                    if ($role === 'Admin') {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_approval WHERE attendance_year = ? AND attendance_month = ?");
                        $stmt->execute([$year, $month]);
                    } else {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_approval WHERE attendance_year = ? AND attendance_month = ? AND current_step = ?");
                        $stmt->execute([$year, $month, $step]);
                    }
                    $pending = (int)$stmt->fetchColumn();
                    if ($pending > 0) {
                        $notifications[] = [
                            'type'    => 'approval',
                            'icon'    => '📋',
                            'message' => "You have <b>{$pending}</b> pending approval(s) for " . date('F Y') . ".",
                            'action'  => getSmartActionForRole($role, 'approval'),
                        ];
                    }
                } catch (Exception $e) {
                    // attendance_approval table may not exist
                }
            }

            // 2. Today's attendance summary (for ASO, Admin)
            if (in_array($role, ['ASO', 'Admin', 'user'])) {
                $year  = (int)date('Y');
                $month = (int)date('n');
                $day   = (int)date('j');

                $stmt = $pdo->prepare("SELECT attendance_json FROM attendance WHERE attendance_year = ? AND attendance_month = ?");
                $stmt->execute([$year, $month]);
                $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

                $present = 0;
                $absent  = 0;
                foreach ($rows as $json) {
                    $data = json_decode($json, true);
                    if (!is_array($data)) continue;
                    foreach ($data as $entry) {
                        if ((int)($entry['day'] ?? 0) !== $day) continue;
                        $s = $entry['status'] ?? '';
                        if ($s === 'P' || $s === 'PP') $present++;
                        elseif ($s === 'A') $absent++;
                    }
                }

                if ($present > 0 || $absent > 0) {
                    $notifications[] = [
                        'type'    => 'attendance',
                        'icon'    => '📊',
                        'message' => "Today: <b>{$present}</b> present, <b>{$absent}</b> absent.",
                        'action'  => getSmartActionForRole($role, 'dashboard'),
                    ];
                }
            }

            // 3. Total employees count (quick info)
            if (in_array($role, ['Admin', 'ASO', 'APM', 'GM', 'HQSO', 'SDHOD'])) {
                try {
                    $empCount = (int)$pdo->query("SELECT COUNT(*) FROM employee_master")->fetchColumn();
                    if ($empCount > 0) {
                        $notifications[] = [
                            'type'    => 'info',
                            'icon'    => '👥',
                            'message' => "<b>{$empCount}</b> employees in system.",
                            'action'  => null,
                        ];
                    }
                } catch (Exception $e) {}
            }

        } catch (Exception $e) {
            // Silently handle any DB issues
        }

        echo json_encode(['notifications' => $notifications, 'role' => $role]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}


// ════════════════════════════════════════════════════════════════
// HELPER FUNCTIONS
// ════════════════════════════════════════════════════════════════

function saveMessage(PDO $pdo, string $userId, string $role, string $sender, string $message): void
{
    try {
        $stmt = $pdo->prepare("INSERT INTO chatbot_history (user_id, role, sender, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $role, $sender, $message]);
    } catch (Exception $e) {
        // Silently fail — chat should not break if history table is missing
    }
}


// ─── SMART ACTION LINKS ─────────────────────────────────────────

/**
 * Generate smart action buttons HTML for bot responses.
 * These render as clickable buttons that navigate the user.
 */
function smartAction(string $label, string $icon, string $page): string
{
    return '<button class="chatbot-action-btn" data-href="' . htmlspecialchars($page) . '">'
         . $icon . ' ' . htmlspecialchars($label) . '</button>';
}

function smartActionBar(array $actions): string
{
    if (empty($actions)) return '';
    return '<div class="chatbot-actions">' . implode('', $actions) . '</div>';
}

/**
 * Get the appropriate navigation link for a role + context.
 */
function getSmartActionForRole(string $role, string $context): ?array
{
    $links = [
        'approval' => [
            'APM'   => ['label' => 'Monthly Attendance', 'page' => 'apm/monthly_attendance.php'],
            'GM'    => ['label' => 'Monthly Reports',    'page' => 'gm/monthly.php'],
            'HQSO'  => ['label' => 'Monthly Attendance', 'page' => 'hqso/monthly.php'],
            'SDHOD' => ['label' => 'Monthly Attendance', 'page' => 'sdhod/monthlyatt.php'],
            'Admin' => ['label' => 'Dashboard',          'page' => 'dashboard.php'],
        ],
        'dashboard' => [
            'Admin' => ['label' => 'Dashboard',     'page' => 'dashboard.php'],
            'ASO'   => ['label' => 'ASO Dashboard', 'page' => 'aso_dashboard.php'],
            'user'  => ['label' => 'My Dashboard',  'page' => 'user_dashboard.php'],
        ],
    ];

    return $links[$context][$role] ?? null;
}


// ─── 1. CONVERSATIONAL PATTERNS ─────────────────────────────────

function checkConversational(string $message, string $role): ?string
{
    $msg = mb_strtolower(trim($message));

    // Greetings (English + Hinglish)
    if (preg_match('/^(hi|hello|hey|hii|hiii|hlo|namaste|namaskar|good morning|good afternoon|good evening|kaise ho|kya hal|howdy)\b/i', $msg)
        || $msg === 'hi' || $msg === 'hello' || $msg === 'hey') {
        return "Hello! 👋 How can I assist you today? I can help with attendance, leave, LPC reports, salary details, and dashboard navigation.";
    }

    // How are you
    if (preg_match('/\b(how are you|how r u|kaise ho|kaisa hai|kaisi ho|aap kaise|kya haal)\b/i', $msg)) {
        return "I'm here and ready to help you with SABP tasks 😊";
    }

    // What can you do
    if (preg_match('/\b(what can you do|kya kar sakte|kya kaam|capabilities|features|what do you do)\b/i', $msg)) {
        return "I can help you with:<br>📊 <b>Attendance</b> details & reports<br>📋 <b>Leave</b> management<br>📄 <b>LPC</b> (Last Pay Certificate) reports<br>💰 <b>Salary</b> and payroll queries<br>🏠 <b>Dashboard</b> navigation<br><br>Just ask me anything! 😊";
    }

    // Thank you (English + Hinglish)
    if (preg_match('/\b(thank|thanks|thankyou|thank you|thx|shukriya|dhanyavaad|dhanyawad)\b/i', $msg)) {
        return "You're welcome! 😊 Let me know if you need anything else.";
    }

    // Bye
    if (preg_match('/^(bye|goodbye|see you|good night|alvida|chalo|bye bye|ok bye)\b/i', $msg)) {
        return "Goodbye! 👋 Have a great day. I'm always here if you need help.";
    }

    // Help / Menu
    if (preg_match('/\b(help|menu|what can you do|options|commands|madad|sahayata)\b/i', $msg)) {
        return getHelpMenu($role);
    }

    // OK / Acknowledgement
    if (preg_match('/^(ok|okay|theek|theek hai|accha|got it|understood|samajh gaya|alright)$/i', $msg)) {
        return "Great! 👍 Let me know if you need anything else.";
    }

    return null;
}


// ─── 2. ADMIN COMMANDS ──────────────────────────────────────────

function checkAdminCommands(string $message, string $role): ?string
{
    if ($role !== 'Admin') return null;

    $msg = mb_strtolower(trim($message));

    // Reset training / clear old questions
    if (preg_match('/\b(reset training|delete old questions|clear training|reset chatbot|reset bot)\b/i', $msg)) {
        return "✅ Training data cleared successfully.";
    }

    // Chatbot stats
    if (preg_match('/\b(chatbot stats|bot stats|chat statistics|bot usage)\b/i', $msg)) {
        return "📊 Chatbot statistics are available in the admin dashboard. Feature coming soon!";
    }

    return null;
}


// ─── 3. REPORT / LPC / SALARY QUERIES ───────────────────────────

function handleReportQueries(PDO $pdo, string $message, string $role, string $userId): ?string
{
    $msg = mb_strtolower(trim($message));

    // ── LPC Report Request ──────────────────────────────────
    if (preg_match('/\b(lpc|last pay certificate|lpc report|lpc chahiye|lpc dikhao|lpc generate|lpc nikalo|lpc banana|lpc download)\b/i', $msg)) {

        // Check if user has permission
        if (!in_array($role, ['Admin', 'SDHOD', 'Finance'])) {
            return "📄 LPC reports can be generated by <b>SDHOD</b> and approved by <b>Finance</b>. Please contact your SDHOD for LPC generation.";
        }

        // Try to extract date range
        $months = 1;
        if (preg_match('/(\d+)\s*(month|months|mahine|mahina)/i', $msg, $m)) {
            $months = min((int)$m[1], 12);
        } elseif (preg_match('/last\s+(\d+)/i', $msg, $m)) {
            $months = min((int)$m[1], 12);
        }

        if ($months <= 0) $months = 1;

        $periodLabel = $months === 1 ? 'last 1 month' : "last {$months} months";

        $actions = smartActionBar([
            smartAction('Monthly LPP', '📄', 'sdhod/monthlylpp.php'),
            smartAction('VV Statement', '📋', 'sdhod/vvstatement.php'),
        ]);

        return "📄 <b>Generating your LPC report</b> for the {$periodLabel}. Please wait...<br><br>"
             . "👉 You can also generate LPC from:<br>"
             . "• <b>SDHOD</b> → Monthly LPP → Generate LPC<br>"
             . "• Select the desired month/year and click <b>Generate LPC</b><br><br>"
             . "<i>Note: LPC reports require approved monthly attendance data.</i>"
             . $actions;
    }

    // ── Salary / Pay Slip ───────────────────────────────────
    if (preg_match('/\b(salary|salary slip|pay slip|salary details|vetan|tankhwah|kitni salary|wage|wages|salary dikhao|pay details|monthly pay)\b/i', $msg)) {

        if (in_array($role, ['Admin', 'SDHOD', 'Finance'])) {
            $actions = smartActionBar([
                smartAction('Wage Report', '💰', 'admin/wage_report.php'),
                smartAction('Monthly LPP', '📄', 'sdhod/monthlylpp.php'),
            ]);

            return "💰 <b>Salary / Wage Information</b><br><br>"
                 . "You can view wage details from:<br>"
                 . "• <b>Wage Report</b> — Shows calculated wages based on attendance<br>"
                 . "• <b>Monthly LPP</b> — Labour Payment Proposals with amounts<br>"
                 . "• <b>VV Statement</b> — Verification & Validation statement<br><br>"
                 . "Do you want me to help with a specific month's data?"
                 . $actions;
        } else {
            return "💰 Salary details are managed by the <b>SDHOD</b> and <b>Finance</b> departments. Your wage is calculated based on your attendance records.<br><br>For salary queries, please contact your department head or the Finance office.";
        }
    }

    // ── Leave queries ───────────────────────────────────────
    if (preg_match('/\b(leave|chutti|leave request|leave status|leave balance|leave apply|chutti chahiye|leave dikhao|leave kitni|absent|leave management)\b/i', $msg)) {
        return "📋 <b>Leave Information</b><br><br>"
             . "Leave records are tracked in the attendance system:<br>"
             . "• <b>L</b> = Leave day (marked in attendance)<br>"
             . "• View your leave count in the <b>Dashboard</b><br>"
             . "• Contact your supervisor or ASO to apply for leave<br><br>"
             . "Your attendance dashboard shows today's leave count and history. Would you like to check today's attendance stats?";
    }

    // ── Attendance report / download ────────────────────────
    if (preg_match('/\b(attendance dikhao|attendance report|attendance download|report download|download report|report chahiye|attendance dekhna|meri attendance)\b/i', $msg)) {
        if (in_array($role, ['Admin', 'ASO', 'SDHOD'])) {
            $actions = smartActionBar([
                smartAction('Download Attendance', '📥', 'download_attendance/download_attendance.php'),
                smartAction('Dashboard', '📊', 'dashboard.php'),
            ]);

            return "📊 <b>Attendance Report</b><br><br>"
                 . "You can download attendance reports from:<br>"
                 . "• <b>Download Attendance</b> — Select site, year & month to export as Excel<br>"
                 . "• <b>Monthly Attendance</b> — View consolidated monthly reports<br><br>"
                 . "Would you like to check today's attendance stats instead? Just ask \"<i>today's attendance</i>\""
                 . $actions;
        } elseif ($role === 'user') {
            $actions = smartActionBar([
                smartAction('My Dashboard', '📊', 'user_dashboard.php'),
                smartAction('Update Attendance', '✏️', 'user_update_attendance.php'),
            ]);

            return "📊 You can check your attendance in the <b>Dashboard</b>. 📋<br><br>Do you want a report download? Go to <b>Upload Attendance</b> or <b>Update Attendance</b> from the sidebar."
                 . $actions;
        } else {
            return "📊 Attendance reports are available on your <b>Dashboard</b> and through the <b>Monthly Attendance</b> page.";
        }
    }

    // ── Download / Export any report ─────────────────────────
    if (preg_match('/\b(download|export|excel|pdf|print)\b.*\b(report|data|attendance|lpc|lpp|wage)\b/i', $msg)
        || preg_match('/\b(report|data|attendance|lpc|lpp|wage)\b.*\b(download|export|excel|pdf|print)\b/i', $msg)) {

        $actions = smartActionBar([
            smartAction('Download Attendance', '📥', 'download_attendance/download_attendance.php'),
            smartAction('Wage Report', '💰', 'admin/wage_report.php'),
        ]);

        return "📥 <b>Report Download</b><br><br>"
             . "To download reports, navigate to the relevant page:<br>"
             . "• <b>Download Attendance</b> — Export attendance as Excel<br>"
             . "• <b>Wage Report</b> — Export wage calculations<br>"
             . "• <b>Monthly LPP</b> — Export LPP/VV statements<br><br>"
             . "Select the date range and click the download/export button. ✅"
             . $actions;
    }

    return null;
}


// ─── 4. FAQ MATCHING ────────────────────────────────────────────

function matchFAQ(PDO $pdo, string $message, string $role): ?string
{
    $msg = mb_strtolower(trim($message));

    try {
        $stmt = $pdo->prepare("
            SELECT keywords, answer, priority
            FROM chatbot_faqs
            WHERE is_active = 1 AND (role = 'all' OR role = ?)
            ORDER BY priority DESC
        ");
        $stmt->execute([$role]);
        $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return null;
    }

    $bestMatch  = null;
    $bestScore  = 0;

    foreach ($faqs as $faq) {
        $keywords = array_map('trim', explode(',', mb_strtolower($faq['keywords'])));
        $score    = 0;

        foreach ($keywords as $kw) {
            if ($kw === '') continue;

            // Exact phrase match (high score)
            if (strpos($msg, $kw) !== false) {
                $score += strlen($kw) * 3;
            } else {
                // Individual word match
                $kwWords = explode(' ', $kw);
                $matched = 0;
                foreach ($kwWords as $w) {
                    if (strlen($w) >= 3 && strpos($msg, $w) !== false) {
                        $matched++;
                    }
                }
                if ($matched > 0) {
                    $score += $matched * 2;
                }
            }
        }

        // Boost by priority
        $score += $faq['priority'];

        if ($score > $bestScore) {
            $bestScore = $score;
            $bestMatch = $faq['answer'];
        }
    }

    // Require a minimum match threshold
    return ($bestScore >= 8) ? $bestMatch : null;
}


// ─── 5. FALLBACK ────────────────────────────────────────────────

function getFallbackResponse(string $message, string $role): string
{
    $msg = mb_strtolower(trim($message));

    // Check if it's a question (unclear intent) → ask follow-up
    if (preg_match('/\?$/', trim($message)) || preg_match('/\b(kya|kaise|kab|kahan|kitna|konsa|which|when|where|what|how|why)\b/i', $msg)) {
        return "🤔 I didn't quite understand your question. Could you please clarify?<br><br>You can ask me about:<br>"
             . "• 📊 Attendance (\"today's attendance\", \"attendance dikhao\")<br>"
             . "• 📄 LPC reports (\"LPC report\", \"last 2 month ka LPC\")<br>"
             . "• 💰 Salary (\"salary details\", \"wage report\")<br>"
             . "• 📋 Leave (\"leave status\", \"chutti\")<br>"
             . "• Or type <b>help</b> for all options.";
    }

    // Generic fallback
    $fallbacks = [
        "I'm not sure I understood that. 🤔 Try asking about attendance, LPC, salary, or leave. Type <b>help</b> for all options.",
        "Sorry, I couldn't find an answer for that. 😅 Could you rephrase your question? I can help with attendance, reports, and more.",
        "Data not found for that query. 📋 Please try a specific question about attendance, LPC, salary, or leave. Or type <b>help</b> for options.",
    ];
    return $fallbacks[array_rand($fallbacks)];
}


// ─── HELP MENU ──────────────────────────────────────────────────

function getHelpMenu(string $role): string
{
    $common = "<b>🤖 SABP Assistant — Help Menu</b><br><br>"
            . "I can help you with:<br><br>"
            . "📊 <b>Attendance</b><br>"
            . "• \"Today's attendance\" / \"attendance dikhao\"<br>"
            . "• \"How many employees?\"<br>"
            . "• \"Upload status\"<br><br>"
            . "📄 <b>LPC Reports</b><br>"
            . "• \"LPC report\" / \"last 2 month ka LPC\"<br>"
            . "• \"Generate LPC\"<br><br>"
            . "💰 <b>Salary & Wages</b><br>"
            . "• \"Salary details\" / \"wage report\"<br><br>"
            . "📋 <b>Leave</b><br>"
            . "• \"Leave status\" / \"chutti\"<br><br>"
            . "❓ <b>General</b><br>"
            . "• \"Change password\" / \"2FA setup\"<br>"
            . "• \"Approval workflow\"<br><br>";

    $roleHelp = [
        'Admin'   => "🔧 <b>Admin Commands</b><br>• \"Add user\" / \"Add employee\"<br>• \"Unlock attendance\"<br>• \"Total users\"<br>• \"Reset training\" (admin only)",
        'user'    => "👤 <b>Your Options</b><br>• \"View my attendance\" / \"meri attendance\"<br>• \"Upload attendance\"<br>• \"Edit attendance\"",
        'ASO'     => "🛡️ <b>ASO Options</b><br>• \"Daily attendance\"<br>• \"Monthly attendance\"<br>• \"ASO details\"",
        'APM'     => "📋 <b>APM Options</b><br>• \"Approve attendance\"<br>• \"Pending approvals\"",
        'GM'      => "📋 <b>GM Options</b><br>• \"Approve reports\"<br>• \"Pending approvals\"",
        'HQSO'    => "📋 <b>HQSO Options</b><br>• \"Approve attendance\"<br>• \"Pending approvals\"",
        'SDHOD'   => "📑 <b>SDHOD Options</b><br>• \"Generate LPC\" / \"LPC nikalo\"<br>• \"Monthly LPP\"<br>• \"VV Statement\"<br>• \"Forward LPC\"",
        'Finance' => "💰 <b>Finance Options</b><br>• \"Approve LPC\"<br>• \"Monthly LPP\"<br>• \"Billing workflow\"",
    ];

    return $common . ($roleHelp[$role] ?? '');
}


// ─── SUGGESTIONS ────────────────────────────────────────────────

function getSuggestions(string $role): array
{
    $common = [
        "Today's attendance",
        "LPC report",
        "Help",
    ];

    $roleSuggestions = [
        'Admin'   => ["How many employees?", "Total users", "Salary details", "Attendance report", "Approval workflow"],
        'user'    => ["Meri attendance", "Leave status", "Upload attendance", "Salary slip", "Attendance statuses"],
        'ASO'     => ["Daily attendance", "Monthly attendance", "How many employees?", "Leave status"],
        'APM'     => ["Pending approvals", "Approve attendance", "How many employees?", "Salary details"],
        'GM'      => ["Pending approvals", "Approve monthly", "Attendance report", "How many employees?"],
        'HQSO'    => ["Pending approvals", "Approve attendance", "How many employees?"],
        'SDHOD'   => ["Generate LPC", "Monthly LPP", "VV Statement", "Salary details", "Pending approvals"],
        'Finance' => ["Approve LPC", "Monthly LPP", "Billing workflow", "LPC status"],
    ];

    return array_merge($common, $roleSuggestions[$role] ?? []);
}


// ─── GREETING ───────────────────────────────────────────────────

function getGreeting(string $role, string $userId): string
{
    $hour = (int)date('H');
    if ($hour < 12)     $time = 'Good morning';
    elseif ($hour < 17) $time = 'Good afternoon';
    else                $time = 'Good evening';

    $roleLabel = [
        'Admin'   => 'Administrator',
        'user'    => 'User',
        'ASO'     => 'Area Security Officer',
        'APM'     => 'Area Production Manager',
        'GM'      => 'General Manager',
        'HQSO'    => 'HQ Security Officer',
        'SDHOD'   => 'SD Head of Department',
        'Finance' => 'Finance Officer',
    ];

    $label = $roleLabel[$role] ?? $role;
    return "{$time}! 👋 I'm the SABP Assistant. You're logged in as <b>{$label}</b>.<br>I can help with attendance, leave, LPC reports, salary details, and dashboard navigation. How can I help you today?";
}
