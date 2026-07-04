const CHANNEL = {
  whatsapp: { label: "WhatsApp", color: "#1FA855" },
  telegram: { label: "Telegram", color: "#229ED9" },
};
const BRASS = "#A9762F";
const POLL_MS = 3000;

const ICONS = {
  search: `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#9AA0AC" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>`,
  send: `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.5"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>`,
  phone: `<svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="3"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>`,
  paperplane: `<svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="3"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>`,
  paperclip: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9AA0AC" stroke-width="2"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>`,
  smile: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9AA0AC" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>`,
  back: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>`,
  check: `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 7 17l-5-5"/><path d="m22 10-7.5 7.5L13 16"/></svg>`,
  checkSingle: `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 6 7 17l-5-5"/></svg>`,
  tag: `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#565C6A" stroke-width="2"><path d="M12 2H2v10l9.29 9.29a1 1 0 0 0 1.41 0l8.29-8.29a1 1 0 0 0 0-1.41L12 2Z"/><circle cx="7" cy="7" r="1"/></svg>`,
  clock: `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#9AA0AC" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`,
  // Brand mark: an arch on two piers, echoing "gerbang" (gateway). Reused in
  // the topbar, the login screen, and the empty-state so it reads as one motif.
  gate: `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V10.5C4 6.36 7.58 3 12 3s8 3.36 8 7.5V21"/><path d="M4 21h16"/><path d="M8.5 21v-8" stroke-width="1.5" opacity=".55"/><path d="M15.5 21v-8" stroke-width="1.5" opacity=".55"/></svg>`,
};

const AVATAR_HUES = ["#5B57D6", "#D9764F", "#3FAFA6", "#C98C42", "#5C8FE0"];

function initials(name) {
  return name.split(" ").map((w) => w[0]).slice(0, 2).join("").toUpperCase();
}
function avatarColor(name) {
  return AVATAR_HUES[name.charCodeAt(0) % AVATAR_HUES.length];
}
function el(tag, attrs = {}, html = "") {
  const node = document.createElement(tag);
  Object.entries(attrs).forEach(([k, v]) => {
    if (k === "class") node.className = v;
    else if (k === "style") node.style.cssText = v;
    else node.setAttribute(k, v);
  });
  if (html) node.innerHTML = html;
  return node;
}

const state = {
  conversations: [],
  selectedId: null,
  filter: "all",
  query: "",
  mobileView: "list",
  whatsapp: { ready: false },
  telegram: { ready: false },
  me: window.__ME__ || null,
};

// Any API call can come back 401 if the session expired — bounce to the login page
// rather than showing a broken dashboard.
async function apiFetch(url, opts) {
  const res = await fetch(url, opts);
  if (res.status === 401) {
    window.location.href = "/login.php";
    throw new Error("unauthorized");
  }
  return res;
}

async function fetchStatus() {
  try {
    const res = await apiFetch("/api/status.php");
    const data = await res.json();
    state.whatsapp = data.whatsapp;
    state.telegram = data.telegram;
  } catch (e) { /* ignore transient errors */ }
}

async function logout() {
  window.location.href = "/logout.php";
}

let lastSignature = "";
async function fetchConversations({ silent = false } = {}) {
  try {
    const res = await apiFetch("/api/conversations.php");
    const data = await res.json();
    const signature = JSON.stringify(data);
    if (signature === lastSignature) return; // nothing changed, skip a re-render
    lastSignature = signature;
    state.conversations = data;
    if (!state.selectedId && state.conversations.length) {
      state.selectedId = state.conversations[0].id;
    }
    render();
  } catch (e) {
    if (!silent) console.error("Failed to load conversations:", e);
  }
}

async function selectConversation(id) {
  state.selectedId = id;
  state.mobileView = "chat";
  const conv = state.conversations.find((c) => c.id === id);
  if (conv && conv.unread) {
    await apiFetch(`/api/mark_read.php?id=${encodeURIComponent(id)}`, { method: "POST" });
    conv.unread = 0;
  }
  render();
}

async function sendMessage(text) {
  const conv = state.conversations.find((c) => c.id === state.selectedId);
  if (!conv || !text.trim()) return;
  const res = await apiFetch(`/api/send_message.php?id=${encodeURIComponent(conv.id)}`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ text }),
  });
  if (!res.ok) {
    const err = await res.json().catch(() => ({}));
    alert("Gagal mengirim pesan: " + (err.detail || err.error || "unknown error"));
  } else {
    fetchConversations();
  }
}

function renderAvatar(name, size) {
  const c = avatarColor(name);
  const wrap = el("div", {
    class: "gi-avatar",
    style: `width:${size}px;height:${size}px;background:linear-gradient(155deg, ${c} 0%, ${c}CC 100%)`,
  });
  wrap.innerHTML = `<span class="gi-display" style="color:#FFFFFF;font-size:${size * 0.36}px">${escapeHtml(initials(name))}</span>`;
  return wrap;
}

function renderChannelBadge(channel, size) {
  const c = CHANNEL[channel];
  const badge = el("div", {
    class: "gi-channel-badge",
    style: `width:${size + 8}px;height:${size + 8}px;background:${c.color}`,
  });
  badge.innerHTML = channel === "whatsapp" ? ICONS.phone : ICONS.paperplane;
  return badge;
}

function renderStatusBar() {
  const bar = el("div", { class: "gi-status-bar" });
  const wa = state.whatsapp.ready;
  const tg = state.telegram.ready;
  bar.innerHTML = `
    <div class="gi-status-pill">
      <span class="gi-status-dot" style="background:${wa ? CHANNEL.whatsapp.color : "#C7C9D1"}"></span>
      WhatsApp ${wa ? "terhubung" : "belum dikonfigurasi"}
    </div>
    <div class="gi-status-pill">
      <span class="gi-status-dot" style="background:${tg ? CHANNEL.telegram.color : "#C7C9D1"}"></span>
      Telegram ${tg ? "terhubung" : "belum dikonfigurasi"}
    </div>
  `;
  return bar;
}

function renderTopbar() {
  const bar = el("div", { class: "gi-topbar" });

  const brand = el("div", { class: "gi-topbar-brand" });
  const mark = el("div", { class: "gi-gate-mark" }, ICONS.gate);
  const label = el("div");
  label.innerHTML = `
    <div class="gi-topbar-name gi-display">Gerbang Inbox</div>
    <div class="gi-topbar-tagline">Satu pintu untuk WhatsApp & Telegram</div>
  `;
  brand.appendChild(mark);
  brand.appendChild(label);
  bar.appendChild(brand);

  if (state.me) {
    const actions = el("div", { class: "gi-topbar-actions" });
    const name = el("span", { class: "gi-agent-name" }, `${escapeHtml(state.me.displayName || state.me.username)}`);
    const manageBtn = el("button", { class: "gi-manage-btn" }, "Pengguna");
    manageBtn.onclick = () => { window.location.href = "/users.php"; };
    const logoutBtn = el("button", { class: "gi-logout-btn" }, "Keluar");
    logoutBtn.onclick = () => logout();
    actions.appendChild(name);
    actions.appendChild(manageBtn);
    actions.appendChild(logoutBtn);
    bar.appendChild(actions);
  }

  return bar;
}

function renderSidebar() {
  const wrap = el("div", { class: `gi-sidebar ${state.mobileView === "chat" ? "hide-mobile" : ""}` });

  wrap.appendChild(renderStatusBar());

  const toolbar = el("div", { class: "gi-sidebar-toolbar" });
  const searchWrap = el("div", { class: "gi-search" });
  searchWrap.innerHTML = ICONS.search;
  const input = el("input", { placeholder: "Cari kontak..." });
  input.value = state.query;
  input.oninput = (e) => {
    state.query = e.target.value;
    renderConvList(list);
  };
  searchWrap.appendChild(input);

  const filters = el("div", { class: "gi-filters" });
  [
    { key: "all", label: "Semua", color: BRASS },
    { key: "whatsapp", label: "WhatsApp", color: CHANNEL.whatsapp.color },
    { key: "telegram", label: "Telegram", color: CHANNEL.telegram.color },
  ].forEach((f) => {
    const btn = el("button", { class: `gi-filter-btn ${state.filter === f.key ? "active" : ""}`, style: `--accent:${f.color}` }, f.label);
    btn.onclick = () => {
      state.filter = f.key;
      render();
    };
    filters.appendChild(btn);
  });

  toolbar.appendChild(searchWrap);
  toolbar.appendChild(filters);
  wrap.appendChild(toolbar);

  const list = el("div", { class: "gi-conv-list" });
  wrap.appendChild(list);
  renderConvList(list);

  return wrap;
}

function renderConvList(list) {
  list.innerHTML = "";
  const filtered = state.conversations.filter((c) => {
    const matchesFilter = state.filter === "all" || c.channel === state.filter;
    const matchesQuery = c.name.toLowerCase().includes(state.query.toLowerCase());
    return matchesFilter && matchesQuery;
  });

  if (!filtered.length) {
    list.appendChild(el("div", { class: "gi-conv-list-empty" }, "Belum ada percakapan.<br />Kirim pesan ke bot Telegram atau nomor WhatsApp yang terhubung untuk memulai."));
    return;
  }

  filtered.forEach((c) => {
    const item = el("button", { class: `gi-conv-item ${c.id === state.selectedId ? "active" : ""}` });
    item.onclick = () => selectConversation(c.id);

    const avatarWrap = el("div", { class: "gi-avatar-wrap" });
    avatarWrap.appendChild(renderAvatar(c.name, 40));
    avatarWrap.appendChild(renderChannelBadge(c.channel, 13));

    const lastMsg = c.messages && c.messages.length ? c.messages[c.messages.length - 1].text : "";
    const body = el("div", { style: "flex:1;min-width:0" });
    body.innerHTML = `
      <div class="gi-conv-top">
        <span class="gi-conv-name">${escapeHtml(c.name)}</span>
        <span class="gi-conv-time ${c.unread ? "unread" : ""}">${escapeHtml(c.lastAt || "")}</span>
      </div>
      <div class="gi-conv-bottom">
        <span class="gi-conv-preview">${escapeHtml(lastMsg)}</span>
        ${c.unread ? `<span class="gi-badge">${c.unread}</span>` : ""}
      </div>
    `;

    item.appendChild(avatarWrap);
    item.appendChild(body);
    list.appendChild(item);
  });
}

function renderChat() {
  const conv = state.conversations.find((c) => c.id === state.selectedId);
  const wrap = el("div", { class: `gi-chat ${state.mobileView === "list" ? "hide-mobile" : ""}` });

  if (!conv) {
    const empty = el("div", { class: "gi-empty" });
    empty.appendChild(el("div", { class: "gi-gate-mark", style: "width:44px;height:44px" }, ICONS.gate.replace('width="18" height="18"', 'width="30" height="30"')));
    empty.appendChild(el("div", {}, "Belum ada percakapan untuk ditampilkan."));
    wrap.appendChild(empty);
    return wrap;
  }

  const accent = CHANNEL[conv.channel].color;

  const header = el("div", { class: "gi-chat-header" });
  const backBtn = el("button", { class: "gi-back-btn" });
  backBtn.innerHTML = ICONS.back;
  backBtn.onclick = () => {
    state.mobileView = "list";
    render();
  };
  header.appendChild(backBtn);
  header.appendChild(renderAvatar(conv.name, 36));
  const headInfo = el("div", { style: "flex:1;min-width:0" });
  headInfo.innerHTML = `
    <div class="gi-chat-name">${escapeHtml(conv.name)}</div>
    <div class="gi-chat-sub">
      <span class="gi-online-dot" style="background:${conv.online ? "#1FA855" : "#C7C9D1"}"></span>
      ${conv.online ? "Online" : conv.lastSeen ? `Terakhir dilihat ${escapeHtml(conv.lastSeen)}` : "Offline"} · ${CHANNEL[conv.channel].label}
    </div>
  `;
  header.appendChild(headInfo);
  wrap.appendChild(header);

  const messages = el("div", { class: "gi-messages", id: "gi-messages" });
  let lastDay = null;
  (conv.messages || []).forEach((m) => {
    if (m.day && m.day !== lastDay) {
      const divider = el("div", { class: "gi-date-divider" });
      divider.innerHTML = `<span>${escapeHtml(m.day)}</span>`;
      messages.appendChild(divider);
      lastDay = m.day;
    }
    const mine = m.from === "me";
    const row = el("div", { class: `gi-msg-row ${mine ? "mine" : ""}` });
    const bubble = el("div", { class: "gi-bubble", style: mine ? `--accent:${accent}` : "" });
    const agentLabel = mine && m.agent ? `<div class="gi-msg-agent">${escapeHtml(m.agent)}</div>` : "";
    let tickHtml = "";
    if (mine) {
      const tickClass = m.status === "read" ? "read" : "sent";
      const tickIcon = m.status === "delivered" || m.status === "read" ? ICONS.check : ICONS.checkSingle;
      tickHtml = `<span class="gi-tick ${tickClass}">${tickIcon}</span>`;
    }
    bubble.innerHTML = `${agentLabel}${escapeHtml(m.text)}<div class="gi-meta">${m.time}${tickHtml}</div>`;
    row.appendChild(bubble);
    messages.appendChild(row);
  });
  wrap.appendChild(messages);

  const composer = el("div", { class: "gi-composer" });
  const clip = el("div", { class: "gi-icon-btn" }, ICONS.paperclip);
  const smile = el("div", { class: "gi-icon-btn" }, ICONS.smile);
  const input = el("input", { placeholder: "Tulis pesan...", "data-testid": "composer-input" });
  const sendBtn = el("button", { class: "gi-send-btn", style: `--accent:${accent}` });
  sendBtn.innerHTML = ICONS.send;

  const doSend = () => {
    if (!input.value.trim()) return;
    sendMessage(input.value);
    input.value = "";
  };
  input.onkeydown = (e) => { if (e.key === "Enter") doSend(); };
  sendBtn.onclick = doSend;

  composer.appendChild(clip);
  composer.appendChild(smile);
  composer.appendChild(input);
  composer.appendChild(sendBtn);
  wrap.appendChild(composer);

  setTimeout(() => { messages.scrollTop = messages.scrollHeight; }, 0);

  return wrap;
}

function renderDetail() {
  const conv = state.conversations.find((c) => c.id === state.selectedId);
  if (!conv) return el("div", { class: "gi-detail" });

  const accent = CHANNEL[conv.channel].color;
  const wrap = el("div", { class: "gi-detail" });
  wrap.innerHTML = `
    <div class="gi-detail-head">
      <div class="gi-avatar" style="width:64px;height:64px;background:${avatarColor(conv.name)};margin:0 auto"><span class="gi-display" style="color:#FFFFFF;font-size:23px">${escapeHtml(initials(conv.name))}</span></div>
      <div class="gi-detail-name gi-display">${escapeHtml(conv.name)}</div>
      <div class="gi-detail-handle">${escapeHtml(conv.handle || "")}</div>
    </div>
    <div class="gi-detail-section">
      <div class="gi-channel-pill" style="background:${accent}22;border:1px solid ${accent}44;color:${accent}">
        ${CHANNEL[conv.channel].label}
      </div>
      <div>
        <div class="gi-label-title">Label</div>
        <div class="gi-tag-chip">${ICONS.tag} ${escapeHtml(conv.tag || "Baru")}</div>
      </div>
      <div>
        <div class="gi-label-title">Status</div>
        <div class="gi-detail-meta-row">${ICONS.clock} Terakhir aktif ${escapeHtml(conv.lastAt || "-")}</div>
      </div>
      <div>
        <div class="gi-label-title">Kontak sejak</div>
        <div class="gi-detail-meta-row">${escapeHtml(conv.joined || "-")}</div>
      </div>
      <div>
        <div class="gi-label-title">Riwayat</div>
        <div class="gi-detail-meta-row">${(conv.messages || []).length} pesan tercatat</div>
      </div>
    </div>
  `;
  return wrap;
}

function escapeHtml(str) {
  const d = document.createElement("div");
  d.innerText = str;
  return d.innerHTML;
}

function render() {
  const app = document.getElementById("app");
  app.innerHTML = "";
  const shell = el("div", { class: "gi-shell" });
  shell.appendChild(renderTopbar());

  const body = el("div", { class: "gi-body" });
  body.appendChild(renderSidebar());
  const conv = state.conversations.find((c) => c.id === state.selectedId);
  body.appendChild(el("div", { class: "gi-gate-rail", style: `--accent:${conv ? CHANNEL[conv.channel].color : BRASS}` }));
  body.appendChild(renderChat());
  body.appendChild(renderDetail());
  shell.appendChild(body);

  app.appendChild(shell);
}

async function init() {
  await fetchStatus();
  await fetchConversations();
  render();

  // No websockets in the PHP version — poll for new messages/conversations instead.
  setInterval(() => fetchConversations({ silent: true }), POLL_MS);
  setInterval(fetchStatus, POLL_MS * 5);
}
init();
