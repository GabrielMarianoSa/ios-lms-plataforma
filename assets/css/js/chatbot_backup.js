(function () {
  const CHATBOT_ID = "lulu-chatbot";
  const BASE = (window.IOS_BASE || "").toString().replace(/\/$/, "");
  const API_COURSES_URL = `${BASE}/api/chatbot.php?action=courses`;
  const API_SITEINFO_URL = `${BASE}/api/chatbot.php?action=siteinfo`;

  function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (typeof text === "string") node.textContent = text;
    return node;
  }

  function scrollToBottom(container) {
    container.scrollTop = container.scrollHeight;
  }

  function addMessage(messagesEl, role, content) {
    const row = el("div", `lulu-msg lulu-msg--${role}`);
    const bubble = el("div", "lulu-bubble");

    if (typeof content === "string") {
      bubble.textContent = content;
    } else {
      bubble.appendChild(content);
    }

    row.appendChild(bubble);
    messagesEl.appendChild(row);
    scrollToBottom(messagesEl);
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

  function normalize(str) {
    return (str || "")
      .toString()
      .toLowerCase()
      .normalize("NFD")
      .replace(/\p{Diacritic}/gu, "");
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

  async function fetchSiteInfo() {
    const res = await fetch(API_SITEINFO_URL, {
      headers: { Accept: "application/json" },
    });
    const data = await res.json();
    if (!data || !data.ok)
      throw new Error(
        data && data.error ? data.error : "Falha ao buscar infos",
      );
    return data;
  }

  function answerFromText(text) {
    const t = normalize(text);

    if (t.includes("curso")) return "cursos";
    if (
      t.includes("idade") ||
      t.includes("faixa etaria") ||
      t.includes("faixa") ||
      t.includes("etaria")
    )
      return "idade";
    if (
      t.includes("criterio") ||
      t.includes("aprov") ||
      t.includes("seletiv") ||
      t.includes("inscr")
    )
      return "criterios";
    if (t.includes("empresa") || t.includes("parceir") || t.includes("apoia"))
      return "parceiros";
    if (
      t.includes("numero") ||
      t.includes("formad") ||
      t.includes("empreg") ||
      t.includes("hist")
    )
      return "numeros";
    if (
      t.includes("como funciona") ||
      t.includes("funciona") ||
      t.includes("passo") ||
      t.includes("como")
    )
      return "como";

    if (t.includes("site") || t.includes("ios.org") || t.includes("instituto"))
      return "site";

    return "menu";
  }

  function createWidget() {
    if (document.getElementById(CHATBOT_ID)) return;

    const root = el("div", "lulu-chatbot");
    root.id = CHATBOT_ID;

    const panel = el("div", "lulu-chatbot__panel");

    const header = el("div", "lulu-chatbot__header");
    const title = el("p", "lulu-chatbot__title", "Lulu — ChatBOT");
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
    input.placeholder = "Digite sua dúvida…";
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

    // Auto-show tooltip após 1s e esconder após 5s
    setTimeout(() => {
      tooltip.classList.add("lulu-chatbot__tooltip--show");
      setTimeout(() => {
        tooltip.classList.remove("lulu-chatbot__tooltip--show");
      }, 5000);
    }, 1000);

    let open = false;
    let greeted = false;
    let closeTimer = null;
    let cachedInfo = null;

    function ensureInfo() {
      if (cachedInfo) return Promise.resolve(cachedInfo);
      return fetchSiteInfo().then((i) => (cachedInfo = i));
    }

    function setOpen(v) {
      open = v;
      root.classList.toggle("lulu-chatbot--open", open);
      if (open && !greeted) {
        greeted = true;
        addMessage(
          messages,
          "bot",
          "Olá! Eu sou a Lulu 😊 Em que posso ajudar?",
        );
        addMessage(
          messages,
          "bot",
          "Dica: clique em uma opção abaixo ou digite sua dúvida (ex: cursos, inscrição, aprovação).",
        );
        addQuickMenu();
      }
    }

    function addChip(label, action) {
      const b = el("button", "lulu-chip", label);
      b.type = "button";
      b.addEventListener("click", () => handleAction(action));
      quick.appendChild(b);
    }

    function addQuickMenu() {
      quick.innerHTML = "";
      addChip("Como funciona", "como");
      addChip("Faixa etária", "idade");
      addChip("Critérios", "criterios");
      addChip("Cursos", "cursos");
      addChip("Parceiros", "parceiros");
      addChip("Números", "numeros");
      addChip("Site oficial", "site");
    }

    async function handleAction(action) {
      if (!open) setOpen(true);

      switch (action) {
        case "menu":
          addMessage(messages, "bot", "Beleza! Escolha uma opção:");
          addQuickMenu();
          return;

        case "como":
          try {
            const info = await ensureInfo();
            addMessage(
              messages,
              "bot",
              info.faq?.como_funciona ||
                "Vamos lá: crie conta, veja cursos, solicite inscrição, aguarde análise e, se aprovado, acesse as aulas.",
            );
          } catch {
            addMessage(
              messages,
              "bot",
              "Vamos lá: crie conta, veja cursos, solicite inscrição, aguarde análise e, se aprovado, acesse as aulas.",
            );
          }
          return;

        case "idade":
          try {
            const info = await ensureInfo();
            addMessage(
              messages,
              "bot",
              info.faq?.faixa_etaria ||
                "A faixa etária pode variar por turma/edital. Para a regra oficial, consulte o site do IOS.",
            );
            addMessage(
              messages,
              "bot",
              `Site oficial: ${info.institute?.site || "https://ios.org.br/"}`,
            );
          } catch {
            addMessage(
              messages,
              "bot",
              "A faixa etária pode variar por turma/edital. Para a regra oficial, consulte o site do IOS: https://ios.org.br/",
            );
          }
          return;

        case "criterios":
          try {
            const info = await ensureInfo();
            addMessage(
              messages,
              "bot",
              info.faq?.criterios ||
                "A aprovação depende da análise do administrador e das regras da turma. Você acompanha o status na Área do Aluno.",
            );
          } catch {
            addMessage(
              messages,
              "bot",
              "A aprovação depende da análise do administrador e das regras da turma. Você acompanha o status na Área do Aluno.",
            );
          }
          return;

        case "parceiros": {
          addMessage(
            messages,
            "bot",
            "Algumas empresas parceiras/apoio que aparecem no site:",
          );
          try {
            const info = await ensureInfo();
            const partners = info.partners || [];
            const nodes = partners.map((p) => {
              const wrap = el("span");
              const img = el("img");
              img.src = p.image;
              img.alt = p.name;
              img.style.width = "18px";
              img.style.height = "18px";
              img.style.objectFit = "contain";
              img.style.marginRight = "8px";
              img.style.verticalAlign = "-3px";
              wrap.appendChild(img);
              wrap.appendChild(document.createTextNode(p.name));
              return wrap;
            });
            addMessage(messages, "bot", buildList(nodes));
          } catch {
            const names = ["TOTVS", "Dell", "Microsoft", "Zendesk", "IBM"];
            const nodes = names.map((n) => document.createTextNode(n));
            addMessage(messages, "bot", buildList(nodes));
          }
          return;
        }

        case "numeros":
          try {
            const info = await ensureInfo();
            const s = info.stats || {};
            addMessage(
              messages,
              "bot",
              `Números em destaque no site: ${s.anos ?? 24} anos de história, +${s.alunos_formados ?? 50000} alunos formados, ~${s.alunos_por_ano ?? 1000} alunos/ano e ${s.empregabilidade_percent ?? 83}% de empregabilidade.`,
            );
            addMessage(
              messages,
              "bot",
              `Fonte/mais info: ${info.institute?.site || "https://ios.org.br/"}`,
            );
          } catch {
            addMessage(
              messages,
              "bot",
              "Números em destaque no site: +50 mil alunos formados, mais de mil alunos por ano e empregabilidade destacada. Mais info: https://ios.org.br/",
            );
          }
          return;

        case "site":
          try {
            const info = await ensureInfo();
            const url = info.institute?.site || "https://ios.org.br/";
            addMessage(messages, "bot", `Site oficial do IOS: ${url}`);

            const btn = el("a", "lulu-chip", "🔗 Abrir site agora");
            btn.href = url;
            btn.target = "_blank";
            btn.style.display = "inline-block";
            btn.style.marginTop = "0.5rem";
            btn.style.background = "var(--ios-purple)";
            btn.style.color = "#fff";
            btn.style.textDecoration = "none";
            btn.style.textAlign = "center";

            addMessage(messages, "bot", btn);
          } catch {
            addMessage(
              messages,
              "bot",
              "Site oficial do IOS: https://ios.org.br/",
            );
          }
          return;

        case "cursos": {
          addMessage(messages, "bot", "Buscando cursos disponíveis…");
          try {
            const courses = await fetchCourses();
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
              "Aqui estão os cursos (atualizados automaticamente pelo banco):",
            );

            const items = courses.map((c) => {
              const a = el("a");
              a.href = `${BASE}/curso.php?id=${encodeURIComponent(c.id)}`;
              a.textContent = c.titulo;
              a.style.fontWeight = "700";
              a.style.color = "inherit";
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
          } catch (err) {
            addMessage(
              messages,
              "bot",
              "Não consegui buscar os cursos agora. Se estiver local, confira se o MySQL do Laragon está ligado e se você está acessando por http://localhost/ios/.",
            );
          }
          return;
        }

        default:
          addMessage(messages, "bot", "Entendi 🙂 Quer ver as opções?");
          addQuickMenu();
      }
    }

    function sendText() {
      const text = (input.value || "").trim();
      if (!text) return;
      input.value = "";
      addMessage(messages, "user", text);
      handleAction(answerFromText(text));
    }

    function cancelCloseTimer() {
      if (closeTimer) {
        clearTimeout(closeTimer);
        closeTimer = null;
      }
    }

    fab.addEventListener("click", () => setOpen(!open));
    closeBtn.addEventListener("click", () => setOpen(false));

    // Hover open (desktop) with small delay to avoid accidental popups
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
