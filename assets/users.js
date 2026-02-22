const form = document.getElementById("userForm");
const table = document.getElementById("userTable");

form.addEventListener("submit", function (e) {
    e.preventDefault();

    const formData = new FormData(form);

    fetch("add_user.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.text())
    .then(message => {

        // If DB + Email succeeded
        if (message.includes("successfully")) {

            // Get values from form
            const id = form.id.value;
            const name = form.name.value;
            const email = form.email.value;
            const role = form.role.value;
            const site = form.site.value;
            // const password = form.password.value;


            // Add row to table
            const row = document.createElement("tr");
            row.innerHTML = `
                <td>${id}</td>
                <td>${name}</td>
                <td>${email}</td>
                <td>${role}</td>
                <td>${site}</td>
            `;
            table.appendChild(row);
        }

        alert(message);
        form.reset();
    })
    .catch(err => {
        console.error(err);
        alert("Something went wrong");
    });
});
function deleteUser(id) {
    if (!confirm("Are you sure you want to delete this user?")) return;

    fetch("delete_user.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "id=" + id
    })
    .then(res => res.text())
    .then(data => {
        if (data === "success") {
            location.reload();
        } else if (data === "self") {
            alert("You cannot delete yourself!");
        } else {
            alert("Delete failed!");
        }
    });
}
// alert("<?= addslashes($_SESSION['success_msg']) ?>");