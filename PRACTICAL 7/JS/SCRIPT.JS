
/* Practical 4 - DOM Manipulation, Event Handling and UI Interactivity */
(function () {
  "use strict";

  const $ = (selector, parent = document) => parent.querySelector(selector);
  const $$ = (selector, parent = document) => [...parent.querySelectorAll(selector)];

  // Practical 5: registration validation, password meter and Canvas CAPTCHA.
  const registrationForm = $("#registrationForm");
  if (registrationForm) {
    const fields = ["fullname", "email", "phone", "dob", "gender", "studentid", "course", "year", "password", "confirmPassword", "captchaInput"];
    let captchaCode = "";
    const rules = {
      fullname: value => /^[A-Za-z][A-Za-z .'-]{1,59}$/.test(value) || "Enter a valid name (letters, spaces, apostrophes or hyphens).",
      email: value => /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value) || "Enter a valid email address.",
      phone: value => /^(?:\+91)?[6-9]\d{9}$/.test(value.replace(/[()\s-]/g, "")) || "Enter a valid 10-digit mobile number.",
      dob: value => Boolean(value) || "Select your date of birth.",
      gender: value => Boolean(value) || "Select a gender option.",
      studentid: value => /^[A-Za-z0-9-]{4,20}$/.test(value) || "Use 4–20 letters, numbers or hyphens.",
      course: value => Boolean(value) || "Select your course or branch.",
      year: value => /^(20\d{2})$/.test(value) && Number(value) <= 2099 || "Enter a year between 2000 and 2099.",
      password: value => /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/.test(value) || "Use 8+ characters with uppercase, lowercase, number and symbol.",
      confirmPassword: value => value === $("#password").value || "Passwords do not match.",
      captchaInput: value => value.toUpperCase() === captchaCode || "CAPTCHA does not match. Generate a new code if needed."
    };
    const errorFor = id => $("#" + id + "Error");
    function setError(id, message) {
      const input = $("#" + id), error = errorFor(id);
      if (input) input.setAttribute("aria-invalid", message ? "true" : "false");
      if (error) error.textContent = message || "";
      return !message;
    }
    function validateField(id) {
      const input = $("#" + id);
      const result = rules[id] ? rules[id](input.value.trim()) : true;
      return setError(id, result === true ? "" : result);
    }
    function drawCaptcha() {
      const canvas = $("#captchaCanvas"), ctx = canvas.getContext("2d");
      const chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
      captchaCode = Array.from({ length: 6 }, () => chars[Math.floor(Math.random() * chars.length)]).join("");
      ctx.clearRect(0, 0, canvas.width, canvas.height); ctx.fillStyle = "#f3f6fa"; ctx.fillRect(0, 0, canvas.width, canvas.height);
      for (let i = 0; i < 7; i++) { ctx.strokeStyle = `rgba(52, 152, 219, ${Math.random() * .5})`; ctx.beginPath(); ctx.moveTo(Math.random() * 220, Math.random() * 65); ctx.lineTo(Math.random() * 220, Math.random() * 65); ctx.stroke(); }
      ctx.font = "bold 30px sans-serif"; ctx.textBaseline = "middle";
      [...captchaCode].forEach((char, i) => { ctx.save(); ctx.translate(22 + i * 31, 33); ctx.rotate((Math.random() - .5) * .35); ctx.fillStyle = i % 2 ? "#2c3e50" : "#23689b"; ctx.fillText(char, -10, 0); ctx.restore(); });
      $("#captchaInput").value = ""; setError("captchaInput", "");
    }
    function updateStrength() {
      const value = $("#password").value, bar = $("#strengthBar"), text = $("#strengthText");
      const score = [/[a-z]/, /[A-Z]/, /\d/, /[^A-Za-z\d]/, /.{12,}/].filter(re => re.test(value)).length;
      const labels = ["not entered", "very weak", "weak", "medium", "strong", "very strong"];
      bar.style.width = `${score * 20}%`; bar.style.background = score < 3 ? "#e74c3c" : score < 5 ? "#f39c12" : "#27ae60"; text.textContent = `Password strength: ${labels[score]}`;
    }
    fields.forEach(id => { const input = $("#" + id); input.addEventListener("blur", () => validateField(id)); input.addEventListener("input", () => { validateField(id); if (id === "password") updateStrength(); }); input.addEventListener("change", () => validateField(id)); });
    $("#terms").addEventListener("change", () => setError("terms", $("#terms").checked ? "" : "Accept the terms to continue."));
    $$(".toggle-password").forEach(button => button.addEventListener("click", () => { const input = $("#" + button.dataset.target); const shown = input.type === "text"; input.type = shown ? "password" : "text"; button.textContent = shown ? "Show" : "Hide"; button.setAttribute("aria-label", shown ? "Show password" : "Hide password"); }));
    $("#refreshCaptcha").addEventListener("click", drawCaptcha);
    registrationForm.addEventListener("submit", event => {
      const valid = fields.map(validateField).every(Boolean) && setError("terms", $("#terms").checked ? "" : "Accept the terms to continue.");
      const summary = $("#formSummary");
      if (!valid) { event.preventDefault(); summary.hidden = false; summary.className = "form-summary"; summary.textContent = "Please correct the highlighted fields before submitting."; const first = registrationForm.querySelector('[aria-invalid="true"]') || $("#terms"); first.focus(); }
      else {
        event.preventDefault();
        // Save THIS user's data locally so Dashboard/Profile show their info (not a fixed placeholder)
        try {
          const user = {
            fullname: $("#fullname").value.trim(),
            email: $("#email").value.trim(),
            phone: $("#phone").value.trim(),
            dob: $("#dob").value,
            gender: $("#gender").value,
            studentid: $("#studentid").value.trim(),
            course: $("#course").value,
            year: $("#year").value
          };
          localStorage.setItem("studentHubUser", JSON.stringify(user));
          if (window.StudentHubAuth) window.StudentHubAuth.saveUser(user);
        } catch (e) {}
        summary.hidden = false; summary.className = "form-summary success"; summary.textContent = "Registration validated successfully. Redirecting to your dashboard…";
        setTimeout(() => { window.location.href = "DASHBOARD.HTML"; }, 900);
      }
    });
    drawCaptcha(); updateStrength();
  }

  function createElement(tag, className, text) {
    const el = document.createElement(tag);
    if (className) el.className = className;
    if (text !== undefined) el.textContent = text;
    return el;
  }

  // DOM-created notification banner
  const banner = createElement("div", "p4-banner");
  banner.append("Welcome to Student Hub! Explore the interactive features of this portal. ");
  const dismiss = createElement("button", "", "Dismiss");
  dismiss.setAttribute("aria-label", "Dismiss notification");
  dismiss.addEventListener("click", () => banner.remove());
  banner.appendChild(dismiss);
  document.body.prepend(banner);

  // DOM-created controls: theme switcher and hamburger menu
  const tools = createElement("div", "p4-tools");
  const themeButton = createElement("button", "p4-tool", "🌙 Dark Mode");
  themeButton.type = "button";
  themeButton.setAttribute("aria-pressed", "false");
  const menuButton = createElement("button", "p4-tool p4-hamburger", "☰ Menu");
  menuButton.type = "button";
  menuButton.setAttribute("aria-expanded", "false");
  tools.append(themeButton, menuButton);
  banner.after(tools);

  const savedTheme = localStorage.getItem("studentHubTheme");
  if (savedTheme === "dark") document.body.classList.add("dark-theme");

  function updateThemeButton() {
    const dark = document.body.classList.contains("dark-theme");
    themeButton.textContent = dark ? "☀️ Light Mode" : "🌙 Dark Mode";
    themeButton.setAttribute("aria-pressed", String(dark));
  }
  updateThemeButton();
  themeButton.addEventListener("click", () => {
    document.body.classList.toggle("dark-theme");
    localStorage.setItem("studentHubTheme",
      document.body.classList.contains("dark-theme") ? "dark" : "light");
    updateThemeButton();
  });

  menuButton.addEventListener("click", () => {
    const menu = $(".nav-menu");
    if (!menu) return;
    const open = menu.classList.toggle("p4-open");
    menuButton.setAttribute("aria-expanded", String(open));
  });

  // Accessible modal popup
  const modal = createElement("div", "p4-modal");
  modal.id = "p4Modal";
  modal.setAttribute("role", "dialog");
  modal.setAttribute("aria-modal", "true");
  modal.setAttribute("aria-labelledby", "p4ModalTitle");
  const modalBox = createElement("div", "p4-modal-box");
  const close = createElement("button", "p4-close", "×");
  close.setAttribute("aria-label", "Close dialog");
  const modalTitle = createElement("h2", "", "Student Hub Notification");
  modalTitle.id = "p4ModalTitle";
  const modalText = createElement("p", "", "Your action was received successfully.");
  modalBox.append(close, modalTitle, modalText);
  modal.appendChild(modalBox);
  document.body.appendChild(modal);

  function openModal(message, title = "Student Hub Notification") {
    modalTitle.textContent = title;
    modalText.textContent = message;
    modal.classList.add("open");
    close.focus();
  }
  function closeModal() { modal.classList.remove("open"); }
  close.addEventListener("click", closeModal);
  modal.addEventListener("click", e => { if (e.target === modal) closeModal(); });
  document.addEventListener("keydown", e => { if (e.key === "Escape") closeModal(); });

  // Event registration buttons
  $$(".btn-register, .register-btn, [data-register]").forEach(btn => {
    btn.addEventListener("click", () => openModal("You have selected this event. Registration demo completed.", "Event Registration"));
  });

  // FAQ accordion using DOM class manipulation
  $$(".faq-question").forEach(button => {
    button.setAttribute("aria-expanded", "false");
    const answer = button.nextElementSibling;
    if (answer) answer.hidden = true;
    button.addEventListener("click", () => {
      const isOpen = button.getAttribute("aria-expanded") === "true";
      button.setAttribute("aria-expanded", String(!isOpen));
      if (answer) answer.hidden = isOpen;
      button.classList.toggle("active", !isOpen);
    });
  });

  // Search/filter FAQ items
  const faqSearch = $("#faqSearch");
  if (faqSearch) {
    faqSearch.addEventListener("input", () => {
      const query = faqSearch.value.toLowerCase().trim();
      $$(".faq-item").forEach(item => {
        item.style.display = item.textContent.toLowerCase().includes(query) ? "" : "none";
      });
    });
  }

  // Image/content slider added to the page through the DOM
  const slider = createElement("section", "p4-slider");
  slider.setAttribute("aria-label", "Student Hub highlights");
  const slides = [
    ["Learn", "Access courses, materials, and academic resources in one place."],
    ["Connect", "Discover events, communicate with classmates, and join activities."],
    ["Grow", "Track progress, celebrate achievements, and plan your next goal."]
  ];
  const slideNodes = slides.map((item, index) => {
    const article = createElement("article", "p4-slide" + (index === 0 ? " active" : ""));
    article.append(createElement("h3", "", item[0]), createElement("p", "", item[1]));
    article.setAttribute("aria-hidden", String(index !== 0));
    slider.appendChild(article);
    return article;
  });
  const controls = createElement("div", "p4-slider-controls");

  // Circular previous button
  const previous = createElement("button", "p4-arrow", "‹");
  previous.type = "button";
  previous.setAttribute("aria-label", "Previous slide");
  previous.title = "Previous slide";

  // Pagination dots
  const dots = createElement("div", "p4-dots");
  dots.setAttribute("role", "tablist");
  dots.setAttribute("aria-label", "Slider navigation");

  const dotNodes = slides.map((item, index) => {
    const dot = createElement("button", "p4-dot" + (index === 0 ? " active" : ""));
    dot.type = "button";
    dot.setAttribute("role", "tab");
    dot.setAttribute("aria-label", `Go to slide ${index + 1}`);
    dot.setAttribute("aria-selected", String(index === 0));
    dots.appendChild(dot);
    return dot;
  });

  // Circular next button
  const next = createElement("button", "p4-arrow", "›");
  next.type = "button";
  next.setAttribute("aria-label", "Next slide");
  next.title = "Next slide";

  controls.append(previous, dots, next);
  slider.appendChild(controls);
  const main = $("main") || $(".container") || $(".hero") || document.body;
  main.appendChild(slider);
  let current = 0;

  function showSlide(index) {
    current = (index + slideNodes.length) % slideNodes.length;

    slideNodes.forEach((slide, i) => {
      const active = i === current;
      slide.classList.toggle("active", active);
      slide.setAttribute("aria-hidden", String(!active));
    });

    dotNodes.forEach((dot, i) => {
      const active = i === current;
      dot.classList.toggle("active", active);
      dot.setAttribute("aria-selected", String(active));
    });
  }

  previous.addEventListener("click", () => showSlide(current - 1));
  next.addEventListener("click", () => showSlide(current + 1));
  dotNodes.forEach((dot, index) => {
    dot.addEventListener("click", () => showSlide(index));
  });

  // Toast notification for ordinary buttons
  const toast = createElement("div", "p4-toast");
  toast.setAttribute("role", "status");
  document.body.appendChild(toast);
  $$(".btn, .card-btn").forEach(btn => {
    if (!btn.classList.contains("btn-register")) {
      btn.addEventListener("click", () => {
        toast.textContent = "Navigation action selected.";
        toast.classList.add("show");
        setTimeout(() => toast.classList.remove("show"), 1800);
      });
    }
  });
})();
