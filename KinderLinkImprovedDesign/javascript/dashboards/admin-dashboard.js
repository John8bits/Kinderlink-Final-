function openModal() {
    document.getElementById('modal').classList.add('show');
}
function closeModal() {
    document.getElementById('modal').classList.remove('show');
}

function openGuardianModal() {
    document.getElementById('guardianModal').classList.add('show');
}
function closeGuardianModal() {
    document.getElementById('guardianModal').classList.remove('show');
}

function openPupilModal() {
    document.getElementById('pupilModal').classList.add('show');
}
function closePupilModal() {
    document.getElementById('pupilModal').classList.remove('show');
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
    const password = form.querySelector('input[name="pass"], input[name="new_password"]');
    const confirm = form.querySelector('input[name="confirm_pass"], input[name="confirm_password"]');
    if (!password || !confirm) {
        return true;
    }

    let isValid = true;
    const passwordValue = password.value.trim();
    const confirmValue = confirm.value.trim();

    if (!passwordValue && showEmptyErrors) {
        setPasswordError(password, 'Password is required.');
        isValid = false;
    } else if (passwordValue && passwordValue.length < 8) {
        setPasswordError(password, 'Password must be at least 8 characters.');
        isValid = false;
    } else {
        setPasswordError(password, '');
    }

    if (!confirmValue && showEmptyErrors) {
        setPasswordError(confirm, 'Please confirm the password.');
        isValid = false;
    } else if (confirmValue && passwordValue !== confirmValue) {
        setPasswordError(confirm, 'Passwords do not match.');
        isValid = false;
    } else {
        setPasswordError(confirm, '');
    }

    return isValid;
}

document.querySelectorAll('.password-match-form').forEach(form => {
    form.addEventListener('input', event => {
        if (event.target.matches('input[type="password"], input[type="text"]')) {
            validatePasswordMatchForm(form, false);
        }
    });

    form.addEventListener('submit', event => {
        if (!validatePasswordMatchForm(form, true)) {
            event.preventDefault();
        }
    });

    form.querySelectorAll('input[name="pass"], input[name="confirm_pass"]').forEach(input => {
        input.addEventListener('invalid', event => {
            event.preventDefault();
            validatePasswordMatchForm(form, true);
        });
    });
});

function toggleSidebar(force) {
    const shouldOpen = typeof force === 'boolean' ? force : !document.body.classList.contains('sidebar-open');
    document.body.classList.toggle('sidebar-open', shouldOpen);
}

document.querySelectorAll('aside .nav a').forEach(link => {
    link.addEventListener('click', () => toggleSidebar(false));
});

function filter() {
    const searchInput = document.getElementById('search').value.toLowerCase();
    const statusFilter = document.getElementById('status').value;
    const rows = document.querySelectorAll('table tbody tr');

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        const statusCell = row.querySelector('td:nth-child(4)');
        const status = statusCell ? statusCell.innerText.toLowerCase().trim() : '';

        const matchesSearch = text.includes(searchInput);
        const selectedStatus = statusFilter.toLowerCase();
        const matchesStatus = statusFilter === '-- All --' || status === selectedStatus;

        row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
    });
}

function filterGuardians() {
    const searchInput = document.getElementById('guardian-search').value.toLowerCase();
    const statusFilter = document.getElementById('guardian-status').value;
    const rows = document.querySelectorAll('#guardianTable tbody tr');

    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        const statusCell = row.querySelector('td:nth-child(4)');
        const status = statusCell ? statusCell.innerText.toLowerCase().trim() : '';

        const matchesSearch = text.includes(searchInput);
        const selectedStatus = statusFilter.toLowerCase();
        const matchesStatus = statusFilter === '-- All --' || status === selectedStatus;

        row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
    });
}

function confirmLogout(event, url) {
    event.preventDefault();
    Swal.fire({
        title: 'Logout',
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

function confirmRemoveLink(event, url) {
    event.preventDefault();
    Swal.fire({
        title: 'Remove link?',
        text: 'This guardian will no longer be connected to this pupil.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, remove',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
        reverseButtons: true,
        scrollbarPadding: false
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
}
