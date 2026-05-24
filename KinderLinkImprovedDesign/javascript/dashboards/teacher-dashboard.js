function toggleAccountMenu(event) {
    event.stopPropagation();
    const menu = document.getElementById('accountDropdown');
    const button = event.currentTarget;
    const isOpen = menu?.classList.toggle('show') || false;
    button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
}

function closeAccountMenu() {
    document.getElementById('accountDropdown')?.classList.remove('show');
    document.querySelector('.account-menu .user-badge')?.setAttribute('aria-expanded', 'false');
}

function openChangePasswordModal() {
    closeAccountMenu();
    const modal = document.getElementById('changePasswordModal');
    if (modal) modal.style.display = 'flex';
}

function closeChangePasswordModal() {
    const modal = document.getElementById('changePasswordModal');
    if (!modal) return;
    modal.style.display = 'none';
    modal.querySelector('form')?.reset();
    modal.querySelectorAll('.input-invalid').forEach((input) => input.classList.remove('input-invalid'));
    modal.querySelectorAll('.input-error').forEach((error) => error.textContent = '');
}

function togglePasswordField(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    if (!input || !icon) return;
    const showPassword = input.type === 'password';
    input.type = showPassword ? 'text' : 'password';
    icon.classList.toggle('fa-eye', !showPassword);
    icon.classList.toggle('fa-eye-slash', showPassword);
    button.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
}

function setPasswordError(input, message) {
    if (!input) return;
    input.classList.toggle('input-invalid', Boolean(message));
    const error = input.closest('form')?.querySelector(`[data-error-for="${input.id}"]`);
    if (error) error.textContent = message;
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
    } else {
        setPasswordError(password, '');
    }

    if (!confirmValue && showEmptyErrors) {
        setPasswordError(confirm, 'Please confirm the new password.');
        isValid = false;
    } else if (confirmValue && passwordValue !== confirmValue) {
        setPasswordError(confirm, 'New password and confirmation do not match.');
        isValid = false;
    } else {
        setPasswordError(confirm, '');
    }

    return isValid;
}

function toggleSidebar(force) {
    const shouldOpen = typeof force === 'boolean' ? force : !document.body.classList.contains('sidebar-open');
    document.body.classList.toggle('sidebar-open', shouldOpen);
}

const guardianValidationList = window.TeacherDashboardData?.guardianValidationList || [];
const pupilNameValidationList = window.TeacherDashboardData?.pupilNameValidationList || [];

function toggleForm() {
    const form = document.getElementById('pupilForm');
    if (!form) return;
    const isHidden = form.style.display === 'none' || form.style.display === '';
    document.getElementById('editPupilForm').style.display = 'none';
    form.style.display = isHidden ? 'block' : 'none';
    if (isHidden) {
        validateAddPupilForm();
    } else {
        form.querySelector('form')?.reset();
        clearAddPupilValidation();
    }
}

function setFieldError(input, errorElement, message) {
    if (!input || !errorElement) return;
    input.classList.toggle('input-invalid', Boolean(message));
    errorElement.textContent = message;
}

function clearAddPupilValidation() {
    setFieldError(document.getElementById('add_first_name'), document.getElementById('pupilNameError'), '');
    setFieldError(document.getElementById('add_last_name'), document.getElementById('pupilNameError'), '');
    setFieldError(document.getElementById('add_age'), document.getElementById('ageError'), '');
    setFieldError(document.getElementById('add_guardian_name'), document.getElementById('guardianError'), '');
    const saveBtn = document.getElementById('addPupilSaveBtn');
    if (saveBtn) saveBtn.disabled = true;
}

function validateAddPupilForm() {
    const firstNameInput = document.getElementById('add_first_name');
    const lastNameInput = document.getElementById('add_last_name');
    const ageInput = document.getElementById('add_age');
    const guardianInput = document.getElementById('add_guardian_name');
    const saveBtn = document.getElementById('addPupilSaveBtn');
    const firstName = (firstNameInput?.value || '').trim().toLowerCase();
    const lastName = (lastNameInput?.value || '').trim().toLowerCase();
    const age = Number(ageInput?.value);
    const guardianName = (guardianInput?.value || '').trim().toLowerCase();
    let pupilNameValid = false;
    let ageValid = false;
    let guardianValid = false;

    if (firstName && lastName) {
        const existingPupil = pupilNameValidationList.find((item) =>
            String(item.first_name || '').trim().toLowerCase() === firstName &&
            String(item.last_name || '').trim().toLowerCase() === lastName
        );
        pupilNameValid = !existingPupil;
        setFieldError(firstNameInput, document.getElementById('pupilNameError'), existingPupil ? 'Pupil name already exist.' : '');
        setFieldError(lastNameInput, document.getElementById('pupilNameError'), existingPupil ? 'Pupil name already exist.' : '');
    } else {
        setFieldError(firstNameInput, document.getElementById('pupilNameError'), '');
        setFieldError(lastNameInput, document.getElementById('pupilNameError'), '');
    }

    if (ageInput?.value) {
        ageValid = Number.isInteger(age) && age >= 4 && age <= 5;
        setFieldError(ageInput, document.getElementById('ageError'), ageValid ? '' : 'Age must be between 4 and 5.');
    } else {
        setFieldError(ageInput, document.getElementById('ageError'), '');
    }

    if (guardianName) {
        const guardian = guardianValidationList.find((item) =>
            String(item.guardian_name || '').trim().toLowerCase() === guardianName
        );
        if (!guardian) {
            setFieldError(guardianInput, document.getElementById('guardianError'), 'Guardian not exist.');
        } else if (Number(guardian.linked_count) > 0) {
            setFieldError(guardianInput, document.getElementById('guardianError'), 'Guardian already linked.');
        } else {
            guardianValid = true;
            setFieldError(guardianInput, document.getElementById('guardianError'), '');
        }
    } else {
        setFieldError(guardianInput, document.getElementById('guardianError'), '');
    }

    if (saveBtn) saveBtn.disabled = !(pupilNameValid && ageValid && guardianValid);
    return pupilNameValid && ageValid && guardianValid;
}

function toggleEditForm() {
    const form = document.getElementById('editPupilForm');
    if (!form) return;
    const isHidden = form.style.display === 'none' || form.style.display === '';
    form.style.display = isHidden ? 'block' : 'none';
    if (!isHidden) form.querySelector('form')?.reset();
}

function editPupil(pupil) {
    document.getElementById('pupilForm').style.display = 'none';
    document.getElementById('editPupilForm').style.display = 'block';
    Object.entries({
        edit_pupil_id: pupil.pupil_id,
        edit_first_name: pupil.first_name,
        edit_last_name: pupil.last_name,
        edit_age: pupil.age,
        edit_gender: pupil.gender,
        edit_birthdate: pupil.birthdate,
        edit_home_address: pupil.home_address,
        edit_health_notes: pupil.health_notes,
        edit_guardian_name: pupil.guardian_name,
        edit_contact_number: pupil.contact_number
    }).forEach(([id, value]) => {
        const field = document.getElementById(id);
        if (field) field.value = value || '';
    });
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function viewPupilProfile(pupil) {
    const rows = [
        ['First Name', pupil.first_name],
        ['Last Name', pupil.last_name],
        ['Age', pupil.age],
        ['Gender', pupil.gender],
        ['Birthdate', pupil.birthdate],
        ['Home Address', pupil.home_address],
        ['Guardian Name', pupil.guardian_name],
        ['Contact Number', pupil.contact_number],
        ['Health Notes', pupil.health_notes || 'None']
    ];
    const html = `<div class="profile-view">${rows.map(([label, value]) => `
        <div class="profile-row"><span>${escapeHtml(label)}</span><strong>${escapeHtml(value || 'Not provided')}</strong></div>
    `).join('')}</div>`;
    Swal.fire({ titleText: pupil.full_name || 'Pupil Profile', html, icon: 'info', confirmButtonText: 'Close', confirmButtonColor: '#4361ee', customClass: { popup: 'profile-modal' } });
}

function filterPupils() {
    const query = document.getElementById('search')?.value.toLowerCase().trim() || '';
    const rows = document.querySelectorAll('#pupilTable tbody tr:not(#no-results-row)');
    let visibleCount = 0;
    rows.forEach((row) => {
        const isVisible = row.innerText.toLowerCase().includes(query);
        row.style.display = isVisible ? '' : 'none';
        if (isVisible) visibleCount++;
    });
    const noResultsRow = document.getElementById('no-results-row');
    if (noResultsRow) noResultsRow.style.display = visibleCount === 0 ? '' : 'none';
}

function setStatus(pupilId, value, btn) {
    document.getElementById(`status-${pupilId}`).value = value;
    btn.parentNode.querySelectorAll('.status-btn').forEach((button) => button.classList.remove('active'));
    btn.classList.add('active');
}

function openAnnouncementModal() {
    const modal = document.getElementById('announcementModal');
    if (!modal) return;
    document.getElementById('announcementModalTitle').textContent = 'New Announcement';
    document.getElementById('announcementId').value = '';
    document.getElementById('announcementTitle').value = '';
    document.getElementById('announcementMessage').value = '';
    document.getElementById('announcementSubmitBtn').innerHTML = '<i class="fa fa-paper-plane"></i> Post Announcement';
    modal.style.display = 'flex';
}

function closeAnnouncementModal() {
    const modal = document.getElementById('announcementModal');
    if (modal) modal.style.display = 'none';
}

function editAnnouncement(announcement) {
    const modal = document.getElementById('announcementModal');
    if (!modal) return;
    document.getElementById('announcementModalTitle').textContent = 'Edit Announcement';
    document.getElementById('announcementId').value = announcement.announcement_id || '';
    document.getElementById('announcementTitle').value = announcement.title || '';
    document.getElementById('announcementMessage').value = announcement.content || '';
    document.getElementById('announcementSubmitBtn').innerHTML = '<i class="fa fa-save"></i> Save Changes';
    modal.style.display = 'flex';
}

function submitDelete(action, name, value) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = action;
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = String(parseInt(value, 10) || '');
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}

function deleteAnnouncementConfirm(announcementId) {
    Swal.fire({ title: 'Delete Announcement?', text: 'This announcement will be removed from the classroom board.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete', cancelButtonText: 'Cancel', confirmButtonColor: '#dc2626', reverseButtons: true })
        .then((result) => result.isConfirmed && submitDelete('../controllers/delete_announcement.php', 'announcement_id', announcementId));
}

function confirmLogout(event, url) {
    event.preventDefault();
    Swal.fire({ title: 'Logout Confirmation', text: 'Are you sure you want to logout?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Yes, logout', cancelButtonText: 'Cancel', reverseButtons: true, scrollbarPadding: false })
        .then((result) => {
            if (result.isConfirmed) window.location.href = url;
        });
}

function openMilestoneModal() {
    const modal = document.getElementById('milestoneModal');
    if (!modal) return;
    modal.style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Record New Milestone';
    document.getElementById('edit_milestone_id').value = '';
    document.getElementById('milestoneName').value = '';
    document.getElementById('milestoneDescription').value = '';
}

function closeMilestoneModal() {
    const modal = document.getElementById('milestoneModal');
    if (modal) modal.style.display = 'none';
}

function editMilestone(milestone) {
    document.getElementById('milestoneModal').style.display = 'flex';
    document.getElementById('modalTitle').textContent = 'Edit Milestone';
    document.getElementById('edit_milestone_id').value = milestone.milestone_id;
    document.getElementById('milestoneName').value = milestone.title || '';
    document.getElementById('milestoneDescription').value = milestone.description || '';
}

function deleteMilestoneConfirm(milestoneId) {
    Swal.fire({ title: 'Delete Milestone?', text: 'This action cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete', cancelButtonText: 'Cancel', reverseButtons: true })
        .then((result) => result.isConfirmed && submitDelete('../controllers/delete_milestone.php', 'milestone_id', milestoneId));
}

function changePupil(pupilId) {
    if (!pupilId) return;
    const select = document.getElementById('pupilSelect');
    if (select) {
        select.disabled = true;
        select.style.opacity = '0.6';
    }
    const quarter = document.getElementById('quarterSelect')?.value || window.TeacherDashboardData?.selectedQuarter || 'Q1';
    window.location.href = `?page=milestones&pupil_id=${encodeURIComponent(pupilId)}&quarter=${encodeURIComponent(quarter)}`;
}

function changeQuarter(quarter) {
    if (!quarter) return;
    const params = new URLSearchParams(window.location.search);
    const page = params.get('page') || 'dashboard';
    const pupilId = params.get('pupil_id');
    const date = params.get('date');
    let url = `?page=${encodeURIComponent(page)}&quarter=${encodeURIComponent(quarter)}`;
    if (pupilId) url += `&pupil_id=${encodeURIComponent(pupilId)}`;
    if (date) url += `&date=${encodeURIComponent(date)}`;
    window.location.href = url;
}

function updateChecklistItem(item, isChecked) {
    item.classList.toggle('completed', isChecked);
    if (isChecked) {
        item.style.transform = 'scale(1.02)';
        setTimeout(() => item.style.transform = '', 200);
    }
}

function changeReportPupil(pupilId) {
    if (!pupilId) return;
    const quarter = document.getElementById('reportQuarterSelect')?.value || 'Q1';
    window.location.href = `?page=reports&pupil_id=${encodeURIComponent(pupilId)}&quarter=${encodeURIComponent(quarter)}`;
}

function changeReportQuarter(quarter) {
    if (!quarter) return;
    const pupilId = document.getElementById('reportPupilSelect')?.value;
    if (pupilId) window.location.href = `?page=reports&pupil_id=${encodeURIComponent(pupilId)}&quarter=${encodeURIComponent(quarter)}`;
}

function viewFullReport() {
    const report = window.currentFullReport;
    if (!report) {
        Swal.fire({ icon: 'warning', title: 'No Report Available', text: 'Please select a pupil to view the full report.', confirmButtonColor: '#4361ee' });
        return;
    }
    const totalAttendance = Number(report.attendance.present) + Number(report.attendance.absent) + Number(report.attendance.late);
    const completedMilestones = report.milestones.filter((item) => item.status === 'Completed').length;
    const milestoneRows = report.milestones.length ? report.milestones.map((item) => `
        <tr>
            <td><strong>${escapeHtml(item.title)}</strong>${item.description ? `<div class="muted">${escapeHtml(item.description)}</div>` : ''}</td>
            <td><span class="status ${item.status === 'Completed' ? 'completed' : 'pending'}">${escapeHtml(item.status || 'Not Started')}</span></td>
            <td>${escapeHtml(item.dateCompleted || '-')}</td>
        </tr>
    `).join('') : '<tr><td colspan="3" class="empty">No milestone templates found.</td></tr>';
    const reportWindow = window.open('', '_blank');
    if (!reportWindow) {
        Swal.fire({ icon: 'warning', title: 'Popup Blocked', text: 'Please allow popups for this site to view the full report.', confirmButtonColor: '#4361ee' });
        return;
    }
    reportWindow.document.write(`
        <!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Progress Report - ${escapeHtml(report.pupilName)}</title>
        <style>*{box-sizing:border-box}body{font-family:Arial,sans-serif;color:#111827;margin:0;background:#f3f4f6}.page{max-width:900px;margin:24px auto;padding:36px;background:#fff;border:1px solid #e5e7eb}.topbar{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;border-bottom:2px solid #111827;padding-bottom:18px;margin-bottom:24px}h1{margin:0 0 8px;font-size:28px}h2{margin:28px 0 12px;font-size:18px;border-bottom:1px solid #e5e7eb;padding-bottom:8px}.muted{color:#6b7280;font-size:13px;margin-top:4px}.print-btn{border:0;background:#4361ee;color:#fff;padding:10px 14px;border-radius:6px;font-weight:700;cursor:pointer}.info-grid,.summary-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}.summary-grid{grid-template-columns:repeat(4,1fr)}.box{border:1px solid #e5e7eb;border-radius:8px;padding:14px;background:#f9fafb}.label{color:#6b7280;font-size:12px;text-transform:uppercase;font-weight:700;margin-bottom:6px}.value{font-size:22px;font-weight:800}table{width:100%;border-collapse:collapse;margin-top:12px}th,td{border:1px solid #e5e7eb;padding:12px;text-align:left;vertical-align:top}th{background:#f9fafb;font-size:13px;text-transform:uppercase;color:#374151}.status{display:inline-block;padding:4px 8px;border-radius:999px;font-size:12px;font-weight:700}.completed{background:#dcfce7;color:#166534}.pending{background:#f3f4f6;color:#374151}.empty{text-align:center;color:#6b7280}@media print{body{background:#fff}.page{margin:0;max-width:none;border:0}.print-btn{display:none}}</style>
        </head><body><div class="page"><div class="topbar"><div><h1>Progress Report</h1><div class="muted">Generated on ${escapeHtml(new Date().toLocaleDateString())}</div></div><button class="print-btn" onclick="window.print()">Print / Save PDF</button></div>
        <div class="info-grid"><div class="box"><div class="label">Pupil</div><strong>${escapeHtml(report.pupilName)}</strong></div><div class="box"><div class="label">Class</div><strong>${escapeHtml(report.className)}</strong></div><div class="box"><div class="label">Teacher</div><strong>${escapeHtml(report.teacherName)}</strong></div><div class="box"><div class="label">Grading Period</div><strong>${escapeHtml(report.quarter)} (${escapeHtml(report.dateStart)} to ${escapeHtml(report.dateEnd)})</strong></div></div>
        <h2>Attendance Summary</h2><div class="summary-grid"><div class="box"><div class="label">Present</div><div class="value">${Number(report.attendance.present)}</div></div><div class="box"><div class="label">Absent</div><div class="value">${Number(report.attendance.absent)}</div></div><div class="box"><div class="label">Late</div><div class="value">${Number(report.attendance.late)}</div></div><div class="box"><div class="label">Total Records</div><div class="value">${totalAttendance}</div></div></div>
        <h2>Milestone Progress</h2><div class="box"><strong>${completedMilestones} of ${report.milestones.length} milestones completed</strong></div><table><thead><tr><th>Milestone</th><th>Status</th><th>Date Completed</th></tr></thead><tbody>${milestoneRows}</tbody></table></div></body></html>
    `);
    reportWindow.document.close();
    reportWindow.focus();
}

function toggleAttendanceHistory() {
    const history = document.getElementById('attendanceHistory');
    if (!history) return;
    history.style.display = history.style.display === 'none' || history.style.display === '' ? 'block' : 'none';
}

function filterAttendanceHistory() {
    const filter = document.getElementById('historyPupilSelect')?.value;
    document.querySelectorAll('#attendanceHistory tbody tr[data-pupil-id]').forEach((row) => {
        row.style.display = !filter || row.dataset.pupilId === filter ? '' : 'none';
    });
}

function updateClock() {
    const clock = document.getElementById('clock');
    if (clock) clock.innerText = new Date().toLocaleTimeString();
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.password-match-form').forEach((form) => {
        form.addEventListener('input', (event) => {
            if (event.target.matches('input')) validatePasswordMatchForm(form, false);
        });
        form.addEventListener('submit', (event) => {
            if (!validatePasswordMatchForm(form, true)) event.preventDefault();
        });
        form.querySelectorAll('input[name="current_password"], input[name="new_password"], input[name="confirm_password"]').forEach((input) => {
            input.addEventListener('invalid', (event) => {
                event.preventDefault();
                validatePasswordMatchForm(form, true);
            });
        });
    });

    document.querySelectorAll('aside .nav a').forEach((link) => link.addEventListener('click', () => toggleSidebar(false)));
    document.addEventListener('click', closeAccountMenu);
    document.getElementById('changePasswordModal')?.addEventListener('click', function (event) {
        if (event.target === this) closeChangePasswordModal();
    });
    document.getElementById('announcementModal')?.addEventListener('click', function (event) {
        if (event.target === this) closeAnnouncementModal();
    });
    document.getElementById('milestoneModal')?.addEventListener('click', function (event) {
        if (event.target === this) closeMilestoneModal();
    });

    const addPupilForm = document.getElementById('addPupilForm');
    ['add_first_name', 'add_last_name', 'add_age', 'add_guardian_name'].forEach((id) => {
        document.getElementById(id)?.addEventListener('input', validateAddPupilForm);
    });
    document.getElementById('add_guardian_name')?.addEventListener('blur', validateAddPupilForm);
    addPupilForm?.addEventListener('submit', (event) => {
        if (!validateAddPupilForm()) event.preventDefault();
    });

    document.querySelectorAll('.checklist-item').forEach((item) => {
        item.addEventListener('click', function (event) {
            if (this.classList.contains('disabled')) {
                event.preventDefault();
                Swal.fire({ icon: 'info', title: 'This pupil is absent', text: 'Milestone progress cannot be updated while the pupil is absent today.', confirmButtonColor: '#4361ee' });
                return;
            }
            if (event.target.type === 'checkbox') return;
            const checkbox = this.querySelector('input[type="checkbox"]');
            if (checkbox) {
                checkbox.checked = !checkbox.checked;
                updateChecklistItem(this, checkbox.checked);
            }
        });
    });

    document.querySelector('form[action*="save_pupil_milestones"]')?.addEventListener('submit', () => {
        const saveBtn = document.getElementById('saveBtn');
        if (!saveBtn) return;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving...';
        saveBtn.style.opacity = '0.7';
    });

    document.querySelectorAll('.form-btn, .milestone-btn').forEach((button) => {
        button.addEventListener('click', function (event) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            ripple.className = 'ripple-effect';
            ripple.style.width = ripple.style.height = `${size}px`;
            ripple.style.left = `${event.clientX - rect.left - size / 2}px`;
            ripple.style.top = `${event.clientY - rect.top - size / 2}px`;
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        });
    });

    updateClock();
    setInterval(updateClock, 1000);
});
