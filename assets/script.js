function checkLogin(){
    let emp = document.getElementById("employeeid").value;
    let pass = document.getElementById("password").value;

    if(emp === "admin" && pass === "1234"){
        alert("Login successful!");
        return true;
    }

    if(pass.length < 4){
        alert("Password must be at least 4 characters");
        return false;
    }

    return true;
}
