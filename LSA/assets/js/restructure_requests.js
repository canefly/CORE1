document.addEventListener("DOMContentLoaded", function () {
    const search = document.getElementById("restructureSearch");
    const table = document.getElementById("restructureTable");

    if (search && table) {
        search.addEventListener("keyup", function () {
            const filter = this.value.toLowerCase();
            const rows = table.getElementsByTagName("tr");

            for (let row of rows) {
                const name = row.querySelector(".client-name");
                if (!name) continue;

                const text = name.textContent || name.innerText;
                row.style.display = text.toLowerCase().includes(filter) ? "" : "none";
            }
        });
    }
});

function openDecisionModal(requestId, clientName, restructureType) {
    document.getElementById("modal_request_id").value = requestId;
    document.getElementById("modal_action").value = "";
    document.getElementById("modal_notes").value = "";
    document.getElementById("modal_text").textContent =
        `Review request #RR-${requestId} for ${clientName} (${restructureType}). Verify if the requirements are complete and valid, or reject if there are issues.`;

    document.getElementById("decisionModal").classList.add("show");
}

function closeDecisionModal() {
    document.getElementById("decisionModal").classList.remove("show");
}

function submitDecision(action) {
    document.getElementById("modal_action").value = action;
    document.getElementById("decisionForm").submit();
}