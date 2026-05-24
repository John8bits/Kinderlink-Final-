<?php
// session_start();
require_once '../autoload.php';

if (!function_exists('e')) {
    function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('js')) {
    function js($value)
    {
        return json_encode((string) $value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    }
}

$teacherModel = new teacher();
$teachers = $teacherModel->getAll();

$guardianModel = new guardian();
$guardians = $guardianModel->getAll();

$db = new Database();
$conn = $db->conn;

$totalTeachers = count($teachers);
$totalGuardians = count($guardians);
$totalPupils = $conn->query("SELECT COUNT(*) FROM pupil")->fetchColumn();
$totalLinks = $conn->query("SELECT COUNT(*) FROM guardian_pupil")->fetchColumn();

$inactiveTeachers = 0;
foreach ($teachers as $t) {
    if ($t['status'] !== 'active') {
        $inactiveTeachers++;
    }
}

$inactiveGuardians = 0;
foreach ($guardians as $g) {
    if ($g['status'] !== 'active') {
        $inactiveGuardians++;
    }
}

$deactivatedAccounts = $inactiveTeachers + $inactiveGuardians;

$adminFormErrors = $_SESSION['admin_form_errors'] ?? [];
$adminOldInput = $_SESSION['admin_old_input'] ?? [];
$adminOpenModal = $_SESSION['admin_open_modal'] ?? '';
unset($_SESSION['admin_form_errors'], $_SESSION['admin_old_input'], $_SESSION['admin_open_modal']);

$existingAccountEmails = [];
try {
    $emailStmt = $conn->query("SELECT email FROM users WHERE email IS NOT NULL AND email <> ''");
    $existingAccountEmails = $emailStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $existingAccountEmails = [];
}

$activityModel = new TeacherActivity();
$teacherActivities = $activityModel->getTodayActivities(40);

function adminActivityMeta($actionType)
{
    $map = [
        'attendance_saved' => ['icon' => 'fa-calendar-check', 'color' => 'blue'],
        'milestone_checklist_updated' => ['icon' => 'fa-square-check', 'color' => 'green'],
        'milestone_template_created' => ['icon' => 'fa-star', 'color' => 'violet'],
        'milestone_template_updated' => ['icon' => 'fa-pen-to-square', 'color' => 'purple'],
        'milestone_template_deleted' => ['icon' => 'fa-trash', 'color' => 'red'],
        'announcement_created' => ['icon' => 'fa-bullhorn', 'color' => 'violet'],
        'announcement_updated' => ['icon' => 'fa-pen', 'color' => 'purple'],
        'announcement_deleted' => ['icon' => 'fa-trash', 'color' => 'red'],
        'pupil_created' => ['icon' => 'fa-user-plus', 'color' => 'green'],
        'pupil_updated' => ['icon' => 'fa-user-pen', 'color' => 'purple'],
    ];

    return $map[$actionType] ?? ['icon' => 'fa-clock-rotate-left', 'color' => 'gray'];
}

$user = $_SESSION['username'] ?? '';
$role = $_SESSION['role'] ?? 'Admin';

$login_success = $_SESSION['login_success'] ?? false;
unset($_SESSION['login_success']);

$allowedPages = ['dashboard', 'teacher', 'guardians', 'pupil'];
$page = $_GET['page'] ?? 'dashboard';
if (!in_array($page, $allowedPages, true)) {
    $page = 'dashboard';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KinderLink</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin_dashb_style.css">
</head>

<body>

    <header>
        <button class="menu-toggle" type="button" onclick="toggleSidebar()" aria-label="Open menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <a href="#" class="logo">
            <img src="../logo.png">
            KinderLink
        </a>

        <div class="header-right">
            <div class="user-badge">
                <div class="name"><?= e($user) ?></div>
                <div class="role"><?= e($role) ?></div>
            </div>
            <a class="logout" href="#" onclick="confirmLogout(event, '../Authentication/logout.php')"><i
                    class="fa fa-sign-out"></i> Logout</a>
        </div>
    </header>

    <div class="container">

        <aside>
            <div class="nav">
                <div class="nav-section">
                    <div class="nav-label">Overview</div>
                    <a href="?page=dashboard" class="<?= $page == 'dashboard' ? 'active' : '' ?>">
                        <i class="fa fa-grip"></i> Dashboard
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-label">Management</div>
                    <a href="?page=teacher" class="<?= $page == 'teacher' ? 'active' : '' ?>">
                        <i class="fa fa-chalkboard-user"></i> Teachers
                    </a>

                    <a href="?page=guardians" class="<?= $page == 'guardians' ? 'active' : '' ?>">
                        <i class="fa fa-users"></i> Guardians
                    </a>

                    <a href="?page=pupil" class="<?= $page == 'pupil' ? 'active' : '' ?>">
                        <i class="fa fa-link"></i> Pupil Linking
                    </a>
                </div>
            </div>
        </aside>
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar(false)"></div>

        <main>

            <?php if ($page == 'dashboard'): ?>

                <div class="page-title">Admin Dashboard</div>

                <div class="cards">
                    <div class="card">
                        <div class="card-top">
                            <div>
                                <p class="small">Total Teachers</p>
                                <div class="value"><?= (int) $totalTeachers ?></div>
                            </div>
                            <div class="icon blue">
                                <i class="fa fa-chalkboard-user"></i>
                            </div>
                        </div>
                        <p class="small">Active accounts</p>
                    </div>

                    <div class="card">
                        <div class="card-top">
                            <div>
                                <p class="small">Total Guardians</p>
                                <div class="value"><?= (int) $totalGuardians ?></div>
                            </div>
                            <div class="icon purple">
                                <i class="fa fa-users"></i>
                            </div>
                        </div>
                        <p class="small">Active accounts</p>
                    </div>

                    <div class="card">
                        <div class="card-top">
                            <div>
                                <p class="small">Total Pupils</p>
                                <div class="value"><?= (int) $totalPupils ?></div>
                            </div>
                            <div class="icon violet">
                                <i class="fa fa-user-graduate"></i>
                            </div>
                        </div>
                        <p class="small">Enrolled students</p>
                    </div>

                    <div class="card">
                        <div class="card-top">
                            <div>
                                <p class="small">Deactivated Accounts</p>
                                <div class="value"><?= (int) $deactivatedAccounts ?></div>
                            </div>
                            <div class="icon gray">
                                <i class="fa fa-user-slash"></i>
                            </div>
                        </div>
                        <p class="small">Inactive users</p>
                    </div>

                    <div class="card">
                        <div class="card-top">
                            <div>
                                <p class="small">Active Pupil Links</p>
                                <div class="value"><?= (int) $totalLinks ?></div>
                            </div>
                            <div class="icon green">
                                <i class="fa fa-link"></i>
                            </div>
                        </div>
                        <p class="small">Guardian-child connections</p>
                    </div>
                </div>

                <div class="activity-card admin-activity-card">
                    <div class="activity-header">
                        <div>
                            <h3>Recent Activity</h3>
                            <p>Teacher actions recorded today for monitoring.</p>
                        </div>
                        <span><?= e(date('M j, Y')) ?></span>
                    </div>

                    <?php if (empty($teacherActivities)): ?>
                        <div class="activity-empty">
                            <i class="fa fa-clock"></i>
                            <div>
                                <strong>No teacher activity yet today</strong>
                                <p>Attendance, milestones, announcements, and pupil updates will appear here once teachers save
                                    changes.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="activity-list">
                            <?php foreach ($teacherActivities as $activity): ?>
                                <?php $meta = adminActivityMeta($activity['action_type'] ?? ''); ?>
                                <div class="activity-item">
                                    <div class="activity-icon <?= e($meta['color']) ?>">
                                        <i class="fa <?= e($meta['icon']) ?>"></i>
                                    </div>
                                    <div class="activity-body">
                                        <div class="activity-row">
                                            <div class="title"><?= e($activity['title'] ?? '') ?></div>
                                            <time><?= e(date('g:i A', strtotime($activity['activity_time'] ?? 'now'))) ?></time>
                                        </div>
                                        <div class="subtitle">
                                            <?= e($activity['description'] ?? '') ?>
                                        </div>
                                        <div class="activity-teacher">
                                            <?= e($activity['teacher_name'] ?? 'Teacher') ?>
                                            <span><?= e(($activity['class_name'] ?? '') ?: 'No class assigned') ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($page == 'teacher'): ?>

                <div class="top">
                    <h2 style="color: #3b5bdb;">Teacher Management</h2>
                    <button class="add-btn" onclick="openModal()">
                        <i class="fa fa-plus"></i> Add Teacher
                    </button>
                </div>

                <div class="card">
                    <p style="margin-bottom:20px; font-weight:bold;">Teacher Accounts</p>
                    <div class="toolbar">
                        <input type="text" id="search" placeholder="Search by name or email..." oninput="filter()"
                            class="search">
                        <select id="status" onchange="filter()">
                            <option>-- All --</option>
                            <option>Active</option>
                            <option>Inactive</option>
                        </select>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Class</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($teachers as $t): ?>
                                <tr>
                                    <td><?= e($t['teacher_name'] ?? ''); ?></td>
                                    <td><?= e($t['email'] ?? ''); ?></td>
                                    <td><?= e($t['class_name'] ?? ''); ?></td>

                                    <?php $teacherStatus = ($t['status'] ?? '') === 'active' ? 'active' : 'inactive'; ?>
                                    <td class="<?= $teacherStatus === 'active' ? 'active-badge' : 'inactive-badge' ?>">
                                        <?= e($teacherStatus); ?>
                                    </td>

                                    <td>
                                        <a class="btn"
                                            href="../controllers/toggle_teacher.php?id=<?= (int) $t['teacher_id'] ?>&status=<?= e($teacherStatus) ?>">
                                            <?= $teacherStatus === 'active' ? 'Deactivate' : 'Activate' ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>

                    </table>
                </div>

            <?php elseif ($page == 'guardians'):
                require_once '../models/guardian.php';
                $guardianModel = new guardian();
                $guardians = $guardianModel->getAll();
                ?>

                <div class="top">
                    <h2 style="color: #3b5bdb;">Guardian Management</h2>
                    <button class="add-btn" onclick="openGuardianModal()">
                        <i class="fa fa-user-plus"></i> Add Guardian
                    </button>
                </div>

                <div class="card">
                    <p style="margin-bottom:20px; font-weight:bold;">Guardian Accounts</p>
                    <div class="toolbar">
                        <input type="text" id="guardian-search" placeholder="Search by name or email..."
                            oninput="filterGuardians()" class="search">
                        <select id="guardian-status" onchange="filterGuardians()">
                            <option>-- All --</option>
                            <option>Active</option>
                            <option>Inactive</option>
                        </select>
                    </div>

                    <table id="guardianTable">
                        <thead>
                            <tr>
                                <th>Guardian Name</th>
                                <th>Linked Children</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($guardians as $g): ?>
                                <tr>
                                    <td><?= e($g['guardian_name'] ?? '') ?></td>
                                    <td><?= e(($g['linked_children'] ?? '') ?: 'No pupil linked') ?></td>
                                    <td><?= e($g['email'] ?? '') ?></td>
                                    <?php $guardianStatus = ($g['status'] ?? '') === 'active' ? 'active' : 'inactive'; ?>
                                    <td class="<?= $guardianStatus === 'active' ? 'active-badge' : 'inactive-badge' ?>">
                                        <?= e($guardianStatus) ?>
                                    </td>
                                    <td>
                                        <a class="btn"
                                            href="../controllers/delete_guardian.php?id=<?= (int) $g['guardian_id'] ?>">
                                            <?= $guardianStatus === 'active' ? 'Deactivate' : 'Activate' ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($page == 'pupil'):
                require_once '../autoload.php';

                $pupilModel = new PupilLink();
                $guardians = $pupilModel->getGuardians();
                $pupils = $pupilModel->getPupils();
                $links = $pupilModel->getLinks();

                ?>
                <h2 style="color: #3b5bdb;">Guardian-Pupil Linking</h2>

                <div class="card">
                    <h3>Link Guardian to Pupil</h3>

                    <form action="../controllers/link_pupil.php" method="POST" onsubmit="return confirmLinkSubmit(event)">
                        <div class="link-form-grid">
                            <div class="field">
                                <label>Select Guardian</label>
                                <select name="guardian_id" required>
                                    <option value="">Choose guardian</option>
                                    <?php foreach ($guardians as $g): ?>
                                        <?php
                                        $guardianLinked = (int) ($g['linked_count'] ?? 0) > 0;
                                        $guardianLabel = ($g['guardian_name'] ?? '') . ($guardianLinked ? ' - Already linked' : '');
                                        if ($guardianLinked && !empty($g['linked_pupils'])) {
                                            $guardianLabel .= ' to ' . $g['linked_pupils'];
                                        }
                                        ?>
                                        <option value="<?= (int) $g['guardian_id'] ?>" <?= $guardianLinked ? 'disabled' : '' ?>>
                                            <?= e($guardianLabel) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="field">
                                <label>Select Pupil</label>
                                <select name="pupil_id" required>
                                    <option value="">Choose pupil</option>
                                    <?php foreach ($pupils as $p): ?>
                                        <?php
                                        $pupilLinked = (int) ($p['linked_count'] ?? 0) > 0;
                                        $pupilName = trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''));
                                        $pupilLabel = $pupilName . ($pupilLinked ? ' - Already linked' : '');
                                        if ($pupilLinked && !empty($p['linked_guardians'])) {
                                            $pupilLabel .= ' to ' . $p['linked_guardians'];
                                        }
                                        ?>
                                        <option value="<?= (int) $p['pupil_id'] ?>" <?= $pupilLinked ? 'disabled' : '' ?>>
                                            <?= e($pupilLabel) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="link-btn">
                            <i class="fa fa-link"></i> Link Account
                        </button>
                    </form>
                </div>

                <div class="card" style="margin-top:20px;">
                    <h3>Existing Links</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Guardian Name</th>
                                <th>Pupil Name</th>
                                <th>Teacher/Class</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($links as $l): ?>
                                <tr>
                                    <td><?= e($l['guardian_name'] ?? '') ?></td>
                                    <td><?= e($l['pupil_name'] ?? '') ?></td>
                                    <td><?= e($l['teacher_class'] ?? '-') ?></td>
                                    <td>
                                        <a class="remove-btn"
                                            href="../controllers/remove_link.php?id=<?= (int) $l['guardian_pupil_id'] ?>"
                                            onclick="confirmRemoveLink(event, this.href)">
                                            Remove Link
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

        </main>
    </div>

    <div class="modal-bg" id="modal">
        <div class="modal">
            <div class="modal-header">
                <h3>Add Teacher</h3>
                <button class="close-btn" onclick="closeModal()">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <form action="../controllers/add_teacher.php" method="POST" class="password-match-form account-form"
                data-account-form="teacher">
                <label for="teacher_name">Teacher Name</label>
                <input id="teacher_name" name="name" placeholder="eg. Ms. Smith"
                    value="<?= e($adminOldInput['teacher_name'] ?? '') ?>" required>

                <label for="teacher_email">Email</label>
                <input id="teacher_email" name="email" type="email" placeholder="eg. teacher@school.com"
                    value="<?= e($adminOldInput['teacher_email'] ?? '') ?>" required>
                <div class="input-error" data-error-for="teacher_email" style="margin-top:-10px; margin-bottom: 30px;">
                    <?= e($adminFormErrors['teacher_email'] ?? '') ?>
                </div>

                <label for="teacher_pass" style="margin-top:-25px;">Password</label>
                <div class="password-field">
                    <input id="teacher_pass" name="pass" type="password" placeholder="Password" minlength="8"
                        required>
                    <button type="button" class="password-toggle" onclick="togglePasswordField('teacher_pass', this)"
                        aria-label="Show password">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
                <div class="input-error" data-error-for="teacher_pass"></div>

                <label for="teacher_confirm_pass" style="margin-top:-10px;">Confirm Password</label>
                <div class="password-field">
                    <input id="teacher_confirm_pass" name="confirm_pass" type="password" placeholder="Confirm password"
                        minlength="8" required>
                    <button type="button" class="password-toggle"
                        onclick="togglePasswordField('teacher_confirm_pass', this)" aria-label="Show password">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
                <div class="input-error" data-error-for="teacher_confirm_pass"></div>

                <label for="teacher_class">Class Name</label>
                <input id="teacher_class" name="cls" placeholder="Enter class name"
                    value="<?= e($adminOldInput['teacher_class'] ?? '') ?>" required>

                <button type="submit" class="btn-add">Add Teacher</button>
            </form>

        </div>
    </div>

    <!--guardian modal-->
    <div class="modal-bg" id="guardianModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Add Guardian</h3>
                <button class="close-btn" onclick="closeGuardianModal()">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <form action="../controllers/add_guardian.php" method="POST" class="password-match-form account-form"
                data-account-form="guardian">
                <label for="guardian_name">Guardian Name</label>
                <input id="guardian_name" name="name" placeholder="eg. John Doe"
                    value="<?= e($adminOldInput['guardian_name'] ?? '') ?>" required>

                <label for="guardian_email">Email</label>
                <input id="guardian_email" name="email" type="email" placeholder="eg. jdoe@gmail.com"
                    value="<?= e($adminOldInput['guardian_email'] ?? '') ?>" required>
                <div class="input-error" data-error-for="guardian_email" style="margin-top:-10px;">
                    <?= e($adminFormErrors['guardian_email'] ?? '') ?>
                </div>

                <label for="guardian_pass" style="margin-top:-7px;">Password</label>
                <div class="password-field">
                    <input id="guardian_pass" name="pass" type="password" placeholder="Password" minlength="8"
                        required>
                    <button type="button" class="password-toggle" onclick="togglePasswordField('guardian_pass', this)"
                        aria-label="Show password">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
                <div class="input-error" data-error-for="guardian_pass"></div>

                <label for="guardian_confirm_pass" style="margin-top: -10px;">Confirm Password</label>
                <div class="password-field">
                    <input id="guardian_confirm_pass" name="confirm_pass" type="password" placeholder="Confirm password"
                        minlength="8" required>
                    <button type="button" class="password-toggle"
                        onclick="togglePasswordField('guardian_confirm_pass', this)" aria-label="Show password">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
                <div class="input-error" data-error-for="guardian_confirm_pass"></div>

                <button type="submit" class="btn-add">Add Guardian</button>
            </form>
        </div>
    </div>

    <!--link modal-->
    <div class="modal-bg" id="pupilModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Link Pupil</h3>
                <button class="close-btn" onclick="closePupilModal()">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <form action="../controllers/link_pupil.php" method="POST">
                <input name="pupil" placeholder="Pupil Name" required>
                <input name="guardian_id" placeholder="Guardian ID" required>

                <button type="submit" class="btn-add">Link</button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <script>
        window.KinderLinkFlashMessages = [
            <?php if ($login_success): ?>{ icon: 'success', title: 'Admin Login Successful!', text: <?= js('Welcome back, Admin ' . $user) ?>, confirmButtonText: 'OK', confirmButtonColor: '#4361ee' }, <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>{ icon: 'success', title: 'Success!', text: <?= js($_SESSION['success']) ?>, confirmButtonColor: '#4361ee' }, <?php unset($_SESSION['success']); ?><?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>{ icon: 'error', title: 'Error!', text: <?= js($_SESSION['error']) ?>, confirmButtonColor: '#ef4444' }, <?php unset($_SESSION['error']); ?><?php endif; ?>
        ];
        window.KinderLinkExistingEmails = <?= json_encode(array_values($existingAccountEmails), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
        window.KinderLinkAdminOpenModal = <?= js($adminOpenModal) ?>;
    </script>
    <script>
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

        function setFieldError(input, message) {
            if (!input) return;
            input.classList.toggle('input-invalid', Boolean(message));
            const error = input.closest('form')?.querySelector(`[data-error-for="${input.id}"]`);
            if (error) {
                error.textContent = message;
            }
        }

        function validateAccountEmail(form, showEmptyErrors = false) {
            const emailInput = form.querySelector('input[name="email"]');
            if (!emailInput) return true;

            const email = emailInput.value.trim();
            const existingEmails = (window.KinderLinkExistingEmails || []).map(item => String(item).trim().toLowerCase());

            if (!email) {
                setFieldError(emailInput, showEmptyErrors ? 'Email is required.' : '');
                return !showEmptyErrors;
            }

            if (!emailInput.validity.valid) {
                setFieldError(emailInput, 'Enter a valid email address.');
                return false;
            }

            if (existingEmails.includes(email.toLowerCase())) {
                setFieldError(emailInput, 'Email is already taken.');
                return false;
            }

            setFieldError(emailInput, '');
            return true;
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
                if (event.target.matches('input[name="email"]')) {
                    validateAccountEmail(form, false);
                }
            });

            form.addEventListener('submit', event => {
                const passwordValid = validatePasswordMatchForm(form, true);
                const emailValid = validateAccountEmail(form, true);
                if (!passwordValid || !emailValid) {
                    event.preventDefault();
                }
            });

            form.querySelectorAll('input[name="email"], input[name="pass"], input[name="confirm_pass"]').forEach(input => {
                input.addEventListener('invalid', event => {
                    event.preventDefault();
                    validateAccountEmail(form, true);
                    validatePasswordMatchForm(form, true);
                });
            });

            const emailInput = form.querySelector('input[name="email"]');
            if (emailInput?.value.trim()) {
                validateAccountEmail(form, false);
            }
        });

        if (window.KinderLinkAdminOpenModal === 'teacher') {
            openModal();
        } else if (window.KinderLinkAdminOpenModal === 'guardian') {
            openGuardianModal();
        }

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

    </script>
    <script>
        (function () {
            const messages = window.KinderLinkFlashMessages || [];
            if (!Array.isArray(messages) || typeof Swal === 'undefined') return;
            messages.forEach((message) => Swal.fire(message));
        })();
    </script>

</body>

</html>
