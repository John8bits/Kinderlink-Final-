function toggleAccountMenu(event) {
    event.stopPropagation();
    const menu = document.getElementById('accountDropdown');
    const button = event.currentTarget;
    const isOpen = menu.classList.toggle('show');
    button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
}

function closeAccountMenu() {
    const menu = document.getElementById('accountDropdown');
    const button = document.querySelector('.account-menu .user-badge');
    menu?.classList.remove('show');
    button?.setAttribute('aria-expanded', 'false');
}

function openChangePasswordModal() {
    closeAccountMenu();
    document.getElementById('changePasswordModal').style.display = 'flex';
}

function closeChangePasswordModal() {
    const modal = document.getElementById('changePasswordModal');
    modal.style.display = 'none';
    modal.querySelector('form')?.reset();
    modal.querySelectorAll('.input-invalid').forEach(input => input.classList.remove('input-invalid'));
    modal.querySelectorAll('.input-error').forEach(error => error.textContent = '');
}

function togglePasswordField(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    const showPassword = input.type === 'password';
    input.type = showPassword ? 'text' : 'password';
    icon.classList.toggle('fa-eye', !showPassword);
    icon.classList.toggle('fa-eye-slash', showPassword);
    button.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
}

function setPasswordError(input, message) {
    input.classList.toggle('input-invalid', Boolean(message));
    const error = input.closest('form')?.querySelector(`[data-error-for="${input.id}"]`);
    if (error) {
        error.textContent = message;
    }
}

function validatePasswordMatchForm(form, showEmptyErrors = false) {
    const current = form.querySelector('input[name="current_password"]');
    const password = form.querySelector('input[name="new_password"]');
    const confirm = form.querySelector('input[name="confirm_password"]');
    let isValid = true;

    if (current) {
        if (!current.value.trim() && showEmptyErrors) {
            setPasswordError(current, 'Current password is required.');
            isValid = false;
        } else {
            setPasswordError(current, '');
        }
    }

    const passwordValue = password?.value.trim() || '';
    const confirmValue = confirm?.value.trim() || '';

    if (!passwordValue && showEmptyErrors) {
        setPasswordError(password, 'New password is required.');
        isValid = false;
    } else if (passwordValue && passwordValue.length < 8) {
        setPasswordError(password, 'New password must be at least 8 characters.');
        isValid = false;
    } else if (password) {
        setPasswordError(password, '');
    }

    if (!confirmValue && showEmptyErrors) {
        setPasswordError(confirm, 'Please confirm the new password.');
        isValid = false;
    } else if (confirmValue && passwordValue !== confirmValue) {
        setPasswordError(confirm, 'New password and confirmation do not match.');
        isValid = false;
    } else if (confirm) {
        setPasswordError(confirm, '');
    }

    return isValid;
}

document.querySelectorAll('.password-match-form').forEach(form => {
    form.addEventListener('input', event => {
        if (event.target.matches('input')) {
            validatePasswordMatchForm(form, false);
        }
    });

    form.addEventListener('submit', event => {
        if (!validatePasswordMatchForm(form, true)) {
            event.preventDefault();
        }
    });

    form.querySelectorAll('input[name="current_password"], input[name="new_password"], input[name="confirm_password"]').forEach(input => {
        input.addEventListener('invalid', event => {
            event.preventDefault();
            validatePasswordMatchForm(form, true);
        });
    });
});

document.addEventListener('click', closeAccountMenu);
document.getElementById('changePasswordModal')?.addEventListener('click', function (event) {
    if (event.target === this) {
        closeChangePasswordModal();
    }
});

function toggleSidebar(force) {
    const shouldOpen = typeof force === 'boolean' ? force : !document.body.classList.contains('sidebar-open');
    document.body.classList.toggle('sidebar-open', shouldOpen);
}

document.querySelectorAll('aside .nav a').forEach(link => {
    link.addEventListener('click', () => toggleSidebar(false));
});

function confirmLogout(event, url) {
    event.preventDefault();
    Swal.fire({
        title: 'Logout Confirmation',
        text: 'Are you sure you want to logout?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, logout',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        scrollbarPadding: false
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
}
