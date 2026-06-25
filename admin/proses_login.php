<?php
// admin/proses_login.php (Versi Final Single-Role)
session_start();
require_once '../core/database.php';

function javascript_redirect($url) {
    echo "<script>window.location.href = '{$url}';</script>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (empty($username) || empty($password) || empty($role)) {
        javascript_redirect("index.php?error=emptyfields");
    }

    try {
        $sql = "SELECT * FROM pegawai WHERE username = ? AND jabatan = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username, $role]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id_pegawai'];
            $_SESSION['username'] = $user['nama_pegawai'];
            $_SESSION['role'] = $user['jabatan'];

            $redirect_file = '';
            switch ($user['jabatan']) {
                case 'koki':
                    $redirect_file = "dapur_koki.php";
                    break;
                case 'pelayan':
                    $redirect_file = "pelayan_dashboard.php";
                    break;
                case 'kasir':
                    $redirect_file = "kasir_dashboard.php";
                    break;
                case 'owner':
                case 'admin':
                    // DIARAHKAN KE DASBOR OWNER BARU
                    $redirect_file = "owner_dashboard.php"; 
                    break;
                default:
                    $redirect_file = "index.php?error=unknownrole";
                    break;
            }
            javascript_redirect($redirect_file);

        } else {
            javascript_redirect("index.php?error=wrongcredentials");
        }
    } catch (PDOException $e) {
        javascript_redirect("index.php?error=dberror");
    }
} else {
    javascript_redirect("index.php");
}
?>
