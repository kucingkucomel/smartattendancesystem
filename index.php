<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Attendance System</title>
    <!-- Use Inter font from Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4F46E5;
            --primary-hover: #4338CA;
            --secondary: #6B7280;
            --danger: #EF4444;
            --danger-hover: #DC2626;
            --success: #10B981;
            --warning: #F59E0B;
            --bg-color: #F3F4F6;
            --card-bg: #FFFFFF;
            --text-main: #111827;
            --text-light: #6B7280;
            --border-color: #E5E7EB;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-color); color: var(--text-main); line-height: 1.5; padding-bottom: 40px; }
        
        /* Layout & Header */
        header { background-color: var(--primary); color: white; padding: 15px 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        header h1 { font-size: 1.25rem; font-weight: 600; margin: 0; display: flex; align-items: center; gap: 10px; }
        .live-clock-badge { background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 20px; font-size: 0.875rem; font-weight: 500; display: flex; align-items: center; gap: 5px; }
        
        .container { max-width: 900px; margin: 30px auto; padding: 0 20px; }
        .login-container { max-width: 450px; }
        
        .hidden { display: none !important; }
        
        /* Cards */
        .card { background: var(--card-bg); border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03); padding: 25px; margin-bottom: 24px; border: 1px solid var(--border-color); }
        .card-header { font-size: 1.25rem; font-weight: 600; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color); color: var(--text-main); }
        .card-header span.subtitle { display: block; font-size: 0.875rem; color: var(--text-light); font-weight: 400; margin-top: 4px; }
        .card-highlight { border: 1px solid var(--primary); box-shadow: 0 4px 12px rgba(79, 70, 229, 0.1); }
        
        /* Typography */
        .fw-bold { font-weight: 600; }
        .text-primary { color: var(--primary); }
        .text-secondary { color: var(--secondary); font-size: 0.875rem; }
        .text-center { text-align: center; }
        .text-danger { color: var(--danger); }
        
        /* Form Controls */
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 6px; color: var(--text-main); }
        input[type="text"], input[type="password"], input[type="time"], select { width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; font-size: 0.95rem; transition: border-color 0.2s; outline: none; background: #fff; }
        input:focus, select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
        
        /* Buttons */
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-weight: 500; padding: 10px 16px; border: none; border-radius: 8px; cursor: pointer; font-size: 0.95rem; transition: all 0.2s; font-family: inherit; }
        .btn-primary { background-color: var(--primary); color: white; }
        .btn-primary:hover { background-color: var(--primary-hover); transform: translateY(-1px); box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.3); }
        .btn-danger { background-color: var(--danger); color: white; }
        .btn-danger:hover { background-color: var(--danger-hover); box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.3); }
        .btn-success { background-color: var(--success); color: white; }
        .btn-success:hover { filter: brightness(0.9); }
        .btn-secondary { background-color: var(--secondary); color: white; }
        .btn-secondary:hover { filter: brightness(0.9); }
        .btn-outline { background-color: transparent; border: 1px solid var(--border-color); color: var(--text-main); }
        .btn-outline:hover { background-color: #F9FAFB; }
        .btn-sm { padding: 6px 12px; font-size: 0.85rem; }
        .btn-block { width: 100%; }
        
        /* Badges */
        .badge { display: inline-block; padding: 4px 10px; font-size: 0.75rem; font-weight: 600; border-radius: 9999px; }
        .badge-active, .badge-success { background-color: #D1FAE5; color: #065F46; }
        .badge-inactive { background-color: #F3F4F6; color: #4B5563; }
        .badge-danger { background-color: #FEE2E2; color: #991B1B; }
        .badge-warning { background-color: #FEF3C7; color: #92400E; }
        
        /* Tables */
        .table-container { overflow-x: auto; border: 1px solid var(--border-color); border-radius: 8px; background: white; margin-top: 15px; }
        table { width: 100%; border-collapse: collapse; text-align: left; white-space: nowrap; }
        th { background-color: #F9FAFB; padding: 12px 16px; font-size: 0.75rem; font-weight: 600; color: var(--secondary); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid var(--border-color); }
        td { padding: 12px 16px; font-size: 0.95rem; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tbody tr:hover { background-color: #F9FAFB; }
        
        .subject-active td { background-color: #F0FDF4; cursor: pointer; border-left: 4px solid var(--success); }
        .subject-active:hover td { background-color: #DCFCE7; }
        .subject-inactive { cursor: not-allowed; opacity: 0.8; border-left: 4px solid transparent; }
        
        /* Action Groups */
        .action-group { display: flex; gap: 8px; align-items: center; }
        
        /* Alert Messages */
        .alert { padding: 12px 16px; border-radius: 8px; font-weight: 500; font-size: 0.95rem; margin-bottom: 24px; display: flex; align-items: center; gap: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .alert-error { background-color: #FEF2F2; color: #991B1B; border-left: 4px solid var(--danger); border-top: 1px solid #FCA5A5; border-right: 1px solid #FCA5A5; border-bottom: 1px solid #FCA5A5;}
        .alert-success { background-color: #ECFDF5; color: #065F46; border-left: 4px solid var(--success); border-top: 1px solid #6EE7B7; border-right: 1px solid #6EE7B7; border-bottom: 1px solid #6EE7B7;}
        
        /* Custom Components */
        .qr-box { background: #F8FAFC; border: 2px dashed var(--primary); padding: 30px 20px; text-align: center; font-size: 1.5rem; font-family: monospace; letter-spacing: 2px; color: var(--primary); font-weight: 700; border-radius: 8px; margin: 15px 0; word-break: break-all; }
        .key-box { background: #F8FAFC; border: 1px solid var(--border-color); padding: 15px; font-size: 1.25rem; font-family: monospace; letter-spacing: 4px; font-weight: bold; color: var(--text-main); margin: 15px 0; border-radius: 8px; text-align: center; }
        .qr-image-wrapper { background: white; padding: 15px; border-radius: 8px; border: 1px solid var(--border-color); display: inline-block; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }

        .flex-inline-form { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; background: #EEF2FF; padding: 16px; border-radius: 8px; border: 1px solid #C7D2FE; margin-bottom: 24px; }
        
        .timer-text { font-size: 1.1rem; font-weight: 600; color: var(--danger); text-align: center; margin-bottom: 15px; }

        @media (max-width: 600px) {
            .action-group { flex-direction: column; align-items: stretch; }
            .action-group .btn { width: 100%; }
            .flex-inline-form { flex-direction: column; align-items: stretch; gap: 10px; }
            .flex-inline-form > div { width: 100%; }
            header { flex-direction: column; text-align: center; }
            .table-container { border: none; }
        }
    </style>
</head>
<body>

<header>
    <h1>
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
        Secure Attendance System
    </h1>
    <div id="live-clock" class="live-clock-badge">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        Loading System Time...
    </div>
</header>

<div class="container">
    <div id="message" class="alert hidden"></div>

    <!-- 1. LOGIN UI -->
    <div id="login-view" class="container login-container" style="margin-top: 50px; padding:0;">
        <div class="card">
            <div class="card-header text-center" style="border-bottom:none; margin-bottom:0;">
                Welcome Back
                <span class="subtitle">Enter your credentials to access the system</span>
            </div>
            
            <div class="form-group">
                <label>UserID / Username</label>
                <input type="text" id="username" placeholder="e.g. BSW01084686 or LEC123">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" id="password" placeholder="••••••••">
            </div>
            <button class="btn btn-primary btn-block" onclick="login()" style="margin-top: 15px;">Secure Login</button>
        </div>
    </div>

    <!-- 2. MFA SETUP UI -->
    <div id="mfa-setup-view" class="hidden container login-container" style="padding:0;">
        <div class="card text-center">
            <div class="card-header">
                Set Up Two-Factor Authentication
                <span class="subtitle">Enhance your account security with Google Authenticator</span>
            </div>
            
            <div class="text-secondary mb-3" style="margin-bottom: 15px; text-align: left;">
                1. Open <b>Google Authenticator</b> on your phone.<br>
                2. Choose <b>Scan a QR Code</b> and scan below:
            </div>
            
            <div class="qr-image-wrapper">
                <img id="qr-image" src="" alt="QR Code" style="width: 170px; height: 170px; display:block;">
            </div>
            
            <div class="text-secondary" style="margin-top:10px;">Or enter this code manually:</div>
            <div class="key-box" id="secret-text">LOADING KEY...</div>
            
            <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 20px 0;">
            
            <div class="text-secondary" style="text-align: left; margin-bottom: 10px;">
                3. Enter the 6-digit code shown on your phone to verify:
            </div>
            
            <div class="form-group">
                <input type="text" id="setup-pin" placeholder="e.g. 123456" style="text-align:center; font-size: 1.1rem; letter-spacing: 2px;">
            </div>
            <button class="btn btn-success btn-block" onclick="verifyMfa('setup-pin')">Verify & Complete Setup</button>
        </div>
    </div>

    <!-- 3. NORMAL MFA LOGIN UI -->
    <div id="mfa-view" class="hidden container login-container" style="margin-top: 50px; padding:0;">
        <div class="card text-center">
            <div class="card-header">
                Two-Factor Authentication
                <span class="subtitle">Enter the 6-digit code from your authenticator app</span>
            </div>
            <div style="margin: 20px 0;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="1.5" style="opacity:0.8;"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
            </div>
            <div class="form-group">
                <input type="text" id="login-pin" placeholder="Enter 6-digit code" style="text-align:center; font-size: 1.1rem; letter-spacing: 2px;">
            </div>
            <button class="btn btn-primary btn-block" onclick="verifyMfa('login-pin')">Authenticate</button>
        </div>
    </div>

    <!-- 4. STUDENT DASHBOARD -->
    <div id="student-view" class="hidden">
        
        <div style="display: flex; justify-content: flex-end; margin-bottom: 15px;">
            <button class="btn btn-outline btn-sm" onclick="logout()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                Logout
            </button>
        </div>

        <div class="card">
            <div class="card-header">
                My Timetable
                <span class="subtitle">Click on an active subject highlighting in green to mark your attendance.</span>
            </div>
            <div id="student-timetable-container"></div>
        </div>

        <div id="student-subject-controls" class="card card-highlight hidden">
            <div class="card-header" style="color:var(--success);">
                Mark Attendance
                <span class="subtitle fw-bold" id="student-active-subject" style="color:var(--text-main);"></span>
            </div>
            <div class="form-group" style="max-width: 400px;">
                <label>Session Token</label>
                <div style="display:flex; gap:10px;">
                    <input type="text" id="attendance-token" placeholder="Paste Token provided by Lecturer">
                    <button class="btn btn-success" onclick="markAttendance()">Submit</button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                Attendance History
                <span class="subtitle">Review your past attendance records</span>
            </div>
            <button class="btn btn-secondary mb-3" onclick="viewHistory()">Load History</button>
            <div id="history-data"></div>
        </div>
    </div>

    <!-- 5. LECTURER DASHBOARD -->
    <div id="lecturer-view" class="hidden">
        
        <div style="display: flex; justify-content: flex-end; margin-bottom: 15px;">
            <button class="btn btn-outline btn-sm" onclick="logout()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                Logout
            </button>
        </div>

        <!-- Testing Panel -->
        <div id="debug-time-panel" class="flex-inline-form hidden">
            <div style="width:100%; font-size:0.85rem; font-weight:600; color:var(--primary); text-transform:uppercase; margin-bottom:-5px;">Admin: Global Time Override Mode</div>
            <div>
                <label style="font-size:0.8rem; font-weight:500;">Simulate Day</label>
                <select id="mock-day">
                    <option value="Monday">Monday</option>
                    <option value="Tuesday">Tuesday</option>
                    <option value="Wednesday">Wednesday</option>
                    <option value="Thursday">Thursday</option>
                    <option value="Friday">Friday</option>
                    <option value="Saturday">Saturday</option>
                    <option value="Sunday">Sunday</option>
                </select>
            </div>
            <div>
                <label style="font-size:0.8rem; font-weight:500;">Simulate Time</label>
                <input type="time" id="mock-time" value="10:00">
            </div>
            <div>
                <button class="btn btn-primary" onclick="setMockTime()">Apply Override System-Wide</button>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                My Timetable
                <span class="subtitle">Click on an active subject highlighting in green to generate tokens and manage the session.</span>
            </div>
            <div id="lecturer-timetable-container"></div>
            <input type="hidden" id="lecturer-subject">
        </div>

        <!-- Active Subject Controls -->
        <div id="subject-controls" class="card card-highlight hidden">
            <div class="card-header" style="color:var(--success);">
                Managing Session: <span id="lecturer-active-subject" class="fw-bold text-main"></span>
            </div>

            <div style="display:flex; flex-wrap:wrap; gap:20px;">
                <!-- Token Generation Config -->
                <div style="flex:1; min-width:300px; background:#F8FAFC; padding:20px; border-radius:8px; border:1px solid #E2E8F0;">
                    <h4 style="margin-bottom:15px; font-size:1.05rem;">Secure Token Broadcasting</h4>
                    <div class="qr-box" id="qr-display">Click 'Generate' to start sharing</div>
                    <div class="timer-text" id="timer-display"></div>
                    <button class="btn btn-success btn-block" onclick="startTokenGeneration()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:5px;"><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path><path d="M18 5l3 3-3 3"></path><path d="M21 8h-9"></path></svg>
                        Start / Refresh Token Generator
                    </button>
                </div>

                <!-- Live Attendance List -->
                <div style="flex:2; min-width:300px;">
                    <h4 style="margin-bottom:15px; font-size:1.05rem;">Live Attendance Audit List</h4>
                    <div id="search-results"></div>
                </div>
            </div>
        </div>

        <!-- Global Enrollment Management -->
        <div class="card">
            <div class="card-header">
                Global Student Enrollment
                <span class="subtitle">Assign dropping students to alternate subjects</span>
            </div>
            <button class="btn btn-secondary mb-3" onclick="loadEnrollmentManagement()">Open Central Student Registry</button>
            <div id="enrollment-management" class="hidden">
                 <div id="enrollment-results"></div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentServerDay = "";
    let serverTimeDateObj = new Date();
    let currentRole = "";
    let autoSyncInterval = null;
    let tokenInterval = null;
    let countdownInterval = null;

    function updateLiveClock() {
        if (currentServerDay === '') return;
        serverTimeDateObj.setSeconds(serverTimeDateObj.getSeconds() + 1);
        let timeStr = serverTimeDateObj.toTimeString().split(' ')[0];
        document.getElementById('live-clock').innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:2px;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> System Time: ${currentServerDay} - ${timeStr} (Shared Demo Mode)`;
    }

    setInterval(updateLiveClock, 1000);

    function isTimeActive(day, startStr, endStr) {
        if (day !== currentServerDay) return false;
        let timeStr = serverTimeDateObj.toTimeString().split(' ')[0];
        return (timeStr >= startStr && timeStr <= endStr);
    }

    function showMsg(msg, type='error') {
        let el = document.getElementById('message');
        el.className = type === 'success' ? 'alert alert-success' : 'alert alert-error';
        
        let icon = type === 'success' 
            ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>'
            : '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';
        
        el.innerHTML = icon + ' <span>' + msg + '</span>';
        el.classList.remove('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });
        setTimeout(() => el.classList.add('hidden'), 5000);
    }

    function startAutoSync() {
        if (autoSyncInterval) clearInterval(autoSyncInterval);

        autoSyncInterval = setInterval(() => {
            if (currentRole === 'student' && !document.getElementById('student-view').classList.contains('hidden')) {
                loadTimetable('student');
            }

            if (currentRole === 'lecturer' && !document.getElementById('lecturer-view').classList.contains('hidden')) {
                loadTimetable('lecturer');

                if (!document.getElementById('subject-controls').classList.contains('hidden') &&
                    document.getElementById('lecturer-subject').value) {
                    loadAttendance();
                }
            }
        }, 5000);
    }

    function stopAutoSync() {
        if (autoSyncInterval) {
            clearInterval(autoSyncInterval);
            autoSyncInterval = null;
        }
    }

    function setMockTime() {
        let fd = new FormData();
        fd.append('action', 'set_global_demo_time');
        fd.append('is_demo_mode', '1');
        fd.append('mock_day', document.getElementById('mock-day').value);

        let tVal = document.getElementById('mock-time').value;
        if (tVal.length === 5) tVal += ':00';
        fd.append('mock_time', tVal);

        fetch('api.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                showMsg("System time override active! Timetables re-evaluated.", "success");

                if (currentRole) {
                    loadTimetable(currentRole);
                }

                if (currentRole === 'lecturer') {
                    document.getElementById('subject-controls').classList.add('hidden');
                }

                if (currentRole === 'student') {
                    document.getElementById('student-subject-controls').classList.add('hidden');
                }
            } else {
                showMsg(data.message || "Failed to apply test time.");
            }
        })
        .catch(() => showMsg("Failed to connect to server."));
    }

    function login() {
        let fd = new FormData();
        fd.append('action', 'login');
        fd.append('username', document.getElementById('username').value);
        fd.append('password', document.getElementById('password').value);

        fetch('api.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'setup_mfa') {
                document.getElementById('login-view').classList.add('hidden');
                document.getElementById('mfa-setup-view').classList.remove('hidden');
                document.getElementById('secret-text').innerText = data.secret;

                let otpUrl = `otpauth://totp/SecureAttendance:${data.username}?secret=${data.secret}&issuer=UniversityApp`;
                document.getElementById('qr-image').src = `https://quickchart.io/qr?text=${encodeURIComponent(otpUrl)}&size=200`;
            } else if (data.status === 'mfa_required') {
                document.getElementById('login-view').classList.add('hidden');
                document.getElementById('mfa-view').classList.remove('hidden');
            } else {
                showMsg(data.message);
            }
        });
    }

    function verifyMfa(inputId) {
        let fd = new FormData();
        fd.append('action', 'verify_mfa');
        fd.append('pin', document.getElementById(inputId).value);

        fetch('api.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('mfa-setup-view').classList.add('hidden');
                document.getElementById('mfa-view').classList.add('hidden');

                currentRole = data.role;

                if (data.role === 'student') {
                    document.getElementById('student-view').classList.remove('hidden');
                    document.getElementById('debug-time-panel').classList.add('hidden');
                    loadTimetable('student');
                    startAutoSync();
                }

                if (data.role === 'lecturer') {
                    document.getElementById('lecturer-view').classList.remove('hidden');
                    document.getElementById('debug-time-panel').classList.remove('hidden');
                    loadTimetable('lecturer');
                    startAutoSync();
                }

                showMsg("Logged in successfully.", "success");
            } else {
                showMsg(data.message);
            }
        });
    }

    function loadTimetable(role) {
        return fetch('api.php?action=get_timetable')
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                currentServerDay = data.server_day;

                let tParts = data.server_time.split(':');
                serverTimeDateObj = new Date();
                serverTimeDateObj.setHours(parseInt(tParts[0]), parseInt(tParts[1]), parseInt(tParts[2]));

                let html = '<div class="table-container"><table><thead><tr><th>Subject Overview</th><th>Schedule</th><th>Time Status</th></tr></thead><tbody>';
                data.data.forEach(sub => {
                    let active = isTimeActive(sub.day_of_week, sub.start_time, sub.end_time);
                    let rowCls = active ? 'subject-active' : 'subject-inactive';
                    let actionBadge = active ? '<span class="badge badge-active">Class Active</span>' : '<span class="badge badge-inactive">Inactive</span>';
                    
                    let clickFunc = active
                        ? `onclick="openSubject('${role}', '${sub.id}', '${sub.name.replace(/'/g, "\\'")}')"`
                        : `onclick="showMsg('This class session is not currently active.')"`;

                    html += `<tr class="${rowCls}" ${clickFunc}>
                        <td><div class="fw-bold text-primary">${sub.id}</div><div class="text-secondary">${sub.name}</div></td>
                        <td><div class="fw-bold">${sub.day_of_week}</div><div class="text-secondary">${sub.start_time.substring(0,5)} - ${sub.end_time.substring(0,5)}</div></td>
                        <td>${actionBadge}</td>
                    </tr>`;
                });
                html += '</tbody></table></div>';

                if (role === 'student') document.getElementById('student-timetable-container').innerHTML = html;
                if (role === 'lecturer') document.getElementById('lecturer-timetable-container').innerHTML = html;
            }
        })
        .catch(() => showMsg("Failed to load timetable."));
    }

    function openSubject(role, sid, sname) {
        if (role === 'student') {
            document.getElementById('student-subject-controls').classList.remove('hidden');
            document.getElementById('student-active-subject').innerText = `${sname} (${sid})`;
            document.getElementById('attendance-token').focus();
            window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
        }

        if (role === 'lecturer') {
            document.getElementById('lecturer-subject').value = sid;
            document.getElementById('lecturer-active-subject').innerText = `${sname} (${sid})`;
            document.getElementById('subject-controls').classList.remove('hidden');

            if (tokenInterval) clearInterval(tokenInterval);
            if (countdownInterval) clearInterval(countdownInterval);

            document.getElementById('qr-display').innerText = "Click 'Start' to share token.";
            document.getElementById('search-results').innerHTML = '';
            document.getElementById('timer-display').innerText = '';

            loadAttendance();
            window.scrollTo({ top: document.getElementById('subject-controls').offsetTop - 20, behavior: 'smooth' });
        }
    }

    // Student Functions
    function markAttendance() {
        let fd = new FormData();
        fd.append('action', 'mark_attendance');
        fd.append('token', document.getElementById('attendance-token').value);

        fetch('api.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                showMsg(data.message, 'success');
                document.getElementById('attendance-token').value = "";
                loadTimetable('student'); // refresh status
                viewHistory(); // auto update history
            } else {
                showMsg(data.message);
            }
        });
    }

    function viewHistory() {
        fetch('api.php?action=get_history')
        .then(res => res.json())
        .then(data => {
            let html = '<div class="table-container"><table><thead><tr><th>Date / Time</th><th>Class Subject</th><th>Status</th></tr></thead><tbody>';
            if(data.data.length === 0) {
                html += '<tr><td colspan="3" class="text-center text-secondary">No attendance history found.</td></tr>';
            } else {
                data.data.forEach(row => {
                    let statusBadge = '<span class="badge badge-inactive">Unrecorded</span>';
                    if (row.status === 'Present') statusBadge = '<span class="badge badge-success">Present</span>';
                    else if (row.status === 'Absent') statusBadge = '<span class="badge badge-danger">Absent</span>';
                    else if (row.status === 'Absent with reason') statusBadge = '<span class="badge badge-warning">Absent (Reason)</span>';
                    
                    html += `<tr><td>${row.recorded_at}</td><td><span class="fw-bold text-primary">${row.class_name}</span></td><td>${statusBadge}</td></tr>`;
                });
            }
            html += '</tbody></table></div>';
            document.getElementById('history-data').innerHTML = html;
        });
    }

    // Lecturer Functions
    function startTokenGeneration() {
        let sid = document.getElementById('lecturer-subject').value;
        if (!sid) return showMsg("Select subject first");

        fetchToken();

        if (tokenInterval) clearInterval(tokenInterval);
        tokenInterval = setInterval(fetchToken, 45000);

        if (countdownInterval) clearInterval(countdownInterval);
        let timeLeft = 45;

        document.getElementById('timer-display').innerText = `Valid for ${timeLeft}s`;
        countdownInterval = setInterval(() => {
            timeLeft = timeLeft <= 0 ? 45 : timeLeft - 1;
            document.getElementById('timer-display').innerText = `Valid for ${timeLeft}s`;
            if (timeLeft <= 5) {
                document.getElementById('timer-display').style.color = 'darkred';
            } else {
                document.getElementById('timer-display').style.color = 'var(--danger)';
            }
        }, 1000);
    }

    function fetchToken() {
        let fd = new FormData();
        fd.append('action', 'generate_token');
        fd.append('subject_id', document.getElementById('lecturer-subject').value);

        fetch('api.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('qr-display').innerText = data.token;
            } else {
                showMsg(data.message || "Failed to generate session token.");
                if (tokenInterval) clearInterval(tokenInterval);
                if (countdownInterval) { clearInterval(countdownInterval); document.getElementById('timer-display').innerText = ''; }
            }
        });
    }

    function loadAttendance() {
        let sid = document.getElementById('lecturer-subject').value;
        if (!sid) return;

        fetch(`api.php?action=search_student&subject_id=${sid}`)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'error') return showMsg(data.message);

            let html = '<div class="table-container"><table><thead><tr><th>Student Details</th><th>Status</th><th>Audit Actions</th></tr></thead><tbody>';
            if (data.data.length === 0) {
                 html += '<tr><td colspan="3" class="text-center text-secondary">No students in registry</td></tr>';
            } else {
                data.data.forEach(row => {
                    if (row.att_id == null) {
                        html += `<tr><td><div class="fw-bold">${row.name}</div><div class="text-secondary">${row.student_id}</div></td><td colspan="2"><span class="badge badge-inactive">No Session Yet</span></td></tr>`;
                    } else {
                        let statusBadge = '';
                        if (row.status === 'Present') statusBadge = '<span class="badge badge-success">Present</span>';
                        else if (row.status === 'Absent') statusBadge = '<span class="badge badge-danger">Absent</span>';
                        else statusBadge = '<span class="badge badge-warning">Absent (R)</span>';

                        let selP = row.status === 'Present' ? 'selected' : '';
                        let selA = row.status === 'Absent' ? 'selected' : '';
                        let selAR = row.status === 'Absent with reason' ? 'selected' : '';

                        html += `<tr>
                            <td><div class="fw-bold">${row.name}</div><div class="text-secondary">${row.student_id}</div></td>
                            <td>${statusBadge}</td>
                            <td>
                                <div class="action-group">
                                    <select id="status_${row.att_id}" class="form-select status-select">
                                        <option value="Present" ${selP}>Present</option>
                                        <option value="Absent" ${selA}>Absent</option>
                                        <option value="Absent with reason" ${selAR}>Absent (Reason)</option>
                                    </select>
                                    <button class="btn btn-primary btn-sm" onclick="updateAttendance(${row.att_id})">Override</button>
                                </div>
                            </td>
                        </tr>`;
                    }
                });
            }

            html += '</tbody></table></div>';
            document.getElementById('search-results').innerHTML = html;
        });
    }

    function updateAttendance(att_id) {
        let fd = new FormData();
        fd.append('action', 'update_attendance');
        fd.append('att_id', att_id);
        fd.append('status', document.getElementById(`status_${att_id}`).value);

        fetch('api.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') {
                showMsg(data.message, 'success');
                loadAttendance();
            } else {
                showMsg(data.message);
            }
        });
    }

    // Global Enrollment Management
    function loadEnrollmentManagement() {
        document.getElementById('enrollment-management').classList.remove('hidden');

        fetch('api.php?action=get_all_students_enrollment')
        .then(res => res.json())
        .then(data => {
            if (data.status !== 'success') return showMsg("Failed to load global enrollment registry.");

            let dropdownHtml = '';
            data.lecturer_subjects.forEach(ls => {
                dropdownHtml += `<option value="${ls.id}">${ls.name} (${ls.id})</option>`;
            });

            let html = '<div class="table-container"><table><thead><tr><th>Student Details</th><th>Current Assignments</th><th>Reassignment</th><th>Manage</th></tr></thead><tbody>';

            data.students.forEach(student => {
                let myClasses = [];
                let otherSection = [];

                data.enrollments.forEach(enr => {
                    if (enr.student_id === student.id) {
                        let isMine = false;

                        data.lecturer_subjects.forEach(ls => {
                            if (ls.id === enr.subject_id) {
                                isMine = true;
                                myClasses.push(ls.id);
                            }
                        });

                        if (!isMine) {
                            let sameNameAsMine = false;
                            data.lecturer_subjects.forEach(ls => {
                                if (ls.name === enr.subject_name) sameNameAsMine = true;
                            });

                            if (sameNameAsMine) {
                                otherSection.push(`${enr.subject_name} (${enr.subject_id})`);
                            }
                        }
                    }
                });

                let statusBadge = '<span class="badge badge-inactive">Unassigned</span>';
                if (myClasses.length > 0) {
                    statusBadge = `<span class="badge badge-success" style="margin-bottom:4px; display:inline-block;">Registered: ${myClasses.join(', ')}</span>`;
                } else if (otherSection.length > 0) {
                    statusBadge = `<span class="badge badge-warning">Other Term: ${otherSection.join(', ')}</span>`;
                }

                html += `<tr>
                    <td><div class="fw-bold">${student.name}</div><div class="text-secondary">${student.id}</div></td>
                    <td>${statusBadge}</td>
                    <td><select id="target_class_${student.id}" class="form-select">${dropdownHtml}</select></td>
                    <td>
                        <div class="action-group">
                            <button class="btn btn-success btn-sm" onclick="manageEnrollment('add', '${student.id}')">Add</button>
                            <button class="btn btn-danger btn-sm" onclick="manageEnrollment('remove', '${student.id}')">Drop</button>
                        </div>
                    </td>
                </tr>`;
            });

            html += '</tbody></table></div>';
            document.getElementById('enrollment-results').innerHTML = html;
            window.scrollTo({ top: document.getElementById('enrollment-management').offsetTop, behavior: 'smooth' });
        });
    }

    function manageEnrollment(action, studentId) {
        let sid = document.getElementById(`target_class_${studentId}`).value;
        if (!sid) return showMsg("Please select a target class specification first.");

        let fd = new FormData();
        fd.append('action', 'manage_enrollment');
        fd.append('action_type', action);
        fd.append('student_id', studentId);
        fd.append('subject_id', sid);

        fetch('api.php', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                showMsg(data.message, 'success');
                loadEnrollmentManagement();

                if (document.getElementById('lecturer-subject').value === sid || document.getElementById('lecturer-subject').value !== '') {
                    loadAttendance();
                }
            } else {
                showMsg(data.message);
            }
        });
    }

    function logout() {
        stopAutoSync();
        if (tokenInterval) clearInterval(tokenInterval);
        if (countdownInterval) clearInterval(countdownInterval);

        fetch('api.php?action=logout').then(() => location.reload());
    }
</script>
</body>
</html>
