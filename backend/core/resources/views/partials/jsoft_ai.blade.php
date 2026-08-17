<!-- JSoft AI Chat Widget -->
<style>
    /* Floating Action Button */
    #jsoft-ai-fab {
        position: fixed;
        bottom: 24px;
        right: 24px;
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #4f46e5, #06b6d4);
        border-radius: 50%;
        box-shadow: 0 8px 24px rgba(79, 70, 229, 0.35);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        cursor: pointer;
        z-index: 999999;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    #jsoft-ai-fab:hover {
        transform: scale(1.1) rotate(5deg);
        box-shadow: 0 10px 28px rgba(79, 70, 229, 0.45);
    }
    #jsoft-ai-fab i {
        font-size: 26px;
    }
    #jsoft-ai-fab .pulse-ring {
        position: absolute;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        border: 2px solid #4f46e5;
        animation: ai-pulse 2s infinite;
        opacity: 0;
    }
    @keyframes ai-pulse {
        0% { transform: scale(1); opacity: 0.5; }
        100% { transform: scale(1.4); opacity: 0; }
    }

    /* Chat Container */
    #jsoft-ai-chat-container {
        position: fixed;
        bottom: 96px;
        right: 24px;
        width: 380px;
        height: 520px;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(226, 232, 240, 0.8);
        border-radius: 20px;
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        z-index: 999999;
        transform: scale(0.9) translateY(20px);
        opacity: 0;
        pointer-events: none;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.15);
    }
    #jsoft-ai-chat-container.open {
        transform: scale(1) translateY(0);
        opacity: 1;
        pointer-events: auto;
    }

    /* Chat Header */
    .jsoft-ai-header {
        background: linear-gradient(135deg, #4f46e5, #06b6d4);
        padding: 16px 20px;
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .jsoft-ai-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 700;
        font-size: 15px;
    }
    .jsoft-ai-title i {
        font-size: 20px;
    }
    .jsoft-ai-status-dot {
        width: 8px;
        height: 8px;
        background: #10b981;
        border-radius: 50%;
        display: inline-block;
    }
    .jsoft-ai-close-btn {
        background: none;
        border: none;
        color: rgba(255, 255, 255, 0.8);
        cursor: pointer;
        font-size: 20px;
        transition: color 0.2s;
    }
    .jsoft-ai-close-btn:hover {
        color: white;
    }

    /* Chat Body */
    .jsoft-ai-body {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 12px;
        background: #f8fafc;
    }

    /* Chat Messages */
    .jsoft-ai-message {
        max-width: 85%;
        padding: 10px 14px;
        border-radius: 16px;
        font-size: 13.5px;
        line-height: 1.5;
        word-wrap: break-word;
    }
    .jsoft-ai-message.user {
        background: #4f46e5;
        color: white;
        align-self: flex-end;
        border-bottom-right-radius: 4px;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
    }
    .jsoft-ai-message.assistant {
        background: white;
        color: #1e293b;
        align-self: flex-start;
        border-bottom-left-radius: 4px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        border: 1px solid #f1f5f9;
    }
    .jsoft-ai-message.assistant p {
        margin: 0 0 8px;
    }
    .jsoft-ai-message.assistant p:last-child {
        margin-bottom: 0;
    }
    .jsoft-ai-message.assistant code {
        background: #f1f5f9;
        color: #e11d48;
        padding: 2px 5px;
        border-radius: 4px;
        font-family: monospace;
        font-size: 12px;
    }
    .jsoft-ai-message.assistant pre {
        background: #1e293b;
        color: #f8fafc;
        padding: 12px;
        border-radius: 8px;
        overflow-x: auto;
        margin: 8px 0;
    }
    .jsoft-ai-message.assistant pre code {
        background: none;
        color: inherit;
        padding: 0;
        font-size: 11.5px;
    }

    /* Typing Indicator */
    .jsoft-ai-typing {
        display: flex;
        gap: 4px;
        padding: 12px 16px;
        background: white;
        border: 1px solid #f1f5f9;
        border-radius: 16px;
        border-bottom-left-radius: 4px;
        align-self: flex-start;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
    }
    .jsoft-ai-dot {
        width: 6px;
        height: 6px;
        background: #94a3b8;
        border-radius: 50%;
        animation: ai-bounce 1.4s infinite ease-in-out both;
    }
    .jsoft-ai-dot:nth-child(1) { animation-delay: -0.32s; }
    .jsoft-ai-dot:nth-child(2) { animation-delay: -0.16s; }
    @keyframes ai-bounce {
        0%, 80%, 100% { transform: scale(0); }
        40% { transform: scale(1); }
    }

    /* Input Footer */
    .jsoft-ai-footer {
        padding: 12px 16px;
        background: white;
        border-top: 1px solid #e2e8f0;
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .jsoft-ai-input {
        flex: 1;
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        padding: 10px 14px;
        font-size: 13.5px;
        outline: none;
        resize: none;
        max-height: 80px;
        font-family: inherit;
        transition: border-color 0.2s;
    }
    .jsoft-ai-input:focus {
        border-color: #4f46e5;
    }
    .jsoft-ai-send-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #4f46e5;
        border: none;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.2s, transform 0.2s;
    }
    .jsoft-ai-send-btn:hover {
        background: #4338ca;
        transform: scale(1.05);
    }
    .jsoft-ai-send-btn:disabled {
        background: #cbd5e1;
        cursor: not-allowed;
        transform: none;
    }

    /* Responsiveness */
    @media (max-width: 480px) {
        #jsoft-ai-chat-container {
            bottom: 0;
            right: 0;
            width: 100%;
            height: 100%;
            border-radius: 0;
        }
        #jsoft-ai-fab {
            bottom: 16px;
            right: 16px;
        }
    }
</style>

<!-- Floating Button -->
<div id="jsoft-ai-fab" onclick="toggleJSoftAiChat()">
    <div class="pulse-ring"></div>
    <i class="las la-robot"></i>
</div>

<!-- Chat Window -->
<div id="jsoft-ai-chat-container">
    <!-- Header -->
    <div class="jsoft-ai-header">
        <div class="jsoft-ai-title">
            <i class="las la-robot"></i>
            <span>JSoft AI</span>
            <span class="jsoft-ai-status-dot"></span>
        </div>
        <div>
            <button class="jsoft-ai-close-btn" onclick="clearJSoftAiChat()" title="Limpiar conversación" style="margin-right: 10px; font-size: 16px;">
                <i class="las la-trash-alt"></i>
            </button>
            <button class="jsoft-ai-close-btn" onclick="toggleJSoftAiChat()">&times;</button>
        </div>
    </div>

    <!-- Message History -->
    <div class="jsoft-ai-body" id="jsoft-ai-body">
        <!-- Messages are rendered dynamically via JavaScript -->
    </div>

    <!-- Input Footer -->
    <div class="jsoft-ai-footer">
        <textarea id="jsoft-ai-input" class="jsoft-ai-input" placeholder="Pregunta algo a JSoft AI..." rows="1" onkeydown="handleJSoftAiKey(event)"></textarea>
        <button id="jsoft-ai-send-btn" class="jsoft-ai-send-btn" onclick="sendJSoftAiMessage()">
            <i class="las la-paper-plane"></i>
        </button>
    </div>
</div>

<script>
    let jsoftAiHistory = [];
    const defaultWelcome = {
        role: 'assistant',
        content: '¡Hola! Soy **JSoft AI**, tu asistente de datos y procesos en la plataforma Lizto.\n\nPuedo ayudarte a consultar información detallada de la base de datos (ventas, pedidos, flotas, zonas), generar reportes avanzados o responder dudas sobre cómo utilizar y llenar formularios del sistema.\n\n¿Qué información necesitas hoy?'
    };

    function loadJSoftAiChat() {
        const storedHistory = localStorage.getItem('jsoft_ai_history');
        if (storedHistory) {
            try {
                jsoftAiHistory = JSON.parse(storedHistory);
            } catch(e) {
                jsoftAiHistory = [defaultWelcome];
            }
        } else {
            jsoftAiHistory = [defaultWelcome];
        }

        // Render messages
        const body = document.getElementById('jsoft-ai-body');
        if (body) {
            body.innerHTML = '';
            jsoftAiHistory.forEach(msg => {
                appendJSoftAiMessage(msg.role, msg.content, true);
            });
            body.scrollTop = body.scrollHeight;
        }

        // Open state
        const container = document.getElementById('jsoft-ai-chat-container');
        const isOpen = localStorage.getItem('jsoft_ai_open') === 'true';
        if (container && isOpen) {
            container.classList.add('open');
        }
    }

    function toggleJSoftAiChat() {
        const container = document.getElementById('jsoft-ai-chat-container');
        if (container) {
            container.classList.toggle('open');
            const isOpen = container.classList.contains('open');
            localStorage.setItem('jsoft_ai_open', isOpen ? 'true' : 'false');
            if (isOpen) {
                document.getElementById('jsoft-ai-input').focus();
            }
        }
    }

    function clearJSoftAiChat() {
        if (confirm('¿Estás seguro de que deseas limpiar la conversación?')) {
            jsoftAiHistory = [defaultWelcome];
            localStorage.setItem('jsoft_ai_history', JSON.stringify(jsoftAiHistory));
            const body = document.getElementById('jsoft-ai-body');
            if (body) {
                body.innerHTML = '';
                appendJSoftAiMessage(defaultWelcome.role, defaultWelcome.content);
            }
        }
    }

    function handleJSoftAiKey(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendJSoftAiMessage();
        }
    }

    function sendJSoftAiMessage() {
        const input = document.getElementById('jsoft-ai-input');
        const sendBtn = document.getElementById('jsoft-ai-send-btn');
        const text = input.value.trim();
        if (!text) return;

        // Append user message
        appendJSoftAiMessage('user', text);
        jsoftAiHistory.push({ role: 'user', content: text });
        localStorage.setItem('jsoft_ai_history', JSON.stringify(jsoftAiHistory));

        // Reset input
        input.value = '';
        input.disabled = true;
        sendBtn.disabled = true;

        // Show typing indicator
        showJSoftAiTyping(true);

        // API Call
        fetch('{{ route("jsoft.ai.chat") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ messages: jsoftAiHistory })
        })
        .then(response => response.json())
        .then(data => {
            showJSoftAiTyping(false);
            input.disabled = false;
            sendBtn.disabled = false;
            input.focus();

            if (data.success && data.message) {
                appendJSoftAiMessage('assistant', data.message.content);
                jsoftAiHistory.push({ role: 'assistant', content: data.message.content });
                localStorage.setItem('jsoft_ai_history', JSON.stringify(jsoftAiHistory));
            } else {
                const errMsg = data.message?.content || 'Ocurrió un error inesperado al conectar con el asistente.';
                appendJSoftAiMessage('assistant', '⚠️ ' + errMsg);
            }
        })
        .catch(err => {
            showJSoftAiTyping(false);
            input.disabled = false;
            sendBtn.disabled = false;
            console.error(err);
            appendJSoftAiMessage('assistant', '⚠️ Error de conexión. Por favor verifica los servicios del servidor.');
        });
    }

    function showJSoftAiTyping(show) {
        const body = document.getElementById('jsoft-ai-body');
        const existing = document.getElementById('jsoft-ai-typing-indicator');
        if (existing) existing.remove();

        if (show) {
            const typingHtml = `
                <div class="jsoft-ai-typing" id="jsoft-ai-typing-indicator">
                    <div class="jsoft-ai-dot"></div>
                    <div class="jsoft-ai-dot"></div>
                    <div class="jsoft-ai-dot"></div>
                </div>
            `;
            body.insertAdjacentHTML('beforeend', typingHtml);
            body.scrollTop = body.scrollHeight;
        }
    }

    function appendJSoftAiMessage(role, text, skipScroll = false) {
        const body = document.getElementById('jsoft-ai-body');
        if (!body) return;
        const msgDiv = document.createElement('div');
        msgDiv.className = `jsoft-ai-message ${role}`;
        msgDiv.innerHTML = formatMarkdown(text);
        body.appendChild(msgDiv);
        if (!skipScroll) {
            body.scrollTop = body.scrollHeight;
        }
    }

    function formatMarkdown(text) {
        // Escapar HTML básico para prevenir XSS
        let escaped = text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");

        // Formatear bloques de código (```sql ... ```)
        escaped = escaped.replace(/```(\w*)\n([\s\S]*?)```/g, function(match, lang, code) {
            return `<pre><code>${code.trim()}</code></pre>`;
        });

        // Formatear código en línea (`code`)
        escaped = escaped.replace(/`([^`]+)`/g, '<code>$1</code>');

        // Formatear negritas (**texto**)
        escaped = escaped.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');

        // Formatear saltos de línea
        escaped = escaped.replace(/\n/g, '<br>');

        return escaped;
    }

    // Initialize on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadJSoftAiChat);
    } else {
        loadJSoftAiChat();
    }
</script>
