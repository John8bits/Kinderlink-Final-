<?php
require_once 'autoload.php';

$title = "KinderLink";

ob_start(); //starts output_buffering. PHP instead stores all output in a buffer (memory).
?>

<section class="descript">
    <div class="descript-content">

        <div class="badge"> Pupil Progress & Monitoring System</div>

        <h1>Welcome to KinderLink</h1>

        <p>
            A comprehensive system designed for kindergarten schools to track
            attendance, milestones, behavior, and foster better parent-teacher communication.
        </p>

        <div class="buttons">
            <a class="primary-btn" href="Authentication/login.php">Get Started</a>
            <a class="secondary-btn" href="learnmore/learn_more.php">Learn More</a>
        </div>

    </div>
</section>

<section class="about" id="about">
    <div class="about-header">
        <h2>System Overview</h2>
        <p>Powerful features designed to simplify school management and enhance learning experiences</p>
    </div>

    <div class="feature-container">

        <div class="card">
            <div class="icon blue">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <h3>Attendance Tracking</h3>
            <p>Daily attendance marking with complete history logs and analytics</p>
        </div>

        <div class="card">
            <div class="icon purple">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <h3>Milestone Monitoring</h3>
            <p>Track developmental milestones aligned to curriculum standards</p>
        </div>

        <div class="card">
            <div class="icon cyan">
                <i class="fa-solid fa-file-lines"></i>
            </div>
            <h3>Progress Reports</h3>
            <p>Comprehensive reports generated per grading period</p>
        </div>

        <div class="card">
            <div class="icon orange">
                <i class="fa-solid fa-bell"></i>
            </div>
            <h3>Notifications</h3>
            <p>Real-time alerts for important updates and events</p>
        </div>

    </div>
</section>

<section class="roles" id="features">

    <div class="roles-header">
        <h2>User Roles</h2>
        <p>Tailored experiences for every member of your school community</p>
    </div>

    <div class="roles-container">

        <div class="role-card">
            <div class="role-icon blue">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <h3>Admin</h3>
            <span>Supervisory / Account Manager</span>
            <p>Creates and manages teacher and guardian accounts, links guardians to pupils and classes</p>
        </div>

        <div class="role-card">
            <div class="role-icon purple">
                <i class="fa-solid fa-chalkboard-user"></i>
            </div>
            <h3>Teacher</h3>
            <span>Primary Active User</span>
            <p>Manages pupil data including attendance, milestones, behavior, announcements, and reports</p>
        </div>

        <div class="role-card">
            <div class="role-icon pink">
                <i class="fa-solid fa-people-roof"></i>
            </div>
            <h3>Guardian</h3>
            <span>Secondary Read-Only User</span>
            <p>Views their child’s attendance, milestones, behavior, announcements, and messages from teacher</p>
        </div>

    </div>

</section>

<footer class="footer" id="contact">

    <div class="footer-content">
        <div class="footer-logo">
            <div class="logo-icon"><img src="logo.png"></div>
            <h3>KinderLink</h3>
        </div>

        <p class="footer-sub">
            Pupil Progress & Monitoring System
        </p>

        <hr>

        <p class="footer-email">
            Email: info@kinderlink.com
        </p>

        <p class="footer-copy">
            &copy; 2026 KinderLink - Pupil Progress & Monitoring System. All rights reserved.
        </p>
    </div>

</footer>

<?php
$content = ob_get_clean(); //Take everything that was output so far, store it in $content, then clear the buffer.

require 'views/layout.php';