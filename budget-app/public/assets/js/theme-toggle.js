(function() {
  const THEME_KEY = "budgetControlTheme";
  function getTheme() { return localStorage.getItem(THEME_KEY) || "day"; }
  function setTheme(theme) {
    localStorage.setItem(THEME_KEY, theme);
    document.documentElement.setAttribute("data-theme", theme);
    const btn = document.getElementById("theme-toggle");
    if (btn) btn.textContent = theme === "night" ? "☀️ Day" : "🌙 Night";
  }
  function toggleTheme() { setTheme(getTheme() === "day" ? "night" : "day"); }
  document.addEventListener("DOMContentLoaded", function() {
    setTheme(getTheme());
    if (\!document.getElementById("theme-toggle")) {
      const btn = document.createElement("button");
      btn.id = "theme-toggle";
      btn.className = "theme-toggle";
      btn.textContent = getTheme() === "night" ? "☀️ Day" : "🌙 Night";
      btn.onclick = toggleTheme;
      document.body.appendChild(btn);
    }
  });
  setTheme(getTheme());
  window.toggleTheme = toggleTheme;
})();
