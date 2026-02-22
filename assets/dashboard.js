
/* ======================
   SIDEBAR TOGGLE (RESPONSIVE)
====================== */
const menuBtn = document.getElementById("menuBtn");
const sidebar = document.getElementById("sidebar");

if (menuBtn && sidebar) {
    menuBtn.addEventListener("click", () => {
        sidebar.classList.toggle("show");
    });
}
/* ======================
   THEME TOGGLE
====================== */
const toggle = document.getElementById("themeToggle");

if (toggle) {
    // Load saved theme
    if (localStorage.getItem("theme") === "dark") {
        document.body.classList.add("dark");
        toggle.checked = true;
    }

    // Toggle theme
    toggle.addEventListener("change", () => {
        if (toggle.checked) {
            document.body.classList.add("dark");
            localStorage.setItem("theme", "dark");
        } else {
            document.body.classList.remove("dark");
            localStorage.setItem("theme", "light");
        }
    });
}

/* ======================
   ACTIVE SIDEBAR MENU
====================== */
const menus = document.querySelectorAll(".menu");

menus.forEach(menu => {
    if (menu.href === window.location.href) {
        menu.classList.add("active");
    }
});

/* ======================
   GRAPH (SAFE LOAD)
====================== */
const chartCanvas = document.getElementById("attendanceChart");

if (chartCanvas) {
    new Chart(chartCanvas, {
        type: "line",
        data: {
            labels: ["01 Jan", "03 Jan", "05 Jan", "07 Jan", "09 Jan", "11 Jan"],
            datasets: [{
                label: "Present",
                data: [0, 200, 800, 1800, 3000, 3555],
                borderColor: "#3b82f6",
                backgroundColor: "rgba(59,130,246,0.2)",
                fill: true,
                tension: 0.4,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}
//for user 
function openUsers() {
    window.location.href = "users.php";
}
//for employee
function openEmployees() {
    window.location.href = "employees.php";
}
