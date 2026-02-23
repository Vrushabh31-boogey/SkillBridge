<?php 
session_start();
if (!isset($_SESSION['student_id'])) {
    header('Location: student-login.html');
    exit();
}

require 'db.php';

$student_id   = $_SESSION['student_id'];
$student_name = $_SESSION['student_name'] ?? 'Student';

// Get student info (photo + education) — graceful fallback if columns not yet added
try {
    $student_row  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT photo, edu_type, inst_name FROM students WHERE id = $student_id"));
} catch (Exception $e) {
    // edu_type / inst_name columns not created yet — run the ALTER TABLE in phpMyAdmin
    $student_row  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT photo FROM students WHERE id = $student_id"));
}
$student_photo = $student_row['photo']     ?? '';
$edu_type      = $student_row['edu_type']  ?? '';
$inst_name     = $student_row['inst_name'] ?? '';


// Get first letter of name for initials avatar
$initial = strtoupper(mb_substr($student_name, 0, 1));

// Get student's skills
$skills_result = mysqli_query($conn, "SELECT * FROM skills WHERE student_id = $student_id");

// Get all internships with company names
$internships_result = mysqli_query($conn, "
    SELECT i.*, c.company_name 
    FROM internships i 
    JOIN companies c ON i.company_id = c.id 
    ORDER BY i.created_at DESC
");

// Get student's applications
$applications_result = mysqli_query($conn, "SELECT internship_id FROM applications WHERE student_id = $student_id");
$applied_internships = [];
while ($app = mysqli_fetch_assoc($applications_result)) {
    $applied_internships[] = $app['internship_id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard — SkillBridge</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* ── Dashboard Layout ── */
        .dashboard {
            max-width: 920px;
            margin: 0 auto;
            padding: 40px 20px 60px;
            position: relative;
            z-index: 1;
        }

        /* ── Dashboard Header ── */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 36px;
            padding-bottom: 24px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }

        .dashboard-title {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .dashboard-title h1 {
            text-align: left;
            font-size: 2rem;
            margin-bottom: 0;
        }

        .dashboard-title .welcome-sub {
            text-align: left;
            font-size: 13px;
            color: #475569;
            margin: 0;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* ── Section Card ── */
        .section {
            background: rgba(10, 15, 30, 0.6);
            backdrop-filter: blur(20px) saturate(150%);
            -webkit-backdrop-filter: blur(20px) saturate(150%);
            padding: 28px;
            border-radius: 20px;
            border: 1px solid rgba(148, 163, 184, 0.1);
            margin-bottom: 24px;
            box-shadow:
                0 20px 60px rgba(0, 0, 0, 0.35),
                inset 0 1px 0 rgba(255, 255, 255, 0.04);
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .section:hover {
            border-color: rgba(56, 189, 248, 0.18);
            box-shadow:
                0 24px 70px rgba(0, 0, 0, 0.4),
                0 0 0 1px rgba(56, 189, 248, 0.08),
                inset 0 1px 0 rgba(255, 255, 255, 0.04);
        }

        .section:nth-child(2) { animation-delay: 0.1s; }
        .section:nth-child(3) { animation-delay: 0.2s; }

        /* ── Section Heading ── */
        .section-heading {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.08);
        }

        .section-heading .icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .icon-skills {
            background: linear-gradient(135deg, rgba(56,189,248,0.2), rgba(99,102,241,0.2));
            border: 1px solid rgba(56,189,248,0.25);
            box-shadow: 0 4px 16px rgba(56,189,248,0.15);
        }

        .icon-internship {
            background: linear-gradient(135deg, rgba(34,211,238,0.2), rgba(99,102,241,0.2));
            border: 1px solid rgba(34,211,238,0.25);
            box-shadow: 0 4px 16px rgba(34,211,238,0.15);
        }

        .section-heading h3 {
            font-size: 1.05rem;
            font-weight: 700;
            color: #e2e8f0;
            background: linear-gradient(135deg, #e0f2fe, #38bdf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ── Skills ── */
        .skills-container {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 8px;
        }

        .add-skill-form {
            display: flex;
            gap: 10px;
            margin-bottom: 16px;
        }

        .add-skill-form input {
            flex: 1;
            margin: 0;
        }

        .add-skill-form button {
            width: auto;
            padding: 14px 24px;
            margin: 0;
            white-space: nowrap;
        }

        /* ── Internship Card ── */
        .internship-card {
            background: rgba(2, 6, 23, 0.55);
            border: 1px solid rgba(148, 163, 184, 0.1);
            padding: 22px;
            border-radius: 16px;
            margin-bottom: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .internship-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(135deg, #38bdf8, #6366f1);
            border-radius: 3px 0 0 3px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .internship-card:hover {
            border-color: rgba(56, 189, 248, 0.3);
            box-shadow: 0 12px 30px rgba(56, 189, 248, 0.12);
            transform: translateY(-2px);
        }

        .internship-card:hover::before {
            opacity: 1;
        }

        .internship-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 4px;
        }

        .internship-company {
            color: #38bdf8;
            font-size: 0.88rem;
            font-weight: 600;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .internship-company::before {
            content: '🏢';
            font-size: 0.85rem;
        }

        .internship-desc {
            color: #64748b;
            font-size: 0.88rem;
            margin-bottom: 16px;
            line-height: 1.65;
        }

        .internship-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .stipend {
            background: linear-gradient(135deg, rgba(56,189,248,0.12), rgba(99,102,241,0.12));
            color: #7dd3fc;
            padding: 7px 16px;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 700;
            border: 1px solid rgba(56,189,248,0.2);
            letter-spacing: 0.2px;
        }

        /* ── Apply Button ── */
        .apply-btn {
            background: linear-gradient(135deg, #38bdf8, #6366f1);
            color: #fff;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            font-size: 0.88rem;
            width: auto;
            margin: 0;
            transition: all 0.3s ease;
            letter-spacing: 0.2px;
        }

        .apply-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(56, 189, 248, 0.35);
        }

        .apply-btn:disabled {
            background: rgba(71, 85, 105, 0.5);
            color: #475569;
            cursor: not-allowed;
            border: 1px solid rgba(71,85,105,0.3);
        }

        .apply-btn:disabled:hover {
            transform: none;
            box-shadow: none;
        }

        /* ── Logout Button ── */
        .logout-btn {
            background: transparent;
            border: 1px solid rgba(148, 163, 184, 0.2);
            color: #64748b;
            padding: 10px 20px;
            width: auto;
            margin: 0;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.25s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .logout-btn:hover {
            border-color: rgba(239, 68, 68, 0.5);
            color: #f87171;
            background: rgba(239, 68, 68, 0.08);
            box-shadow: 0 4px 16px rgba(239, 68, 68, 0.15);
            text-decoration: none;
            transform: none;
        }

        /* ── Messages ── */
        .empty-message {
            color: #334155;
            font-style: italic;
            font-size: 13px;
            padding: 12px 0;
        }

        #msg {
            margin-top: 6px;
            font-size: 13px;
            font-weight: 500;
            min-height: 20px;
            color: #22c55e;
        }

        /* ── Stats Row ── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: rgba(10, 15, 30, 0.6);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .stat-card:hover {
            border-color: rgba(56,189,248,0.25);
            box-shadow: 0 8px 24px rgba(56,189,248,0.1);
            transform: translateY(-3px);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 900;
            background: linear-gradient(135deg, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 6px;
        }

        .stat-label {
            font-size: 12px;
            color: #475569;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        /* ── Education Section ── */
        .edu-row {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: flex-end;
        }

        .edu-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        #instWrap {
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>
<body>

    <!-- ══════════════════════════════════
         USER PROFILE CORNER (top-right)
         ══════════════════════════════════ -->
    <div class="user-profile-corner" onclick="document.getElementById('photoInput').click()" title="Click to change profile photo">
        <div class="avatar-wrapper">
            <?php if (!empty($student_photo) && file_exists($student_photo)): ?>
                <img id="avatarImg" class="avatar-img" src="<?php echo htmlspecialchars($student_photo); ?>?v=<?php echo time(); ?>" alt="Profile">
            <?php else: ?>
                <div class="avatar-initials" id="avatarInitials"><?php echo $initial; ?></div>
                <img id="avatarImg" class="avatar-img" src="" alt="Profile" style="display:none;">
            <?php endif; ?>
            <div class="avatar-upload-overlay">📷</div>
        </div>
        <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($student_name); ?></span>
            <span class="user-status">Online</span>
        </div>
    </div>

    <!-- Hidden file input for photo upload -->
    <input type="file" id="photoInput" accept="image/jpeg,image/png,image/gif,image/webp">

    <!-- ══════════════════════════════════
         MAIN DASHBOARD
         ══════════════════════════════════ -->
    <div class="dashboard">
        <div class="dashboard-header">
            <div class="dashboard-title">
                <h1>Welcome back,<br><?php echo htmlspecialchars($student_name); ?> 👋</h1>
                <p class="welcome-sub">Here's your internship hub. Explore opportunities and track your skills.</p>
            </div>
            <div class="header-actions">
                <a href="logout.php" class="logout-btn">⬡ Logout</a>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number"><?php echo mysqli_num_rows(mysqli_query($conn, "SELECT id FROM skills WHERE student_id = $student_id")); ?></div>
                <div class="stat-label">Skills</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($applied_internships); ?></div>
                <div class="stat-label">Applied</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo mysqli_num_rows(mysqli_query($conn, "SELECT id FROM internships")); ?></div>
                <div class="stat-label">Available</div>
            </div>
        </div>

        <!-- Education Section -->
        <div class="section" id="edu-section">
            <div class="section-heading">
                <div class="icon" style="background:linear-gradient(135deg,rgba(34,211,238,0.18),rgba(99,102,241,0.18));border:1px solid rgba(34,211,238,0.25);box-shadow:0 4px 16px rgba(34,211,238,0.12);">🎓</div>
                <h3>Education Background</h3>
            </div>

            <div class="edu-row">
                <!-- Dropdown -->
                <div style="flex:1;min-width:160px;">
                    <label class="edu-label">Where are you studying?</label>
                    <select id="eduType" onchange="handleEduType()">
                        <option value="" <?php echo $edu_type==='' ? 'selected':'' ?>>— Select —</option>
                        <option value="School"           <?php echo $edu_type==='School'           ? 'selected':'' ?>>🏫 School</option>
                        <option value="College"          <?php echo $edu_type==='College'          ? 'selected':'' ?>>🎓 College / University</option>
                        <option value="Learning Purpose" <?php echo $edu_type==='Learning Purpose' ? 'selected':'' ?>>📚 Self-Learning</option>
                    </select>
                </div>

                <!-- College name — shown only when College is selected -->
                <div id="instWrap" style="flex:2;min-width:200px;display:<?php echo $edu_type==='College' ? 'block':'none'; ?>">
                    <label class="edu-label">College / University Name</label>
                    <input type="text" id="instName" placeholder="e.g. IIT Bombay, MIT, DY Patil College…"
                           value="<?php echo htmlspecialchars($inst_name); ?>">
                </div>
            </div>

            <div style="margin-top:14px;display:flex;align-items:center;gap:12px;">
                <button type="button" onclick="saveEducation()" style="width:auto;margin:0;padding:12px 26px;">Save Education</button>
                <span id="eduMsg" style="font-size:13px;font-weight:600;"></span>
            </div>
        </div>

        <!-- Skills Section -->
        <div class="section">
            <div class="section-heading">
                <div class="icon icon-skills">🎯</div>
                <h3>Your Skills</h3>
            </div>
            <form class="add-skill-form" onsubmit="saveSkill(); return false;">
                <input id="skill" placeholder="Add a skill (e.g., Python, React, Marketing)" required>
                <button type="submit">+ Add Skill</button>
            </form>
            <div id="msg"></div>
            <div class="skills-container" id="skills-list">
                <?php if (mysqli_num_rows($skills_result) > 0): ?>
                    <?php while ($skill = mysqli_fetch_assoc($skills_result)): ?>
                        <span class="skill"><?php echo htmlspecialchars($skill['skill_name']); ?></span>
                    <?php endwhile; ?>
                <?php else: ?>
                    <span class="empty-message">No skills added yet. Add your first skill above!</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Internships Section -->
        <div class="section">
            <div class="section-heading">
                <div class="icon icon-internship">💼</div>
                <h3>Available Internships</h3>
            </div>
            <?php if (mysqli_num_rows($internships_result) > 0): ?>
                <?php while ($internship = mysqli_fetch_assoc($internships_result)): ?>
                    <div class="internship-card">
                        <div class="internship-title"><?php echo htmlspecialchars($internship['title']); ?></div>
                        <div class="internship-company"><?php echo htmlspecialchars($internship['company_name']); ?></div>
                        <div class="internship-desc"><?php echo htmlspecialchars($internship['description']); ?></div>
                        <div class="internship-footer">
                            <span class="stipend">₹<?php echo htmlspecialchars($internship['stipend']); ?>/month</span>
                            <?php if (in_array($internship['id'], $applied_internships)): ?>
                                <button class="apply-btn" disabled>✓ Applied</button>
                            <?php else: ?>
                                <button class="apply-btn" onclick="applyInternship(<?php echo $internship['id']; ?>, this)">Apply Now →</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="empty-message">No internships available yet. Check back later!</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // ────────────────────────────────────
        // PHOTO UPLOAD
        // ────────────────────────────────────
        const photoInput = document.getElementById('photoInput');
        const avatarImg  = document.getElementById('avatarImg');
        const avatarInit = document.getElementById('avatarInitials');

        photoInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            // Instant preview
            const reader = new FileReader();
            reader.onload = function (e) {
                avatarImg.src = e.target.result;
                avatarImg.style.display = 'block';
                if (avatarInit) avatarInit.style.display = 'none';
            };
            reader.readAsDataURL(file);

            // Upload to server
            const formData = new FormData();
            formData.append('photo', file);

            showToast('⏳ Uploading photo…', '');

            fetch('upload-photo.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                removeToast();
                if (data.success) {
                    showToast('✅ Profile photo updated!', 'success');
                } else {
                    showToast('❌ ' + (data.error || 'Upload failed'), 'error');
                }
            })
            .catch(() => {
                removeToast();
                showToast('❌ Network error during upload', 'error');
            });

            // Reset so same file can be re-selected
            photoInput.value = '';
        });

        // ────────────────────────────────────
        // TOAST NOTIFICATION
        // ────────────────────────────────────
        let _toast = null;
        let _toastTimer = null;

        function showToast(message, type) {
            removeToast();
            _toast = document.createElement('div');
            _toast.className = 'photo-toast' + (type ? ' ' + type : '');
            _toast.innerHTML = `<span>${message}</span>`;
            document.body.appendChild(_toast);
            if (type) {
                _toastTimer = setTimeout(removeToast, 3500);
            }
        }

        function removeToast() {
            clearTimeout(_toastTimer);
            if (_toast) {
                _toast.style.animation = 'toastOut 0.3s ease forwards';
                setTimeout(() => { if (_toast) { _toast.remove(); _toast = null; } }, 300);
            }
        }

        // ────────────────────────────────────
        // EDUCATION
        // ────────────────────────────────────
        function handleEduType() {
            const type    = document.getElementById('eduType').value;
            const wrap    = document.getElementById('instWrap');
            const input   = document.getElementById('instName');

            if (type === 'College') {
                wrap.style.display = 'block';
                input.focus();
            } else {
                wrap.style.display = 'none';
                input.value = '';
            }
        }

        function saveEducation() {
            const type     = document.getElementById('eduType').value;
            const instName = (document.getElementById('instName').value || '').trim();
            const msg      = document.getElementById('eduMsg');

            if (!type) {
                msg.style.color = '#f87171';
                msg.textContent = '⚠️ Please select an option first.';
                setTimeout(() => msg.textContent = '', 3000);
                return;
            }
            if (type === 'College' && !instName) {
                msg.style.color = '#f87171';
                msg.textContent = '⚠️ Please enter your college name.';
                setTimeout(() => msg.textContent = '', 3000);
                return;
            }

            msg.style.color = '#94a3b8';
            msg.textContent = 'Saving…';

            fetch('save-education.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'edu_type=' + encodeURIComponent(type) + '&inst_name=' + encodeURIComponent(instName)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    msg.style.color = '#22c55e';
                    msg.textContent = '✅ Saved!';
                } else {
                    msg.style.color = '#f87171';
                    msg.textContent = '❌ ' + (data.error || 'Save failed');
                }
                setTimeout(() => msg.textContent = '', 3500);
            })
            .catch(() => {
                msg.style.color = '#f87171';
                msg.textContent = '❌ Network error';
                setTimeout(() => msg.textContent = '', 3500);
            });
        }

        // ────────────────────────────────────
        // SAVE SKILL
        // ────────────────────────────────────
        function saveSkill() {
            const skillInput = document.getElementById('skill');
            const skill = skillInput.value.trim();
            if (!skill) return;

            fetch('save-skill.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'skill=' + encodeURIComponent(skill)
            })
            .then(r => r.text())
            .then(t => {
                const msg = document.getElementById('msg');
                msg.innerText = t;
                if (t === 'Skill saved') {
                    msg.style.color = '#22c55e';
                    const skillsList = document.getElementById('skills-list');
                    const emptyMsg = skillsList.querySelector('.empty-message');
                    if (emptyMsg) emptyMsg.remove();

                    const newSkill = document.createElement('span');
                    newSkill.className = 'skill';
                    newSkill.textContent = skill;
                    newSkill.style.animation = 'fadeInUp 0.3s ease';
                    skillsList.appendChild(newSkill);
                    skillInput.value = '';
                } else {
                    msg.style.color = '#f87171';
                }
                setTimeout(() => { msg.innerText = ''; }, 3000);
            });
        }

        // ────────────────────────────────────
        // APPLY TO INTERNSHIP
        // ────────────────────────────────────
        function applyInternship(internshipId, btn) {
            btn.disabled = true;
            btn.textContent = 'Applying…';

            fetch('apply.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'internship_id=' + internshipId
            })
            .then(r => r.text())
            .then(t => {
                if (t === 'Applied successfully') {
                    btn.textContent = '✓ Applied';
                } else {
                    btn.textContent = 'Apply Now →';
                    btn.disabled = false;
                    alert(t);
                }
            });
        }
    </script>
</body>
</html>