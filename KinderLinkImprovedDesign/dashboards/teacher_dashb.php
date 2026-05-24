<?php
// session_start();
// require_once '../config/database.php';
// require_once '../models/pupil_manager.php';
// require_once '../models/milestone_manager.php';
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

if (!function_exists('safe_json')) {
    function safe_json($value)
    {
        return json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    }
}

$db = new Database();
$conn = $db->conn;

$user = $_SESSION['username'] ?? '';
$role = $_SESSION['role'] ?? 'Teacher';
$allowedPages = ['dashboard', 'pupils', 'attendance', 'milestones', 'announcements', 'reports'];
$page = $_GET['page'] ?? 'dashboard';
if (!in_array($page, $allowedPages, true)) {
    $page = 'dashboard';
}

$currentMonth = (int) date('m');
$currentYear = (int) date('Y');
$currentQuarter = $currentMonth <= 3 ? 'Q1' : ($currentMonth <= 6 ? 'Q2' : ($currentMonth <= 9 ? 'Q3' : 'Q4'));
$quarterRanges = [
    'Q1' => ['start' => "$currentYear-01-01", 'end' => "$currentYear-03-31"],
    'Q2' => ['start' => "$currentYear-04-01", 'end' => "$currentYear-06-30"],
    'Q3' => ['start' => "$currentYear-07-01", 'end' => "$currentYear-09-30"],
    'Q4' => ['start' => "$currentYear-10-01", 'end' => "$currentYear-12-31"],
];
$quarterLabels = [
    'Q1' => 'Quarter 1',
    'Q2' => 'Quarter 2',
    'Q3' => 'Quarter 3',
    'Q4' => 'Quarter 4',
];
$quarterOrder = array_keys($quarterRanges);
$currentQuarterIndex = array_search($currentQuarter, $quarterOrder, true);
$selectedQuarter = $_GET['quarter'] ?? $currentQuarter;
if (!isset($quarterRanges[$selectedQuarter])) {
    $selectedQuarter = $currentQuarter;
}

$login_success = $_SESSION['login_success'] ?? false;
unset($_SESSION['login_success']);

$teacherEmail = $_SESSION['email'] ?? '';

// Get teacher info
$stmt = $conn->prepare("
                SELECT t.teacher_id, t.teacher_name, c.class_name, c.class_id
                FROM teacher t
                LEFT JOIN class c ON t.teacher_id = c.teacher_teacher_id
                WHERE t.email = ?
");
$stmt->execute([$teacherEmail]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

$teacher_name = $teacher['teacher_name'] ?? 'Teacher';
$class_name = $teacher['class_name'] ?? 'No Class';
$class_id = $teacher['class_id'] ?? 0;

$pupilModel = new PupilManager();
$pupils = $pupilModel->getByClass($class_id);

// Total pupils
$stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM pupil
                WHERE class_class_id = ?
            ");
$stmt->execute([$class_id]);
$total_pupils = $stmt->fetchColumn();

// Attendance today
$today = date('Y-m-d');

// Present
$stmt = $conn->prepare("
                SELECT COUNT(*)
                FROM attendance a
                JOIN pupil p ON a.pupil_pupil_id = p.pupil_id
                WHERE p.class_class_id = ?
                AND DATE(a.date) = ?
                AND a.status = 'Present'
            ");
$stmt->execute([$class_id, $today]);
$present_today = $stmt->fetchColumn();

// Absent
$stmt = $conn->prepare("
                SELECT COUNT(*)
                FROM attendance a
                JOIN pupil p ON a.pupil_pupil_id = p.pupil_id
                WHERE p.class_class_id = ?
                AND DATE(a.date) = ?
                AND a.status = 'Absent'
            ");
$stmt->execute([$class_id, $today]);
$absent_today = $stmt->fetchColumn();

// Late
$stmt = $conn->prepare("
                SELECT COUNT(*)
                FROM attendance a
                JOIN pupil p ON a.pupil_pupil_id = p.pupil_id
                WHERE p.class_class_id = ?
                AND DATE(a.date) = ?
                AND a.status = 'Late'
            ");
$stmt->execute([$class_id, $today]);
$late_today = $stmt->fetchColumn();

$recent_activity = [
    [
        'icon' => 'fa-calendar-check',
        'color' => '#3b6ef5',
        'title' => 'Attendance taken for today',
        'subtitle' => "$present_today present, $absent_today absent, $late_today late",
    ],
    [
        'icon' => 'fa-users',
        'color' => '#8b5cf6',
        'title' => "$total_pupils pupils enrolled in your class",
        'subtitle' => 'All pupils are active',
    ],
];

$stmt = $conn->prepare("SELECT a.* FROM announcement a WHERE a.class_class_id = ? ORDER BY a.date_posted DESC");
$stmt->execute([$class_id]);
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KinderLink – Teacher Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/teacher_dashb_style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="<?= e($page) ?>-page">

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
            <div class="account-menu">
                <button type="button" class="user-badge" onclick="toggleAccountMenu(event)" aria-haspopup="true"
                    aria-expanded="false">
                    <span>
                        <span class="name"><?= e($user) ?></span>
                        <span class="role"><?= e($role) ?></span>
                    </span>
                </button>
                <div class="account-dropdown" id="accountDropdown">
                    <button type="button" onclick="openEditProfileModal()">
                        <i class="fa fa-user"></i> Edit Profile
                    </button>
                    <button type="button" onclick="openChangePasswordModal()">
                        <i class="fa fa-key"></i> Change Password
                    </button>
                </div>
            </div>
            <a class="logout" href="#" onclick="confirmLogout(event, '../Authentication/logout.php')"><i
                    class="fa fa-sign-out"></i> Logout</a>
        </div>
    </header>

    <div class="wrapper">

        <aside>
            <nav class="nav">
                <div class="nav-section">
                    <div class="nav-label">Overview</div>
                    <a href="?page=dashboard&quarter=<?= e($selectedQuarter) ?>"
                        class="<?= $page == 'dashboard' ? 'active' : '' ?>">
                        <i class="fa fa-grip"></i> Dashboard
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-label">Management</div>
                    <a href="?page=pupils&quarter=<?= e($selectedQuarter) ?>"
                        class="<?= $page == 'pupils' ? 'active' : '' ?>">
                        <i class="fa fa-user-group"></i> Pupils
                    </a>
                    <a href="?page=attendance&quarter=<?= e($selectedQuarter) ?>"
                        class="<?= $page == 'attendance' ? 'active' : '' ?>">
                        <i class="fa fa-calendar-check"></i> Attendance
                    </a>
                    <a href="?page=milestones&quarter=<?= e($selectedQuarter) ?>"
                        class="<?= $page == 'milestones' ? 'active' : '' ?>">
                        <i class="fa fa-star"></i> Milestones
                    </a>
                    <a href="?page=announcements&quarter=<?= e($selectedQuarter) ?>"
                        class="<?= $page == 'announcements' ? 'active' : '' ?>">
                        <i class="fa fa-bullhorn"></i> Announcements
                    </a>
                    <a href="?page=reports&quarter=<?= e($selectedQuarter) ?>"
                        class="<?= $page == 'reports' ? 'active' : '' ?>">
                        <i class="fa fa-file-lines"></i> Reports
                    </a>
                </div>
                <div class="nav-section">
                    <div class="nav-label">Grading Period</div>
                    <select id="globalQuarterSelect" class="form-select" onchange="changeQuarter(this.value)">
                        <option value="Q1" <?= $selectedQuarter === 'Q1' ? 'selected' : '' ?>>Quarter 1</option>
                        <option value="Q2" <?= $selectedQuarter === 'Q2' ? 'selected' : '' ?>>Quarter 2</option>
                        <option value="Q3" <?= $selectedQuarter === 'Q3' ? 'selected' : '' ?>>Quarter 3</option>
                        <option value="Q4" <?= $selectedQuarter === 'Q4' ? 'selected' : '' ?>>Quarter 4</option>
                    </select>
                </div>
            </nav>
        </aside>
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar(false)"></div>


        <main>

            <?php if ($page == 'dashboard'): ?>

                <div class="page-title">Teacher Dashboard</div>
                <div class="page-subtitle">
                    Welcome back, <?= e($teacher_name) ?>
                    (<?= e($class_name) ?>)
                </div>

                <div class="stat-grid">
                    <div class="stat-card">
                        <div class="top-row">
                            <span class="label">Total Pupils</span>
                            <div class="icon-box" style="background:#3b5bdb;">
                                <i class="fa fa-user-group"></i>
                            </div>
                        </div>
                        <div class="number"><?= (int) $total_pupils ?></div>
                        <div class="sublabel">In your class</div>
                    </div>

                    <div class="stat-card">
                        <div class="top-row">
                            <span class="label">Present Today</span>
                            <div class="icon-box" style="background:#16a34a;">
                                <i class="fa fa-calendar-check"></i>
                            </div>
                        </div>
                        <div class="number"><?= (int) $present_today ?></div>
                        <div class="sublabel">Attending today</div>
                    </div>

                    <div class="stat-card">
                        <div class="top-row">
                            <span class="label">Absent Today</span>
                            <div class="icon-box" style="background:#dc2626;">
                                <i class="fa fa-calendar-xmark"></i>
                            </div>
                        </div>
                        <div class="number"><?= (int) $absent_today ?></div>
                        <div class="sublabel">Not present</div>
                    </div>

                    <div class="stat-card">
                        <div class="top-row">
                            <span class="label">Late Today</span>
                            <div class="icon-box" style="background:#ea580c;">
                                <i class="fa fa-clock"></i>
                            </div>
                        </div>
                        <div class="number"><?= (int) $late_today ?></div>
                        <div class="sublabel">Arrived late</div>
                    </div>
                </div>


                <div class="activity-card">
                    <h3>Recent Activity</h3>
                    <?php foreach ($recent_activity as $act): ?>
                        <div class="activity-item">
                            <div class="activity-icon" style="background: <?= e($act['color']) ?>;">
                                <i class="fa <?= e($act['icon']) ?>"></i>
                            </div>
                            <div>
                                <div class="title"><?= e($act['title']) ?></div>
                                <div class="subtitle"><?= e($act['subtitle']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php elseif ($page == 'pupils'): ?>
                <?php
                $guardianValidationList = $pupilModel->getGuardianValidationList();
                $pupilNameValidationList = $pupilModel->getPupilNameValidationList($class_id);
                ?>
                <!-- <div class="page-title">Pupils</div> -->
                <div class="top">
                    <h2>Pupil Lists</h2>
                    <button class="add-btn" onclick="toggleForm()">
                        <i class="fa fa-plus"></i> Add Pupil
                    </button>
                </div>

                <div class="form-card" id="pupilForm" style="display:none;">

                    <div class="form-title">Add New Pupil</div>

                    <form method="POST" action="../controllers/add_pupil.php" id="addPupilForm">

                        <input type="hidden" name="class_id" value="<?= (int) $class_id ?>">

                        <div class="form-grid">
                            <div>
                                <label>First Name</label>
                                <input class="form-input" type="text" name="first_name" id="add_first_name"
                                    placeholder="Enter first name" required>
                                <div class="input-error" id="addFirstNameError"></div>
                            </div>

                            <div>
                                <label>Last Name</label>
                                <input class="form-input" type="text" name="last_name" id="add_last_name"
                                    placeholder="Enter last name" required>
                                <div class="input-error" id="addLastNameError"></div>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div>
                                <label>Age</label>
                                <input class="form-input" type="number" name="age" id="add_age" placeholder="Enter age"
                                    min="4" max="5" required>
                                <div class="input-error" id="ageError"></div>
                            </div>

                            <div>
                                <label>Gender</label>
                                <select class="form-select" name="gender" required>
                                    <option value="">Select gender</option>
                                    <option>Male</option>
                                    <option>Female</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div>
                                <label>Birthdate</label>
                                <input class="form-input" type="date" name="birthdate" id="add_birthdate" required>
                                <div class="input-error" id="addBirthdateError"></div>
                            </div>

                            <div>
                                <label>Home Address</label>
                                <input class="form-input" type="text" name="home_address" id="add_home_address"
                                    placeholder="Enter address" required>
                                <div class="input-error" id="addAddressError"></div>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div>
                                <label>Guardian Name</label>
                                <input class="form-input" type="text" name="guardian_name" id="add_guardian_name"
                                    placeholder="Enter guardian name" required>
                                <div class="input-error" id="guardianError"></div>
                            </div>

                            <div>
                                <label>Contact Number</label>
                                <input class="form-input" type="text" name="contact_number" id="add_contact_number"
                                    placeholder="Enter 11-digit contact number" inputmode="numeric" maxlength="11"
                                    pattern="[0-9]{11}" required>
                                <div class="input-error" id="addContactError"></div>
                            </div>
                        </div>

                        <div class="form-grid-1">
                            <label>Health Notes</label>
                            <textarea class="form-textarea" name="health_notes" id="add_health_notes"
                                placeholder="Enter health notes (optional)"></textarea>
                            <div class="input-error" id="addHealthNotesError"></div>
                        </div>

                        <div class="form-actions">

                            <button type="submit" class="form-btn btn-save" id="addPupilSaveBtn" disabled>
                                <i class="fa fa-save"></i> Save Pupil
                            </button>

                            <button type="button" class="form-btn btn-cancel" onclick="toggleForm()">
                                <i class="fa fa-times"></i> Cancel
                            </button>

                        </div>

                    </form>

                </div>

                <div class="form-card" id="editPupilForm" style="display:none;">

                    <div class="form-title">Edit Pupil</div>

                    <form method="POST" action="../controllers/update_pupil.php" id="editPupilFormElement">

                        <input type="hidden" name="pupil_id" id="edit_pupil_id">
                        <input type="hidden" name="class_id" value="<?= (int) $class_id ?>">

                        <div class="form-grid">
                            <div>
                                <label>First Name</label>
                                <input class="form-input" type="text" name="first_name" id="edit_first_name"
                                    placeholder="Enter first name" required>
                                <div class="input-error" id="editFirstNameError"></div>
                            </div>

                            <div>
                                <label>Last Name</label>
                                <input class="form-input" type="text" name="last_name" id="edit_last_name"
                                    placeholder="Enter last name" required>
                                <div class="input-error" id="editLastNameError"></div>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div>
                                <label>Age</label>
                                <input class="form-input" type="number" name="age" id="edit_age" placeholder="Enter age"
                                    min="4" max="5" required>
                                <div class="input-error" id="editAgeError"></div>
                            </div>

                            <div>
                                <label>Gender</label>
                                <select class="form-select" name="gender" id="edit_gender" required>
                                    <option value="">Select gender</option>
                                    <option>Male</option>
                                    <option>Female</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div>
                                <label>Birthdate</label>
                                <input class="form-input" type="date" name="birthdate" id="edit_birthdate" required>
                                <div class="input-error" id="editBirthdateError"></div>
                            </div>

                            <div>
                                <label>Home Address</label>
                                <input class="form-input" type="text" name="home_address" id="edit_home_address"
                                    placeholder="Enter address" required>
                                <div class="input-error" id="editAddressError"></div>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div>
                                <label>Guardian Name</label>
                                <input class="form-input" type="text" name="guardian_name" id="edit_guardian_name"
                                    placeholder="Enter guardian name" readonly required>
                                <div class="input-error" id="editGuardianError"></div>
                            </div>

                            <div>
                                <label>Contact Number</label>
                                <input class="form-input" type="text" name="contact_number" id="edit_contact_number"
                                    placeholder="Enter 11-digit contact number" inputmode="numeric" maxlength="11"
                                    pattern="[0-9]{11}" required>
                                <div class="input-error" id="editContactError"></div>
                            </div>
                        </div>

                        <div class="form-grid-1">
                            <label>Health Notes</label>
                            <textarea class="form-textarea" name="health_notes" id="edit_health_notes"
                                placeholder="Enter health notes (optional)"></textarea>
                            <div class="input-error" id="editHealthNotesError"></div>
                        </div>

                        <div class="form-actions">

                            <button type="submit" class="form-btn btn-save" id="editPupilSaveBtn">
                                <i class="fa fa-save"></i> Update Pupil
                            </button>

                            <button type="button" class="form-btn btn-cancel" onclick="toggleEditForm()">
                                <i class="fa fa-times"></i> Cancel
                            </button>

                        </div>

                    </form>

                </div>

                <div class="card">
                    <p style="margin-bottom:20px; font-weight:bold;">All Pupils</p>
                    <div class="toolbar">
                        <input type="text" id="search" placeholder="Search by name..." oninput="filterPupils()"
                            class="search">
                    </div>

                    <table id="pupilTable">
                        <thead>
                            <tr>
                                <th>Pupil</th>
                                <th>Age</th>
                                <th>Guardian</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($pupils as $pupil): ?>
                                <?php
                                $pupilData = [
                                    'pupil_id' => $pupil['pupil_id'],
                                    'first_name' => $pupil['first_name'] ?? '',
                                    'last_name' => $pupil['last_name'] ?? '',
                                    'full_name' => trim(($pupil['first_name'] ?? '') . ' ' . ($pupil['last_name'] ?? '')),
                                    'age' => $pupil['age'] ?? '',
                                    'gender' => $pupil['gender'] ?? '',
                                    'birthdate' => $pupil['birthdate'] ?? '',
                                    'home_address' => $pupil['home_address'] ?? '',
                                    'guardian_name' => $pupil['guardian_name'] ?? '',
                                    'contact_number' => $pupil['contact_number'] ?? '',
                                    'health_notes' => $pupil['health_notes'] ?? '',
                                ];
                                $pupilJson = e(safe_json($pupilData));
                                ?>
                                <tr>
                                    <td><?= e(($pupil['first_name'] ?? '') . ' ' . ($pupil['last_name'] ?? '')) ?></td>
                                    <td><?= (int) $pupil['age'] ?></td>
                                    <td><?= e($pupil['guardian_name'] ?? '') ?></td>
                                    <td><span class="status-active">Active</span></td>
                                    <td class="table-actions">
                                        <button class="btn btn-view" onclick='viewPupilProfile(<?= $pupilJson ?>)'>
                                            <i class="fa fa-eye"></i> View Profile
                                        </button>
                                        <button class="btn btn-edit" onclick='editPupil(<?= $pupilJson ?>)'>Edit</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr id="no-results-row" style="display: <?= empty($pupils) ? '' : 'none' ?>;">
                                <td colspan="5" style="text-align:center; color:#6b7280; padding:20px;">
                                    No pupil found.
                                </td>
                            </tr>
                        </tbody>

                    </table>
                </div>

            <?php elseif ($page == 'announcements'): ?>
                <div class="announcement-header">
                    <div>
                        <div class="page-title">Announcements Board</div>
                        <div class="page-subtitle">Create and manage classroom updates in one place.</div>
                    </div>
                    <button class="btn-primary" onclick="openAnnouncementModal()">
                        <i class="fa fa-plus"></i> Post Announcement
                    </button>
                </div>

                <div class="announcement-layout">
                    <div class="announcement-board">
                        <?php if (empty($announcements)): ?>
                            <div class="announcement-card">
                                <p>No announcements yet. Post one using the button above.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($announcements as $announcement): ?>
                                <div class="announcement-card">
                                    <div class="announcement-card-header">
                                        <div>
                                            <h3><?= e($announcement['title']) ?></h3>
                                            <span><?= e(date('Y-m-d', strtotime($announcement['date_posted']))) ?></span>
                                        </div>
                                        <div class="announcement-actions">
                                            <button type="button" class="announcement-icon-btn edit"
                                                onclick='editAnnouncement(<?= e(safe_json($announcement)) ?>)'
                                                title="Edit announcement">
                                                <i class="fa fa-pen"></i>
                                            </button>
                                            <button type="button" class="announcement-icon-btn delete"
                                                onclick="deleteAnnouncementConfirm(<?= (int) $announcement['announcement_id'] ?>)"
                                                title="Delete announcement">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <p><?= e($announcement['content']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <aside class="announcement-sidebar">
                        <h4>Recent Posts</h4>
                        <?php if (empty($announcements)): ?>
                            <div class="sidebar-item">
                                <h5>No announcements</h5>
                                <span>Start posting updates</span>
                            </div>
                        <?php else: ?>
                            <?php foreach ($announcements as $announcement): ?>
                                <div class="sidebar-item">
                                    <h5><?= htmlspecialchars($announcement['title']) ?></h5>
                                    <span><?= htmlspecialchars(date('Y-m-d', strtotime($announcement['date_posted']))) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </aside>
                </div>

                <div id="announcementModal" class="modal-overlay" style="display: none;">
                    <div class="modal-box">
                        <div class="modal-header">
                            <h3 id="announcementModalTitle">New Announcement</h3>
                            <button type="button" class="modal-close" onclick="closeAnnouncementModal()">&times;</button>
                        </div>
                        <form method="POST" action="../controllers/add_announcement.php">
                            <input type="hidden" name="announcement_id" id="announcementId">
                            <label for="announcementTitle">Title</label>
                            <input id="announcementTitle" class="form-input" type="text" name="title"
                                placeholder="Enter announcement title" required>

                            <label for="announcementMessage">Message</label>
                            <textarea id="announcementMessage" class="form-textarea" name="content"
                                placeholder="Enter announcement message" required></textarea>

                            <div class="modal-actions">
                                <button type="submit" class="form-btn btn-primary" id="announcementSubmitBtn">
                                    <i class="fa fa-paper-plane"></i> Post Announcement
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            <?php elseif ($page == 'attendance'): ?>
                <?php
                $today = date('Y-m-d');
                $date = $_GET['date'] ?? $today;
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    $date = $today;
                }
                $quarterRange = $quarterRanges[$selectedQuarter];
                if ($date < $quarterRange['start'] || $date > $quarterRange['end']) {
                    $date = ($selectedQuarter === $currentQuarter) ? $today : $quarterRange['start'];
                }
                if ($selectedQuarter === $currentQuarter && $date > $today) {
                    $date = $today;
                }
                $isToday = ($date === $today);
                $selectedQuarterIndex = array_search($selectedQuarter, $quarterOrder, true);
                $isCurrentQuarter = ($selectedQuarter === $currentQuarter);
                $isPastQuarter = $selectedQuarterIndex !== false && $selectedQuarterIndex < $currentQuarterIndex;
                $isFutureQuarter = $selectedQuarterIndex !== false && $selectedQuarterIndex > $currentQuarterIndex;
                $attendanceEditable = $isToday && $isCurrentQuarter;
                $attendanceLockMessage = '';
                if ($isPastQuarter) {
                    $attendanceLockMessage = ($quarterLabels[$selectedQuarter] ?? $selectedQuarter) . ' has ended. Attendance records for ended quarters are locked.';
                } elseif ($isFutureQuarter) {
                    $attendanceLockMessage = ($quarterLabels[$selectedQuarter] ?? $selectedQuarter) . ' has not started yet. Attendance can only be saved for the current day.';
                } elseif (!$isToday) {
                    $attendanceLockMessage = 'Only today\'s attendance can be edited and saved.';
                }
                $dateDisplay = $isToday ? 'Today' : date('F j, Y', strtotime($date));
                ?>
                <div class="page-title">Attendance <?= $isToday ? '<span style="color:#16a34a;">(Today)</span>' : '' ?>
                </div>
                <div class="attendance-top">

                    <form method="GET" style="display: flex; gap: 15px; align-items: center;">
                        <input type="hidden" name="page" value="attendance">

                        <div style="display: flex; align-items: center; gap: 10px;">
                            <label style="font-weight: bold; margin: 0;"><b>Select Date:</b></label>
                            <input type="date" name="date" value="<?= e($date) ?>" min="<?= e($quarterRange['start']) ?>"
                                max="<?= e($isCurrentQuarter ? min($quarterRange['end'], $today) : $quarterRange['end']) ?>"
                                onchange="this.form.submit()"
                                style="padding: 8px 12px; border: 2px solid #3b5bdb; border-radius: 6px; font-size: 14px; cursor: pointer;">
                            <?php if ($isToday): ?>
                                <span
                                    style="background: #16a34a; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold;">TODAY</span>
                            <?php endif; ?>
                        </div>

                        <div style="display: flex; align-items: center; gap: 10px;">
                            <label style="font-weight: bold; margin: 0;"><b>Quarter:</b></label>
                            <select name="quarter" onchange="this.form.submit()"
                                style="padding: 8px 12px; border: 2px solid #3b5bdb; border-radius: 6px; font-size: 14px; cursor: pointer;">
                                <option value="Q1" <?= $selectedQuarter === 'Q1' ? 'selected' : '' ?>>Quarter 1</option>
                                <option value="Q2" <?= $selectedQuarter === 'Q2' ? 'selected' : '' ?>>Quarter 2</option>
                                <option value="Q3" <?= $selectedQuarter === 'Q3' ? 'selected' : '' ?>>Quarter 3</option>
                                <option value="Q4" <?= $selectedQuarter === 'Q4' ? 'selected' : '' ?>>Quarter 4</option>
                            </select>
                        </div>
                    </form>

                    <button type="button" class="btn-history" onclick="toggleAttendanceHistory()">
                        <i class="fa fa-clock-rotate-left"></i> View History
                    </button>

                    <div class="clock-box">
                        <span id="clock"></span>
                    </div>
                </div>

                <?php
                $stmt = $conn->prepare("
                    SELECT COUNT(*) 
                    FROM attendance a
                    JOIN pupil p ON a.pupil_pupil_id = p.pupil_id
                    WHERE p.class_class_id = ?
                    AND DATE(a.date) = ?
                ");
                $stmt->execute([$class_id, $date]);
                $attendance_exists = $stmt->fetchColumn() > 0;

                $stmt = $conn->prepare("
                    SELECT * FROM pupil 
                    WHERE class_class_id = ?
                ");
                $stmt->execute([$class_id]);
                $pupils = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $stmt = $conn->prepare("
                    SELECT a.*, p.first_name, p.last_name
                    FROM attendance a
                    JOIN pupil p ON a.pupil_pupil_id = p.pupil_id
                    WHERE p.class_class_id = ?
                    AND DATE(a.date) BETWEEN ? AND ?
                    ORDER BY DATE(a.date) DESC, p.last_name ASC
                ");
                $stmt->execute([$class_id, $quarterRange['start'], $quarterRange['end']]);
                $attendance_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $stmt = $conn->prepare("
                    SELECT a.pupil_pupil_id, a.status
                    FROM attendance a
                    JOIN pupil p ON a.pupil_pupil_id = p.pupil_id
                    WHERE p.class_class_id = ?
                    AND DATE(a.date) = ?
                ");
                $stmt->execute([$class_id, $date]);

                $existing = [];
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $existing[$row['pupil_pupil_id']] = $row['status'];
                }
                ?>

                <?php if ($attendance_exists && $isToday): ?>
                    <div class="alert-warning"
                        style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px 16px; margin-bottom: 20px; border-radius: 4px; color:green;">
                        <i class="fa fa-exclamation-triangle" style="color: #f59e0b;"></i>
                        Attendance already recorded for today
                    </div>
                <?php elseif ($attendance_exists && !$isToday): ?>
                    <div class="alert-warning"
                        style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 12px 16px; margin-bottom: 20px; border-radius: 4px;">
                        <i class="fa fa-exclamation-triangle" style="color: #f59e0b;"></i>
                        Attendance already recorded for <?= date('F j, Y', strtotime($date)) ?>
                    </div>
                <?php endif; ?>

                <?php if (!$attendanceEditable): ?>
                    <div class="alert-warning"
                        style="background: #e0f2fe; border-left: 4px solid #0284c7; padding: 12px 16px; margin-bottom: 20px; border-radius: 4px; color:#0f172a;">
                        <i class="fa fa-lock" style="color: #0284c7;"></i>
                        <?= e($attendanceLockMessage) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="../controllers/save_attendance.php">

                    <input type="hidden" name="date" value="<?= e($date) ?>">
                    <input type="hidden" name="class_id" value="<?= (int) $class_id ?>">
                    <input type="hidden" name="quarter" value="<?= e($selectedQuarter) ?>">

                    <div class="card">

                        <h3>Mark Attendance</h3>

                        <table>
                            <thead>
                                <tr>
                                    <th>Pupil Name</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($pupils as $p): ?>
                                    <tr>
                                        <td>
                                            <?= e(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '')) ?>
                                            <div style="font-size:12px;color:gray;">
                                                <?= (int) $p['age'] ?> years
                                            </div>
                                        </td>

                                        <td>
                                            <?php $status = $existing[$p['pupil_id']] ?? ''; ?>

                                            <div class="status-buttons">

                                                <button type="button"
                                                    class="status-btn present <?= $status == 'Present' ? 'active' : '' ?>"
                                                    onclick="setStatus(<?= (int) $p['pupil_id'] ?>,'Present',this)"
                                                    <?= $attendanceEditable ? '' : 'disabled' ?>>
                                                    Present
                                                </button>

                                                <button type="button"
                                                    class="status-btn absent <?= $status == 'Absent' ? 'active' : '' ?>"
                                                    onclick="setStatus(<?= (int) $p['pupil_id'] ?>,'Absent',this)"
                                                    <?= $attendanceEditable ? '' : 'disabled' ?>>
                                                    Absent
                                                </button>

                                                <button type="button"
                                                    class="status-btn late <?= $status == 'Late' ? 'active' : '' ?>"
                                                    onclick="setStatus(<?= (int) $p['pupil_id'] ?>,'Late',this)"
                                                    <?= $attendanceEditable ? '' : 'disabled' ?>>
                                                    Late
                                                </button>

                                                <input type="hidden" name="status[<?= (int) $p['pupil_id'] ?>]"
                                                    id="status-<?= (int) $p['pupil_id'] ?>" value="<?= e($status) ?>">
                                            </div>

                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <?php
                        $present = $absent = $late = 0;

                        foreach ($existing as $s) {
                            if ($s == 'Present')
                                $present++;
                            if ($s == 'Absent')
                                $absent++;
                            if ($s == 'Late')
                                $late++;
                        }
                        ?>

                        <div class="summary-box">
                            <h4>Summary</h4>
                            <p>Present: <?= (int) $present ?></p>
                            <p>Absent: <?= (int) $absent ?></p>
                            <p>Late: <?= (int) $late ?></p>
                        </div>

                        <button type="submit" class="save-btn" <?= $attendanceEditable ? '' : 'disabled style="opacity:.55; cursor:not-allowed;"' ?>>
                            <?= $attendanceEditable ? 'Save Attendance' : 'Attendance Locked' ?>
                        </button>

                    </div>
                </form>

                <div class="attendance-history-card" id="attendanceHistory" style="display:none;">
                    <div class="history-header">
                        <div>
                            <h3>Attendance History</h3>
                            <p>Review past attendance records for this class.</p>
                        </div>
                        <div class="history-filter">
                            <label for="historyPupilSelect">View Records</label>
                            <select id="historyPupilSelect" onchange="filterAttendanceHistory()">
                                <option value="">All Pupils</option>
                                <?php foreach ($pupils as $pupil): ?>
                                    <option value="<?= (int) $pupil['pupil_id'] ?>">
                                        <?= e(($pupil['first_name'] ?? '') . ' ' . ($pupil['last_name'] ?? '')) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="history-table-wrapper">
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Pupil Name</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($attendance_history)): ?>
                                    <tr>
                                        <td colspan="3" style="text-align:center; color:#6b7280; padding:20px;">
                                            No attendance records found.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($attendance_history as $record): ?>
                                        <tr data-pupil-id="<?= (int) $record['PUPIL_pupil_id'] ?>"
                                            data-pupil-name="<?= e(($record['first_name'] ?? '') . ' ' . ($record['last_name'] ?? '')) ?>">
                                            <td><?= e(date('Y-m-d', strtotime($record['date']))) ?></td>
                                            <td><?= e(($record['first_name'] ?? '') . ' ' . ($record['last_name'] ?? '')) ?></td>
                                            <td>
                                                <?php $recordStatusClass = in_array($record['status'], ['Present', 'Absent', 'Late'], true) ? strtolower($record['status']) : ''; ?>
                                                <span
                                                    class="status-label <?= e($recordStatusClass) ?>"><?= e($record['status']) ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php elseif ($page == 'milestones'): ?>
                <?php
                $milestoneModel = new MilestoneManager();
                $pupils = $pupilModel->getByClass($class_id);
                $selectedPupilId = filter_input(INPUT_GET, 'pupil_id', FILTER_VALIDATE_INT);
                $selectedPupilId = $selectedPupilId ?: ($pupils[0]['pupil_id'] ?? null);
                $milestones = $milestoneModel->getAllMilestones();
                $pupilMilestones = $selectedPupilId ? $milestoneModel->getPupilMilestones($selectedPupilId) : [];
                $selectedPupilAbsentToday = false;
                $selectedQuarterIndex = array_search($selectedQuarter, $quarterOrder, true);
                $isCurrentQuarter = ($selectedQuarter === $currentQuarter);
                $isPastQuarter = $selectedQuarterIndex !== false && $selectedQuarterIndex < $currentQuarterIndex;
                $isFutureQuarter = $selectedQuarterIndex !== false && $selectedQuarterIndex > $currentQuarterIndex;
                $milestonesLocked = !$isCurrentQuarter;
                $milestoneLockMessage = '';
                if ($isPastQuarter) {
                    $milestoneLockMessage = ($quarterLabels[$selectedQuarter] ?? $selectedQuarter) . ' has ended. Milestones can only be saved for the current day.';
                } elseif ($isFutureQuarter) {
                    $milestoneLockMessage = ($quarterLabels[$selectedQuarter] ?? $selectedQuarter) . ' has not started yet. Milestones can only be saved for the current day.';
                }

                if ($selectedPupilId) {
                    $stmt = $conn->prepare("
                            SELECT COUNT(*)
                            FROM attendance
                            WHERE pupil_pupil_id = ?
                            AND DATE(date) = ?
                            AND status = 'Absent'
                        ");
                    $stmt->execute([$selectedPupilId, $today]);
                    $selectedPupilAbsentToday = $stmt->fetchColumn() > 0;
                }

                $completedCount = 0;
                foreach ($pupilMilestones as $item) {
                    if ($item['status'] === 'Completed') {
                        $completedCount++;
                    }
                }
                $totalCount = count($pupilMilestones);
                $progressPercent = $totalCount ? round(($completedCount / $totalCount) * 100) : 0;
                ?>

                <div class="milestone-header-row">
                    <div>
                        <h2>Milestone Checklist</h2>
                        <p class="subtitle">Track pupil progress.</p>
                    </div>
                    <div class="milestone-select-wrapper">
                        <label for="pupilSelect" class="small-label">Select Pupil</label>
                        <select id="pupilSelect" class="form-select" onchange="changePupil(this.value)">
                            <?php foreach ($pupils as $pupil): ?>
                                <option value="<?= (int) $pupil['pupil_id'] ?>" <?= (int) $pupil['pupil_id'] === (int) $selectedPupilId ? 'selected' : '' ?>>
                                    <?= e(($pupil['first_name'] ?? '') . ' ' . ($pupil['last_name'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="milestone-select-wrapper">
                        <label for="quarterSelect" class="small-label">Grading Period</label>
                        <select id="quarterSelect" class="form-select" onchange="changeQuarter(this.value)">
                            <option value="Q1" <?= $selectedQuarter === 'Q1' ? 'selected' : '' ?>>Quarter 1</option>
                            <option value="Q2" <?= $selectedQuarter === 'Q2' ? 'selected' : '' ?>>Quarter 2</option>
                            <option value="Q3" <?= $selectedQuarter === 'Q3' ? 'selected' : '' ?>>Quarter 3</option>
                            <option value="Q4" <?= $selectedQuarter === 'Q4' ? 'selected' : '' ?>>Quarter 4</option>
                        </select>
                    </div>
                </div>

                <?php if (empty($pupils)): ?>
                    <div class="card" style="text-align:center; padding: 40px;">
                        <p>No pupils found for your class yet.</p>
                    </div>
                <?php else: ?>
                    <div class="milestone-progress-card">
                        <div class="progress-overview">
                            <div>
                                <span class="progress-label">Completion</span>
                                <strong><?= (int) $progressPercent ?>%</strong>
                            </div>
                            <div class="progress-details"><?= (int) $completedCount ?> of <?= (int) $totalCount ?> completed
                            </div>
                        </div>
                        <div class="progress-bar-background">
                            <div class="progress-bar-fill" style="width: <?= (int) $progressPercent ?>%;"></div>
                        </div>
                    </div>

                    <?php if ($selectedPupilAbsentToday): ?>
                        <div class="milestone-absent-alert">
                            <i class="fa fa-calendar-xmark"></i>
                            <span>This pupil is absent today. Milestone progress cannot be updated.</span>
                        </div>
                    <?php endif; ?>
                    <?php if ($milestonesLocked): ?>
                        <div class="milestone-absent-alert">
                            <i class="fa fa-lock"></i>
                            <span><?= e($milestoneLockMessage) ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="../controllers/save_pupil_milestones.php">
                        <input type="hidden" name="pupil_id" value="<?= (int) $selectedPupilId ?>">
                        <input type="hidden" name="quarter" value="<?= e($selectedQuarter) ?>">
                        <div class="milestone-checklist-card <?= ($selectedPupilAbsentToday || $milestonesLocked) ? 'is-locked' : '' ?>">
                            <?php if (empty($pupilMilestones)): ?>
                                <div class="empty-state">
                                    <i class="fa fa-star"></i>
                                    <p>No milestones found. Add milestone templates to start tracking progress.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($pupilMilestones as $item): ?>
                                    <label
                                        class="checklist-item <?= $item['status'] === 'Completed' ? 'completed' : '' ?> <?= ($selectedPupilAbsentToday || $milestonesLocked) ? 'disabled' : '' ?>"
                                        data-lock-message="<?= e($milestonesLocked ? $milestoneLockMessage : 'This pupil is absent today. Milestone progress cannot be updated.') ?>">
                                        <div class="checklist-input">
                                            <input type="checkbox" name="completed[]" value="<?= (int) $item['milestone_id'] ?>"
                                                <?= $item['status'] === 'Completed' ? 'checked' : '' ?>                 <?= ($selectedPupilAbsentToday || $milestonesLocked) ? 'disabled' : '' ?>>
                                        </div>
                                        <div class="checklist-content">
                                            <div class="checklist-title"><?= e($item['title']) ?></div>
                                            <?php if (!empty($item['description'])): ?>
                                                <div class="checklist-desc"><?= e($item['description']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($item['status'] === 'Completed' && !empty($item['date_completed'])): ?>
                                            <span class="checklist-meta">Completed on
                                                <?= date('Y-m-d', strtotime($item['date_completed'])) ?></span>
                                        <?php endif; ?>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="milestone-actions-row">
                            <button type="submit" class="form-btn btn-save" id="saveBtn" <?= ($selectedPupilAbsentToday || $milestonesLocked) ? 'disabled' : '' ?>>
                                <i class="fa fa-save"></i> <?= $milestonesLocked ? 'Milestones Locked' : 'Save Progress' ?>
                            </button>
                            <!-- <button type="button" class="form-btn btn-cancel" onclick="openMilestoneModal()">
                                <i class="fa fa-plus"></i> Add New Milestone Template
                            </button> -->
                        </div>
                    </form>

                    <div class="milestone-templates-section">
                        <div class="section-header">
                            <div>
                                <h3>Milestone Templates</h3>
                                <p class="subtitle">Manage milestone checklist items that apply to all pupils.</p>
                            </div>
                            <button type="button" class="btn-primary" onclick="openMilestoneModal()">
                                <i class="fa fa-plus"></i> New Template
                            </button>
                        </div>

                        <?php if (empty($milestones)): ?>
                            <div class="template-empty">
                                <i class="fa fa-star"></i>
                                <div>
                                    <strong>No milestone templates yet.</strong>
                                    <p>Add a checklist item and it will be available for every pupil.</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="template-grid">
                                <?php foreach ($milestones as $template): ?>
                                    <div class="template-card">
                                        <div class="template-card-content">
                                            <strong><?= e($template['title']) ?></strong>
                                            <?php if (!empty($template['description'])): ?>
                                                <p><?= e($template['description']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="template-actions">
                                            <button type="button" class="milestone-btn edit-btn"
                                                onclick='editMilestone(<?= e(safe_json($template)) ?>)' title="Edit template">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button type="button" class="milestone-btn delete-btn"
                                                onclick="deleteMilestoneConfirm(<?= (int) $template['milestone_id'] ?>)"
                                                title="Delete template">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php endif; ?>

                <div id="milestoneModal" class="modal-overlay" style="display: none;">
                    <div class="modal-box">
                        <div class="modal-header">
                            <h3 id="modalTitle">Record New Milestone</h3>
                            <button type="button" class="modal-close" onclick="closeMilestoneModal()">&times;</button>
                        </div>
                        <form method="POST" action="../controllers/add_milestone.php">
                            <input type="hidden" name="milestone_id" id="edit_milestone_id">

                            <label for="milestoneName">Milestone Title</label>
                            <input id="milestoneName" class="form-input" type="text" name="title"
                                placeholder="e.g., Reading Recognition" required>

                            <label for="milestoneDescription">Description</label>
                            <textarea id="milestoneDescription" class="form-textarea" name="description"
                                placeholder="Enter milestone details..."></textarea>

                            <div class="modal-actions">
                                <button type="button" class="form-btn btn-cancel" onclick="closeMilestoneModal()">
                                    <i class="fa fa-times"></i> Cancel
                                </button>
                                <button type="submit" class="form-btn btn-save">
                                    <i class="fa fa-save"></i> Save Milestone
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            <?php elseif ($page == 'reports'): ?>
                <?php
                // Get quarters for the current year
                $currentMonth = (int) date('m');
                $currentYear = (int) date('Y');

                // Determine current quarter
                if ($currentMonth <= 3)
                    $currentQuarter = 'Q1';
                elseif ($currentMonth <= 6)
                    $currentQuarter = 'Q2';
                elseif ($currentMonth <= 9)
                    $currentQuarter = 'Q3';
                else
                    $currentQuarter = 'Q4';

                $selectedQuarter = $_GET['quarter'] ?? $currentQuarter;
                $selectedPupilId = filter_input(INPUT_GET, 'pupil_id', FILTER_VALIDATE_INT);
                $selectedPupilId = $selectedPupilId ?: ($pupils[0]['pupil_id'] ?? null);

                // Get quarter date ranges
                $quarterDates = [
                    'Q1' => ['start' => "$currentYear-01-01", 'end' => "$currentYear-03-31"],
                    'Q2' => ['start' => "$currentYear-04-01", 'end' => "$currentYear-06-30"],
                    'Q3' => ['start' => "$currentYear-07-01", 'end' => "$currentYear-09-30"],
                    'Q4' => ['start' => "$currentYear-10-01", 'end' => "$currentYear-12-31"],
                ];
                if (!isset($quarterDates[$selectedQuarter])) {
                    $selectedQuarter = $currentQuarter;
                }

                $dateRange = $quarterDates[$selectedQuarter];
                $selectedQuarterIndex = array_search($selectedQuarter, $quarterOrder, true);
                $reportIsViewable = $selectedQuarterIndex !== false
                    && $selectedQuarterIndex <= $currentQuarterIndex
                    && $selectedQuarterIndex >= max(0, $currentQuarterIndex - 1);
                $firstViewableQuarterIndex = max(0, $currentQuarterIndex - 1);
                $reportUnavailableMessage = '';
                if (!$reportIsViewable) {
                    if ($selectedQuarterIndex !== false && $selectedQuarterIndex > $currentQuarterIndex) {
                        $reportUnavailableMessage = ($quarterLabels[$selectedQuarter] ?? $selectedQuarter) . ' is not the current grading period yet. Nothing to display for this pupil.';
                    } else {
                        $reportUnavailableMessage = 'Only the current and previous grading periods are viewable. Nothing to display for this pupil.';
                    }
                }

                // Get selected pupil info
                $selectedPupil = null;
                if ($selectedPupilId) {
                    foreach ($pupils as $p) {
                        if ($p['pupil_id'] == $selectedPupilId) {
                            $selectedPupil = $p;
                            break;
                        }
                    }
                }

                // Attendance Summary for selected quarter
                $presentCount = 0;
                $absentCount = 0;
                $lateCount = 0;

                if ($selectedPupilId && $reportIsViewable) {
                    $stmt = $conn->prepare("
                            SELECT status, COUNT(*) as count
                            FROM attendance
                            WHERE pupil_pupil_id = ?
                            AND DATE(date) BETWEEN ? AND ?
                            GROUP BY status
                        ");
                    $stmt->execute([$selectedPupilId, $dateRange['start'], $dateRange['end']]);
                    $attendanceData = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($attendanceData as $row) {
                        if ($row['status'] === 'Present')
                            $presentCount = $row['count'];
                        elseif ($row['status'] === 'Absent')
                            $absentCount = $row['count'];
                        elseif ($row['status'] === 'Late')
                            $lateCount = $row['count'];
                    }
                }

                // Milestone data for selected pupil
                $milestoneModel = new MilestoneManager();
                $pupilMilestones = ($selectedPupilId && $reportIsViewable) ? $milestoneModel->getPupilMilestones($selectedPupilId) : [];

                $completedMilestones = 0;
                foreach ($pupilMilestones as $milestone) {
                    if ($milestone['status'] === 'Completed') {
                        $completedMilestones++;
                    }
                }
                $totalMilestones = count($pupilMilestones);
                $reportData = null;

                if ($selectedPupil && $reportIsViewable) {
                    $reportData = [
                        'pupilName' => trim(($selectedPupil['first_name'] ?? '') . ' ' . ($selectedPupil['last_name'] ?? '')),
                        'className' => $class_name,
                        'teacherName' => $teacher_name,
                        'quarter' => $selectedQuarter,
                        'dateStart' => $dateRange['start'],
                        'dateEnd' => $dateRange['end'],
                        'attendance' => [
                            'present' => (int) $presentCount,
                            'absent' => (int) $absentCount,
                            'late' => (int) $lateCount,
                        ],
                        'milestones' => array_map(function ($milestone) {
                            return [
                                'title' => $milestone['title'] ?? '',
                                'description' => $milestone['description'] ?? '',
                                'status' => $milestone['status'] ?? 'Not Started',
                                'dateCompleted' => !empty($milestone['date_completed'])
                                    ? date('Y-m-d', strtotime($milestone['date_completed']))
                                    : '',
                            ];
                        }, $pupilMilestones),
                    ];
                }
                ?>

                <div class="reports-container">
                    <div class="reports-header">
                        <h1>Progress Reports</h1>
                    </div>

                    <div class="reports-layout">
                        <!-- Sidebar -->
                        <aside class="reports-sidebar">
                            <div class="sidebar-section">
                                <label for="reportPupilSelect" class="sidebar-label">Select Pupil</label>
                                <select id="reportPupilSelect" class="form-select" onchange="changeReportPupil(this.value)">
                                    <?php foreach ($pupils as $pupil): ?>
                                        <option value="<?= (int) $pupil['pupil_id'] ?>" <?= (int) $pupil['pupil_id'] === (int) $selectedPupilId ? 'selected' : '' ?>>
                                            <?= e(($pupil['first_name'] ?? '') . ' ' . ($pupil['last_name'] ?? '')) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="sidebar-section">
                                <label for="reportQuarterSelect" class="sidebar-label">Grading Period</label>
                                <select id="reportQuarterSelect" class="form-select"
                                    onchange="changeReportQuarter(this.value)">
                                    <?php foreach ($quarterDates as $quarterKey => $quarterDate): ?>
                                        <?php
                                        $quarterIndex = array_search($quarterKey, $quarterOrder, true);
                                        $quarterViewable = $quarterIndex !== false
                                            && $quarterIndex <= $currentQuarterIndex
                                            && $quarterIndex >= $firstViewableQuarterIndex;
                                        $quarterIndicator = 'Not viewable';
                                        if ($quarterKey === $currentQuarter) {
                                            $quarterIndicator = 'Current';
                                        } elseif ($quarterIndex !== false && $quarterIndex === $currentQuarterIndex - 1) {
                                            $quarterIndicator = 'Previous';
                                        }
                                        ?>
                                        <option value="<?= e($quarterKey) ?>" <?= $selectedQuarter === $quarterKey ? 'selected' : '' ?> <?= $quarterViewable ? '' : 'disabled' ?>>
                                            <?= e(($quarterLabels[$quarterKey] ?? $quarterKey) . ' - ' . $quarterIndicator) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </aside>

                        <!-- Main Content -->
                        <main class="reports-content">
                            <?php if ($selectedPupil && !$reportIsViewable): ?>
                                <div class="empty-state-box">
                                    <i class="fa fa-circle-info"></i>
                                    <p><?= e($reportUnavailableMessage) ?></p>
                                    <p class="report-period">Selected Grading Period:
                                        <?= e($quarterLabels[$selectedQuarter] ?? $selectedQuarter) ?> | Current Grading Period:
                                        <?= e($quarterLabels[$currentQuarter] ?? $currentQuarter) ?></p>
                                </div>
                            <?php elseif ($selectedPupil): ?>
                                <div class="report-header-box">
                                    <h2>Progress Report -
                                        <?= e(($selectedPupil['first_name'] ?? '') . ' ' . ($selectedPupil['last_name'] ?? '')) ?>
                                    </h2>
                                    <p class="report-period">Grading Period: <?= e($selectedQuarter) ?></p>
                                </div>

                                <!-- Attendance Summary -->
                                <div class="report-section">
                                    <h3>Attendance Summary</h3>
                                    <div class="attendance-cards-grid">
                                        <div class="attendance-card present">
                                            <div class="attendance-value"><?= (int) $presentCount ?></div>
                                            <div class="attendance-label">Present</div>
                                        </div>
                                        <div class="attendance-card absent">
                                            <div class="attendance-value"><?= (int) $absentCount ?></div>
                                            <div class="attendance-label">Absent</div>
                                        </div>
                                        <div class="attendance-card late">
                                            <div class="attendance-value"><?= (int) $lateCount ?></div>
                                            <div class="attendance-label">Late</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Milestone Completion -->
                                <div class="report-section">
                                    <h3>Milestone Completion</h3>
                                    <div class="milestone-summary">
                                        <p class="milestone-count"><?= (int) $completedMilestones ?> of
                                            <?= (int) $totalMilestones ?> milestones completed</p>
                                        <?php if ($totalMilestones > 0): ?>
                                            <div class="milestone-list">
                                                <?php foreach ($pupilMilestones as $milestone): ?>
                                                    <div
                                                        class="milestone-item <?= $milestone['status'] === 'Completed' ? 'completed' : '' ?>">
                                                        <i
                                                            class="fa <?= $milestone['status'] === 'Completed' ? 'fa-circle-check' : 'fa-circle' ?>"></i>
                                                        <span><?= e($milestone['title']) ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Behavior Summary -->
                                <!-- <div class="report-section">
                                    <h3>Behavior Summary</h3>
                                    <div class="behavior-summary">
                                        <p class="behavior-count">0 behavior notes recorded</p>
                                        <div class="behavior-details">
                                            <div class="behavior-item">
                                                <span class="behavior-label">Positive behaviors:</span>
                                                <span class="behavior-value">0</span>
                                            </div>
                                            <div class="behavior-item">
                                                <span class="behavior-label">Concerns:</span>
                                                <span class="behavior-value">0</span>
                                            </div>
                                        </div>
                                    </div>
                                </div> -->

                                <!-- View Full Report -->
                                <div class="report-actions">
                                    <button type="button" class="btn-primary btn-view-full"
                                        onclick='viewFullReport(<?= e(safe_json($reportData)) ?>)'>
                                        <i class="fa fa-file-pdf" style="margin-left:45%;"></i> View Full Report
                                        <!-- <i class="vfr"></i> View Full Report -->
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="empty-state-box">
                                    <i class="fa fa-file-lines"></i>
                                    <p>No pupil selected. Please select a pupil to view their progress report.</p>
                                </div>
                            <?php endif; ?>
                        </main>
                    </div>
                </div>

                <script>
                    window.currentFullReport = <?= safe_json($reportData) ?>;
                </script>

            <?php else: ?>
                <div class="page-title"><?= ucfirst($page) ?></div>
            <?php endif; ?>

        </main>
    </div>

    <div id="changePasswordModal" class="modal-overlay" style="display:none;">
        <div class="modal-box account-modal">
            <div class="modal-header">
                <h3>Change Password</h3>
                <button type="button" class="modal-close" onclick="closeChangePasswordModal()">&times;</button>
            </div>

            <form method="POST" action="../Authentication/change_password.php"
                class="password-match-form change-password-form">
                <?= csrf_field() ?>

                <label for="teacherCurrentPassword">Current Password</label>
                <div class="password-field">
                    <input id="teacherCurrentPassword" class="form-input" type="password" name="current_password"
                        autocomplete="current-password" required>
                    <button type="button" class="password-toggle"
                        onclick="togglePasswordField('teacherCurrentPassword', this)" aria-label="Show password">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
                <div class="input-error" data-error-for="teacherCurrentPassword"></div>

                <label for="teacherNewPassword">New Password</label>
                <div class="password-field">
                    <input id="teacherNewPassword" class="form-input" type="password" name="new_password"
                        autocomplete="new-password" minlength="8" required>
                    <button type="button" class="password-toggle"
                        onclick="togglePasswordField('teacherNewPassword', this)" aria-label="Show password">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
                <div class="input-error" data-error-for="teacherNewPassword"></div>

                <label for="teacherConfirmPassword">Confirm New Password</label>
                <div class="password-field">
                    <input id="teacherConfirmPassword" class="form-input" type="password" name="confirm_password"
                        autocomplete="new-password" minlength="8" required>
                    <button type="button" class="password-toggle"
                        onclick="togglePasswordField('teacherConfirmPassword', this)" aria-label="Show password">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
                <div class="input-error" data-error-for="teacherConfirmPassword"></div>

                <div class="modal-actions">
                    <button type="button" class="form-btn btn-cancel"
                        onclick="closeChangePasswordModal()">Cancel</button>
                    <button type="submit" class="form-btn btn-save"><i class="fa fa-save"></i> Save Password</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editProfileModal" class="modal-overlay" style="display:none;">
        <div class="modal-box account-modal">
            <div class="modal-header">
                <h3>Edit Profile</h3>
                <button type="button" class="modal-close" onclick="closeEditProfileModal()">&times;</button>
            </div>

            <form method="POST" action="../Authentication/update_profile.php" class="profile-edit-form">
                <?= csrf_field() ?>

                <label for="profileName">Name</label>
                <input id="profileName" class="form-input" type="text" name="name" value="<?= e($teacher_name) ?>" required>
                <div class="input-error" data-error-for="profileName"></div>

                <label for="profileEmail">Email</label>
                <input id="profileEmail" class="form-input" type="email" name="email" value="<?= e($teacherEmail) ?>" required>
                <div class="input-error" data-error-for="profileEmail"></div>

                <div class="modal-actions">
                    <button type="button" class="form-btn btn-cancel" onclick="closeEditProfileModal()">Cancel</button>
                    <button type="submit" class="form-btn btn-save"><i class="fa fa-save"></i> Save Profile</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        window.TeacherDashboardData = {
            guardianValidationList: <?= json_encode($guardianValidationList ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
            pupilNameValidationList: <?= json_encode($pupilNameValidationList ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>,
            selectedQuarter: <?= js($selectedQuarter) ?>
        };
        window.KinderLinkFlashMessages = [
            <?php if ($login_success): ?>{ icon: 'success', title: 'Teacher Login Successful!', text: <?= js('Welcome back, Teacher ' . $user) ?>, confirmButtonText: 'OK', confirmButtonColor: '#4361ee' }, <?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>{ icon: 'success', title: 'Success!', text: <?= js($_SESSION['success']) ?>, confirmButtonColor: '#4361ee' }, <?php unset($_SESSION['success']); ?><?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>{ icon: 'error', title: 'Unable to Save', text: <?= js($_SESSION['error']) ?>, confirmButtonColor: '#4361ee' }, <?php unset($_SESSION['error']); ?><?php endif; ?>
        ];
    </script>
    <script>
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

        function openEditProfileModal() {
            closeAccountMenu();
            const modal = document.getElementById('editProfileModal');
            if (modal) modal.style.display = 'flex';
        }

        function closeEditProfileModal() {
            const modal = document.getElementById('editProfileModal');
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

        function validateEmailAddress(value) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value || '').trim());
        }

        function setProfileError(input, message) {
            if (!input) return;
            input.classList.toggle('input-invalid', Boolean(message));
            const error = input.closest('form')?.querySelector(`[data-error-for="${input.id}"]`);
            if (error) error.textContent = message;
        }

        function validateProfileForm(form, showEmptyErrors = false) {
            const nameInput = form.querySelector('input[name="name"]');
            const emailInput = form.querySelector('input[name="email"]');
            let isValid = true;
            const name = String(nameInput?.value || '').trim();
            const email = String(emailInput?.value || '').trim();

            if (!name) {
                if (showEmptyErrors) setProfileError(nameInput, 'Name is required.');
                isValid = false;
            } else {
                setProfileError(nameInput, '');
            }

            if (!email) {
                if (showEmptyErrors) setProfileError(emailInput, 'Email is required.');
                isValid = false;
            } else if (!validateEmailAddress(email)) {
                setProfileError(emailInput, 'Enter a valid email address.');
                isValid = false;
            } else {
                setProfileError(emailInput, '');
            }

            return isValid;
        }

        function toggleSidebar(force) {
            const shouldOpen = typeof force === 'boolean' ? force : !document.body.classList.contains('sidebar-open');
            document.body.classList.toggle('sidebar-open', shouldOpen);
        }

        const guardianValidationList = window.TeacherDashboardData?.guardianValidationList || [];
        const pupilNameValidationList = window.TeacherDashboardData?.pupilNameValidationList || [];

        // Track which fields have been touched by the user
        const touchedFields = {
            add_first_name: false,
            add_last_name: false,
            add_age: false,
            add_birthdate: false,
            add_guardian_name: false,
            edit_first_name: false,
            edit_last_name: false,
            edit_age: false,
            edit_birthdate: false,
            edit_guardian_name: false
        };

        function resetTouchedFields() {
            Object.keys(touchedFields).forEach((key) => {
                touchedFields[key] = false;
            });
        }

        function markFieldAsTouched(fieldId) {
            if (touchedFields.hasOwnProperty(fieldId)) {
                touchedFields[fieldId] = true;
            }
        }

        function toggleForm() {
            const form = document.getElementById('pupilForm');
            if (!form) return;
            const isHidden = form.style.display === 'none' || form.style.display === '';
            document.getElementById('editPupilForm').style.display = 'none';
            form.style.display = isHidden ? 'block' : 'none';
            if (isHidden) {
                resetTouchedFields();
                clearAddPupilValidation();
            } else {
                form.querySelector('form')?.reset();
                resetTouchedFields();
                clearAddPupilValidation();
            }
        }

        function setFieldError(input, errorElement, message) {
            if (!input || !errorElement) return;
            input.classList.toggle('input-invalid', Boolean(message));
            errorElement.textContent = message;
        }

        function getBirthdateAgeError(age, birthdateValue) {
            if (!birthdateValue) return '';
            const date = new Date(birthdateValue);
            if (Number.isNaN(date.getTime())) return 'Enter a valid birthdate.';
            if (!Number.isInteger(age) || age < 4 || age > 5) return '';
            const today = new Date();
            let calculatedAge = today.getFullYear() - date.getFullYear();
            const birthdayThisYear = new Date(today.getFullYear(), date.getMonth(), date.getDate());
            if (birthdayThisYear > today) calculatedAge--;
            return calculatedAge !== age ? "Birthdate must match the pupil's age." : '';
        }

        function clearAddPupilValidation() {
            setFieldError(document.getElementById('add_first_name'), document.getElementById('addFirstNameError'), '');
            setFieldError(document.getElementById('add_last_name'), document.getElementById('addLastNameError'), '');
            setFieldError(document.getElementById('add_age'), document.getElementById('ageError'), '');
            setFieldError(document.getElementById('add_birthdate'), document.getElementById('addBirthdateError'), '');
            setFieldError(document.getElementById('add_guardian_name'), document.getElementById('guardianError'), '');
            const saveBtn = document.getElementById('addPupilSaveBtn');
            if (saveBtn) saveBtn.disabled = true;
        }

        function clearEditPupilValidation() {
            setFieldError(document.getElementById('edit_first_name'), document.getElementById('editFirstNameError'), '');
            setFieldError(document.getElementById('edit_last_name'), document.getElementById('editLastNameError'), '');
            setFieldError(document.getElementById('edit_age'), document.getElementById('editAgeError'), '');
            setFieldError(document.getElementById('edit_birthdate'), document.getElementById('editBirthdateError'), '');
            setFieldError(document.getElementById('edit_guardian_name'), document.getElementById('editGuardianError'), '');
            const saveBtn = document.getElementById('editPupilSaveBtn');
            if (saveBtn) saveBtn.disabled = false;
        }

        function validateAddPupilForm(showEmptyErrors = false) {
            const firstNameInput = document.getElementById('add_first_name');
            const lastNameInput = document.getElementById('add_last_name');
            const ageInput = document.getElementById('add_age');
            const birthdateInput = document.getElementById('add_birthdate');
            const guardianInput = document.getElementById('add_guardian_name');
            const saveBtn = document.getElementById('addPupilSaveBtn');
            const firstName = (firstNameInput?.value || '').trim().toLowerCase();
            const lastName = (lastNameInput?.value || '').trim().toLowerCase();
            const age = Number(ageInput?.value);
            const birthdateValue = birthdateInput?.value || '';
            const guardianName = (guardianInput?.value || '').trim().toLowerCase();
            const existingPupil = firstName && lastName ? pupilNameValidationList.find((item) =>
                String(item.first_name || '').trim().toLowerCase() === firstName &&
                String(item.last_name || '').trim().toLowerCase() === lastName
            ) : null;
            const guardian = guardianName ? guardianValidationList.find((item) =>
                String(item.guardian_name || '').trim().toLowerCase() === guardianName
            ) : null;
            const ageValid = Number.isInteger(age) && age >= 4 && age <= 5;
            const birthdateError = getBirthdateAgeError(age, birthdateValue);
            const birthdateValid = Boolean(birthdateValue) && !birthdateError;
            const pupilNameValid = Boolean(firstName && lastName && !existingPupil);
            const guardianValid = Boolean(guardian && Number(guardian.linked_count) === 0);

            const showNameErrors = showEmptyErrors || touchedFields.add_first_name || touchedFields.add_last_name;
            if (showNameErrors) {
                if (!firstName) {
                    setFieldError(firstNameInput, document.getElementById('addFirstNameError'), 'First name is required.');
                } else {
                    setFieldError(firstNameInput, document.getElementById('addFirstNameError'), '');
                }

                if (!lastName) {
                    setFieldError(lastNameInput, document.getElementById('addLastNameError'), 'Last name is required.');
                } else {
                    setFieldError(lastNameInput, document.getElementById('addLastNameError'), '');
                }

                if (firstName && lastName) {
                    if (existingPupil) {
                        setFieldError(lastNameInput, document.getElementById('addLastNameError'), 'Pupil name already exist.');
                    }
                }
            } else {
                setFieldError(firstNameInput, document.getElementById('addFirstNameError'), '');
                setFieldError(lastNameInput, document.getElementById('addLastNameError'), '');
            }

            const showAgeErrors = showEmptyErrors || touchedFields.add_age;
            if (showAgeErrors) {
                if (!ageInput?.value) {
                    setFieldError(ageInput, document.getElementById('ageError'), 'Age is required.');
                } else {
                    setFieldError(ageInput, document.getElementById('ageError'), ageValid ? '' : 'Age must be between 4 and 5.');
                }
            } else {
                setFieldError(ageInput, document.getElementById('ageError'), '');
            }

            const showBirthdateErrors = showEmptyErrors || touchedFields.add_birthdate;
            if (showBirthdateErrors) {
                if (!birthdateValue) {
                    setFieldError(birthdateInput, document.getElementById('addBirthdateError'), 'Birthdate is required.');
                } else {
                    setFieldError(birthdateInput, document.getElementById('addBirthdateError'), birthdateError);
                }
            } else {
                setFieldError(birthdateInput, document.getElementById('addBirthdateError'), '');
            }

            const showGuardianErrors = showEmptyErrors || touchedFields.add_guardian_name;
            if (showGuardianErrors) {
                if (guardianName) {
                    if (!guardian) {
                        setFieldError(guardianInput, document.getElementById('guardianError'), 'Guardian not exist.');
                    } else if (Number(guardian.linked_count) > 0) {
                        setFieldError(guardianInput, document.getElementById('guardianError'), 'Guardian already linked.');
                    } else {
                        setFieldError(guardianInput, document.getElementById('guardianError'), '');
                    }
                } else {
                    setFieldError(guardianInput, document.getElementById('guardianError'), 'Guardian name is required.');
                }
            } else {
                setFieldError(guardianInput, document.getElementById('guardianError'), '');
            }

            const allFieldsFilled = firstName && lastName && ageInput?.value && birthdateValue && guardianName;
            if (saveBtn) saveBtn.disabled = !(allFieldsFilled && pupilNameValid && ageValid && birthdateValid && guardianValid);
            return pupilNameValid && ageValid && birthdateValid && guardianValid && allFieldsFilled;
        }

        function validateEditPupilForm(showEmptyErrors = false) {
            const firstNameInput = document.getElementById('edit_first_name');
            const lastNameInput = document.getElementById('edit_last_name');
            const ageInput = document.getElementById('edit_age');
            const birthdateInput = document.getElementById('edit_birthdate');
            const guardianInput = document.getElementById('edit_guardian_name');
            const saveBtn = document.getElementById('editPupilSaveBtn');
            const firstName = (firstNameInput?.value || '').trim().toLowerCase();
            const lastName = (lastNameInput?.value || '').trim().toLowerCase();
            const age = Number(ageInput?.value);
            const birthdateValue = birthdateInput?.value || '';
            const guardianName = (guardianInput?.value || '').trim().toLowerCase();
            const firstNameValid = Boolean(firstName);
            const lastNameValid = Boolean(lastName);
            const ageValid = Number.isInteger(age) && age >= 4 && age <= 5;
            const birthdateError = getBirthdateAgeError(age, birthdateValue);
            const birthdateValid = Boolean(birthdateValue) && !birthdateError;
            const guardianValid = Boolean(guardianName);

            const showNameErrors = showEmptyErrors || touchedFields.edit_first_name || touchedFields.edit_last_name;
            if (showNameErrors) {
                if (!firstName) {
                    setFieldError(firstNameInput, document.getElementById('editFirstNameError'), 'First name is required.');
                } else {
                    setFieldError(firstNameInput, document.getElementById('editFirstNameError'), '');
                }
                if (!lastName) {
                    setFieldError(lastNameInput, document.getElementById('editLastNameError'), 'Last name is required.');
                } else {
                    setFieldError(lastNameInput, document.getElementById('editLastNameError'), '');
                }
            } else {
                setFieldError(firstNameInput, document.getElementById('editFirstNameError'), '');
                setFieldError(lastNameInput, document.getElementById('editLastNameError'), '');
            }

            const showAgeErrors = showEmptyErrors || touchedFields.edit_age;
            if (showAgeErrors) {
                if (!ageInput?.value) {
                    setFieldError(ageInput, document.getElementById('editAgeError'), 'Age is required.');
                } else {
                    setFieldError(ageInput, document.getElementById('editAgeError'), ageValid ? '' : 'Age must be between 4 and 5.');
                }
            } else {
                setFieldError(ageInput, document.getElementById('editAgeError'), '');
            }

            const showBirthdateErrors = showEmptyErrors || touchedFields.edit_birthdate;
            if (showBirthdateErrors) {
                if (!birthdateValue) {
                    setFieldError(birthdateInput, document.getElementById('editBirthdateError'), 'Birthdate is required.');
                } else {
                    setFieldError(birthdateInput, document.getElementById('editBirthdateError'), birthdateError);
                }
            } else {
                setFieldError(birthdateInput, document.getElementById('editBirthdateError'), '');
            }

            const showGuardianErrors = showEmptyErrors || touchedFields.edit_guardian_name;
            if (showGuardianErrors) {
                if (guardianName) {
                    setFieldError(guardianInput, document.getElementById('editGuardianError'), '');
                } else {
                    setFieldError(guardianInput, document.getElementById('editGuardianError'), 'Guardian name is required.');
                }
            } else {
                setFieldError(guardianInput, document.getElementById('editGuardianError'), '');
            }

            const allFieldsFilled = firstName && lastName && ageInput?.value && birthdateValue && guardianName;
            if (saveBtn) saveBtn.disabled = !(allFieldsFilled && firstNameValid && lastNameValid && ageValid && birthdateValid && guardianValid);
            return firstNameValid && lastNameValid && ageValid && birthdateValid && guardianValid && allFieldsFilled;
        }

        function toggleEditForm() {
            const form = document.getElementById('editPupilForm');
            if (!form) return;
            const isHidden = form.style.display === 'none' || form.style.display === '';
            form.style.display = isHidden ? 'block' : 'none';
            if (!isHidden) {
                form.querySelector('form')?.reset();
                resetTouchedFields();
                clearEditPupilValidation();
            }
        }

        function editPupil(pupil) {
            document.getElementById('pupilForm').style.display = 'none';
            document.getElementById('editPupilForm').style.display = 'block';
            resetTouchedFields();
            clearEditPupilValidation();
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
            document.getElementById('editProfileModal')?.addEventListener('click', function (event) {
                if (event.target === this) closeEditProfileModal();
            });
            document.getElementById('announcementModal')?.addEventListener('click', function (event) {
                if (event.target === this) closeAnnouncementModal();
            });
            document.getElementById('milestoneModal')?.addEventListener('click', function (event) {
                if (event.target === this) closeMilestoneModal();
            });

            const addPupilForm = document.getElementById('addPupilForm');
            ['add_first_name', 'add_last_name', 'add_age', 'add_birthdate', 'add_guardian_name'].forEach((id) => {
                const field = document.getElementById(id);
                if (field) {
                    field.addEventListener('blur', () => {
                        markFieldAsTouched(id);
                        validateAddPupilForm();
                    });
                    field.addEventListener('input', () => {
                        markFieldAsTouched(id);
                        validateAddPupilForm();
                    });
                }
            });
            addPupilForm?.addEventListener('submit', (event) => {
                if (!validateAddPupilForm(true)) event.preventDefault();
            });

            const editPupilFormElement = document.getElementById('editPupilFormElement');
            ['edit_first_name', 'edit_last_name', 'edit_age', 'edit_birthdate', 'edit_guardian_name'].forEach((id) => {
                const field = document.getElementById(id);
                if (field) {
                    field.addEventListener('blur', () => {
                        markFieldAsTouched(id);
                        validateEditPupilForm();
                    });
                    field.addEventListener('input', () => {
                        markFieldAsTouched(id);
                        validateEditPupilForm();
                    });
                }
            });
            editPupilFormElement?.addEventListener('submit', (event) => {
                if (!validateEditPupilForm(true)) event.preventDefault();
            });

            document.querySelectorAll('.checklist-item').forEach((item) => {
                item.addEventListener('click', function (event) {
                    if (this.classList.contains('disabled')) {
                        event.preventDefault();
                        Swal.fire({ icon: 'info', title: 'Milestones Locked', text: this.dataset.lockMessage || 'Milestone progress cannot be updated right now.', confirmButtonColor: '#4361ee' });
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
