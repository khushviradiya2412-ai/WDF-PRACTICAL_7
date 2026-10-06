/* Student Hub - local (no-PHP) session bridge.
 * Saves the REGISTER.HTML data to localStorage so DASHBOARD.HTML /
 * PROFILE.HTML show THIS user's info instead of the hardcoded placeholder.
 * PHP flow (XAMPP) uses $_SESSION via dashboard.php / profile.php.
 */
(function () {
  "use strict";
  var KEY = "studentHubUser";

  function loadUser() {
    try {
      var raw = localStorage.getItem(KEY);
      if (!raw) return null;
      return JSON.parse(raw);
    } catch (e) { return null; }
  }

  function saveUser(u) {
    try { localStorage.setItem(KEY, JSON.stringify(u)); } catch (e) {}
  }

  function genderLabel(g) {
    var map = { female: "Female", male: "Male", "non-binary": "Non-binary", "prefer-not-to-say": "Prefer not to say" };
    return map[g] || g || "";
  }

  // Fill every [data-user="field"] element with the stored user's value.
  function paint() {
    var user = loadUser();
    if (!user) {
      // Also support ?fullname=...&email=... fallback (GET form submit without JS save)
      try {
        var q = new URLSearchParams(window.location.search);
        if (q.get("fullname")) {
          user = {
            fullname: q.get("fullname") || "",
            email: q.get("email") || "",
            phone: q.get("phone") || "",
            dob: q.get("dob") || "",
            gender: q.get("gender") || "",
            studentid: q.get("studentid") || "",
            course: q.get("course") || "",
            year: q.get("year") || ""
          };
          saveUser(user);
        }
      } catch (e) {}
    }
    if (!user) return;

    document.querySelectorAll("[data-user]").forEach(function (el) {
      var f = el.getAttribute("data-user");
      var v = user[f] || "";
      if (f === "gender") v = genderLabel(v);
      if (v) el.textContent = v;
    });

    // Welcome lines: "Welcome, <name>!"
    document.querySelectorAll("[data-welcome]").forEach(function (el) {
      el.textContent = "Welcome, " + (user.fullname || "Student") + "!";
    });
  }

  // Expose for SCRIPT.JS registration handler
  window.StudentHubAuth = { loadUser: loadUser, saveUser: saveUser, paint: paint };

  document.addEventListener("DOMContentLoaded", paint);
})();
