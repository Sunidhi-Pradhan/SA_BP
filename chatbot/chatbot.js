/**
 * SABP Chatbot — Client-Side Logic
 * Handles chat panel toggle, API communication, message rendering,
 * single message deletion, and Hinglish input support.
 */
(function () {
  'use strict';

  // ── DOM refs ────────────────────────────────────────────────
  const fab         = document.getElementById('chatbotFab');
  const badge       = document.getElementById('chatbotBadge');
  const panel       = document.getElementById('chatbotPanel');
  const body        = document.getElementById('chatbotBody');
  const input       = document.getElementById('chatbotInput');
  const sendBtn     = document.getElementById('chatbotSend');
  const typingEl    = document.getElementById('chatbotTyping');
  const sugWrap     = document.getElementById('chatbotSuggestions');
  const clearBtn    = document.getElementById('chatbotClearBtn');
  const minimizeBtn = document.getElementById('chatbotMinimize');

  if (!fab || !panel) return;

  const API_URL = (function () {
    var scripts = document.querySelectorAll('script[src*="chatbot.js"]');
    if (scripts.length > 0) {
      var src = scripts[scripts.length - 1].getAttribute('src');
      return src.replace('chatbot.js', 'chatbot_api.php');
    }
    return document.querySelector('meta[name="chatbot-api"]')?.content
      || 'chatbot/chatbot_api.php';
  })();

  var isOpen     = false;
  var isLoaded   = false;
  var unread     = 0;
  var msgIdCounter = 0; // local counter for messages sent in this session

  // ── Toggle panel ───────────────────────────────────────────
  fab.addEventListener('click', function () {
    isOpen = !isOpen;
    panel.classList.toggle('open', isOpen);
    fab.classList.toggle('active', isOpen);

    if (isOpen) {
      unread = 0;
      badge.classList.remove('show');
      badge.textContent = '';

      if (!isLoaded) {
        loadChat();
        isLoaded = true;
      }

      setTimeout(function () { input.focus(); }, 350);
    }
  });

  // Minimize button
  if (minimizeBtn) {
    minimizeBtn.addEventListener('click', function () {
      isOpen = false;
      panel.classList.remove('open');
      fab.classList.remove('active');
    });
  }

  // ── Clear history ──────────────────────────────────────────
  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      if (!confirm('Clear all chat history?')) return;
      apiCall('clear_history').then(function () {
        body.innerHTML = '';
        addWelcome('Chat history cleared. How can I help you?');
      });
    });
  }

  // ── Send message ───────────────────────────────────────────
  sendBtn.addEventListener('click', send);
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      send();
    }
  });

  // Auto-resize textarea
  input.addEventListener('input', function () {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 80) + 'px';
  });

  // ── Escape to close ────────────────────────────────────────
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && isOpen) {
      isOpen = false;
      panel.classList.remove('open');
      fab.classList.remove('active');
    }
  });

  // ── Core: send message ─────────────────────────────────────
  function send() {
    var text = input.value.trim();
    if (!text) return;

    addMessage('user', text, now(), false, null);
    input.value = '';
    input.style.height = 'auto';
    sendBtn.disabled = true;
    showTyping(true);

    apiCall('send_message', { message: text })
      .then(function (data) {
        showTyping(false);
        sendBtn.disabled = false;

        if (data.reply) {
          addMessage('bot', data.reply, data.time || now(), false, null);
        }
      })
      .catch(function () {
        showTyping(false);
        sendBtn.disabled = false;
        addMessage('bot', 'Sorry, something went wrong. Please try again.', now(), false, null);
      });
  }

  // ── Load chat (history + suggestions) ──────────────────────
  function loadChat() {
    // Get suggestions first
    apiCall('get_suggestions').then(function (data) {
      if (data.greeting) {
        addWelcome(data.greeting);
      }
      if (data.suggestions && data.suggestions.length) {
        renderSuggestions(data.suggestions);
      }
    });

    // Load history
    apiCall('get_history', { limit: 30 }).then(function (data) {
      if (data.history && data.history.length > 0) {
        data.history.forEach(function (m) {
          addMessage(m.sender, m.message, m.time, true, m.id || null);
        });
      }
    });
  }

  // ── Render suggestions ─────────────────────────────────────
  function renderSuggestions(suggestions) {
    if (!sugWrap) return;
    sugWrap.innerHTML = '';
    suggestions.forEach(function (text) {
      var chip = document.createElement('button');
      chip.className = 'chatbot-chip';
      chip.textContent = text;
      chip.addEventListener('click', function () {
        input.value = text;
        send();
      });
      sugWrap.appendChild(chip);
    });
  }

  // ── Add message to body ────────────────────────────────────
  function addMessage(sender, html, time, silent, dbId) {
    var wrap = document.createElement('div');
    wrap.className = 'chatbot-msg ' + sender;

    // Store DB id for deletion
    var localId = dbId || ('local_' + (++msgIdCounter));
    wrap.setAttribute('data-msg-id', localId);

    var avatarHtml = '';
    if (sender === 'bot') {
      avatarHtml = '<div class="chatbot-msg-avatar">🤖</div>';
    }

    // Delete button
    var deleteBtn = '<button class="chatbot-msg-delete" title="Delete this message" aria-label="Delete message">'
                  + '<i class="fa-solid fa-trash-can"></i>'
                  + '</button>';

    wrap.innerHTML =
      avatarHtml +
      '<div class="chatbot-msg-content">' +
        '<div class="chatbot-msg-bubble">' + html + '</div>' +
        '<div class="chatbot-msg-meta">' +
          '<span class="chatbot-msg-time">' + (time || '') + '</span>' +
          deleteBtn +
        '</div>' +
      '</div>';

    // Attach delete handler
    var delEl = wrap.querySelector('.chatbot-msg-delete');
    delEl.addEventListener('click', function (e) {
      e.stopPropagation();
      deleteMessage(wrap, localId);
    });

    body.appendChild(wrap);
    scrollToBottom();

    // Show unread badge if panel is closed
    if (!isOpen && sender === 'bot' && !silent) {
      unread++;
      badge.textContent = unread > 9 ? '9+' : unread;
      badge.classList.add('show');
    }
  }

  // ── Delete single message ──────────────────────────────────
  function deleteMessage(el, msgId) {
    // Animate out
    el.style.transition = 'opacity 0.25s ease, transform 0.25s ease, max-height 0.3s ease';
    el.style.opacity = '0';
    el.style.transform = 'translateX(' + (el.classList.contains('user') ? '30px' : '-30px') + ')';
    el.style.maxHeight = el.offsetHeight + 'px';

    setTimeout(function () {
      el.style.maxHeight = '0';
      el.style.padding = '0';
      el.style.margin = '0';
      el.style.overflow = 'hidden';
    }, 200);

    setTimeout(function () {
      el.remove();
    }, 450);

    // Delete from DB if it has a numeric ID (not local_*)
    if (typeof msgId === 'number' || (typeof msgId === 'string' && !msgId.startsWith('local_'))) {
      apiCall('delete_message', { message_id: parseInt(msgId) });
    }
  }

  // ── Welcome card ───────────────────────────────────────────
  function addWelcome(text) {
    var el = document.createElement('div');
    el.className = 'chatbot-welcome';
    el.innerHTML =
      '<div class="chatbot-welcome-icon">🤖</div>' +
      '<h4>SABP Assistant</h4>' +
      '<p>' + text + '</p>';
    body.appendChild(el);
    scrollToBottom();
  }

  // ── Typing indicator ──────────────────────────────────────
  function showTyping(show) {
    if (!typingEl) return;
    typingEl.classList.toggle('show', show);
    if (show) scrollToBottom();
  }

  // ── Scroll to bottom ──────────────────────────────────────
  function scrollToBottom() {
    setTimeout(function () {
      body.scrollTop = body.scrollHeight;
    }, 50);
  }

  // ── API call helper ────────────────────────────────────────
  function apiCall(action, data) {
    var payload = Object.assign({ action: action }, data || {});
    return fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
      credentials: 'same-origin'
    })
    .then(function (res) { return res.json(); });
  }

  // ── Current time string ────────────────────────────────────
  function now() {
    var d = new Date();
    var h = d.getHours();
    var m = d.getMinutes();
    var ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    return h + ':' + (m < 10 ? '0' : '') + m + ' ' + ampm;
  }

})();
