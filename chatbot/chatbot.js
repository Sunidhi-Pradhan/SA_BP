/**
 * SABP Chatbot — Client-Side Logic (Enhanced)
 * ─────────────────────────────────────────────────────────────
 * Features:
 *   1. Voice Input        — Web Speech API (SpeechRecognition)
 *   2. Text-to-Speech     — Web Speech API (SpeechSynthesis)
 *   3. Smart Quick Actions — Clickable navigation buttons
 *   4. Proactive Notifs   — Pending tasks on panel open
 *   5. Export Chat as PDF — html2pdf.js
 *   6. Bot Feedback Rating — Thumbs up / down
 * ─────────────────────────────────────────────────────────────
 * APIs used: All browser-built-in or free CDN libraries.
 *   • SpeechRecognition (browser)
 *   • SpeechSynthesis   (browser)
 *   • html2pdf.js       (CDN, free)
 *   • Fetch API         (browser)
 */
(function () {
  'use strict';

  // ── DOM refs ────────────────────────────────────────────────
  var fab         = document.getElementById('chatbotFab');
  var badge       = document.getElementById('chatbotBadge');
  var panel       = document.getElementById('chatbotPanel');
  var body        = document.getElementById('chatbotBody');
  var input       = document.getElementById('chatbotInput');
  var sendBtn     = document.getElementById('chatbotSend');
  var typingEl    = document.getElementById('chatbotTyping');
  var sugWrap     = document.getElementById('chatbotSuggestions');
  var clearBtn    = document.getElementById('chatbotClearBtn');
  var minimizeBtn = document.getElementById('chatbotMinimize');
  var voiceBtn    = document.getElementById('chatbotVoiceBtn');
  var pdfBtn      = document.getElementById('chatbotPdfBtn');
  var proactiveEl = document.getElementById('chatbotProactive');

  if (!fab || !panel) return;

  // ── API URL auto-detection ─────────────────────────────────
  var API_URL = (function () {
    var scripts = document.querySelectorAll('script[src*="chatbot.js"]');
    if (scripts.length > 0) {
      var src = scripts[scripts.length - 1].getAttribute('src');
      return src.replace('chatbot.js', 'chatbot_api.php');
    }
    return document.querySelector('meta[name="chatbot-api"]')?.content
      || 'chatbot/chatbot_api.php';
  })();

  // ── Compute base path for smart action navigation ──────────
  var BASE_PATH = (function () {
    var meta = document.querySelector('meta[name="chatbot-api"]');
    if (meta) {
      var content = meta.getAttribute('content');
      // e.g. "../chatbot/chatbot_api.php" → "../"
      //      "chatbot/chatbot_api.php"    → ""
      return content.replace('chatbot/chatbot_api.php', '');
    }
    return '';
  })();

  var isOpen       = false;
  var isLoaded     = false;
  var unread       = 0;
  var msgIdCounter = 0;
  var isRecording  = false;
  var recognition  = null;
  var isSpeaking   = false;

  // ══════════════════════════════════════════════════════════════
  // 1. VOICE INPUT — Web Speech API (SpeechRecognition)
  // ══════════════════════════════════════════════════════════════
  var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
  var voiceSupported = !!SpeechRecognition;

  if (voiceBtn) {
    if (!voiceSupported) {
      voiceBtn.title = 'Voice input not supported in this browser';
      voiceBtn.style.opacity = '0.4';
      voiceBtn.style.cursor = 'not-allowed';
    } else {
      recognition = new SpeechRecognition();
      recognition.lang = 'en-IN'; // English (India) — also picks up Hindi
      recognition.interimResults = false;
      recognition.maxAlternatives = 1;
      recognition.continuous = false;

      recognition.onresult = function (event) {
        var transcript = event.results[0][0].transcript;
        input.value = transcript;
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 80) + 'px';
        stopRecording();
      };

      recognition.onerror = function () {
        stopRecording();
      };

      recognition.onend = function () {
        stopRecording();
      };

      voiceBtn.addEventListener('click', function () {
        if (isRecording) {
          recognition.stop();
          stopRecording();
        } else {
          startRecording();
        }
      });
    }
  }

  function startRecording() {
    if (!recognition) return;
    isRecording = true;
    voiceBtn.classList.add('recording');
    voiceBtn.innerHTML = '<i class="fa-solid fa-stop"></i>';
    voiceBtn.title = 'Listening... click to stop';
    try {
      recognition.start();
    } catch (e) {
      stopRecording();
    }
  }

  function stopRecording() {
    isRecording = false;
    if (voiceBtn) {
      voiceBtn.classList.remove('recording');
      voiceBtn.innerHTML = '<i class="fa-solid fa-microphone"></i>';
      voiceBtn.title = 'Voice input (Speech-to-Text)';
    }
  }


  // ══════════════════════════════════════════════════════════════
  // 2. TEXT-TO-SPEECH — Web Speech API (SpeechSynthesis)
  // ══════════════════════════════════════════════════════════════
  var ttsSupported = 'speechSynthesis' in window;

  function speakText(htmlContent, btnEl) {
    if (!ttsSupported) return;

    // If already speaking, stop
    if (isSpeaking) {
      window.speechSynthesis.cancel();
      isSpeaking = false;
      // Reset all TTS buttons
      document.querySelectorAll('.chatbot-tts-btn.speaking').forEach(function (b) {
        b.classList.remove('speaking');
        b.innerHTML = '<i class="fa-solid fa-volume-up"></i>';
      });
      return;
    }

    // Strip HTML tags to get plain text
    var temp = document.createElement('div');
    temp.innerHTML = htmlContent;
    var plainText = temp.textContent || temp.innerText || '';

    if (!plainText.trim()) return;

    var utterance = new SpeechSynthesisUtterance(plainText);
    utterance.lang = 'en-IN';
    utterance.rate = 0.95;
    utterance.pitch = 1;

    utterance.onstart = function () {
      isSpeaking = true;
      if (btnEl) {
        btnEl.classList.add('speaking');
        btnEl.innerHTML = '<i class="fa-solid fa-volume-xmark"></i>';
      }
    };

    utterance.onend = function () {
      isSpeaking = false;
      if (btnEl) {
        btnEl.classList.remove('speaking');
        btnEl.innerHTML = '<i class="fa-solid fa-volume-up"></i>';
      }
    };

    utterance.onerror = function () {
      isSpeaking = false;
      if (btnEl) {
        btnEl.classList.remove('speaking');
        btnEl.innerHTML = '<i class="fa-solid fa-volume-up"></i>';
      }
    };

    window.speechSynthesis.cancel();
    window.speechSynthesis.speak(utterance);
  }


  // ══════════════════════════════════════════════════════════════
  // 5. EXPORT CHAT AS PDF — html2pdf.js
  // ══════════════════════════════════════════════════════════════
  if (pdfBtn) {
    pdfBtn.addEventListener('click', function () {
      if (typeof html2pdf === 'undefined') {
        alert('PDF library is still loading. Please try again in a moment.');
        return;
      }

      // Create a clone of the chat body for PDF rendering
      var clone = body.cloneNode(true);
      clone.style.background = '#fff';
      clone.style.padding = '20px';
      clone.style.maxHeight = 'none';
      clone.style.overflow = 'visible';
      clone.style.color = '#1f2937';

      // Remove delete buttons and TTS buttons from PDF
      clone.querySelectorAll('.chatbot-msg-delete, .chatbot-tts-btn, .chatbot-feedback-bar, .chatbot-actions').forEach(function (el) {
        el.remove();
      });

      // Add header to PDF
      var header = document.createElement('div');
      header.innerHTML = '<h2 style="text-align:center;color:#0f766e;margin-bottom:5px;">🤖 SABP Assistant — Chat Export</h2>'
                       + '<p style="text-align:center;color:#6b7280;font-size:12px;margin-bottom:20px;">Exported on: ' + new Date().toLocaleString() + '</p>'
                       + '<hr style="border:1px solid #e5e7eb;margin-bottom:15px;">';
      clone.insertBefore(header, clone.firstChild);

      var opt = {
        margin:       [10, 10, 10, 10],
        filename:     'SABP_Chat_' + new Date().toISOString().slice(0,10) + '.pdf',
        image:        { type: 'jpeg', quality: 0.95 },
        html2canvas:  { scale: 2, useCORS: true },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
      };

      // Show loading state
      pdfBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
      pdfBtn.disabled = true;

      html2pdf().set(opt).from(clone).save().then(function () {
        pdfBtn.innerHTML = '<i class="fa-solid fa-file-pdf"></i>';
        pdfBtn.disabled = false;
      }).catch(function () {
        pdfBtn.innerHTML = '<i class="fa-solid fa-file-pdf"></i>';
        pdfBtn.disabled = false;
      });
    });
  }


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
        if (proactiveEl) proactiveEl.style.display = 'none';
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

    addMessage('user', text, now(), false, null, null);
    input.value = '';
    input.style.height = 'auto';
    sendBtn.disabled = true;
    showTyping(true);

    apiCall('send_message', { message: text })
      .then(function (data) {
        showTyping(false);
        sendBtn.disabled = false;

        if (data.reply) {
          addMessage('bot', data.reply, data.time || now(), false, null, null);
        }
      })
      .catch(function () {
        showTyping(false);
        sendBtn.disabled = false;
        addMessage('bot', 'Sorry, something went wrong. Please try again.', now(), false, null, null);
      });
  }

  // ── Load chat (history + suggestions + proactive) ──────────
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
          addMessage(m.sender, m.message, m.time, true, m.id || null, m.rating || null);
        });
      }
    });

    // ══════════════════════════════════════════════════════════
    // 4. PROACTIVE NOTIFICATIONS — check pending tasks
    // ══════════════════════════════════════════════════════════
    apiCall('get_proactive').then(function (data) {
      if (data.notifications && data.notifications.length > 0) {
        renderProactive(data.notifications);
      }
    });
  }


  // ══════════════════════════════════════════════════════════════
  // 4. PROACTIVE NOTIFICATIONS — Render
  // ══════════════════════════════════════════════════════════════
  function renderProactive(notifications) {
    if (!proactiveEl || !notifications.length) return;

    var html = '';
    notifications.forEach(function (n) {
      html += '<div class="chatbot-proactive-item">';
      html += '<span class="chatbot-proactive-icon">' + n.icon + '</span>';
      html += '<span class="chatbot-proactive-text">' + n.message + '</span>';
      if (n.action && n.action.label) {
        html += '<button class="chatbot-proactive-action" data-href="' + n.action.page + '">'
             +  n.action.label + ' →</button>';
      }
      html += '</div>';
    });

    // Add dismiss button
    html += '<button class="chatbot-proactive-dismiss" id="chatbotProactiveDismiss" title="Dismiss">'
         +  '<i class="fa-solid fa-xmark"></i></button>';

    proactiveEl.innerHTML = html;
    proactiveEl.style.display = 'block';

    // Dismiss handler
    var dismissBtn = document.getElementById('chatbotProactiveDismiss');
    if (dismissBtn) {
      dismissBtn.addEventListener('click', function () {
        proactiveEl.style.display = 'none';
      });
    }

    // Smart action handlers in proactive bar
    proactiveEl.querySelectorAll('.chatbot-proactive-action').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var href = this.getAttribute('data-href');
        if (href) window.location.href = BASE_PATH + href;
      });
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


  // ══════════════════════════════════════════════════════════════
  // MESSAGE RENDERING — with TTS + Feedback + Smart Actions
  // ══════════════════════════════════════════════════════════════
  function addMessage(sender, html, time, silent, dbId, existingRating) {
    var wrap = document.createElement('div');
    wrap.className = 'chatbot-msg ' + sender;

    // Store DB id for deletion / feedback
    var localId = dbId || ('local_' + (++msgIdCounter));
    wrap.setAttribute('data-msg-id', localId);

    var avatarHtml = '';
    if (sender === 'bot') {
      avatarHtml = '<div class="chatbot-msg-avatar">🤖</div>';
    }

    // ── TTS button (only for bot messages) ──────────────────
    var ttsBtn = '';
    if (sender === 'bot' && ttsSupported) {
      ttsBtn = '<button class="chatbot-tts-btn" title="Read aloud (Text-to-Speech)" aria-label="Read aloud">'
             + '<i class="fa-solid fa-volume-up"></i>'
             + '</button>';
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
          ttsBtn +
          deleteBtn +
        '</div>' +
        // ══ 6. BOT FEEDBACK RATING — thumbs up/down ══
        (sender === 'bot' ? buildFeedbackBar(localId, existingRating) : '') +
      '</div>';

    // ── Attach delete handler ─────────────────────────────
    var delEl = wrap.querySelector('.chatbot-msg-delete');
    delEl.addEventListener('click', function (e) {
      e.stopPropagation();
      deleteMessage(wrap, localId);
    });

    // ── Attach TTS handler ────────────────────────────────
    var ttsEl = wrap.querySelector('.chatbot-tts-btn');
    if (ttsEl) {
      ttsEl.addEventListener('click', function (e) {
        e.stopPropagation();
        var bubble = wrap.querySelector('.chatbot-msg-bubble');
        speakText(bubble ? bubble.innerHTML : html, ttsEl);
      });
    }

    // ── Attach feedback handlers ──────────────────────────
    attachFeedbackHandlers(wrap, localId);

    // ── 3. SMART QUICK ACTIONS — attach navigation handlers ──
    wrap.querySelectorAll('.chatbot-action-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var href = this.getAttribute('data-href');
        if (href) {
          window.location.href = BASE_PATH + href;
        }
      });
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


  // ══════════════════════════════════════════════════════════════
  // 6. BOT FEEDBACK RATING — Build + Handle
  // ══════════════════════════════════════════════════════════════
  function buildFeedbackBar(msgId, existingRating) {
    var upActive   = existingRating === 'up'   ? ' active' : '';
    var downActive = existingRating === 'down' ? ' active' : '';

    return '<div class="chatbot-feedback-bar">'
         + '<span class="chatbot-feedback-label">Helpful?</span>'
         + '<button class="chatbot-feedback-btn chatbot-fb-up' + upActive + '" data-rating="up" title="Helpful">'
         + '<i class="fa-solid fa-thumbs-up"></i>'
         + '</button>'
         + '<button class="chatbot-feedback-btn chatbot-fb-down' + downActive + '" data-rating="down" title="Not helpful">'
         + '<i class="fa-solid fa-thumbs-down"></i>'
         + '</button>'
         + '</div>';
  }

  function attachFeedbackHandlers(wrap, msgId) {
    var fbBtns = wrap.querySelectorAll('.chatbot-feedback-btn');
    fbBtns.forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        var rating = this.getAttribute('data-rating');
        var isActive = this.classList.contains('active');

        // If clicking same rating → toggle off (unrate)
        if (isActive) {
          this.classList.remove('active');
          // Only call unrate for DB-stored messages
          if (typeof msgId === 'number' || (typeof msgId === 'string' && !msgId.toString().startsWith('local_'))) {
            apiCall('unrate_message', { message_id: parseInt(msgId) });
          }
          return;
        }

        // Remove active from all siblings
        fbBtns.forEach(function (b) { b.classList.remove('active'); });
        // Set this as active
        this.classList.add('active');

        // Send rating to server
        if (typeof msgId === 'number' || (typeof msgId === 'string' && !msgId.toString().startsWith('local_'))) {
          apiCall('rate_message', { message_id: parseInt(msgId), rating: rating });
        }
      });
    });
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
