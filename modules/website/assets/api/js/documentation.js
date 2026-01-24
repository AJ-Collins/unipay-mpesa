/* =================== CONFIG & STORAGE =================== */
axios.defaults.withCredentials = true;
let envs = JSON.parse(localStorage.getItem("ad_envs") || "null") || {};
let defaultEnv = localStorage.getItem("ad_defaultEnv") || "";

// If defaultEnv is invalid, fall back to first available or "dev"
if (!defaultEnv || !envs[defaultEnv]) {
  const keys = Object.keys(envs);
  defaultEnv = keys.length > 0 ? keys[0] : "dev"; // fallback to first or "dev"
  localStorage.setItem("ad_defaultEnv", defaultEnv); // persist fix
}

// Now safe to access
const currentEnv = envs[defaultEnv] || { desc: "No environment" };
document.getElementById("envLabel") &&
  (document.getElementById("envLabel").innerText = currentEnv.desc ?? "No Env");

let corsProxy = localStorage.getItem("ad_corsProxy") || "";
let openApiUrl = localStorage.getItem("ad_openApiUrl") || null;
/* Auth */
const AUTH = {
  basicUser: localStorage.getItem("ad_basicUser") || "",
  basicPass: localStorage.getItem("ad_basicPass") || "",
  bearer: localStorage.getItem("ad_bearer") || "",
  apiKey: localStorage.getItem("ad_apiKey") || "",
};
/* Storage containers */
const PLUGINS = [];
/* Persist helpers */
function persistEnvs() {
  localStorage.setItem("ad_envs", JSON.stringify(envs));
}

function persistDefaultEnv() {
  // Ensure defaultEnv exists in envs
  if (!envs[defaultEnv]) {
    const keys = Object.keys(envs);
    defaultEnv = keys.length > 0 ? keys[0] : "dev";
  }
  localStorage.setItem("ad_defaultEnv", defaultEnv);

  const currentEnv = envs[defaultEnv] || { desc: "Unknown" };
  const label = document.getElementById("envLabel");
  if (label) {
    label.innerText = currentEnv.desc || defaultEnv;
  }
}

function persistAuth() {
  localStorage.setItem("ad_basicUser", AUTH.basicUser);
  localStorage.setItem("ad_basicPass", AUTH.basicPass);
  localStorage.setItem("ad_bearer", AUTH.bearer);
  localStorage.setItem("ad_apiKey", AUTH.apiKey);
}
/* Utilities */
function escapeHtml(s) {
  if (s === undefined || s === null) return "";
  return String(s)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;");
}

function prettyJson(obj) {
  try {
    return JSON.stringify(obj, null, 2);
  } catch (e) {
    return String(obj);
  }
}
// New: Debounce utility for inputs (e.g., future auto-save)
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}
// New: Show toast notifications for better UX (using Bootstrap alerts)
// ================== Enhanced iziToast wrapper ==================
function showToast(msg, type = "info") {
  const toastConfig = {
    message: msg,
    position: "topCenter", // all toasts appear at bottom-right
    timeout: 10000, // duration in ms
    progressBar: true, // show progress bar
    close: true, // show close button
    pauseOnHover: true, // pause timeout when hovering
    transitionIn: "fadeInDown",
    transitionOut: "fadeOutUp",
  };

  // Set color and icon based on type
  switch (type) {
    case "success":
      iziToast.success(toastConfig);
      break;
    case "danger":
      iziToast.error(toastConfig);
      break;
    case "warning":
      iziToast.warning(toastConfig);
      break;
    case "info":
    default:
      iziToast.info(toastConfig);
      break;
  }
}

function applyAuth() {
  AUTH.basicUser = document.getElementById("authBasicUser").value;
  AUTH.basicPass = document.getElementById("authBasicPass").value;
  AUTH.bearer = document.getElementById("authBearer").value;
  AUTH.apiKey = document.getElementById("authApiKey").value;
  persistAuth();
  showToast("Auth applied successfully", "success");
}

function buildAuthHeaders() {
  const headers = {};
  if (AUTH.basicUser && AUTH.basicPass) {
    headers["Authorization"] =
      "Basic " + btoa(`${AUTH.basicUser}:${AUTH.basicPass}`);
    return headers;
  }
  if (AUTH.bearer) {
    headers["Authorization"] = "Bearer " + AUTH.bearer;
  }
  if (AUTH.apiKey) {
    headers["x-api-key"] = AUTH.apiKey;
  }
  return headers;
}

function registerPlugin(p) {
  PLUGINS.push(p);
  if (p.init) p.init();
}
/* =================== OPENAPI LOAD & PARSE =================== */
let openApiData = null;

async function loadOpenApi(urlOverride) {
  const url = urlOverride || openApiUrl;
  setLoading(true);

  try {
    const finalUrl =
      corsProxy && corsProxy.trim() !== "" ? corsProxy + url : url;
    const r = await axios.get(finalUrl, {
      timeout: 10000,
    });
    openApiData = r.data;
    document.getElementById("openApiUrlInput").value = openApiUrl;

    // ===== Generate environments from OpenAPI servers =====
    const servers = openApiData?.servers ?? [];
    if (servers.length) {
      envs = {}; // reset
      servers.forEach((s) => {
        const name = (s.description || s.url || "env")
          .toLowerCase()
          .replace(/[^a-z0-9]/g, "");
        envs[name] = {
          baseUrl: s.url || "",
          desc: s.description || "",
        };
      });
    } else {
      envs = {
        dev: {
          baseUrl: "http://localhost",
        },
      };
    }

    // Persist environments
    persistEnvs();

    // Optional: set defaultEnv to first environment if not set
    if (!defaultEnv || !envs[defaultEnv]) {
      defaultEnv = Object.keys(envs)[0];
      persistDefaultEnv();
    }

    // Re-render environment selector
    if (typeof rebuildEnvironmentSelector === "function")
      rebuildEnvironmentSelector();

    // Continue as normal
    buildTagMapAndRender();
    showToast("OpenAPI loaded successfully", "success");
  } catch (e) {
    //console.error("Failed to load OpenAPI:", e);
    const errorMsg = e.response?.status
      ? `HTTP ${e.response.status}: ${e.response.statusText}`
      : e.message;
    document.getElementById(
      "content-area"
    ).innerHTML = `<div class="alert alert-danger"><i class="fa fa-exclamation-triangle me-2"></i>Failed to load OpenAPI JSON from <code>${escapeHtml(
      url
    )}</code>. ${errorMsg}. Check console for details.</div>`;
    showToast("Failed to load OpenAPI", "danger");
  } finally {
    setLoading(false);
  }
}

/* Build tag map */
let TAG_MAP = {};

function buildTagMapAndRender() {
  if (!openApiData) return;
  TAG_MAP = {};
  const paths = openApiData.paths || {};
  for (const path in paths) {
    const methods = paths[path];
    for (const m in methods) {
      const op = methods[m];
      const tags = op.tags && op.tags.length ? op.tags : ["Default"];
      const ep = {
        path,
        method: m.toUpperCase(),
        summary: op.summary || op.operationId || path,
        description: op.description || "",
        parameters: op.parameters || [],
        requestBody: op.requestBody || null,
        responses: op.responses || {},
        operation: op,
      };
      tags.forEach((t) => {
        if (!TAG_MAP[t]) TAG_MAP[t] = [];
        TAG_MAP[t].push(ep);
      });
    }
  }
  renderSidebar();
  renderOverview();
}
/* =================== SIDEBAR =================== */
// New: Mobile sidebar toggle
function toggleSidebar() {
  const sidebar = document.getElementById("sidebar");
  sidebar.classList.toggle("show");
}

function renderSidebar() {
  const el = document.getElementById("sidebar");
  el.innerHTML = `<nav class="menu px-2"></nav>`;

  const menu = el.querySelector(".menu");

  for (const tag in TAG_MAP) {
    // MENU ITEM (tag header)
    const menuItem = document.createElement("div");
    menuItem.className = "menu-item";
    menuItem.dataset.tag = tag;
    menuItem.innerHTML = `
      <span>${escapeHtml(tag)}</span>
      <i class="fas fa-chevron-right arrow"></i>
    `;
    menuItem.onclick = function () {
      toggleSubmenu(this);
    };

    menu.appendChild(menuItem);

    // SUBMENU (endpoints)
    const submenu = document.createElement("div");
    submenu.className = "submenu";

    TAG_MAP[tag].forEach((ep) => {
      const item = document.createElement("a");
      item.className = "submenu-item";
      item.href = "#";
      item.dataset.path = ep.path;
      item.dataset.method = ep.method;

      const color = methodColors[ep.method] || "secondary";
      item.innerHTML = `
        <span class="text-truncate" style="max-width: 160px;">${escapeHtml(
          ep.summary || ep.path
        )}</span>
        <span class="badge bg-${color} ms-auto">${ep.method}</span>
      `;

      item.onclick = (e) => {
        e.preventDefault();
        openEndpointCard(ep);
        highlightActiveEndpoint(ep.path, ep.method);
        if (window.innerWidth < 768) toggleSidebar();
      };

      submenu.appendChild(item);
    });

    menu.appendChild(submenu);
  }
}

// TOGGLE SUBMENU (accordion style)
function toggleSubmenu(element) {
  const submenu = element.nextElementSibling;
  const isActive = element.classList.contains("active");

  // Close all
  document.querySelectorAll(".menu-item").forEach((item) => {
    item.classList.remove("active");
    item.querySelector(".arrow").style.transform = "rotate(0deg)";
  });
  document.querySelectorAll(".submenu").forEach((sub) => {
    sub.classList.remove("active");
  });

  // Open current if not active
  if (!isActive) {
    element.classList.add("active");
    element.querySelector(".arrow").style.transform = "rotate(90deg)";
    submenu.classList.add("active");
  }
}

// HIGHLIGHT ACTIVE ENDPOINT + OPEN PARENT
function highlightActiveEndpoint(path, method) {
  // Reset all active states
  document
    .querySelectorAll(".submenu-item.active, .menu-item.active")
    .forEach((el) => {
      el.classList.remove("active");
    });
  document
    .querySelectorAll(".submenu.active")
    .forEach((el) => el.classList.remove("active"));
  document
    .querySelectorAll(".arrow")
    .forEach((icon) => (icon.style.transform = "rotate(0deg)"));

  // Highlight clicked item
  const activeItem = document.querySelector(
    `.submenu-item[data-path="${path}"][data-method="${method}"]`
  );
  if (activeItem) {
    activeItem.classList.add("active");

    // Open parent menu item
    const parentMenu = activeItem.closest(".submenu").previousElementSibling;
    if (parentMenu && parentMenu.classList.contains("menu-item")) {
      parentMenu.classList.add("active");
      parentMenu.querySelector(".arrow").style.transform = "rotate(90deg)";
      activeItem.closest(".submenu").classList.add("active");
    }

    // Smooth scroll
    activeItem.scrollIntoView({ behavior: "smooth", block: "center" });
  }
}

function renderAllResponseSamplesTabbed(ep) {
  const container = document.getElementById("responseSamplesContainer");
  container.innerHTML = "";

  const responses = ep.responses || {};
  const statusCodes = Object.keys(responses);

  if (statusCodes.length === 0) {
    container.innerHTML = `<p class="text-muted">No response samples available.</p>`;
    return;
  }

  // Sort status codes: success first (2xx), then others
  statusCodes.sort((a, b) => {
    if (a.startsWith('2') && !b.startsWith('2')) return -1;
    if (!a.startsWith('2') && b.startsWith('2')) return 1;
    return a.localeCompare(b);
  });

  // Build tab buttons with description
  let buttonsHtml = `<div class="btn-group mb-3" role="group" aria-label="Response tabs">`;
  statusCodes.forEach((statusCode, idx) => {
    const response = responses[statusCode];
    const description =  response.description ?? getDefaultDescription(statusCode);
    const active = idx === 0 ? "active" : "";

    buttonsHtml += `
      <button 
        type="button" 
        class="btn btn-outline-primary ${active}" 
        data-target="resp-${statusCode}"
        title="${escapeHtml(description)}">
        ${statusCode} - ${escapeHtml(description)}
      </button>`;
  });
  buttonsHtml += `</div>`;

  // Build tab content
  let contentHtml = ``;
  statusCodes.forEach((statusCode, idx) => {
    const display = idx === 0 ? "block" : "none";
    contentHtml += `
      <div id="resp-${statusCode}" class="resp-tab-content border p-3 bg-light rounded" style="display:${display};">
        ${renderResponseSamplesForStatus(responses[statusCode], statusCode)}
      </div>
    `;
  });

  container.innerHTML = `
    <h6 class="fw-bold mb-2">Response Samples</h6>
    ${buttonsHtml}
    <div class="mt-3">${contentHtml}</div>
  `;

  // Tab switching logic
  container.querySelectorAll(".btn-group button").forEach((btn) => {
    btn.addEventListener("click", () => {
      // Update active button
      container.querySelectorAll(".btn-group button").forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");

      // Show corresponding content
      const target = btn.dataset.target;
      container.querySelectorAll(".resp-tab-content").forEach((tab) => {
        tab.style.display = "none";
      });
      container.querySelector(`#${target}`).style.display = "block";
    });
  });
 // activate highlight.js after DOM update
  container
    .querySelectorAll("pre")
    .forEach((pre) => hljs.highlightElement(pre));
}

// Helper: fallback descriptions for common codes
function getDefaultDescription(statusCode) {
  const defaults = {
    "200": "OK",
    "201": "Created",
    "202": "Accepted",
    "204": "No Content",
    "400": "Bad Request",
    "401": "Unauthorized",
    "403": "Forbidden",
    "404": "Not Found",
    "422": "Validation error",
    "429": "Too Many Requests",
    "500": "Internal Server Error",
    "503": "Service Unavailable",
  };
  return defaults[statusCode] || "Unknown";
}

function renderResponseSamplesForStatus(resObj, statusCode) {
  if (!resObj || !resObj.content)
    return `<p class="text-muted">No samples.</p>`;

  let html = ``;

  for (const contentType of Object.keys(resObj.content)) {
    const media = resObj.content[contentType];
    const schema = media.schema;
    let example = null;

    // direct example
    if (media.example) {
      example = media.example;
    }
    // examples.{key}.value
    else if (media.examples) {
      const f = Object.keys(media.examples)[0];
      example = media.examples[f].value;
    }
    // fallback sample from schema
    else if (schema) {
      example = schemaToExample(schema);
    }

    if (!example) continue;

    html += `
      <div class="mb-3">
        <div class="fw-bold mb-1">${contentType}</div>
        <pre class="json-pre hljs">${escapeHtml(prettyJson(example))}</pre>
      </div>
    `;
  }

  return html || `<p class="text-muted">No samples available.</p>`;
}

let currentRequestBodyType = null; // used by executeTry

function renderRequestBodyThemed(ep) {
  const rb = ep.requestBody;
  const container = document.getElementById("requestBodyContainer");

  if (!rb || !rb.content) {
    container.innerHTML = `<p class="text-muted">No request body</p>`;
    currentRequestBodyType = null;
    return;
  }

  const jsonMedia = rb.content["application/json"];
  const formMedia = rb.content["application/x-www-form-urlencoded"];

  let html = `<h6 class="fw-bold mb-2">Request Body </h6>
              <p class="text-muted fst-italic">${escapeHtml(rb.description || "No description")}</p>`;

  //
  // ────────────────────────────────────────────────────────────────
  // JSON REQUEST BODY
  // ────────────────────────────────────────────────────────────────
  //
  if (jsonMedia) {
    currentRequestBodyType = "json";

    const schema = jsonMedia.schema || {};
    const exampleJson = prettyJson(schemaToExample(schema));
    const schemaTree = renderSchemaNode(schema, {
      level: 0,
      propName: "RequestBody",
      visitedRef: new Set(),
    });

    html += `
      <!-- Toggle Buttons -->
      <div class="btn-group mb-2" role="group" aria-label="Request Body Tabs">
        <button class="btn btn-sm btn-primary active" id="reqTabExample">Example</button>
        <button class="btn btn-sm btn-outline-primary" id="reqTabSchema">Schema</button>
      </div>

      <!-- Example JSON -->
      <div id="reqExamplePane">
        <textarea id="requestBody" class="form-control" rows="8" disabled>${escapeHtml(
          exampleJson
        )}</textarea>
      </div>

      <!-- Schema Tree -->
      <div id="reqSchemaPane" class="d-none">
        <div class="border rounded p-2 bg-light">${schemaTree}</div>
      </div>
    `;
  }

  //
  // ────────────────────────────────────────────────────────────────
  // FORM-URLENCODED REQUEST BODY
  // ────────────────────────────────────────────────────────────────
  //
  else if (formMedia) {
    currentRequestBodyType = "form";

    const schema = formMedia.schema || {};
    const props = schema.properties || {};

    html += `
      <div id="formFields" class="mt-2">
    `;

    for (const [name, prop] of Object.entries(props)) {
      const placeholder = prop.example || prop.default || prop.type || "";
      html += `
        <label class="form-label" for="field-${name}">${name}</label>
        <input type="text" class="form-control mb-2"
               id="field-${name}"
               name="${name}"
               placeholder="${escapeHtml(placeholder)}">
      `;
    }

    html += `</div>`;
  }

  //
  // ────────────────────────────────────────────────────────────────
  // UNSUPPORTED BODY
  // ────────────────────────────────────────────────────────────────
  //
  else {
    html += `<p class="text-muted">No supported request body format.</p>`;
    currentRequestBodyType = null;
  }

  container.innerHTML = html;

  //
  // ────────────────────────────────────────────────────────────────
  // TAB BEHAVIOR (Example / Schema)
  // ────────────────────────────────────────────────────────────────
  //
  const btnExample = document.getElementById("reqTabExample");
  const btnSchema = document.getElementById("reqTabSchema");
  const paneExample = document.getElementById("reqExamplePane");
  const paneSchema = document.getElementById("reqSchemaPane");

  if (btnExample && btnSchema) {
    btnExample.addEventListener("click", () => {
      btnExample.classList.add("btn-primary");
      btnExample.classList.remove("btn-outline-primary");

      btnSchema.classList.add("btn-outline-primary");
      btnSchema.classList.remove("btn-primary");

      paneExample.classList.remove("d-none");
      paneSchema.classList.add("d-none");
    });

    btnSchema.addEventListener("click", () => {
      btnSchema.classList.add("btn-primary");
      btnSchema.classList.remove("btn-outline-primary");

      btnExample.classList.add("btn-outline-primary");
      btnExample.classList.remove("btn-primary");

      paneSchema.classList.remove("d-none");
      paneExample.classList.add("d-none");
    });
  }
}
// New: Show loading state
function setLoading(state = true) {
  const content = document.getElementById("content-area");
  content.innerHTML = state
    ? '<div class="text-center"><div class="loading-spinner me-2"></div>Loading...</div>'
    : content.innerHTML;
}
const methodColors = {
  GET: "primary",
  POST: "success",
  PUT: "warning text-dark",
  PATCH: "info text-dark",
  DELETE: "danger",
  HEAD: "secondary",
  OPTIONS: "dark",
};

/* =================== Renderers =================== */
function renderOverview() {
  const info = openApiData ? openApiData.info || {} : {};
  document.getElementById("content-area").innerHTML = `
    <div class="container-fluid p-0">
      <h2>${escapeHtml(info.title || "API Overview")}</h2>
      <p class="text-muted">Version ${escapeHtml(info.version || "")}</p>
      <p>${escapeHtml(info.description || "")}</p>
      <div class="row">
        <div class="col-md-6">
          <div class="card mb-3"><div class="card-body">
            <h6>Contact</h6>
            <p>${
              openApiData?.info?.contact
                ? escapeHtml(openApiData.info.contact.name || "") +
                  " &lt;" +
                  escapeHtml(openApiData.info.contact.email || "") +
                  "&gt;"
                : '<span class="text-muted">—</span>'
            }</p>
          </div></div>
        </div>
        <div class="col-md-6">
          <div class="card mb-3"><div class="card-body">
            <h6>License</h6>
            <p>${
              openApiData?.info?.license
                ? escapeHtml(openApiData.info.license.name || "")
                : '<span class="text-muted">—</span>'
            }</p>
          </div></div>
        </div>
      </div>
    </div>
  `;
}
/* =================== MODELS: recursion-safe Swagger-like renderer =================== */
/* Resolve $ref string to schema object and name */
function resolveRefObject(ref) {
  if (!ref) return null;
  const name = ref
    .replace("#/components/schemas/", "")
    .replace("#/definitions/", "");
  const resolved =
    openApiData?.components?.schemas?.[name] ||
    openApiData?.definitions?.[name] ||
    null;
  return {
    name,
    resolved,
  };
}
/* Render a schema node (Swagger-like) with visitedRef guard
       visitedRefSet is a Set of ref names currently in stack to prevent infinite recursion */
function renderSchemaNode(schema, opts = {}) {
  const { level = 0, propName = null, visitedRef = new Set() } = opts;
  if (!schema) return "";
  // If it's a $ref, resolve
  if (schema.$ref) {
    const { name, resolved } = resolveRefObject(schema.$ref);
    if (!resolved) {
      return `<div class="prop"><strong>${escapeHtml(
        propName || name
      )}</strong> <span class="text-muted">(unresolved ref)</span></div>`;
    }
    if (visitedRef.has(name)) {
      // recursive reference detected
      return `<div class="prop"><strong>${escapeHtml(
        propName || name
      )}</strong> <span class="text-muted">↳ ${escapeHtml(
        name
      )} (recursive)</span></div>`;
    }
    // add to visited and render resolved
    const newVisited = new Set(visitedRef);
    newVisited.add(name);
    return renderSchemaNode(resolved, {
      level,
      propName: propName || name,
      visitedRef: newVisited,
    });
  }
  // oneOf / anyOf / allOf
  if (schema.oneOf || schema.anyOf || schema.allOf) {
    const group = schema.oneOf || schema.anyOf || schema.allOf;
    const label = schema.oneOf ? "oneOf" : schema.anyOf ? "anyOf" : "allOf";
    let html = `<div class="prop"><strong>${
      propName ? escapeHtml(propName) : label
    }</strong> <span class="text-muted">(${label})</span></div>`;
    html += `<div class="schema-indent">`;
    group.forEach((sub, i) => {
      html += `<div class="mb-2">${renderSchemaNode(sub, {
        level: level + 1,
        propName: null,
        visitedRef,
      })}</div>`;
    });
    html += `</div>`;
    return html;
  }
  // Arrays
  if (schema.type === "array") {
    let html = `<div class="prop"><strong>${
      propName ? escapeHtml(propName) : "items"
    }</strong> <span class="text-muted">(array)</span></div>`;
    html += `<div class="schema-indent">`;
    html += renderSchemaNode(schema.items || {}, {
      level: level + 1,
      propName: "items",
      visitedRef,
    });
    html += `</div>`;
    return html;
  }
  // Objects
  if (schema.type === "object" || schema.properties) {
    let title = propName
      ? `<strong>${escapeHtml(
          propName
        )}</strong> <span class="text-muted">(${escapeHtml(
          schema.type || "object"
        )})</span>`
      : "";
    let html = `<div class="prop">${title}</div>`;
    const props = schema.properties || {};
    html += `<div class="schema-indent">`;
    for (const key in props) {
      const p = props[key];
      // If p is $ref and points to a visited ref, show recursive arrow
      if (p.$ref) {
        const { name } = resolveRefObject(p.$ref);
        if (visitedRef.has(name)) {
          html += `<div class="mb-2"><strong>${escapeHtml(
            key
          )}</strong> <span class="text-muted">↳ ${escapeHtml(
            name
          )} (recursive)</span></div>`;
          continue;
        }
      }
      html += `<div class="mb-2">${renderSchemaNode(p, {
        level: level + 1,
        propName: key,
        visitedRef,
      })}</div>`;
    }
    html += `</div>`;
    return html;
  }
  // Primitives
  const typeLabel = escapeHtml(schema.type || schema.format || "any");
  const example =
    schema.example !== undefined
      ? schema.example
      : schema.default !== undefined
      ? schema.default
      : "";
  const description = schema.description ? escapeHtml(schema.description) : "";
  let ex = "";
  if (example !== "") {
    // Show raw example for strings/numbers/booleans
    if (
      typeof example === "string" ||
      typeof example === "number" ||
      typeof example === "boolean"
    ) {
      ex = escapeHtml(String(example));
    } else {
      ex = escapeHtml(prettyJson(example));
    }
  } else {
    // fallback placeholder
    if (schema.type === "string") ex = "";
    else if (schema.type === "array") ex = "[]";
    else if (schema.type === "object") ex = "{}";
    else if (schema.type === "integer" || schema.type === "number") ex = "0";
    else if (schema.type === "boolean") ex = "true";
    else ex = "";
  }
  let out = `<div><code class="text-muted">${typeLabel}</code>`;
  if (propName)
    out = `<div><strong>${escapeHtml(
      propName
    )}</strong> <small class="text-muted">(${typeLabel})</small></div>`;
  if (ex !== "")
    out += `<div class="schema-indent mt-1"><strong>Example:</strong> <code>${escapeHtml(
      String(ex)
    )}</code></div>`;
  if (description)
    out += `<div class="schema-indent"><small class="text-muted">${description}</small></div>`;
  return out;
}
/* Render top-level properties table (Property | Type | Example | Description) */
function renderModelPropertiesTable(schema) {
  if (!schema || !schema.properties) return "";
  let html = `<table class="table table-bordered table-sm mt-3"><thead class="table-light"><tr>
    <th style="width:20%;">Property</th>
    <th style="width:10%;">Type</th>
    <th style="width:30%;">Example</th>
    <th style="width:40%;">Description</th>
  </tr></thead><tbody>`;
  for (const key in schema.properties) {
    const p = schema.properties[key];
    const type =
      p.type ||
      (p.$ref ? p.$ref.replace("#/components/schemas/", "") : "object");
    let example = "";
    if (p.example !== undefined) example = p.example;
    else if (p.default !== undefined) example = p.default;
    else if (type === "array") example = "[]";
    else if (type === "object") example = "{}";
    else if (type === "string") example = "";
    else if (type === "integer" || type === "number") example = 0;
    else if (type === "boolean") example = true;
    html += `<tr>
      <td>${escapeHtml(key)}</td>
      <td>${escapeHtml(String(type))}</td>
      <td>${escapeHtml(String(example))}</td>
      <td>${escapeHtml(p.description || "")}</td>
    </tr>`;
  }
  html += `</tbody></table>`;
  return html;
}
/* Compose full model output (table + schema tree) */
function renderFullModel(schema, modelName) {
  // For each model expand using a fresh visitedRef set
  const table = renderModelPropertiesTable(schema);
  const tree = `<div class="mt-3"><h6 class="mb-1">Schema</h6><div class="schema-tree border rounded p-2 bg-light">${renderSchemaNode(
    schema,
    { level: 0, propName: modelName, visitedRef: new Set() }
  )}</div></div>`;
  return table + tree;
}
/* Main models renderer (accordion) */
function renderModels() {
  if (!openApiData) {
    document.getElementById("content-area").innerHTML =
      '<p class="text-muted">No API loaded</p>';
    return;
  }
  const models =
    openApiData.components?.schemas || openApiData.definitions || {};
  const container = document.createElement("div");
  container.innerHTML = `<h3>Models / Schemas</h3>`;
  const accordion = document.createElement("div");
  accordion.className = "accordion";
  accordion.id = "modelsAccordion";
  let idx = 0;
  for (const name in models) {
    const schema = models[name];
    const item = document.createElement("div");
    item.className = "accordion-item mb-2";
    const bodyHtml = renderFullModel(schema, name);
    item.innerHTML = `
      <h2 class="accordion-header" id="mheading${idx}">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#mcollapse${idx}" aria-expanded="false" aria-controls="mcollapse${idx}">
          ${escapeHtml(name)}
        </button>
      </h2>
      <div id="mcollapse${idx}" class="accordion-collapse collapse" data-bs-parent="#modelsAccordion" aria-labelledby="mheading${idx}">
        <div class="accordion-body">${bodyHtml}</div>
      </div>
    `;
    accordion.appendChild(item);
    idx++;
  }
  container.appendChild(accordion);
  document.getElementById("content-area").innerHTML = "";
  document.getElementById("content-area").appendChild(container);
}
/* =================== ENDPOINT CARD & TRY-OUT =================== */
let currentEndpoint = null;

function openEndpointCard(ep) {
  currentEndpoint = ep;
  const content = document.getElementById("content-area");
  const color = methodColors[ep.method] || "secondary";
  // params html
  let paramsHtml = "";
  const params = ep.parameters || [];
  if (params.length) {
    paramsHtml += '<div class="row">';
    params.forEach((p) => {
      const col = p.in === "path" || p.in === "query" ? "col-md-3" : "col-4";
      paramsHtml += `<div class="${col} mb-2">
        <label class="form-label mb-1" for="param-${escapeHtml(
          p.name
        )}">${escapeHtml(p.description || p.name)} <small class="text-muted">(${escapeHtml(
        p.in
      )})</small></label>
        <input class="form-control form-control-sm try-input" id="param-${escapeHtml(
          p.name
        )}" data-name="${escapeHtml(p.name)}" data-in="${escapeHtml(
        p.in
      )}" placeholder="${escapeHtml(
       p.schema?.example || p.description || "Enter " + p.name
      )}" disabled aria-describedby="param-help-${escapeHtml(p.name)}">
      </div>`;
    });
    paramsHtml += "</div>";
  } else {
    paramsHtml = '<p class="text-muted">No parameters</p>';
  }
  let bodyExample = "";
  const rb = ep.requestBody;
  if (
    rb &&
    rb.content &&
    rb.content["application/json"] &&
    rb.content["application/json"].schema
  ) {
    const ex = schemaToExample(rb.content["application/json"].schema);
    bodyExample = prettyJson(ex);
  }
  content.innerHTML = `
    <div class="card mb-3 shadow-sm">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div><span class="badge bg-${color} method-badge me-2">${
    ep.method
  }</span><span class="fs-6">${escapeHtml(ep.path)}</span></div>
        <div class="d-flex align-items-center">
          <div class="me-2 text-muted small">${escapeHtml(
            ep.description || ep.summary || ""
          )}</div>
          <button class="btn btn-outline-primary btn-sm me-2" id="tryOutBtn" onclick="toggleTryOut()" aria-label="Toggle try-out mode">Try out</button>
          <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">More</button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" onclick="showSchemaModal(currentEndpoint)" role="button" tabindex="0">View Schema</a></li>
              <li><a class="dropdown-item" onclick="openSaveRequestModal()" role="button" tabindex="0">Save Request</a></li>
            </ul>
          </div>
        </div>
      </div>
      <div class="card-body">
        <h6 class="fw-bold mb-2">Parameters</h6>
        ${paramsHtml}
        <hr>
        <div id="requestBodyContainer"></div>
        <div id="tryControlArea" class="mt-3" style="display:none;">
          <div class="try-controls">
            <button class="btn btn-success btn-sm" onclick="executeTry(currentEndpoint)" aria-label="Execute request">Execute</button>
            <button class="btn btn-outline-secondary btn-sm" onclick="cancelTry()" aria-label="Cancel try-out">Cancel</button>
            <button class="btn btn-outline-info btn-sm" onclick="showCurl()" aria-label="Show cURL command">Show cURL</button>
          </div>
        </div>
        <hr>
        <div class="row">
          <div class="col-md-12">
            <div id="curlBox" style="display:none;"><h6 class="fw-bold mb-2">cURL</h6><div id="curlData" class="curl-box"></div></div>
          </div>
          <div class="col-md-6">
            <h6 class="fw-bold mb-2">Response</h6>
            <pre id="responseBox" class="json-pre hljs" style="min-height:160px;max-height:510px;">(execute to see result)</pre>
          </div>
          <div class="col-md-6">
            <h6 class="fw-bold mb-2">Response headers</h6>
            <pre id="responseHeaders" class="json-pre hljs" style="min-height:160px;max-height:510px;">-</pre>
          </div>
        </div>
        <hr>
        <div class="row">
          <div class="col-md-12">
            <div id="responseSamplesContainer"></div>
          </div>
        </div>
      </div>
    </div>
  `;
  window.scrollTo({
    top: 0,
    behavior: "smooth",
  });
  renderAllResponseSamplesTabbed(ep);
  renderRequestBodyThemed(ep);
}

function toggleTryOut() {
  const btn = document.getElementById("tryOutBtn");
  const enabling = btn.innerText.toLowerCase().includes("try");
  const inputs = document.querySelectorAll(".try-input");
  const body = document.getElementById("requestBody");
  inputs.forEach((i) => (i.disabled = !enabling));
  if (body) body.disabled = !enabling;
  document.getElementById("tryControlArea").style.display = enabling
    ? "block"
    : "none";
  btn.innerText = enabling ? "Disable" : "Try out";
  document.getElementById("responseBox").innerText = "(execute to see result)";
  document.getElementById("responseHeaders").innerText = "-";
  document.getElementById("curlBox").style.display = "none";
}

function cancelTry() {
  const btn = document.getElementById("tryOutBtn");
  const inputs = document.querySelectorAll(".try-input");
  const body = document.getElementById("requestBody");
  inputs.forEach((i) => {
    i.disabled = true;
    i.value = "";
  });
  if (body) {
    body.disabled = true;
    const rb = currentEndpoint.requestBody;
    if (
      rb &&
      rb.content &&
      rb.content["application/json"] &&
      rb.content["application/json"].schema
    ) {
      document.getElementById("requestBody").value = prettyJson(
        schemaToExample(rb.content["application/json"].schema)
      );
    } else document.getElementById("requestBody").value = "";
  }
  document.getElementById("tryControlArea").style.display = "none";
  btn.innerText = "Try out";
  document.getElementById("responseBox").innerText = "(execute to see result)";
  document.getElementById("responseHeaders").innerText = "-";
  document.getElementById("curlBox").style.display = "none";
}

function showSchemaModal(ep) {
  const modal = new bootstrap.Modal(document.getElementById("schemaModal"));
  const body = document.getElementById("schemaModalBody");
  const schema =
    ep.requestBody?.content?.["application/json"]?.schema || ep.operation || {};
  body.innerHTML = `<pre class="json-pre hljs">${escapeHtml(
    prettyJson(schema)
  )}</pre>`;
  modal.show();
}

function buildUrlForEndpoint(ep, pathParams, queryParams) {
  let path = ep.path;
  for (const k in pathParams)
    path = path.replace(`{${k}}`, encodeURIComponent(pathParams[k]));
  const envBase = envs[defaultEnv]?.baseUrl || "";
  let url = envBase + path;
  if (Object.keys(queryParams).length) {
    const qs = new URLSearchParams(queryParams).toString();
    url += (url.includes("?") ? "&" : "?") + qs;
  }
  if (corsProxy) url = corsProxy + url;
  return url;
}

function buildCurl(method, url, headers, body) {
  const safeHeaders = Object.entries(headers || {})
    .map(([k, v]) => `-H "${k}: ${String(v).replace(/"/g, '\\"')}"`)
    .join(" ");
  const dataPart = body ? `--data '${JSON.stringify(body)}'` : "";
  return `curl -X ${method} ${safeHeaders} ${dataPart} "${url}"`;
}
async function executeTry(ep) {
  const inputs = document.querySelectorAll(".try-input");
  const pathParams = {},
    queryParams = {},
    headers = {};
  inputs.forEach((i) => {
    const name = i.dataset.name;
    const where = i.dataset.in;
    if (!i.value) return;
    if (where === "path") pathParams[name] = i.value;
    else if (where === "query") queryParams[name] = i.value;
    else if (where === "header") headers[name] = i.value;
    else if (where === "cookie") headers["Cookie"] = `${name}=${i.value}`;
  });

  // BUILD FRESH AUTH HEADERS EVERY TIME
  let authHeaders = buildAuthHeaders();
  Object.assign(headers, authHeaders);

  let body = null;
  if (currentRequestBodyType === "form") {
    body = {};
    const inputs = document.querySelectorAll("#formFields input");
    inputs.forEach((inp) => {
      body[inp.name] = inp.value;
    });
  } else if (currentRequestBodyType === "json") {
    const txt = (document.getElementById("requestBody")?.value ?? "").trim();
    if (!txt) body = [];
    else {
      const parsed = JSON.parse(txt || "null");
      body = parsed === null ? [] : parsed;
    }
  }

  const url = buildUrlForEndpoint(ep, pathParams, queryParams);
  const method = ep.method.toLowerCase();

  const curl = buildCurl(ep.method, url, headers, body);
  document.getElementById("curlData").innerText = curl;
  document.getElementById("curlBox").style.display = "block";

  // Helper to make request with CURRENT headers
  const makeRequest = async () => {
    // REBUILD AUTH HEADERS RIGHT BEFORE SENDING (critical for retry!)
    const currentHeaders = { ...headers };
    const freshAuth = buildAuthHeaders();
    Object.assign(currentHeaders, freshAuth);

    const start = Date.now();
    const resp = await axios({
      method,
      url,
      headers: currentHeaders,
      data: body,
      validateStatus: () => true,
      timeout: 30000,
      withCredentials: true,
    });
    const duration = Date.now() - start;
    return { resp, duration };
  };

  try {
    let { resp, duration } = await makeRequest();

    // AUTO-SAVE FROM LOGIN
    if (
      method === "post" &&
      (url.includes("/auth/login") || url.includes("/login"))
    ) {
      autoSaveBearerToken(resp);
    }

    // AUTO-REFRESH ON 401
    if (resp.status === 401 && AUTH.bearer) {
      showToast("Access token expired. Refreshing...", "info");

      try {
        const refreshUrl = buildUrlForEndpoint(
          { path: "/iam/auth/refresh" },
          {},
          {}
        );
        const refreshResp = await axios.post(
          refreshUrl,
          {},
          {
            withCredentials: true,
            validateStatus: () => true,
          }
        );

        if (refreshResp.status === 200) {
          autoSaveBearerToken(refreshResp);
          showToast("Token refreshed!", "success");

          // RETRY WITH FRESH HEADERS
          ({ resp, duration } = await makeRequest());
        } else {
          throw new Error("Refresh failed");
        }
      } catch (refreshErr) {
        showToast("Session expired. Please log in again.", "danger");
        localStorage.removeItem("ad_bearer");
        AUTH.bearer = "";
        const input = document.getElementById("authBearer");
        if (input) input.value = "";
        throw refreshErr;
      }
    }

    // SUCCESS DISPLAY
    const respBox = document.getElementById("responseBox");
    respBox.innerHTML = `<pre><code class="json">${escapeHtml(
      prettyJson(resp.data)
    )}</code></pre>`;
    respBox
      .querySelectorAll("pre code")
      .forEach((block) => hljs.highlightElement(block));

    const headersBox = document.getElementById("responseHeaders");
    headersBox.innerHTML = `<pre><code class="json">${escapeHtml(
      prettyJson({
        status: resp.status,
        time_ms: duration,
        headers: resp.headers,
      })
    )}</code></pre>`;
    headersBox
      .querySelectorAll("pre code")
      .forEach((block) => hljs.highlightElement(block));

    // Store the new entry in IndexedDB
    db.history
      .add({
        time: new Date().toISOString(),
        method: ep.method,
        url: url,
        request: body,
        response: resp.data,
        status: resp.status,
        duration: duration,
      })
      .catch((err) => {
        console.error("Failed to save history entry:", err);
      });

    showToast(`Success: ${resp.status} in ${duration}ms`, "success");
  } catch (err) {
    const errorMsg = err.response?.data
      ? prettyJson(err.response.data)
      : String(err.message || err);
    document.getElementById(
      "responseBox"
    ).innerHTML = `<pre class="text-danger">${escapeHtml(errorMsg)}</pre>`;
    document.getElementById(
      "responseHeaders"
    ).innerHTML = `<pre><code class="json">${escapeHtml(
      prettyJson({
        status: err.response?.status || "Error",
        error: err.message,
      })
    )}</code></pre>`;
    showToast("Failed: " + (err.response?.status || err.message), "danger");
  }
}

function showCurl() {
  const cb = document.getElementById("curlBox");
  cb.style.display = cb.style.display === "block" ? "none" : "block";
}

function openSaveRequestModal() {
  new bootstrap.Modal(document.getElementById("saveRequestModal")).show();
}
/* =================== WebSocket Tester =================== */
let ws = null;

function renderWebSocketTester() {
  document.getElementById("content-area").innerHTML = `
    <h3>WebSocket Tester</h3>
    <div class="mb-2">
      <label class="form-label" for="wsUrl">WebSocket URL</label>
      <input id="wsUrl" class="form-control" placeholder="wss://echo.websocket.org" aria-describedby="ws-url-help" />
      <small id="ws-url-help" class="form-text text-muted">Enter a WebSocket URL (e.g., wss://echo.websocket.org).</small>
    </div>
    <div class="mb-2 d-flex gap-2">
      <button class="btn btn-sm btn-primary" onclick="openWs()" aria-label="Open WebSocket">Open</button>
      <button class="btn btn-sm btn-success" onclick="sendWs()" aria-label="Send message">Send</button>
      <button class="btn btn-sm btn-danger" onclick="closeWs()" aria-label="Close WebSocket">Close</button>
    </div>
    <div class="mb-2">
      <label class="form-label" for="wsMessage">Message</label>
      <textarea id="wsMessage" class="form-control" rows="3" placeholder="message" aria-describedby="ws-msg-help"></textarea>
      <small id="ws-msg-help" class="form-text text-muted">Message to send over WebSocket.</small>
    </div>
    <pre id="wsLog" class="json-pre hljs" style="min-height:200px;"></pre>
  `;
}

function openWs() {
  const url = document.getElementById("wsUrl").value.trim();
  if (!url) {
    showToast("Enter a WebSocket URL", "warning");
    return;
  }
  if (ws && ws.readyState === WebSocket.OPEN) {
    showToast("WebSocket already open", "warning");
    return;
  }
  ws = new WebSocket(url);
  ws.onopen = () => logWs("OPEN");
  ws.onmessage = (e) => logWs("RECV: " + e.data);
  ws.onerror = (e) => logWs("ERR: " + JSON.stringify(e));
  ws.onclose = () => logWs("CLOSED");
  showToast("WebSocket opened", "success");
}

function sendWs() {
  if (!ws || ws.readyState !== WebSocket.OPEN) {
    showToast("WebSocket not open", "warning");
    return;
  }
  const m = document.getElementById("wsMessage").value.trim();
  if (!m) {
    showToast("Enter a message to send", "warning");
    return;
  }
  ws.send(m);
  logWs("SENT: " + m);
  document.getElementById("wsMessage").value = ""; // New: Clear input after send
}

function closeWs() {
  if (ws) {
    ws.close();
    showToast("WebSocket closed", "info");
  }
}

function logWs(msg) {
  const l = document.getElementById("wsLog");
  const timestamp = new Date().toLocaleTimeString();
  l.innerText = (l.innerText || "") + `\n[${timestamp}] ${msg}`;
  l.scrollTop = l.scrollHeight; // New: Auto-scroll to bottom
}
/* =================== Collections & History =================== */
async function loadHistory() {
    const allEntries = await db.history
        .orderBy('time')  // or .reverse() for newest first
        .toArray();
    
    HISTORY = allEntries;  // populate your in-memory array if you have one
    // Now render/update UI with HISTORY
}


function renderHistory() {
  loadHistory();
  let html = `
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="mb-0">API Test History</h3>
      <button class="btn btn-sm btn-outline-danger" id="clear-history-btn">
        Clear History
      </button>
    </div>
  `;
  if (HISTORY.length === 0) {
    html += '<p class="text-muted">No history</p>';
  }
  HISTORY.slice()
    .reverse()
    .forEach((h) => {
      html += `
      <div class="card mb-2">
        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div><strong>${escapeHtml(h.method)}</strong> 
              <small>${escapeHtml(h.url)}</small></div>
            <div><small class="text-muted">
              ${escapeHtml(new Date(h.time).toLocaleString())} • 
              ${h.duration || 0} ms • ${h.status || ""}
            </small></div>
          </div>
          <pre class="json-pre hljs mt-2">${escapeHtml(
            prettyJson(h.response)
          )}</pre>
        </div>
      </div>`;
    });
  document.getElementById("content-area").innerHTML = html;
  document.getElementById("clear-history-btn").onclick = async () => {
    if (!confirm("Clear all history? This cannot be undone.")) {
        return;
    }
    try {
        // Clear IndexedDB
        await db.history.clear();
        // Clear in-memory array
        HISTORY = [];
        // Update the UI
        renderHistory();
        // Feedback to user
        showToast("API test history cleared", "success");
    } catch (err) {
        console.error("Failed to clear history:", err);
        showToast("Failed to clear history", "error");
    }
};
  
}

/* =================== Settings & Envs =================== */
function openDefaultEnvModal() {
  const el = document.getElementById("envList");
  el.innerHTML = "";
  for (const k in envs) {
    el.innerHTML += `<div class="d-flex mb-2 align-items-center">
      <div class="me-2"><strong>${escapeHtml(k)}</strong></div>
      <div class="flex-grow-1">${escapeHtml(envs[k].baseUrl)}</div>
      <div>
        <button class="btn btn-sm btn-outline-secondary me-1" onclick="setDefaultEnv('${escapeHtml(
          k
        )}')" aria-label="Set as default">Set default</button>
      </div>
    </div>`;
  }
  new bootstrap.Modal(document.getElementById("defaultEnvModal")).show();
}

function setDefaultEnv(name) {
  defaultEnv = name;
  persistDefaultEnv();
  showToast(`Default Environment set to ${name}`, "success");
}

function closeEnvModal() {
  persistEnvs();
  bootstrap.Modal.getInstance(
    document.getElementById("defaultEnvModal")
  ).hide();
  document.getElementById("envLabel").innerText = envs[defaultEnv].desc;
}
/* Settings save/reset */
function saveSettings() {
  openApiUrl =
    document.getElementById("openApiUrlInput").value.trim() || openApiUrl;
  corsProxy =
    document.getElementById("corsProxyInput").value.trim() || corsProxy;
  localStorage.setItem("ad_openApiUrl", openApiUrl);
  localStorage.setItem("ad_corsProxy", corsProxy);
  loadOpenApi(openApiUrl);
  showToast("Settings saved", "success");
}

function resetConfig() {
  if (confirm("Reset all settings? This will reload the default OpenAPI.")) {
    localStorage.removeItem("ad_envs");
    localStorage.removeItem("ad_defaultEnv");
    localStorage.removeItem("ad_corsProxy");
    localStorage.removeItem("ad_openApiUrl");
    envs = DEFAULT_ENVIRONMENTS;
    defaultEnv = "dev";
    corsProxy = "";
    openApiUrl = "";
    persistEnvs();
    persistDefaultEnv();
    showToast("Settings reset. Reloading OpenAPI...", "info");
    loadOpenApi(openApiUrl);
  }
}
/* =================== Schema example generator (used for request body) =================== */
function schemaToExample(schema) {
  if (!schema) return "";
  if (schema.example !== undefined) return schema.example;
  if (schema.$ref) {
    const { name, resolved } = resolveRefObject(schema.$ref);
    if (resolved) return schemaToExample(resolved);
    return {};
  }
  if (schema.type === "object") {
    const o = {};
    const props = schema.properties || {};
    for (const k in props) {
      o[k] = schemaToExample(props[k]);
    }
    return o;
  }
  if (schema.type === "array") {
    const it = schema.items ? schemaToExample(schema.items) : null;
    return [it];
  }
  if (schema.type === "string") return "";
  if (schema.type === "integer" || schema.type === "number") return 0;
  if (schema.type === "boolean") return true;
  return "";
}
/* =================== Init =================== */
document.getElementById("openApiUrlInput").value = openApiUrl;
document.getElementById("corsProxyInput").value = corsProxy;
document.getElementById("authBasicUser").value = AUTH.basicUser;
document.getElementById("authBasicPass").value = AUTH.basicPass;
document.getElementById("authBearer").value = AUTH.bearer;
document.getElementById("authApiKey").value = AUTH.apiKey;
/* convenience wrappers used earlier in navbar onclicks */

function showHistory() {
  renderHistory();
}

function showModels() {
  renderModels();
}

function renderOverviewSection() {
  renderOverview();
}
/* New: Keyboard navigation support for sidebar links */
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape" && window.innerWidth < 768) toggleSidebar();
});
/* load API now */
loadOpenApi(openApiUrl);
async function checkProxy() {
  const proxyUrl = corsProxy;
  const badge = document.getElementById("proxyStatus");

  if (!proxyUrl) {
    badge.className = "badge bg-warning text-dark";
    badge.textContent = "Proxy: OFF";
    return;
  }

  try {
    const u = new URL(envs[defaultEnv].baseUrl);
    const testUrl = proxyUrl + encodeURIComponent(`${u.protocol}//${u.host}`);
    await fetch(testUrl, {
      method: "GET",
    });

    badge.className = "badge bg-success";
    badge.textContent = "Proxy: OK";
  } catch (e) {
    badge.className = "badge bg-danger";
    badge.textContent = "Proxy: FAIL";
  }
}
// Run at load & after saving settings
document.addEventListener("DOMContentLoaded", checkProxy);
const originalSaveSettings = saveSettings;
saveSettings = function () {
  originalSaveSettings();
  checkProxy();
};
// Auto-save Bearer token from successful login responses
function autoSaveBearerToken(response) {
  try {
    // Handle your API response format
    const data = response.data?.dataPayload?.data || response.data;

    if (data && data.access_token && data.token_type === "Bearer") {
      // Save to localStorage
      localStorage.setItem("ad_bearer", data.access_token);

      // Update the input field
      const bearerInput = document.getElementById("authBearer");
      if (bearerInput) {
        bearerInput.value = data.access_token;
      }

      // Update global AUTH
      AUTH.bearer = data.access_token;

      showToast("Bearer token auto-saved!", "success");
    }
  } catch (e) {
    console.warn("Failed to auto-save token:", e);
  }
}
