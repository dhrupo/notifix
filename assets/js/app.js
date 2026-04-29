(function () {
  const config = window.RTNotifyConfig || null;

  if (!config || !config.transportDriver) {
    return;
  }

  const state = {
    connection: null,
    queue: [],
    showing: false,
    shownCount: 0,
    seen: new Set(),
    container: null,
  };

  function shouldConnect() {
    if (document.hidden) {
      return false;
    }

    const deviceTarget = String(config.displayRules.device_targeting || "all");
    const isMobile = window.matchMedia("(max-width: 767px)").matches;

    if (deviceTarget === "mobile" && !isMobile) {
      return false;
    }

    if (deviceTarget === "desktop" && isMobile) {
      return false;
    }

    const max = Number(config.displayRules.max_notifications_session || 5);
    return state.shownCount < max;
  }

  function buildContainer() {
    if (state.container) {
      return state.container;
    }

    const container = document.createElement("div");
    container.className = "rt-notify rt-notify--" + (config.ui.position || "bottom-left");
    container.style.setProperty("--rt-bg", config.ui.bg_color || "#111827");
    container.style.setProperty("--rt-text", config.ui.text_color || "#ffffff");
    container.style.setProperty("--rt-accent", config.ui.accent_color || "#22c55e");
    container.style.setProperty("--rt-radius", (config.ui.border_radius || 12) + "px");
    container.style.setProperty("--rt-width", (config.ui.max_width || 360) + "px");
    container.style.setProperty("--rt-gap", (config.ui.spacing || 16) + "px");
    container.style.setProperty("--rt-duration", (config.ui.animation_speed || 280) + "ms");
    document.body.appendChild(container);
    state.container = container;

    return container;
  }

  function connect() {
    if (!shouldConnect()) {
      return;
    }

    if (config.transportDriver === "pusher") {
      connectPusher();
      return;
    }

    if (config.transportDriver === "ably") {
      connectAbly();
      return;
    }

    if (config.transportDriver === "websocket") {
      connectWebSocket();
    }
  }

  function reconnect() {
    state.connection = null;
    window.setTimeout(connect, 5000);
  }

  function connectPusher() {
    if (!window.Pusher || state.connection) {
      return;
    }

    try {
      const pusher = new window.Pusher(config.providers.pusher.key, {
        cluster: config.providers.pusher.cluster,
        forceTLS: true,
      });
      const channel = pusher.subscribe(config.channelName);
      channel.bind(config.eventName, function (payload) {
        onPayload(payload);
      });
      state.connection = pusher;
    } catch (error) {
      reconnect();
    }
  }

  function connectAbly() {
    if (!window.Ably || state.connection) {
      return;
    }

    try {
      const clientId =
        (config.providers.ably.clientIdPrefix || "rt-notify") +
        "-" +
        Math.random().toString(36).slice(2, 10);
      const realtime = new window.Ably.Realtime({
        key: config.providers.ably.key,
        clientId: clientId,
      });
      const channel = realtime.channels.get(config.channelName);
      channel.subscribe(config.eventName, function (message) {
        onPayload(message.data);
      });
      state.connection = realtime;
    } catch (error) {
      reconnect();
    }
  }

  function connectWebSocket() {
    if (state.connection) {
      return;
    }

    try {
      state.connection = new window.WebSocket(config.providers.websocket.socketUrl);
      state.connection.addEventListener("message", function (event) {
        let payload;

        try {
          payload = JSON.parse(event.data);
        } catch (error) {
          return;
        }

        onPayload(payload);
      });
      state.connection.addEventListener("close", reconnect);
      state.connection.addEventListener("error", reconnect);
    } catch (error) {
      state.connection = null;
      reconnect();
    }
  }

  function onPayload(payload) {
    const normalized = payload.event || payload;
    const signature = normalized.id || normalized.dedupe_key || JSON.stringify(normalized);

    if (state.seen.has(signature)) {
      return;
    }

    state.seen.add(signature);
    state.queue.push(normalized);
    flushQueue();
  }

  function flushQueue() {
    if (state.showing || !state.queue.length) {
      return;
    }

    const next = state.queue.shift();
    renderToast(next);
  }

  function resolveActorName(item) {
    const fallback = config.fallbackName || "Someone";
    const identity = config.identity || {};

    if (identity.show_username !== "yes") {
      return fallback;
    }

    const actor = item.actor || {};

    if (identity.mask_mode === "always-anonymous") {
      return fallback;
    }

    return actor.username || actor.label || fallback;
  }

  function timeAgo(item) {
    const timestamp = Number(item.timestamp || 0);
    let date;

    if (timestamp > 0) {
      date = new Date(timestamp * 1000);
    } else {
      date = new Date(String(item.created_at || "").replace(" ", "T"));
    }

    if (Number.isNaN(date.getTime())) {
      return "";
    }

    const diff = Math.max(1, Math.floor((Date.now() - date.getTime()) / 1000));
    const units = [
      { limit: 60, seconds: 1, label: "second" },
      { limit: 3600, seconds: 60, label: "minute" },
      { limit: 86400, seconds: 3600, label: "hour" },
      { limit: 604800, seconds: 86400, label: "day" },
    ];

    for (const unit of units) {
      if (diff < unit.limit) {
        const value = Math.max(1, Math.floor(diff / unit.seconds));
        return value + " " + unit.label + (value === 1 ? "" : "s") + " ago";
      }
    }

    const days = Math.floor(diff / 86400);
    return days + " days ago";
  }

  function renderMessage(item) {
    const templates = config.templates || {};
    const template = templates[item.type] || "{actor_name} {object_name} {event_time_ago}";
    const actor = resolveActorName(item);
    const objectName = (item.object && item.object.label) || "this item";

    return template
      .replaceAll("{actor_name}", actor)
      .replaceAll("{object_name}", objectName)
      .replaceAll("{event_time_ago}", timeAgo(item))
      .replaceAll("{actor_location}", (item.actor && item.actor.location) || "")
      .replaceAll("{source}", item.source || "")
      .replaceAll("{event_type}", item.type || "")
      .replace(/\s+/g, " ")
      .trim();
  }

  function renderToast(item) {
    state.showing = true;
    state.shownCount += 1;

    const container = buildContainer();
    const toast = document.createElement("div");
    toast.className = "rt-notify__toast";
    toast.innerHTML = [
      '<div class="rt-notify__bar"></div>',
      '<div class="rt-notify__body">',
      '<p class="rt-notify__message"></p>',
      "</div>",
    ].join("");

    toast.querySelector(".rt-notify__message").textContent = renderMessage(item);
    container.appendChild(toast);

    window.requestAnimationFrame(function () {
      toast.classList.add("is-visible");
    });

    const duration = Number(config.ui.duration || 6000);

    window.setTimeout(function () {
      toast.classList.remove("is-visible");
      window.setTimeout(function () {
        toast.remove();
        state.showing = false;
        window.setTimeout(
          flushQueue,
          Number(config.displayRules.cooldown_between || 0) * 1000
        );
      }, Number(config.ui.animation_speed || 280));
    }, duration);
  }

  document.addEventListener("visibilitychange", function () {
    if (!document.hidden && !state.connection) {
      connect();
    }
  });

  window.setTimeout(connect, Number(config.displayRules.delay_before_first || 5) * 1000);
})();
