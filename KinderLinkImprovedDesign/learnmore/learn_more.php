<?php ob_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learn More - KinderLink</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../css/learnmore_style.css">
</head>

<body>
    <header>
        <a href="../index.php" class="logo">
            <img src="../logo.png" alt="KinderLink logo">
            <span>KinderLink</span>
        </a>

        <div class="header-actions">
            <a class="back-btn" href="../index.php" aria-label="Back to home">
                <i class="fa-solid fa-arrow-left"></i>
                Home
            </a>
            <a class="nav-btn primary" href="../Authentication/login.php">
                <i class="fa-solid fa-right-to-bracket"></i>
                Login
            </a>
        </div>
    </header>

    <main>
        <section class="wrap hero">
            <div class="reveal">
                <div class="eyebrow">
                    <i class="fa-solid fa-graduation-cap"></i>
                    Pupil Progress & Monitoring System
                </div>
                <h1 style="font-variant: small-caps;">One place for kindergarten records, progress, and guardian
                    updates.</h1>
                <!-- <h1>ONE PLACE FOR KINDERGARTEN RECORDS, PROGRESS, AND GUARDIAN UPDATES.</h1> -->
                <p class="hero-copy">
                    KinderLink helps administrators, teachers, and guardians stay connected through organized pupil
                    profiles,
                    attendance tracking, milestone monitoring, classroom announcements, and progress reports.
                </p>
                <div class="hero-actions">
                    <a class="nav-btn primary" href="../Authentication/login.php">
                        <i class="fa-solid fa-play"></i>
                        Get Started
                    </a>
                    <a class="nav-btn" href="#features">
                        <i class="fa-solid fa-layer-group"></i>
                        View Features
                    </a>
                </div>
            </div>

            <div class="hero-panel reveal" aria-label="System summary preview">
                <div class="panel-top">
                    <h2>System Snapshot</h2>
                    <span class="status-dot">Active</span>
                </div>
                <div class="panel-grid">
                    <div class="metric">
                        <i class="fa-solid fa-chalkboard-user blue"></i>
                        <strong>Admin</strong>
                        <span>Accounts & links</span>
                    </div>
                    <div class="metric">
                        <i class="fa-solid fa-user-group purple"></i>
                        <strong>Teacher</strong>
                        <span>Class management</span>
                    </div>
                    <div class="metric">
                        <i class="fa-solid fa-calendar-check green"></i>
                        <strong>Daily</strong>
                        <span>Attendance logs</span>
                    </div>
                    <div class="metric">
                        <i class="fa-solid fa-file-lines orange"></i>
                        <strong>Reports</strong>
                        <span>Quarterly progress</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="section" id="features">
            <div class="wrap">
                <div class="section-head center reveal">
                    <h2>System Features</h2>
                    <p class="section-desc">
                        The page now highlights the actual tools available in KinderLink, from account setup to guardian
                        progress viewing.
                    </p>
                </div>

                <div class="feature-grid">
                    <article class="feature-card reveal">
                        <div class="icon blue"><i class="fa-solid fa-gauge-high"></i></div>
                        <h3>Admin Dashboard</h3>
                        <p>View totals for teachers, guardians, pupils, inactive accounts, and active guardian-pupil
                            links.</p>
                    </article>

                    <article class="feature-card reveal">
                        <div class="icon purple"><i class="fa-solid fa-users-gear"></i></div>
                        <h3>Account Management</h3>
                        <p>Create teacher and guardian accounts, search records, and activate or deactivate user access.
                        </p>
                    </article>

                    <article class="feature-card reveal">
                        <div class="icon indigo"><i class="fa-solid fa-link"></i></div>
                        <h3>Guardian-Pupil Linking</h3>
                        <p>Connect each guardian account to the correct pupil so families only see their linked child
                            records.</p>
                    </article>

                    <article class="feature-card reveal">
                        <div class="icon cyan"><i class="fa-solid fa-address-card"></i></div>
                        <h3>Pupil Profiles</h3>
                        <p>Store pupil names, age, gender, birthdate, address, guardian contact details, and health
                            notes.</p>
                    </article>

                    <article class="feature-card reveal">
                        <div class="icon green"><i class="fa-solid fa-calendar-check"></i></div>
                        <h3>Attendance Tracking</h3>
                        <p>Teachers can record Present, Absent, and Late statuses while guardians can review attendance
                            history.</p>
                    </article>

                    <article class="feature-card reveal">
                        <div class="icon orange"><i class="fa-solid fa-star"></i></div>
                        <h3>Milestone Monitoring</h3>
                        <p>Teachers maintain milestone templates and mark pupil progress as completed or pending.</p>
                    </article>

                    <article class="feature-card reveal">
                        <div class="icon pink"><i class="fa-solid fa-bullhorn"></i></div>
                        <h3>Announcements</h3>
                        <p>Teachers post classroom updates that guardians can read from their own announcement view.</p>
                    </article>

                    <article class="feature-card reveal">
                        <div class="icon slate"><i class="fa-solid fa-file-lines"></i></div>
                        <h3>Progress Reports</h3>
                        <p>Generate quarter-based reports with attendance summaries and milestone completion details.
                        </p>
                    </article>

                    <article class="feature-card reveal">
                        <div class="icon blue"><i class="fa-solid fa-shield-halved"></i></div>
                        <h3>Role-Based Access</h3>
                        <p>Separate dashboards keep admin, teacher, and guardian workflows focused on the right
                            responsibilities.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="wrap">
                <div class="section-head reveal">
                    <h2>Who Uses KinderLink?</h2>
                    <p class="section-desc">
                        Each role has a focused view, making the system easier to use for school staff and families.
                    </p>
                </div>

                <div class="role-grid">
                    <article class="role-card reveal">
                        <div class="icon blue"><i class="fa-solid fa-user-shield"></i></div>
                        <h3>Admin</h3>
                        <span>System supervisor</span>
                        <p>Manages teacher accounts, guardian accounts, pupil links, and overall system records.</p>
                    </article>

                    <article class="role-card reveal">
                        <div class="icon purple"><i class="fa-solid fa-chalkboard-user"></i></div>
                        <h3>Teacher</h3>
                        <span>Classroom manager</span>
                        <p>Handles pupil profiles, daily attendance, milestone records, announcements, and progress
                            reports.</p>
                    </article>

                    <article class="role-card reveal">
                        <div class="icon pink"><i class="fa-solid fa-people-roof"></i></div>
                        <h3>Guardian</h3>
                        <span>Family viewer</span>
                        <p>Views linked child profiles, attendance, milestone progress, announcements, and report
                            summaries.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="wrap">
                <div class="section-head center reveal">
                    <h2>How It Works</h2>
                    <p class="section-desc">
                        A simple workflow connects account setup, classroom recording, and guardian progress viewing.
                    </p>
                </div>

                <div class="workflow">
                    <article class="step reveal">
                        <div class="step-number">1</div>
                        <h3>Admin Sets Up</h3>
                        <p>Teacher and guardian accounts are created.</p>
                    </article>
                    <article class="step reveal">
                        <div class="step-number">2</div>
                        <h3>Pupils Are Linked</h3>
                        <p>Guardians are connected to the correct child.</p>
                    </article>
                    <article class="step reveal">
                        <div class="step-number">3</div>
                        <h3>Teacher Records</h3>
                        <p>Attendance, milestones, and announcements are updated.</p>
                    </article>
                    <article class="step reveal">
                        <div class="step-number">4</div>
                        <h3>Guardian Reviews</h3>
                        <p>Families check child records and class updates.</p>
                    </article>
                    <article class="step reveal">
                        <div class="step-number">5</div>
                        <h3>Reports Are Made</h3>
                        <p>Quarterly progress can be reviewed and printed.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="wrap">
                <div class="section-head reveal">
                    <h2>Project Goals</h2>
                    <p class="section-desc">
                        KinderLink is built to reduce manual work and make early-childhood progress easier to monitor.
                    </p>
                </div>

                <div class="objective-grid">
                    <article class="objective-card reveal">
                        <i class="fa-solid fa-folder-open"></i>
                        <p>Centralize pupil information, attendance, milestones, guardian links, and class
                            announcements.</p>
                    </article>
                    <article class="objective-card reveal">
                        <i class="fa-solid fa-clock"></i>
                        <p>Help teachers spend less time on scattered paper records and repeated manual summaries.</p>
                    </article>
                    <article class="objective-card reveal">
                        <i class="fa-solid fa-chart-line"></i>
                        <p>Make pupil development easier to review through milestone status and attendance history.</p>
                    </article>
                    <article class="objective-card reveal">
                        <i class="fa-solid fa-comments"></i>
                        <p>Improve school-home visibility through guardian dashboards and classroom announcements.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="wrap">
                <div class="section-head center reveal">
                    <h2>Meet the Developers</h2>
                    <p class="section-desc">
                        KinderLink was created by a development team focused on design, implementation, and testing.
                    </p>
                </div>

                <div class="developer-grid">
                    <article class="developer-card reveal">
                        <div class="developer-avatar">KM</div>
                        <h3>Kaye Justine D. Maitem</h3>
                        <div class="main-role">System Designer & Frontend Developer</div>
                        <p>BS Information Technology <br>Southern Leyte State University - Main Campus, Faculty of
                            Computing and Information Sciences.</p>
                    </article>

                    <article class="developer-card reveal">
                        <div class="developer-avatar">JB</div>
                        <h3>Johnrelle P. Bito</h3>
                        <div class="main-role">System Developer</div>
                        <p>BS Information Technology <br>Southern Leyte State University - Main Campus, Faculty of
                            Computing and Information Sciences.</p>
                    </article>

                    <article class="developer-card reveal">
                        <div class="developer-avatar">JP</div>
                        <h3>Jay Daryl B. Paz</h3>
                        <div class="main-role">System Tester</div>
                        <p>BS Information Technology <br>Southern Leyte State University - Main Campus, Faculty of
                            Computing and Information Sciences.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="contact-band">
            <div class="wrap contact-inner reveal">
                <div>
                    <h2>Ready to use KinderLink?</h2>
                    <p>Log in to access the dashboard assigned to your role.</p>
                </div>
                <a class="nav-btn primary" href="../Authentication/login.php">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Login to KinderLink
                </a>
            </div>
        </section>
    </main>

    <footer>
        &copy; 2026 <strong>KinderLink</strong>. Pupil Progress & Monitoring System.
    </footer>
    <script>
        // const observer = new IntersectionObserver((entries) => {
        //     entries.forEach((entry) => {
        //         if (entry.isIntersecting) {
        //             entry.target.classList.add('visible');
        //         }
        //     });
        // }, { threshold: 0.12 });

        // document.querySelectorAll('.reveal').forEach((element) => observer.observe(element));

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.12 });

        document.querySelectorAll('.reveal').forEach((element) => observer.observe(element));


    </script>



</body>

</html>