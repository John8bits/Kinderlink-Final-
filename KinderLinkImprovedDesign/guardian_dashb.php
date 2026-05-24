<?php
require_once '../autoload.php';

if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('js')) {
    function js($value) {
        return json_encode((string) $value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    }
}

$db = new Database();
$conn = $db->conn;

$user = $_SESSION['username'] ?? '';

$login_success = $_SESSION['login_success'] ?? false;
unset($_SESSION['login_success']);
$guardianEmail = $_SESSION['email'] ?? '';
$allowedPages = ['dashboard', 'child_profile', 'attendance', 'behavior', 'reports', 'announcements'];
$page = $_GET['page'] ?? 'dashboard';
if (!in_array($page, $allowedPages, true)) {
    $page = 'dashboard';
}

$lastSeen = $_SESSION['guardian_announcements_last_seen'] ?? '1970-01-01 00:00:00';
$notifications = 0;

$linked_children = [];
$selectedChild = null;
$selectedChildId = null;
$dashboardStats = [
    'attendance_status' => 'No Record',
    'milestones_completed' => 0,
    'milestones_total' => 0,
    'new_announcements' => 0,
    'messages' => 0,
    'recent_activity' => 'Attendance: No record for today'
];
$primaryChild = null;
$attendanceSummary = ['Present' => 0, 'Absent' => 0, 'Late' => 0];
$attendanceHistory = [];
$pupilMilestones = [];
$completedMilestones = 0;
$totalMilestones = 0;
$currentMonth = (int) date('m');
$currentYear = (int) date('Y');
$currentQuarter = $currentMonth <= 3 ? 'Q1' : ($currentMonth <= 6 ? 'Q2' : ($currentMonth <= 9 ? 'Q3' : 'Q4'));
$selectedQuarter = $_GET['quarter'] ?? $currentQuarter;
$quarterDates = [
    'Q1' => ['start' => "$currentYear-01-01", 'end' => "$currentYear-03-31", 'label' => 'Quarter 1'],
    'Q2' => ['start' => "$currentYear-04-01", 'end' => "$currentYear-06-30", 'label' => 'Quarter 2'],
    'Q3' => ['start' => "$currentYear-07-01", 'end' => "$currentYear-09-30", 'label' => 'Quarter 3'],
    'Q4' => ['start' => "$currentYear-10-01", 'end' => "$currentYear-12-31", 'label' => 'Quarter 4'],
];
if (!isset($quarterDates[$selectedQuarter])) {
    $selectedQuarter = $currentQuarter;
}
$reportAttendanceSummary = ['Present' => 0, 'Absent' => 0, 'Late' => 0];

$announcements = [];
if ($guardianEmail || $user) {
    $childrenStmt = $conn->prepare(
        "SELECT
            p.pupil_id,
            p.first_name,
            p.last_name,
            CONCAT(p.first_name, ' ', p.last_name) AS name,
            p.age,
            p.gender,
            p.birthdate,
            p.home_address,
            p.health_notes,
            COALESCE(c.class_name, 'No Class') AS class
        FROM pupil p
        JOIN guardian_pupil gp ON gp.pupil_pupil_id = p.pupil_id
        JOIN guardian g ON gp.guardian_guardian_id = g.guardian_id
        LEFT JOIN class c ON p.class_class_id = c.class_id
        WHERE ((? <> '' AND g.email = ?)
        OR g.guardian_name = ?
        )
        ORDER BY p.first_name, p.last_name"
    );
    $childrenStmt->execute([$guardianEmail, $guardianEmail, $user]);
    $linked_children = $childrenStmt->fetchAll(PDO::FETCH_ASSOC);
    $primaryChild = $linked_children[0] ?? null;
    $requestedChildId = filter_input(INPUT_GET, 'child_id', FILTER_VALIDATE_INT);
    foreach ($linked_children as $child) {
        if ($requestedChildId !== false && $requestedChildId !== null && (int) $child['pupil_id'] === (int) $requestedChildId) {
            $selectedChild = $child;
            break;
        }
    }
    $selectedChild = $selectedChild ?: $primaryChild;
    $selectedChildId = $selectedChild['pupil_id'] ?? null;

    $stmt = $conn->prepare(
        "SELECT DISTINCT a.announcement_id, a.title, a.content, a.date_posted, t.teacher_name
        FROM announcement a
        JOIN class c ON a.class_class_id = c.class_id
        JOIN teacher t ON c.teacher_teacher_id = t.teacher_id
        JOIN pupil p ON p.class_class_id = c.class_id
        JOIN guardian_pupil gp ON gp.pupil_pupil_id = p.pupil_id
        JOIN guardian g ON gp.guardian_guardian_id = g.guardian_id
        WHERE ((? <> '' AND g.email = ?)
        OR g.guardian_name = ?
        )
        ORDER BY a.date_posted DESC"
    );
    $stmt->execute([$guardianEmail, $guardianEmail, $user]);
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($page !== 'announcements') {
        $countStmt = $conn->prepare(
            "SELECT COUNT(DISTINCT a.announcement_id) AS unread_count
            FROM announcement a
            JOIN class c ON a.class_class_id = c.class_id
            JOIN pupil p ON p.class_class_id = c.class_id
            JOIN guardian_pupil gp ON gp.pupil_pupil_id = p.pupil_id
            JOIN guardian g ON gp.guardian_guardian_id = g.guardian_id
            WHERE ((? <> '' AND g.email = ?)
            OR g.guardian_name = ?)
            AND a.date_posted > ?"
        );
        $countStmt->execute([$guardianEmail, $guardianEmail, $user, $lastSeen]);
        $notifications = (int) $countStmt->fetchColumn();
    } else {
        $_SESSION['guardian_announcements_last_seen'] = date('Y-m-d H:i:s');
        $notifications = 0;
    }

    $dashboardStats['new_announcements'] = $notifications;

    if (!empty($linked_children)) {
        $childIds = array_column($linked_children, 'pupil_id');
        $placeholders = implode(',', array_fill(0, count($childIds), '?'));

        $attendanceStmt = $conn->prepare(
            "SELECT pupil_pupil_id, status
            FROM attendance
            WHERE pupil_pupil_id IN ($placeholders)
            AND DATE(date) = CURDATE()
            ORDER BY date DESC"
        );
        $attendanceStmt->execute($childIds);
        $todayAttendance = $attendanceStmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($todayAttendance)) {
            if (count($linked_children) === 1) {
                $dashboardStats['attendance_status'] = $todayAttendance[0]['status'];
                $dashboardStats['recent_activity'] = 'Attendance: ' . $todayAttendance[0]['status'] . ' today';
            } else {
                $statusCounts = [];
                foreach ($todayAttendance as $record) {
                    $status = $record['status'] ?: 'Recorded';
                    $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
                }
                arsort($statusCounts);
                $mainStatus = array_key_first($statusCounts);
                $dashboardStats['attendance_status'] = $statusCounts[$mainStatus] . ' ' . $mainStatus;
                $dashboardStats['recent_activity'] = 'Attendance: ' . count($todayAttendance) . ' record(s) for today';
            }
        }

        $milestoneStmt = $conn->prepare(
            "SELECT
                COUNT(DISTINCT m.milestone_id) AS total_milestones,
                COUNT(DISTINCT CASE WHEN pm.status = 'Completed' THEN CONCAT(pm.pupil_pupil_id, '-', pm.milestone_milestone_id) END) AS completed_milestones
            FROM milestone m
            LEFT JOIN pupil_milestone pm
                ON pm.milestone_milestone_id = m.milestone_id
                AND pm.pupil_pupil_id IN ($placeholders)"
        );
        $milestoneStmt->execute($childIds);
        $milestoneCounts = $milestoneStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $dashboardStats['milestones_total'] = (int) ($milestoneCounts['total_milestones'] ?? 0);
        $dashboardStats['milestones_completed'] = (int) ($milestoneCounts['completed_milestones'] ?? 0);
    }

    if ($selectedChildId) {
        $summaryStmt = $conn->prepare(
            "SELECT status, COUNT(*) AS count
            FROM attendance
            WHERE pupil_pupil_id = ?
            GROUP BY status"
        );
        $summaryStmt->execute([$selectedChildId]);
        foreach ($summaryStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (isset($attendanceSummary[$row['status']])) {
                $attendanceSummary[$row['status']] = (int) $row['count'];
            }
        }

        $historyStmt = $conn->prepare(
            "SELECT date, status
            FROM attendance
            WHERE pupil_pupil_id = ?
            ORDER BY date DESC"
        );
        $historyStmt->execute([$selectedChildId]);
        $attendanceHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

        $milestonesStmt = $conn->prepare(
            "SELECT
                m.milestone_id,
                m.title,
                m.description,
                COALESCE(pm.status, 'Pending') AS status,
                pm.date_completed
            FROM milestone m
            LEFT JOIN (
                SELECT pm_inner.*
                FROM pupil_milestone pm_inner
                JOIN (
                    SELECT MILESTONE_milestone_id, MAX(pupil_milestone_id) AS max_id
                    FROM pupil_milestone
                    WHERE PUPIL_pupil_id = ?
                    GROUP BY MILESTONE_milestone_id
                ) latest ON pm_inner.pupil_milestone_id = latest.max_id
            ) pm ON pm.MILESTONE_milestone_id = m.milestone_id
            ORDER BY m.milestone_id ASC"
        );
        $milestonesStmt->execute([$selectedChildId]);
        $pupilMilestones = $milestonesStmt->fetchAll(PDO::FETCH_ASSOC);
        $totalMilestones = count($pupilMilestones);
        foreach ($pupilMilestones as $milestone) {
            if ($milestone['status'] === 'Completed') {
                $completedMilestones++;
            }
        }

        $dateRange = $quarterDates[$selectedQuarter];
        $reportSummaryStmt = $conn->prepare(
            "SELECT status, COUNT(*) AS count
            FROM attendance
            WHERE pupil_pupil_id = ?
            AND DATE(date) BETWEEN ? AND ?
            GROUP BY status"
        );
        $reportSummaryStmt->execute([$selectedChildId, $dateRange['start'], $dateRange['end']]);
        foreach ($reportSummaryStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (isset($reportAttendanceSummary[$row['status']])) {
                $reportAttendanceSummary[$row['status']] = (int) $row['count'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KinderLink – Guardian</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/guardian_dashb_style.css">
</head>

<body>

    <!-- Header -->
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
                <button type="button" class="user-badge" onclick="toggleAccountMenu(event)" aria-haspopup="true" aria-expanded="false">
                    <span>
                        <span class="name"><?= e($user) ?></span>
                        <span class="role">Guardian</span>
                    </span>
                    <i class="fa fa-chevron-down"></i>
                </button>
                <div class="account-dropdown" id="accountDropdown">
                    <button type="button" onclick="openChangePasswordModal()">
                        <i class="fa fa-key"></i> Change Password
                    </button>
                    <!-- <a href="#" onclick="confirmLogout(event, '../Authentication/logout.php')">
                        <i class="fa fa-right-from-bracket"></i> Logout
                    </a> -->
                </div>
            </div>
            <a href="?page=announcements" class="notif-btn" aria-label="Announcements">
                <i class="fa fa-bell"></i>
                <?php if ($notifications > 0): ?>
                    <span class="badge"><?= (int) $notifications ?></span>
                <?php endif; ?>
            </a>
            <a href="#" class="logout-btn" onclick="confirmLogout(event, '../Authentication/logout.php')">
                <i class="fa fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </header>

    <div class="wrapper">

        <!-- Sidebar -->
        <aside>
            <nav class="nav">
                <div class="nav-section">
                    <div class="nav-label">Overview</div>
                    <a href="?page=dashboard" class="<?= $page == 'dashboard' ? 'active' : '' ?>">
                        <i class="fa fa-grip"></i> <span class="nav-text">Dashboard</span>
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-label">Child Information</div>
                    <a href="?page=child_profile" class="<?= $page == 'child_profile' ? 'active' : '' ?>">
                        <i class="fa fa-child"></i> <span class="nav-text">Child Profile</span>
                    </a>
                    <a href="?page=attendance" class="<?= $page == 'attendance' ? 'active' : '' ?>">
                        <i class="fa fa-calendar-check"></i> <span class="nav-text">Attendance</span>
                    </a>
                    <a href="?page=behavior" class="<?= $page == 'behavior' ? 'active' : '' ?>">
                        <i class="fa fa-star"></i> <span class="nav-text">Milestones</span>
                    </a>
                    <a href="?page=reports" class="<?= $page == 'reports' ? 'active' : '' ?>">
                        <i class="fa fa-file-lines"></i> <span class="nav-text">Reports</span>
                    </a>
                    <a href="?page=announcements" class="<?= $page == 'announcements' ? 'active' : '' ?>">
                        <i class="fa fa-bullhorn"></i> <span class="nav-text">Announcements</span>
                        <?php if ($notifications > 0): ?>
                            <span class="nav-badge"><?= (int) $notifications ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </nav>
        </aside>
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar(false)"></div>

        <!-- Main -->
        <main>

            <?php if ($page == 'dashboard'): ?>

                <div class="page-title">Guardian Dashboard</div>
                <div class="page-subtitle">Track your child's progress and stay connected.</div>

                <div class="guardian-stats-grid">
                    <div class="stat-card">
                        <div class="stat-top">
                            <div class="stat-label">Attendance Status</div>
                            <div class="stat-icon blue"><i class="fa fa-clipboard-check"></i></div>
                        </div>
                        <div>
                            <div class="stat-value"><?= htmlspecialchars($dashboardStats['attendance_status']) ?></div>
                            <div class="stat-note">Today's status</div>
                        </div>
                    </div>

                    <div class="stat-card purple-card">
                        <div class="stat-top">
                            <div class="stat-label">Milestones Completed</div>
                            <div class="stat-icon purple"><i class="fa fa-award"></i></div>
                        </div>
                        <div>
                            <div class="stat-value"><?= (int) $dashboardStats['milestones_completed'] ?>/<?= (int) $dashboardStats['milestones_total'] ?></div>
                            <div class="stat-note">Achievements</div>
                        </div>
                    </div>

                    <div class="stat-card indigo-card">
                        <div class="stat-top">
                            <div class="stat-label">New Announcements</div>
                            <div class="stat-icon indigo"><i class="fa fa-bullhorn"></i></div>
                        </div>
                        <div>
                            <div class="stat-value"><?= (int) $dashboardStats['new_announcements'] ?></div>
                            <div class="stat-note">From teacher</div>
                        </div>
                    </div>

                    <div class="stat-card teal-card">
                        <div class="stat-top">
                            <div class="stat-label">Linked Children</div>
                            <div class="stat-icon teal"><i class="fa fa-children"></i></div>
                        </div>
                        <div>
                            <div class="stat-value"><?= count($linked_children) ?></div>
                            <div class="stat-note">Connected profile(s)</div>
                        </div>
                    </div>

                    <!-- <div class="stat-card pink-card">
                        <div class="stat-top">
                            <div class="stat-label">Messages</div>
                            <div class="stat-icon pink"><i class="fa-regular fa-comment"></i></div>
                        </div>
                        <div>
                            <div class="stat-value"><?= (int) $dashboardStats['messages'] ?></div>
                            <div class="stat-note">Unread</div>
                        </div>
                    </div> -->
                </div>

                <section class="quick-overview">
                    <h2>Quick Overview</h2>

                    <?php if (empty($linked_children)): ?>
                        <div class="empty-card">
                            No children linked to your account
                        </div>
                    <?php else: ?>
                        <?php
                            $nameParts = preg_split('/\s+/', trim($primaryChild['name'] ?? ''));
                            $initials = '';
                            foreach ($nameParts as $part) {
                                if ($part !== '') {
                                    $initials .= strtoupper(substr($part, 0, 1));
                                }
                                if (strlen($initials) >= 2) {
                                    break;
                                }
                            }
                            $initials = $initials ?: 'CH';
                            $metaParts = [];
                            if ($primaryChild['age'] !== null && $primaryChild['age'] !== '') {
                                $metaParts[] = $primaryChild['age'] . ' years old';
                            }
                            if (!empty($primaryChild['gender'])) {
                                $metaParts[] = $primaryChild['gender'];
                            }
                            $childMeta = implode(' - ', $metaParts);
                        ?>
                        <div class="overview-row overview-child">
                            <div class="overview-avatar"><?= htmlspecialchars($initials) ?></div>
                            <div>
                                <div class="overview-name"><?= htmlspecialchars($primaryChild['name']) ?></div>
                                <div class="overview-meta"><?= htmlspecialchars($childMeta) ?></div>
                            </div>
                        </div>

                        <div class="overview-row overview-activity">
                            <div class="activity-icon"><i class="fa fa-clipboard-check"></i></div>
                            <div>
                                <div class="activity-title">Recent Activity</div>
                                <div class="activity-text"><?= htmlspecialchars($dashboardStats['recent_activity']) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </section>

            <?php elseif ($page == 'child_profile'): ?>
                <div class="page-title">Child Profile</div>
                <div class="page-subtitle">View your child's profile details.</div>

                <?php if (count($linked_children) > 1): ?>
                    <form class="child-switcher" method="GET">
                        <input type="hidden" name="page" value="child_profile">
                        <label for="profileChildSelect">Select Child</label>
                        <select id="profileChildSelect" name="child_id" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($linked_children as $child): ?>
                                <option value="<?= (int) $child['pupil_id'] ?>" <?= (int) $child['pupil_id'] === (int) $selectedChildId ? 'selected' : '' ?>>
                                    <?= e($child['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php endif; ?>

                <?php if (!$selectedChild): ?>
                    <div class="empty-card">No children linked to your account</div>
                <?php else: ?>
                    <?php
                        $profileMeta = [];
                        if ($selectedChild['age'] !== null && $selectedChild['age'] !== '') {
                            $profileMeta[] = $selectedChild['age'] . ' years old';
                        }
                        if (!empty($selectedChild['gender'])) {
                            $profileMeta[] = $selectedChild['gender'];
                        }
                    ?>
                    <div class="content-card">
                        <div class="overview-row overview-child">
                            <div class="overview-avatar">
                                <?= htmlspecialchars(strtoupper(substr($selectedChild['first_name'], 0, 1) . substr($selectedChild['last_name'], 0, 1))) ?>
                            </div>
                            <div>
                                <div class="overview-name"><?= htmlspecialchars($selectedChild['name']) ?></div>
                                <div class="overview-meta"><?= htmlspecialchars(implode(' - ', $profileMeta)) ?></div>
                            </div>
                        </div>
                        <div class="list-row">
                            <div>
                                <div class="list-title">Class</div>
                                <div class="list-description"><?= htmlspecialchars($selectedChild['class']) ?></div>
                            </div>
                        </div>
                        <div class="list-row">
                            <div>
                                <div class="list-title">Birthdate</div>
                                <div class="list-description">
                                    <?= !empty($selectedChild['birthdate']) ? htmlspecialchars(date('Y-m-d', strtotime($selectedChild['birthdate']))) : 'Not recorded' ?>
                                </div>
                            </div>
                        </div>
                        <div class="list-row">
                            <div>
                                <div class="list-title">Home Address</div>
                                <div class="list-description"><?= htmlspecialchars($selectedChild['home_address'] ?: 'Not recorded') ?></div>
                            </div>
                        </div>
                        <div class="list-row">
                            <div>
                                <div class="list-title">Health Notes</div>
                                <div class="list-description"><?= htmlspecialchars($selectedChild['health_notes'] ?: 'No health notes recorded') ?></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            <?php elseif ($page == 'attendance'): ?>
                <div class="page-title">Attendance View</div>
                <div class="page-subtitle">View your child's attendance records.</div>

                <?php if (count($linked_children) > 1): ?>
                    <form class="child-switcher" method="GET">
                        <input type="hidden" name="page" value="attendance">
                        <label for="attendanceChildSelect">Select Child</label>
                        <select id="attendanceChildSelect" name="child_id" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($linked_children as $child): ?>
                                <option value="<?= (int) $child['pupil_id'] ?>" <?= (int) $child['pupil_id'] === (int) $selectedChildId ? 'selected' : '' ?>>
                                    <?= e($child['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php endif; ?>

                <?php if (!$selectedChild): ?>
                    <div class="empty-card">No children linked to your account</div>
                <?php else: ?>
                    <div class="content-card">
                        <div class="section-title"><?= htmlspecialchars($selectedChild['name']) ?> - Attendance History</div>

                        <div class="summary-grid">
                            <div class="summary-box present">
                                <div class="summary-label">Present Days</div>
                                <div class="summary-value"><?= (int) $attendanceSummary['Present'] ?></div>
                            </div>
                            <div class="summary-box absent">
                                <div class="summary-label">Absent Days</div>
                                <div class="summary-value"><?= (int) $attendanceSummary['Absent'] ?></div>
                            </div>
                            <div class="summary-box late">
                                <div class="summary-label">Late Days</div>
                                <div class="summary-value"><?= (int) $attendanceSummary['Late'] ?></div>
                            </div>
                        </div>

                        <?php if (empty($attendanceHistory)): ?>
                            <div class="muted-empty">No attendance records found.</div>
                        <?php else: ?>
                            <?php foreach ($attendanceHistory as $record): ?>
                                <div class="list-row">
                                    <strong><?= htmlspecialchars(date('Y-m-d', strtotime($record['date']))) ?></strong>
                                    <span class="status-badge"><?= htmlspecialchars(strtolower($record['status'])) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($page == 'behavior'): ?>
                <div class="page-title">Milestone Progress</div>
                <div class="page-subtitle">Track completed and pending milestones.</div>

                <?php if (count($linked_children) > 1): ?>
                    <form class="child-switcher" method="GET">
                        <input type="hidden" name="page" value="behavior">
                        <label for="milestoneChildSelect">Select Child</label>
                        <select id="milestoneChildSelect" name="child_id" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($linked_children as $child): ?>
                                <option value="<?= (int) $child['pupil_id'] ?>" <?= (int) $child['pupil_id'] === (int) $selectedChildId ? 'selected' : '' ?>>
                                    <?= e($child['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php endif; ?>

                <?php if (!$selectedChild): ?>
                    <div class="empty-card">No children linked to your account</div>
                <?php else: ?>
                    <div class="content-card">
                        <div class="section-title">Milestone Progress</div>

                        <?php if (empty($pupilMilestones)): ?>
                            <div class="muted-empty">No milestone templates found.</div>
                        <?php else: ?>
                            <?php foreach ($pupilMilestones as $milestone): ?>
                                <?php $isCompleted = $milestone['status'] === 'Completed'; ?>
                                <div class="list-row">
                                    <div>
                                        <div class="list-title"><?= htmlspecialchars($milestone['title']) ?></div>
                                        <div class="list-description"><?= htmlspecialchars($milestone['description'] ?: 'No description') ?></div>
                                        <?php if ($isCompleted && !empty($milestone['date_completed'])): ?>
                                            <div class="completed-date">Completed on: <?= htmlspecialchars(date('Y-m-d', strtotime($milestone['date_completed']))) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($isCompleted): ?>
                                        <span class="status-badge">Completed</span>
                                    <?php else: ?>
                                        <span class="status-text">Pending</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($page == 'reports'): ?>
                <div class="page-title">Progress Report</div>
                <div class="page-subtitle">Review attendance and milestone completion by grading period.</div>

                <?php if (!$selectedChild): ?>
                    <div class="empty-card">No children linked to your account</div>
                <?php else: ?>
                    <form class="content-card report-filter-card" method="GET">
                        <input type="hidden" name="page" value="reports">
                        <?php if (count($linked_children) > 1): ?>
                            <label class="form-label" for="reportChildSelect">Select Child</label>
                            <select id="reportChildSelect" name="child_id" class="form-select" onchange="this.form.submit()">
                                <?php foreach ($linked_children as $child): ?>
                                    <option value="<?= (int) $child['pupil_id'] ?>" <?= (int) $child['pupil_id'] === (int) $selectedChildId ? 'selected' : '' ?>>
                                        <?= e($child['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="hidden" name="child_id" value="<?= (int) $selectedChildId ?>">
                        <?php endif; ?>

                        <label class="form-label form-label-spaced" for="reportQuarterSelect">Grading Period</label>
                        <select id="reportQuarterSelect" name="quarter" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($quarterDates as $quarterKey => $quarter): ?>
                                <option value="<?= e($quarterKey) ?>" <?= $selectedQuarter === $quarterKey ? 'selected' : '' ?>>
                                    <?= e($quarter['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <div class="content-card report-card">
                        <div class="section-title tight">Progress Report - <?= htmlspecialchars($selectedChild['name']) ?></div>
                        <div class="report-subtitle">Grading Period: <?= htmlspecialchars($selectedQuarter) ?></div>

                        <div class="report-section-title">Attendance Summary</div>
                        <div class="summary-grid">
                            <div class="summary-box present">
                                <div class="summary-label">Present</div>
                                <div class="summary-value"><?= (int) $reportAttendanceSummary['Present'] ?></div>
                            </div>
                            <div class="summary-box absent">
                                <div class="summary-label">Absent</div>
                                <div class="summary-value"><?= (int) $reportAttendanceSummary['Absent'] ?></div>
                            </div>
                            <div class="summary-box late">
                                <div class="summary-label">Late</div>
                                <div class="summary-value"><?= (int) $reportAttendanceSummary['Late'] ?></div>
                            </div>
                        </div>

                        <div class="report-section-title">Milestone Completion</div>
                        <div class="milestone-count"><?= (int) $completedMilestones ?> of <?= (int) $totalMilestones ?> milestones completed</div>

                        <?php if (empty($pupilMilestones)): ?>
                            <div class="muted-empty">No milestone templates found.</div>
                        <?php else: ?>
                            <?php foreach ($pupilMilestones as $milestone): ?>
                                <?php $isCompleted = $milestone['status'] === 'Completed'; ?>
                                <div class="list-row compact-row">
                                    <span>
                                        <span class="milestone-check"><?= $isCompleted ? '&#10003;' : '&#9675;' ?></span>
                                        <?= htmlspecialchars($milestone['title']) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($page == 'announcements'): ?>
                <div class="page-title">Announcements</div>
                <div class="page-subtitle">Teacher updates for your linked child(ren).</div>

                <?php if (empty($announcements)): ?>
                    <div class="empty-card">No announcements available yet.</div>
                <?php else: ?>
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="announcement-card">
                            <div class="announcement-card-header">
                                <h3><?= htmlspecialchars($announcement['title']) ?></h3>
                                <span><?= htmlspecialchars(date('Y-m-d', strtotime($announcement['date_posted']))) ?></span>
                            </div>
                            <p><?= htmlspecialchars($announcement['content']) ?></p>
                            <div class="announcement-meta">Posted by <?= htmlspecialchars($announcement['teacher_name']) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            <?php else: ?>
                <div class="page-title"><?= e(ucfirst(str_replace('_', ' ', $page))) ?></div>
                <!-- <div class="page-subtitle">This section is under construction.</div> -->
            <?php endif; ?>

        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <div id="changePasswordModal" class="modal-overlay" style="display:none;">
        <div class="modal-box account-modal">
            <div class="modal-header">
                <h3>Change Password</h3>
                <button type="button" class="modal-close" onclick="closeChangePasswordModal()">&times;</button>
            </div>

            <form method="POST" action="../Authentication/change_password.php" class="password-match-form change-password-form">
                <?= csrf_field() ?>

                <label for="guardianCurrentPassword">Current Password</label>
                <div class="password-field">
                    <input id="guardianCurrentPassword" class="form-input" type="password" name="current_password" autocomplete="current-password" required>
                    <button type="button" class="password-toggle" onclick="togglePasswordField('guardianCurrentPassword', this)" aria-label="Show password">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
                <div class="input-error" data-error-for="guardianCurrentPassword"></div>

                <label for="guardianNewPassword">New Password</label>
                <div class="password-field">
                    <input id="guardianNewPassword" class="form-input" type="password" name="new_password" autocomplete="new-password" minlength="8" required>
                    <button type="button" class="password-toggle" onclick="togglePasswordField('guardianNewPassword', this)" aria-label="Show password">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
                <div class="input-error" data-error-for="guardianNewPassword"></div>

                <label for="guardianConfirmPassword">Confirm New Password</label>
                <div class="password-field">
                    <input id="guardianConfirmPassword" class="form-input" type="password" name="confirm_password" autocomplete="new-password" minlength="8" required>
                    <button type="button" class="password-toggle" onclick="togglePasswordField('guardianConfirmPassword', this)" aria-label="Show password">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
                <div class="input-error" data-error-for="guardianConfirmPassword"></div>

                <div class="modal-actions">
                    <button type="button" class="form-btn btn-cancel" onclick="closeChangePasswordModal()">Cancel</button>
                    <button type="submit" class="form-btn btn-save"><i class="fa fa-save"></i> Save Password</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        window.KinderLinkFlashMessages = [
            <?php if ($login_success): ?>{ icon: 'success', title: 'Login Successful!', text: <?= js('Welcome back, ' . $user) ?>, confirmButtonText: 'OK' },<?php endif; ?>
            <?php if (isset($_SESSION['success'])): ?>{ icon: 'success', title: 'Success!', text: <?= js($_SESSION['success']) ?>, confirmButtonColor: '#4361ee' },<?php unset($_SESSION['success']); ?><?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>{ icon: 'error', title: 'Unable to Save', text: <?= js($_SESSION['error']) ?>, confirmButtonColor: '#4361ee' },<?php unset($_SESSION['error']); ?><?php endif; ?>
        ];
    </script>
    <script>
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
