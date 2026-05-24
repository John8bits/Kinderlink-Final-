function togglePassword() {
    const passwordInput = document.getElementById("password");
    const eye = document.getElementById("eye");

    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        eye.classList.remove("fa-eye");
        eye.classList.add("fa-eye-slash");
    } else {
        passwordInput.type = "password";
        eye.classList.remove("fa-eye-slash");
        eye.classList.add("fa-eye");
    }
}
