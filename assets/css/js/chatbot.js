(function () {
  const CHATBOT_ID = "lulu-chatbot";
  const BASE = (window.IOS_BASE || "").toString().replace(/\/$/, "");
  const API_AI_URL = `${BASE}/api/chat-ai.php`;
  const API_COURSES_URL = `${BASE}/api/chatbot.php?action=courses`;

  function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (typeof text === "string") node.textContent = text;
    return node;
  }

  function scrollToBottom(container) {
    container.scrollTop = container.scrollHeight;
  }

  function addMessage(messagesEl, role, content, isLoading = false) {
    const row = el("div", `lulu-msg lulu-msg--${role}`);
    const bubble = el("div", "lulu-bubble");

    if (isLoading) {
      bubble.innerHTML = '<span class="lulu-typing">●●●</span>';
    } else if (typeof content === "string") {
      bubble.textContent = content;
    } else {
      bubble.appendChild(content);
    }

    row.appendChild(bubble);
    messagesEl.appendChild(row);
    scrollToBottom(messagesEl);
    return row;
  }

  function buildList(items) {
    const wrap = el("div");
    const ul = el("ul");
    ul.style.margin = "0.5rem 0 0 1rem";
    ul.style.padding = "0";

    items.forEach((item) => {
      const li = el("li");
      li.appendChild(item);
      ul.appendChild(li);
    });

    wrap.appendChild(ul);
    return wrap;
  }

  async function fetchCourses() {
    const res = await fetch(API_COURSES_URL, {
      headers: { Accept: "application/json" },
    });
    const data = await res.json();
    if (!data || !data.ok)
      throw new Error(
        data && data.error ? data.error : "Falha ao buscar cursos",
      );
    return data.courses || [];
  }

  async function askAI(message) {
    const res = await fetch(API_AI_URL, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({ message }),
    });

    if (res.status === 429) {
      throw new Error(
        "Muitas perguntas! Aguarde um pouco e tente novamente. 😊",
      );
    }

    const data = await res.json();
    if (!data || !data.ok) {
      throw new Error(
        data && data.error
          ? data.error
          : "Erro ao processar sua pergunta. Tente novamente.",
      );
    }
    return data.message;
  }

  function createWidget() {
    if (document.getElementById(CHATBOT_ID)) return;

    const root = el("div", "lulu-chatbot");
    root.id = CHATBOT_ID;

    const panel = el("div", "lulu-chatbot__panel");

    const header = el("div", "lulu-chatbot__header");
    const title = el("p", "lulu-chatbot__title", "Lulu — Assistente IA 🤖");
    const closeBtn = el("button", "lulu-chatbot__close");
    closeBtn.type = "button";
    closeBtn.setAttribute("aria-label", "Fechar chat");
    closeBtn.textContent = "×";

    header.appendChild(title);
    header.appendChild(closeBtn);

    const messages = el("div", "lulu-chatbot__messages");

    const quick = el("div", "lulu-chatbot__quick");

    const composer = el("div", "lulu-chatbot__composer");
    const input = el("input", "lulu-input");
    input.type = "text";
    input.placeholder = "Digite sua pergunta...";
    input.setAttribute("aria-label", "Mensagem");

    const send = el("button", "lulu-send", "Enviar");
    send.type = "button";

    composer.appendChild(input);
    composer.appendChild(send);

    panel.appendChild(header);
    panel.appendChild(messages);
    panel.appendChild(quick);
    panel.appendChild(composer);

    const fab = el("button", "lulu-chatbot__fab");
    fab.type = "button";
    fab.setAttribute("aria-label", "Abrir chat da Lulu");

    const img = el("img", "lulu-chatbot__avatar");
    img.alt = "Lulu ChatBOT";
    img.src = `${BASE}/assets/images/Lulu-ChatBOT.jpg`;

    fab.appendChild(img);

    const tooltip = el("div", "lulu-chatbot__tooltip");
    tooltip.textContent = "Pergunta qualquer coisa! 😊";

    root.appendChild(panel);
    root.appendChild(fab);
    root.appendChild(tooltip);

    document.body.appendChild(root);

    let tooltipShowTimer = null;
    let tooltipHideTimer = null;

    function hideTooltip() {
      tooltip.classList.remove("lulu-chatbot__tooltip--show");
    }

    function cancelTooltipTimers() {
      if (tooltipShowTimer) {
        clearTimeout(tooltipShowTimer);
        tooltipShowTimer = null;
      }
      if (tooltipHideTimer) {
        clearTimeout(tooltipHideTimer);
        tooltipHideTimer = null;
      }
    }

    // Auto-show tooltip após 1s e esconder após 5s (somente se o chat estiver fechado)
    tooltipShowTimer = setTimeout(() => {
      if (open) return;
      tooltip.classList.add("lulu-chatbot__tooltip--show");
      tooltipHideTimer = setTimeout(() => {
        hideTooltip();
      }, 5000);
    }, 1000);

    let open = false;
    let greeted = false;
    let closeTimer = null;
    let isProcessing = false;

    function setOpen(v) {
      open = v;
      root.classList.toggle("lulu-chatbot--open", open);

      // Se abriu, o tooltip não deve aparecer / nem ficar marcado.
      if (open) {
        cancelTooltipTimers();
        hideTooltip();
      }

      if (open && !greeted) {
        greeted = true;
        addMessage(
          messages,
          "bot",
          "Olá! Eu sou a Lulu, sua assistente com IA! 😊 Pergunte-me qualquer coisa sobre o IOS.",
        );
        addQuickMenu();
      }
    }

    function addChip(label, action) {
      const b = el("button", "lulu-chip", label);
      b.type = "button";
      b.addEventListener("click", () => handleQuickAction(action));
      quick.appendChild(b);
    }

    function addQuickMenu() {
      quick.innerHTML = "";
      addChip("📚 Cursos disponíveis", "cursos");
      addChip("❓ Como funciona", "como");
      addChip("📋 Critérios de aprovação", "criterios");
      addChip("🏢 Parceiros", "parceiros");
    }

    async function handleQuickAction(action) {
      if (!open) setOpen(true);
      if (isProcessing) return;

      // Mapeia ações rápidas para perguntas
      const questions = {
        cursos: "Quais cursos estão disponíveis?",
        como: "Como funciona o processo de inscrição no IOS?",
        criterios: "Quais são os critérios de aprovação?",
        parceiros: "Quais são os parceiros do IOS?",
      };

      const question = questions[action] || action;

      // Mostra pergunta do usuário
      addMessage(messages, "user", question);

      // Se for lista de cursos, mostra direto do banco
      if (action === "cursos") {
        const loadingRow = addMessage(messages, "bot", "", true);
        try {
          const courses = await fetchCourses();
          loadingRow.remove();

          if (!courses.length) {
            addMessage(
              messages,
              "bot",
              "Ainda não encontrei cursos cadastrados no banco.",
            );
            return;
          }

          addMessage(
            messages,
            "bot",
            "Aqui estão os cursos disponíveis no IOS:",
          );

          const items = courses.map((c) => {
            const a = el("a");
            a.href = `${BASE}/curso.php?id=${encodeURIComponent(c.id)}`;
            a.textContent = c.titulo;
            a.style.fontWeight = "700";
            a.style.color = "var(--ios-purple)";
            a.style.textDecoration = "underline";

            const span = el("span");
            span.appendChild(a);
            if (c.carga_horaria) {
              span.appendChild(
                document.createTextNode(` — ${c.carga_horaria}h`),
              );
            }
            return span;
          });

          addMessage(messages, "bot", buildList(items));
        } catch {
          loadingRow.remove();
          addMessage(
            messages,
            "bot",
            "Não consegui buscar os cursos agora. Tente novamente.",
          );
        }
        return;
      }

      // Para outras perguntas, usa IA
      await sendToAI(question);
    }

    async function sendToAI(text) {
      if (isProcessing || !text.trim()) return;

      isProcessing = true;
      send.disabled = true;
      input.disabled = true;

      const loadingRow = addMessage(messages, "bot", "", true);

      try {
        const response = await askAI(text);
        loadingRow.remove();
        addMessage(messages, "bot", response);
      } catch (err) {
        loadingRow.remove();
        addMessage(
          messages,
          "bot",
          err.message || "Desculpe, ocorreu um erro. Tente novamente.",
        );
      } finally {
        isProcessing = false;
        send.disabled = false;
        input.disabled = false;
        input.focus();
      }
    }

    function sendText() {
      const text = (input.value || "").trim();
      if (!text || isProcessing) return;

      addMessage(messages, "user", text);
      input.value = "";

      sendToAI(text);
    }

    function cancelCloseTimer() {
      if (closeTimer) {
        clearTimeout(closeTimer);
        closeTimer = null;
      }
    }

    fab.addEventListener("click", () => setOpen(!open));
    closeBtn.addEventListener("click", () => setOpen(false));

    // Hover open (desktop) with small delay
    root.addEventListener("mouseenter", () => {
      cancelCloseTimer();
      setOpen(true);
    });

    root.addEventListener("mouseleave", () => {
      cancelCloseTimer();
      closeTimer = setTimeout(() => setOpen(false), 700);
    });

    send.addEventListener("click", sendText);
    input.addEventListener("keydown", (e) => {
      if (e.key === "Enter") sendText();
    });
  }

  document.addEventListener("DOMContentLoaded", createWidget);
})();
