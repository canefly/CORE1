function filterTable() {
    let input = document.getElementById("searchInput").value.toLowerCase();
    let rows = document.querySelectorAll(".data-row");

    rows.forEach(row => {
        let nameEl = row.querySelector(".client-name");
        if (!nameEl) return;

        let name = nameEl.textContent.toLowerCase();
        row.style.display = name.includes(input) ? "" : "none";
    });
}