document.addEventListener("DOMContentLoaded", () => {
    const lucide = window.lucide;
    const apiUrl = "../../modules/disbursement/loan-disbursement-action.php";

    const byId = (id) => document.getElementById(id);

    const esc = (value) => {
        if (value === null || value === undefined) return "";
        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    };

    const peso = (value) => {
        const num = Number(value || 0);
        return `₱${num.toLocaleString("en-PH", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        })}`;
    };

    const fetchJson = async (url, options = {}) => {
        const res = await fetch(url, options);
        const data = await res.json();
        if (!res.ok || data.ok === false) {
            throw new Error(data.message || "Request failed.");
        }
        return data;
    };

    const updateIcons = () => {
        if (lucide) lucide.createIcons();
    };

    // Theme
    const savedTheme = localStorage.getItem("theme");
    if (savedTheme === "dark") document.body.classList.add("dark-mode");

    byId("themeToggle")?.addEventListener("click", () => {
        document.body.classList.toggle("dark-mode");
        localStorage.setItem("theme", document.body.classList.contains("dark-mode") ? "dark" : "light");
    });

    // Sidebar
    byId("sidebarToggle")?.addEventListener("click", () => {
        byId("sidebar")?.classList.toggle("collapsed");
    });

    byId("mobileMenuBtn")?.addEventListener("click", () => {
        byId("sidebar")?.classList.toggle("mobile-open");
    });

    // Clock
    const updateClock = () => {
        const now = new Date();
        const text = now.toLocaleString("en-PH", {
            year: "numeric",
            month: "short",
            day: "2-digit",
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit"
        });
        const el = byId("realTimeClock");
        if (el) el.textContent = text;
    };
    updateClock();
    setInterval(updateClock, 1000);

    // Tabs
    document.querySelectorAll(".tab-link").forEach(btn => {
        btn.addEventListener("click", () => {
            document.querySelectorAll(".tab-link").forEach(x => x.classList.remove("active"));
            document.querySelectorAll(".tab-pane").forEach(x => x.classList.remove("active"));

            btn.classList.add("active");
            const target = btn.dataset.tab;

            if (target === "pending") byId("pendingTab")?.classList.add("active");
            if (target === "history") byId("historyTab")?.classList.add("active");
        });
    });

    const loadStats = async () => {
        try {
            const data = await fetchJson(`${apiUrl}?action=stats`);
            byId("statPendingAmount").textContent = peso(data.stats.pending_amount);
            byId("statDisbursedToday").textContent = peso(data.stats.disbursed_today);
            byId("statWaitingCount").textContent = data.stats.waiting_count ?? 0;
        } catch (e) {
            console.error(e);
        }
    };

    const renderPending = (rows) => {
        const body = byId("pendingDisbursementBody");
        body.innerHTML = "";

        if (!rows || rows.length === 0) {
            body.innerHTML = `<tr><td colspan="7" class="td-empty-state">No pending disbursements found.</td></tr>`;
            return;
        }

        rows.forEach(row => {
            const tr = document.createElement("tr");
            tr.className = "row-fade-in";
            tr.innerHTML = `
                <td><strong>#${esc(row.application_id)}</strong></td>
                <td>${esc(row.borrower_name)}</td>
                <td>${peso(row.principal_amount)}</td>
                <td>${esc(row.term_months)} months</td>
                <td>${peso(row.monthly_due)}</td>
                <td>
                    <span class="badge-premium badge-warning">
                        <i data-lucide="clock-3"></i> ${esc(row.status)}
                    </span>
                </td>
                <td>
                    <div class="action-btn-group">
                        <button class="btn-premium btn-view" data-id="${esc(row.id)}">
                            <i data-lucide="eye"></i> View
                        </button>
                        <button class="btn-premium btn-disburse" data-id="${esc(row.id)}">
                            <i data-lucide="send"></i> Disburse
                        </button>
                    </div>
                </td>
            `;
            body.appendChild(tr);
        });

        updateIcons();
    };

    const renderHistory = (rows) => {
        const body = byId("historyDisbursementBody");
        body.innerHTML = "";

        if (!rows || rows.length === 0) {
            body.innerHTML = `<tr><td colspan="7" class="td-empty-state">No history found.</td></tr>`;
            return;
        }

        rows.forEach(row => {
            const tr = document.createElement("tr");
            tr.className = "row-fade-in";
            tr.innerHTML = `
                <td><strong>#${esc(row.application_id)}</strong></td>
                <td>${esc(row.borrower_name)}</td>
                <td>${peso(row.principal_amount)}</td>
                <td>${esc(row.term_months)} months</td>
                <td>${peso(row.monthly_due)}</td>
                <td>${esc(row.disbursed_at ?? "-")}</td>
                <td>
                    <button class="btn-premium btn-view" data-id="${esc(row.id)}">
                        <i data-lucide="eye"></i> View
                    </button>
                </td>
            `;
            body.appendChild(tr);
        });

        updateIcons();
    };

    const loadPending = async (search = "") => {
        try {
            const data = await fetchJson(`${apiUrl}?action=list_pending&search=${encodeURIComponent(search)}`);
            renderPending(data.rows || []);
        } catch (e) {
            console.error(e);
            byId("pendingDisbursementBody").innerHTML = `<tr><td colspan="7" class="td-empty-state">${esc(e.message)}</td></tr>`;
        }
    };

    const loadHistory = async (search = "") => {
        try {
            const data = await fetchJson(`${apiUrl}?action=list_history&search=${encodeURIComponent(search)}`);
            renderHistory(data.rows || []);
        } catch (e) {
            console.error(e);
            byId("historyDisbursementBody").innerHTML = `<tr><td colspan="7" class="td-empty-state">${esc(e.message)}</td></tr>`;
        }
    };

    const openModal = () => {
        const modal = byId("detailModal");
        modal.style.display = "flex";
        setTimeout(() => modal.classList.add("open"), 10);
    };

    const closeModal = () => {
        const modal = byId("detailModal");
        modal.classList.remove("open");
        setTimeout(() => {
            modal.style.display = "none";
        }, 180);
    };

    const loadDetail = async (id) => {
        const detailContent = byId("detailContent");
        detailContent.innerHTML = `
            <div class="loading-sync">
                <i data-lucide="refresh-cw" class="spin icon-loading-spin"></i>
                <p class="text-sync-msg">Loading details...</p>
            </div>
        `;
        openModal();
        updateIcons();

        try {
            const data = await fetchJson(`${apiUrl}?action=view&id=${encodeURIComponent(id)}`);
            const row = data.row;

            detailContent.innerHTML = `
                <div class="detail-grid">
                    <div class="detail-card">
                        <span class="detail-label">Application ID</span>
                        <strong class="detail-value">#${esc(row.application_id)}</strong>
                    </div>
                    <div class="detail-card">
                        <span class="detail-label">Borrower</span>
                        <strong class="detail-value">${esc(row.borrower_name)}</strong>
                    </div>
                    <div class="detail-card">
                        <span class="detail-label">Principal Amount</span>
                        <strong class="detail-value">${peso(row.principal_amount)}</strong>
                    </div>
                    <div class="detail-card">
                        <span class="detail-label">Term</span>
                        <strong class="detail-value">${esc(row.term_months)} months</strong>
                    </div>
                    <div class="detail-card">
                        <span class="detail-label">Monthly Due</span>
                        <strong class="detail-value">${peso(row.monthly_due)}</strong>
                    </div>
                    <div class="detail-card">
                        <span class="detail-label">Interest Rate</span>
                        <strong class="detail-value">${esc(row.interest_rate)}%</strong>
                    </div>
                    <div class="detail-card">
                        <span class="detail-label">Interest Type</span>
                        <strong class="detail-value">${esc(row.interest_type)}</strong>
                    </div>
                    <div class="detail-card">
                        <span class="detail-label">Interest Method</span>
                        <strong class="detail-value">${esc(row.interest_method)}</strong>
                    </div>
                    <div class="detail-card">
                        <span class="detail-label">Total Interest</span>
                        <strong class="detail-value">${peso(row.total_interest)}</strong>
                    </div>
                    <div class="detail-card">
                        <span class="detail-label">Total Payable</span>
                        <strong class="detail-value">${peso(row.total_payable)}</strong>
                    </div>
                    <div class="detail-card">
                        <span class="detail-label">Source of Income</span>
                        <strong class="detail-value">${esc(row.source_of_income)}</strong>
                    </div>
                    <div class="detail-card">
                        <span class="detail-label">Estimated Monthly Income</span>
                        <strong class="detail-value">${peso(row.estimated_monthly_income)}</strong>
                    </div>
                    <div class="detail-card detail-card-full">
                        <span class="detail-label">Loan Purpose</span>
                        <strong class="detail-value">${esc(row.loan_purpose)}</strong>
                    </div>
                    <div class="detail-card">
                        <span class="detail-label">Status</span>
                        <strong class="detail-value">${esc(row.status)}</strong>
                    </div>
                    <div class="detail-card">
                        <span class="detail-label">Disbursed At</span>
                        <strong class="detail-value">${esc(row.disbursed_at ?? "-")}</strong>
                    </div>
                    <div class="detail-card">
                        <span class="detail-label">Linked Loan ID</span>
                        <strong class="detail-value">${data.loan_id ? "#" + esc(data.loan_id) : "Not yet created"}</strong>
                    </div>
                </div>
            `;
            updateIcons();
        } catch (e) {
            detailContent.innerHTML = `<div class="td-empty-state">${esc(e.message)}</div>`;
        }
    };

    const markDisbursed = async (id) => {
        const result = await Swal.fire({
            title: "Confirm disbursement?",
            text: "This will mark the loan as DISBURSED and create/update the ACTIVE loan record.",
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Yes, disburse now",
            cancelButtonText: "Cancel"
        });

        if (!result.isConfirmed) return;

        const formData = new FormData();
        formData.append("action", "mark_disbursed");
        formData.append("id", id);

        try {
            Swal.fire({
                title: "Processing...",
                text: "Please wait.",
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            const data = await fetchJson(apiUrl, {
                method: "POST",
                body: formData
            });

            await Swal.fire({
                icon: "success",
                title: "Success",
                text: data.message || "Loan disbursed successfully."
            });

            await loadStats();
            await loadPending(byId("searchPending")?.value || "");
            await loadHistory(byId("searchHistory")?.value || "");
        } catch (e) {
            Swal.fire({
                icon: "error",
                title: "Failed",
                text: e.message || "Unable to disburse loan."
            });
        }
    };

    document.addEventListener("click", async (e) => {
        const viewBtn = e.target.closest(".btn-view");
        if (viewBtn) {
            loadDetail(viewBtn.dataset.id);
            return;
        }

        const disburseBtn = e.target.closest(".btn-disburse");
        if (disburseBtn) {
            markDisbursed(disburseBtn.dataset.id);
        }
    });

    byId("btnCloseModal")?.addEventListener("click", closeModal);
    byId("detailModal")?.addEventListener("click", (e) => {
        if (e.target.id === "detailModal") closeModal();
    });

    let pendingTimer = null;
    byId("searchPending")?.addEventListener("input", (e) => {
        clearTimeout(pendingTimer);
        pendingTimer = setTimeout(() => loadPending(e.target.value.trim()), 300);
    });

    let historyTimer = null;
    byId("searchHistory")?.addEventListener("input", (e) => {
        clearTimeout(historyTimer);
        historyTimer = setTimeout(() => loadHistory(e.target.value.trim()), 300);
    });

    loadStats();
    loadPending();
    loadHistory();
    updateIcons();
});