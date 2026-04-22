<?php
declare(strict_types=1);
ini_set('session.cookie_httponly', '1');
session_start();
header('Content-Type: application/json');

// --- Google Authenticator (TOTP) Class ---
class TOTP {
    private static $b32 = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";

    public static function base32Decode($b32) {
        $b32 = strtoupper($b32);
        $n = 0; $j = 0; $binary = "";
        for ($i = 0; $i < strlen($b32); $i++) {
            $n = $n << 5;
            $n = $n + strpos(self::$b32, $b32[$i]);
            $j = $j + 5;
            if ($j >= 8) {
                $j = $j - 8;
                $binary .= chr(($n & (0xFF << $j)) >> $j);
            }
        }
        return $binary;
    }

    public static function verify($secret, $code) {
        $binarySecret = self::base32Decode($secret);
        $currentSlice = floor(time() / 30);
        
        for ($i = -1; $i <= 1; $i++) { // Allow 30s drift
            $timeBytes = "\x00\x00\x00\x00" . pack('N', $currentSlice + $i);
            $hmac = hash_hmac('sha1', $timeBytes, $binarySecret, true);
            $offset = ord(substr($hmac, -1)) & 0x0F;
            $hashPart = substr($hmac, $offset, 4);
            $value = unpack('N', $hashPart)[1] & 0x7FFFFFFF;
            $calculatedCode = str_pad((string)($value % 1000000), 6, '0', STR_PAD_LEFT);
            
            if ($calculatedCode === $code) return true;
        }
        return false;
    }

    public static function generateSecret() {
        $secret = '';
        for ($i = 0; $i < 16; $i++) $secret .= self::$b32[random_int(0, 31)];
        return $secret;
    }
}

// Database Connection
try {
    $dbHost = getenv('MYSQLHOST') ?: 'localhost';
    $dbPort = getenv('MYSQLPORT') ?: '3306';
    $dbName = getenv('MYSQLDATABASE') ?: 'secure_attendance';
    $dbUser = getenv('MYSQLUSER') ?: 'root';
    $dbPass = getenv('MYSQLPASSWORD') ?: '';

    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die(json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]));
}

function getCurrentSystemTime(PDO $pdo) {
    try {
        $stmt = $pdo->query("SELECT is_demo_mode, demo_day, demo_time FROM demo_time_control WHERE id = 1");
        $demo = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($demo && $demo['is_demo_mode']) {
            return ['day' => $demo['demo_day'], 'time' => $demo['demo_time']];
        }
    } catch (Exception $e) {}
    return ['day' => date('l'), 'time' => date('H:i:s')];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'login':
            $username = trim(htmlspecialchars($_POST['username'] ?? ''));
            $password = $_POST['password'] ?? '';
            
            if(strlen($username) > 20 || strlen($password) > 100) throw new Exception("Input exceeds allowed length.");

            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->execute(['id' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                if (empty($user['mfa_secret']) || $user['is_mfa_verified'] == 0) {
                    $newSecret = $user['mfa_secret'];
                    if (empty($newSecret)) {
                        $newSecret = TOTP::generateSecret(); // Generate Unique Setup Key
                        $update = $pdo->prepare("UPDATE users SET mfa_secret = :sec WHERE id = :id");
                        $update->execute(['sec' => $newSecret, 'id' => $user['id']]);
                    }
                    
                    $_SESSION['temp_user'] = $user;
                    $_SESSION['temp_user']['mfa_secret'] = $newSecret;
                    echo json_encode(['status' => 'setup_mfa', 'secret' => $newSecret, 'username' => $user['id']]);
                } else {
                    $_SESSION['temp_user'] = $user;
                    echo json_encode(['status' => 'mfa_required']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid credentials.']);
            }
            break;

        case 'verify_mfa':
            $pin = trim($_POST['pin'] ?? '');
            if (isset($_SESSION['temp_user'])) {
                if (TOTP::verify($_SESSION['temp_user']['mfa_secret'], $pin)) {
                    $update = $pdo->prepare("UPDATE users SET is_mfa_verified = 1 WHERE id = :id");
                    $update->execute(['id' => $_SESSION['temp_user']['id']]);
                    
                    $_SESSION['user_id'] = $_SESSION['temp_user']['id'];
                    $_SESSION['role'] = $_SESSION['temp_user']['role'];
                    unset($_SESSION['temp_user']);
                    echo json_encode(['status' => 'success', 'role' => $_SESSION['role']]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Invalid Google Authenticator Code.']);
                }
            }
            break;

        case 'generate_token':
            if ($_SESSION['role'] !== 'lecturer') throw new Exception("Unauthorized");
            $subject_id = trim($_POST['subject_id'] ?? '');
            if (!$subject_id) throw new Exception("Subject ID is required");
            
            $stmtTime = $pdo->prepare("SELECT lecturer_id, day_of_week, start_time, end_time FROM subjects WHERE id = :sid");
            $stmtTime->execute(['sid' => $subject_id]);
            $subTime = $stmtTime->fetch(PDO::FETCH_ASSOC);
            
            if (!$subTime) throw new Exception("Subject not found");
            if ($subTime['lecturer_id'] !== $_SESSION['user_id']) throw new Exception("Unauthorized for this subject");
            
            $sysTime = getCurrentSystemTime($pdo);
            $currentDay = $sysTime['day'];
            $currentTime = $sysTime['time'];
            if ($subTime['day_of_week'] !== $currentDay || $currentTime < $subTime['start_time'] || $currentTime > $subTime['end_time']) {
                throw new Exception("Cannot generate token: Class is not currently active.");
            }
            
            $token = bin2hex(random_bytes(16));
            
            $sess = $pdo->prepare("SELECT id FROM active_sessions WHERE subject_id = :sid AND is_active = 1");
            $sess->execute(['sid' => $subject_id]);
            $active_session = $sess->fetch(PDO::FETCH_ASSOC);
            
            if ($active_session) {
                $session_id = $active_session['id'];
                $stmt = $pdo->prepare("UPDATE active_sessions SET current_qr_token = :token, token_expires_at = :expires WHERE id = :id");
                $stmt->execute(['token' => $token, 'expires' => time() + 45, 'id' => $session_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO active_sessions (subject_id, is_active, current_qr_token, token_expires_at) VALUES (:sid, 1, :token, :expires)");
                $stmt->execute(['sid' => $subject_id, 'token' => $token, 'expires' => time() + 45]);
                $session_id = $pdo->lastInsertId();
                
                $students = $pdo->prepare("SELECT student_id FROM student_subject WHERE subject_id = :sid");
                $students->execute(['sid' => $subject_id]);
                $insertAtt = $pdo->prepare("INSERT INTO attendance (session_id, student_id, status) VALUES (:sess, :stu, 'Absent')");
                while($stu = $students->fetch(PDO::FETCH_ASSOC)) {
                    $insertAtt->execute(['sess' => $session_id, 'stu' => $stu['student_id']]);
                }
            }
            echo json_encode(['status' => 'success', 'token' => $token]);
            break;

        case 'mark_attendance':
            if ($_SESSION['role'] !== 'student') throw new Exception("Unauthorized");
            $token = trim(htmlspecialchars($_POST['token'] ?? ''));

            $stmt = $pdo->prepare("SELECT id, subject_id FROM active_sessions WHERE current_qr_token = :token AND token_expires_at > :now AND is_active = 1");
            $stmt->execute(['token' => $token, 'now' => time()]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($session) {
                $checkReg = $pdo->prepare("SELECT student_id FROM student_subject WHERE subject_id = :sid AND student_id = :uid");
                $checkReg->execute(['sid' => $session['subject_id'], 'uid' => $_SESSION['user_id']]);
                if(!$checkReg->fetch()) {
                    echo json_encode(['status' => 'error', 'message' => 'Invalid attendance: you are not registered for this class.']);
                    break;
                }

                $checkAtt = $pdo->prepare("SELECT status FROM attendance WHERE session_id = :sid AND student_id = :uid");
                $checkAtt->execute(['sid' => $session['id'], 'uid' => $_SESSION['user_id']]);
                $att = $checkAtt->fetch(PDO::FETCH_ASSOC);
                
                if ($att && $att['status'] === 'Present') {
                    echo json_encode(['status' => 'error', 'message' => 'Attendance already recorded.']);
                } else {
                    if ($att) {
                        $update = $pdo->prepare("UPDATE attendance SET status = 'Present', recorded_at = CURRENT_TIMESTAMP WHERE session_id = :sid AND student_id = :uid");
                        $update->execute(['sid' => $session['id'], 'uid' => $_SESSION['user_id']]);
                    } else {
                        $insert = $pdo->prepare("INSERT INTO attendance (session_id, student_id, status) VALUES (:sid, :uid, 'Present')");
                        $insert->execute(['sid' => $session['id'], 'uid' => $_SESSION['user_id']]);
                    }
                    echo json_encode(['status' => 'success', 'message' => 'Attendance marked successfully.']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid or expired Token.']);
            }
            break;

        case 'get_history':
            if ($_SESSION['role'] !== 'student') throw new Exception("Unauthorized");
            $stmt = $pdo->prepare("SELECT a.status, a.recorded_at, sub.name as class_name FROM attendance a JOIN active_sessions s ON a.session_id = s.id JOIN subjects sub ON s.subject_id = sub.id WHERE a.student_id = :uid ORDER BY a.recorded_at DESC");
            $stmt->execute(['uid' => $_SESSION['user_id']]);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

         case 'search_student': 
            if ($_SESSION['role'] !== 'lecturer') throw new Exception("Unauthorized");
            $subject_id = $_GET['subject_id'] ?? '';
            
            $check = $pdo->prepare("SELECT id FROM subjects WHERE id = :sid AND lecturer_id = :lid");
            $check->execute(['sid' => $subject_id, 'lid' => $_SESSION['user_id']]);
            if(!$check->fetch()) throw new Exception("Unauthorized for this subject");

            $sessStmt = $pdo->prepare("SELECT id FROM active_sessions WHERE subject_id = :sid ORDER BY id DESC LIMIT 1");
            $sessStmt->execute(['sid' => $subject_id]);
            $latest_session = $sessStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$latest_session) {
                $stmt = $pdo->prepare("SELECT u.name, u.id as student_id, NULL as att_id, 'No Session' as status FROM student_subject ss JOIN users u ON ss.student_id = u.id WHERE ss.subject_id = :sid AND u.role = 'student'");
                $stmt->execute(['sid' => $subject_id]);
                echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
                break;
            }
            
            $stmt = $pdo->prepare("
                SELECT u.name, a.id as att_id, a.status, u.id as student_id 
                FROM student_subject ss 
                JOIN users u ON ss.student_id = u.id 
                LEFT JOIN attendance a ON u.id = a.student_id AND a.session_id = :sess_id
                WHERE ss.subject_id = :sid AND u.role = 'student'
            ");
            $stmt->execute(['sess_id' => $latest_session['id'], 'sid' => $subject_id]);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'get_subjects':
            if ($_SESSION['role'] !== 'lecturer') throw new Exception("Unauthorized");
            $stmt = $pdo->prepare("SELECT id, name FROM subjects WHERE lecturer_id = :uid");
            $stmt->execute(['uid' => $_SESSION['user_id']]);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'set_global_demo_time':
            if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'lecturer') throw new Exception("Unauthorized");
            
            $is_demo = 1; // force demo mode on when Apply Test Time is used
            $demo_day = trim($_POST['mock_day'] ?? $_POST['demo_day'] ?? 'Monday');
            $demo_time = trim($_POST['mock_time'] ?? $_POST['demo_time'] ?? '00:00:00');
            $uid = $_SESSION['user_id'];
            
            $checkStmt = $pdo->query("SELECT cooldown_until FROM demo_time_control WHERE id = 1");
            $cooldownRow = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if ($cooldownRow && strtotime($cooldownRow['cooldown_until']) > time()) {
                throw new Exception("Please wait 1 minute before changing the global demo time again.");
            }
            
            $newCooldown = date('Y-m-d H:i:s', time() + 60);
            
            $stmt = $pdo->prepare("UPDATE demo_time_control SET is_demo_mode = :is_demo, demo_day = :day, demo_time = :time, updated_by = :uid, cooldown_until = :cooldown WHERE id = 1");
            $stmt->execute([
                'is_demo' => $is_demo,
                'day' => $demo_day,
                'time' => $demo_time,
                'uid' => $uid,
                'cooldown' => $newCooldown
            ]);
            
            echo json_encode(['status' => 'success']);
            break;

        case 'get_all_students_enrollment':
            if ($_SESSION['role'] !== 'lecturer') throw new Exception("Unauthorized");
            $lid = $_SESSION['user_id'];
            
            $subStmt = $pdo->prepare("SELECT id, name FROM subjects WHERE lecturer_id = :lid");
            $subStmt->execute(['lid' => $lid]);
            $lecturer_subjects = $subStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stuStmt = $pdo->prepare("SELECT id, name FROM users WHERE role = 'student'");
            $stuStmt->execute();
            $all_students = $stuStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $enrollStmt = $pdo->prepare("SELECT ss.student_id, ss.subject_id, sub.name as subject_name, sub.lecturer_id FROM student_subject ss JOIN subjects sub ON ss.subject_id = sub.id");
            $enrollStmt->execute();
            $enrollments = $enrollStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'status' => 'success', 
                'students' => $all_students, 
                'enrollments' => $enrollments,
                'lecturer_subjects' => $lecturer_subjects
            ]);
            break;

        case 'manage_enrollment':
            if ($_SESSION['role'] !== 'lecturer') throw new Exception("Unauthorized");
            $action_type = $_POST['action_type'] ?? '';
            $student_id = $_POST['student_id'] ?? '';
            $subject_id = $_POST['subject_id'] ?? '';
            $old_subject_id = $_POST['old_subject_id'] ?? '';
            
            if ($action_type === 'add') {
                $checkAssigned = $pdo->prepare("SELECT student_id FROM student_subject WHERE student_id = :uid AND subject_id = :sid");
                $checkAssigned->execute(['uid' => $student_id, 'sid' => $subject_id]);
                if(!$checkAssigned->fetch()) {
                    $stmt = $pdo->prepare("INSERT INTO student_subject (student_id, subject_id) VALUES (:uid, :sid)");
                    $stmt->execute(['uid' => $student_id, 'sid' => $subject_id]);
                    
                    // Create un-started "Absent" record if subject is currently doing an Active Session
                    $sessStmt = $pdo->prepare("SELECT id FROM active_sessions WHERE subject_id = :sid AND is_active = 1 ORDER BY id DESC LIMIT 1");
                    $sessStmt->execute(['sid' => $subject_id]);
                    if($activeSess = $sessStmt->fetch(PDO::FETCH_ASSOC)) {
                        $attInsert = $pdo->prepare("INSERT IGNORE INTO attendance (session_id, student_id, status) VALUES (:sess, :uid, 'Absent')");
                        $attInsert->execute(['sess' => $activeSess['id'], 'uid' => $student_id]);
                    }
                }
            } elseif ($action_type === 'remove') {
                $stmt = $pdo->prepare("DELETE FROM student_subject WHERE student_id = :uid AND subject_id = :sid");
                $stmt->execute(['uid' => $student_id, 'sid' => $subject_id]);
            } elseif ($action_type === 'move') {
                $stmt = $pdo->prepare("DELETE FROM student_subject WHERE student_id = :uid AND subject_id = :sid");
                $stmt->execute(['uid' => $student_id, 'sid' => $old_subject_id]);
                
                $checkAssigned = $pdo->prepare("SELECT student_id FROM student_subject WHERE student_id = :uid AND subject_id = :sid");
                $checkAssigned->execute(['uid' => $student_id, 'sid' => $subject_id]);
                if(!$checkAssigned->fetch()) {
                    $insert = $pdo->prepare("INSERT INTO student_subject (student_id, subject_id) VALUES (:uid, :sid)");
                    $insert->execute(['uid' => $student_id, 'sid' => $subject_id]);
                    
                    // Create un-started "Absent" record if target subject is currently doing an Active Session
                    $sessStmt = $pdo->prepare("SELECT id FROM active_sessions WHERE subject_id = :sid AND is_active = 1 ORDER BY id DESC LIMIT 1");
                    $sessStmt->execute(['sid' => $subject_id]);
                    if($activeSess = $sessStmt->fetch(PDO::FETCH_ASSOC)) {
                        $attInsert = $pdo->prepare("INSERT IGNORE INTO attendance (session_id, student_id, status) VALUES (:sess, :uid, 'Absent')");
                        $attInsert->execute(['sess' => $activeSess['id'], 'uid' => $student_id]);
                    }
                }
            }
            echo json_encode(['status' => 'success', 'message' => 'Enrollment updated successfully.']);
            break;

        case 'get_timetable':
            $role = $_SESSION['role'] ?? '';
            $uid = $_SESSION['user_id'] ?? '';
            if (!$role) throw new Exception("Unauthorized");
            
            if ($role === 'lecturer') {
                $stmt = $pdo->prepare("SELECT id, name, day_of_week, start_time, end_time FROM subjects WHERE lecturer_id = :uid ORDER BY field(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time");
                $stmt->execute(['uid' => $uid]);
            } else {
                $stmt = $pdo->prepare("SELECT s.id, s.name, s.day_of_week, s.start_time, s.end_time FROM subjects s JOIN student_subject ss ON s.id = ss.subject_id WHERE ss.student_id = :uid ORDER BY field(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), s.start_time");
                $stmt->execute(['uid' => $uid]);
            }
            // Add server time for sync (dynamically use global demo time variables)
            $sysTime = getCurrentSystemTime($pdo);
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'server_day' => $sysTime['day'], 'server_time' => $sysTime['time']]);
            break;

        // Assign student case deleted since integrated into manage_enrollment

        case 'update_attendance':
            if ($_SESSION['role'] !== 'lecturer') throw new Exception("Unauthorized");
            $att_id = (int)$_POST['att_id'];
            $new_status = trim(htmlspecialchars($_POST['status'] ?? ''));

            $getOld = $pdo->prepare("SELECT student_id, status FROM attendance WHERE id = :id");
            $getOld->execute(['id' => $att_id]);
            $oldData = $getOld->fetch(PDO::FETCH_ASSOC);

            if ($oldData) {
                $update = $pdo->prepare("UPDATE attendance SET status = :status WHERE id = :id");
                $update->execute(['status' => $new_status, 'id' => $att_id]);

                $audit = $pdo->prepare("INSERT INTO audit_logs (modified_by, student_affected, old_status, new_status) VALUES (:mod_by, :student, :old, :new)");
                $audit->execute(['mod_by' => $_SESSION['user_id'], 'student' => $oldData['student_id'], 'old' => $oldData['status'], 'new' => $new_status]);
                echo json_encode(['status' => 'success', 'message' => 'Record updated and securely logged.']);
            }
            break;

        case 'logout':
            session_destroy();
            echo json_encode(['status' => 'success']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'An internal error occurred.']);
}
?>
