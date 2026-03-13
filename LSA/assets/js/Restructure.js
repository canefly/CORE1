function filterTable() {
    let input = document.getElementById("searchInput").value.toLowerCase();
    let rows = document.querySelectorAll(".data-row");

    rows.forEach(row => {
        let name = row.querySelector(".client-name").textContent.toLowerCase();
        row.style.display = name.includes(input) ? "" : "none";
    });
}