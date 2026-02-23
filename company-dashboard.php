<?php 
session_start();
if (!isset($_SESSION['company_id'])) {
    header('Location: company-login.html');
    exit();
}

require 'db.php';

$company_id = $_SESSION['company_id'];
$company_name = $_SESSION['company_name'] ?? 'Company';

// Get company's posted internships with applicant count
$internships_result = mysqli_query($conn, "
    SELECT i.*, 
           (SELECT COUNT(*) FROM applications a WHERE a.internship_id = i.id) as applicant_count
    FROM internships i 
    WHERE i.company_id = $company_id 
    ORDER BY i.created_at DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Company Dashboard - SkillBridge</title>
    <link rel='stylesheet' href='style.css'>
    <style>
        .dashboard {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .dashboard-header h1 {
            text-align: left;
        }
        
        .section {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(14px);
            padding: 24px;
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.12);
            margin-bottom: 24px;
        }
        
        .section h3 {
            color: #38bdf8;
            margin-bottom: 16px;
            font-size: 1.1rem;
        }
        
        .post-form {
            display: grid;
            gap: 12px;
        }
        
        .form-row {
            display: flex;
            gap: 12px;
        }
        
        .form-row input {
            flex: 1;
        }
        
        .post-form textarea {
            min-height: 100px;
        }
        
        .post-form button {
            width: 200px;
        }
        
        .internship-card {
            background: rgba(2, 6, 23, 0.7);
            border: 1px solid rgba(148, 163, 184, 0.15);
            padding: 20px;
            border-radius: 14px;
            margin-bottom: 16px;
            transition: 0.25s ease;
        }
        
        .internship-card:hover {
            border-color: rgba(56, 189, 248, 0.4);
        }
        
        .internship-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        
        .internship-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #e5e7eb;
        }
        
        .internship-desc {
            color: #94a3b8;
            font-size: 0.9rem;
            margin-bottom: 12px;
            line-height: 1.5;
        }
        
        .internship-meta {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .meta-badge {
            background: rgba(56, 189, 248, 0.1);
            color: #38bdf8;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .applicant-badge {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }
        
        .view-applicants-btn {
            background: transparent;
            border: 1px solid #38bdf8;
            color: #38bdf8;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            width: auto;
            margin: 0;
        }
        
        .view-applicants-btn:hover {
            background: rgba(56, 189, 248, 0.1);
            box-shadow: none;
            transform: none;
        }
        
        .applicants-section {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid rgba(148, 163, 184, 0.15);
            display: none;
        }
        
        .applicants-section.show {
            display: block;
        }
        
        .applicant-card {
            background: rgba(15, 23, 42, 0.5);
            padding: 16px;
            border-radius: 10px;
            margin-bottom: 12px;
            border: 1px solid rgba(148, 163, 184, 0.1);
        }
        
        .applicant-name {
            font-weight: 600;
            color: #e5e7eb;
            margin-bottom: 4px;
        }
        
        .applicant-email {
            color: #64748b;
            font-size: 0.85rem;
            margin-bottom: 10px;
        }
        
        .applicant-skills {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        
        .applicant-skills .skill {
            font-size: 0.75rem;
            padding: 4px 10px;
            margin: 0;
        }
        
        .no-applicants {
            color: #64748b;
            font-style: italic;
            font-size: 0.9rem;
        }

        /* Education badge inside applicant card */
        .edu-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(34, 211, 238, 0.08);
            border: 1px solid rgba(34, 211, 238, 0.2);
            color: #22d3ee;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 99px;
            margin-bottom: 10px;
        }
        
        .logout-btn {
            background: transparent;
            border: 1px solid rgba(148, 163, 184, 0.25);
            color: #94a3b8;
            padding: 10px 20px;
            width: auto;
            margin: 0;
        }
        
        .logout-btn:hover {
            border-color: #ef4444;
            color: #ef4444;
            box-shadow: none;
        }
        
        #msg {
            margin-top: 12px;
            color: #22c55e;
            font-size: 0.9rem;
        }
        
        .empty-message {
            color: #64748b;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="dashboard-header">
            <h1><?php echo htmlspecialchars($company_name); ?></h1>
            <a href='logout.php' class="logout-btn" style="text-decoration: none; display: inline-block;">Logout</a>
        </div>
        
        <!-- Post New Internship -->
        <div class="section">
            <h3>📝 Post New Internship</h3>
            <form class="post-form" onsubmit='postInternship(); return false;'>
                <div class="form-row">
                    <input id='title' placeholder='Internship Title (e.g., Frontend Developer Intern)' required>
                    <input id='stipend' placeholder='Stipend (e.g., 15000)' required>
                </div>
                <textarea id='desc' placeholder='Describe the role, responsibilities, and requirements...' required></textarea>
                <button type="submit">Post Internship</button>
            </form>
            <div id='msg'></div>
        </div>
        
        <!-- Posted Internships -->
        <div class="section">
            <h3>📋 Your Posted Internships</h3>
            <?php if (mysqli_num_rows($internships_result) > 0): ?>
                <?php while ($internship = mysqli_fetch_assoc($internships_result)): ?>
                    <div class="internship-card" id="internship-<?php echo $internship['id']; ?>">
                        <div class="internship-header">
                            <div class="internship-title"><?php echo htmlspecialchars($internship['title']); ?></div>
                            <button class="view-applicants-btn" onclick="toggleApplicants(<?php echo $internship['id']; ?>)">
                                View Applicants
                            </button>
                        </div>
                        <div class="internship-desc"><?php echo htmlspecialchars($internship['description']); ?></div>
                        <div class="internship-meta">
                            <span class="meta-badge">₹<?php echo htmlspecialchars($internship['stipend']); ?>/month</span>
                            <span class="meta-badge applicant-badge"><?php echo $internship['applicant_count']; ?> Applicant<?php echo $internship['applicant_count'] != 1 ? 's' : ''; ?></span>
                        </div>
                        
                        <!-- Applicants Section (Hidden by default) -->
                        <div class="applicants-section" id="applicants-<?php echo $internship['id']; ?>">
                            <?php
                            // Get applicants for this internship
                            $applicants_result = mysqli_query($conn, "
                                SELECT s.id, s.name, s.email, s.edu_type, s.inst_name
                                FROM applications a 
                                JOIN students s ON a.student_id = s.id 
                                WHERE a.internship_id = " . $internship['id'] . "
                                ORDER BY a.applied_at DESC
                            ");
                            
                            if (mysqli_num_rows($applicants_result) > 0):
                                while ($applicant = mysqli_fetch_assoc($applicants_result)):
                                    // Get skills for this applicant
                                    $skills_result = mysqli_query($conn, "SELECT skill_name FROM skills WHERE student_id = " . $applicant['id']);
                            ?>
                                <div class="applicant-card">
                                    <div class="applicant-name"><?php echo htmlspecialchars($applicant['name']); ?></div>
                                    <div class="applicant-email"><?php echo htmlspecialchars($applicant['email']); ?></div>

                                    <?php
                                    // Education badge
                                    $edu  = $applicant['edu_type']  ?? '';
                                    $inst = $applicant['inst_name'] ?? '';
                                    if ($edu):
                                        $edu_icon = $edu === 'School' ? '🏫' : ($edu === 'College' ? '🎓' : '📚');
                                        $edu_display = htmlspecialchars($edu);
                                        if ($edu === 'College' && $inst) {
                                            $edu_display .= ' — ' . htmlspecialchars($inst);
                                        }
                                    ?>
                                        <div class="edu-badge"><?php echo $edu_icon; ?> <?php echo $edu_display; ?></div>
                                    <?php endif; ?>

                                    <div class="applicant-skills">
                                        <?php if (mysqli_num_rows($skills_result) > 0): ?>
                                            <?php while ($skill = mysqli_fetch_assoc($skills_result)): ?>
                                                <span class="skill"><?php echo htmlspecialchars($skill['skill_name']); ?></span>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <span class="no-applicants">No skills listed</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <p class="no-applicants">No applicants yet for this position.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="empty-message">You haven't posted any internships yet. Create your first one above!</p>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function postInternship() {
            const title = document.getElementById('title').value.trim();
            const desc = document.getElementById('desc').value.trim();
            const stipend = document.getElementById('stipend').value.trim();
            
            if (!title || !desc || !stipend) return;
            
            fetch('save-internship.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'title=' + encodeURIComponent(title) + 
                      '&desc=' + encodeURIComponent(desc) + 
                      '&stipend=' + encodeURIComponent(stipend)
            })
            .then(r => r.text())
            .then(t => {
                document.getElementById('msg').innerText = t;
                if (t === 'Internship posted') {
                    // Clear form and reload to show new internship
                    document.getElementById('title').value = '';
                    document.getElementById('desc').value = '';
                    document.getElementById('stipend').value = '';
                    setTimeout(() => location.reload(), 1000);
                }
            });
        }
        
        function toggleApplicants(internshipId) {
            const section = document.getElementById('applicants-' + internshipId);
            section.classList.toggle('show');
        }
    </script>
</body>
</html>