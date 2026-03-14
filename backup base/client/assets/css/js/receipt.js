document.addEventListener("DOMContentLoaded", () => {
  const btn = document.querySelector(".btn-print");
  if (btn) {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      window.print();
    });
  }
});