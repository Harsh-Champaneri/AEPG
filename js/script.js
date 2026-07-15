function init() {
  // seedDemo();
  // sidebar toggle
  const app = document.getElementById("app");
  const toggleBtn = document.getElementById("toggleSidebarBtn");
  const icon = toggleBtn.querySelector("i");
  toggleBtn.addEventListener("click", () => {
    app.classList.toggle("collapsed");

    if (app.classList.contains("collapsed")) {
      // sidebar closed → show right arrow
      icon.classList.remove("fa-angle-left");
      icon.classList.add("fa-angle-right");
    } else {
      // sidebar open → show left arrow
      icon.classList.remove("fa-angle-right");
      icon.classList.add("fa-angle-left");
    }
  });
}

document.addEventListener("DOMContentLoaded", init);
