<?php
/**
 * SABP Chatbot — Floating Widget (Includable)
 * Include this file at the bottom of any page (before </body>)
 * to render the chatbot widget.
 *
 * Features: Voice Input, Text-to-Speech, Smart Quick Actions,
 *           Proactive Notifications, Export Chat as PDF, Bot Feedback Rating
 *
 * Usage:
 *   Root pages:  <?php include 'chatbot/chatbot_widget.php'; ?>
 *   Sub-dir:     <?php include __DIR__ . '/../chatbot/chatbot_widget.php'; ?>
 */

// Determine the relative path to the chatbot directory
$chatbotDir = '';
$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
$sabpRoot   = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$relPath    = str_replace($sabpRoot, '', dirname($scriptPath));
$depth      = substr_count(trim($relPath, '/'), '/') + (trim($relPath, '/') !== '' ? 1 : 0);
$chatbotDir = str_repeat('../', $depth) . 'chatbot/';
?>
<!-- ═══════════════════════════════════════════════════════════
     SABP CHATBOT WIDGET — Enhanced
     ═══════════════════════════════════════════════════════════ -->
<link rel="stylesheet" href="<?= $chatbotDir ?>chatbot.css">

<!-- Floating Action Button -->
<button class="chatbot-fab" id="chatbotFab" title="Chat with SABP Assistant" aria-label="Open chatbot">
  <span class="fab-icon"><i class="fa-solid fa-robot"></i></span>
  <span class="fab-close"><i class="fa-solid fa-xmark"></i></span>
  <span class="chatbot-badge" id="chatbotBadge"></span>
</button>

<!-- Chat Panel -->
<div class="chatbot-panel" id="chatbotPanel">

  <!-- Header -->
  <div class="chatbot-header">
    <div class="chatbot-avatar">🤖</div>
    <div class="chatbot-header-info">
      <div class="chatbot-header-title">SABP Assistant</div>
      <div class="chatbot-header-status">Always online</div>
    </div>
    <div class="chatbot-header-actions">
      <button class="chatbot-header-btn" id="chatbotPdfBtn" title="Export chat as PDF">
        <i class="fa-solid fa-file-pdf"></i>
      </button>
      <button class="chatbot-header-btn" id="chatbotClearBtn" title="Clear chat">
        <i class="fa-solid fa-trash-can"></i>
      </button>
      <button class="chatbot-header-btn" id="chatbotMinimize" title="Minimize">
        <i class="fa-solid fa-minus"></i>
      </button>
    </div>
  </div>

  <!-- Proactive Notification Bar -->
  <div class="chatbot-proactive" id="chatbotProactive" style="display:none;">
    <!-- Filled by JS -->
  </div>

  <!-- Message Body -->
  <div class="chatbot-body" id="chatbotBody">
    <!-- Messages rendered by JS -->
  </div>

  <!-- Typing Indicator -->
  <div class="chatbot-typing" id="chatbotTyping">
    <div class="chatbot-typing-avatar">🤖</div>
    <div class="chatbot-typing-dots">
      <span></span><span></span><span></span>
    </div>
  </div>

  <!-- Quick Suggestions -->
  <div class="chatbot-suggestions" id="chatbotSuggestions">
    <!-- Chips rendered by JS -->
  </div>

  <!-- Input Footer -->
  <div class="chatbot-footer">
    <button class="chatbot-voice-btn" id="chatbotVoiceBtn" title="Voice input (Speech-to-Text)" aria-label="Voice input">
      <i class="fa-solid fa-microphone"></i>
    </button>
    <textarea
      class="chatbot-input"
      id="chatbotInput"
      placeholder="Type or speak your message..."
      rows="1"
      maxlength="500"
    ></textarea>
    <button class="chatbot-send" id="chatbotSend" title="Send message" aria-label="Send">
      <i class="fa-solid fa-paper-plane"></i>
    </button>
  </div>

</div>

<!-- html2pdf.js CDN for PDF export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" defer></script>

<meta name="chatbot-api" content="<?= $chatbotDir ?>chatbot_api.php">
<script src="<?= $chatbotDir ?>chatbot.js"></script>
