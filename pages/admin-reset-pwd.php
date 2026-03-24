<?php
include_once '../includes/config.php';
include_once '../includes/lang.php';

// Auth Protection (ADMIN ONLY)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: /login');
    exit;
}

// Fetch user data again to verify admin status (SlapIA company check)
$notionApiKey = config('NOTION_API_KEY');
$userId = $_SESSION['user_id'];
$ch = curl_init('https://api.notion.com/v1/pages/' . $userId);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $notionApiKey,
        'Notion-Version: 2022-06-28'
    ]
]);
$response = curl_exec($ch);
$userPage = json_decode($response, true);
$props = $userPage['properties'] ?? [];
$company = $props['Nom d\'entreprise']['rich_text'][0]['text']['content'] ?? '';

if ($company !== 'SlapIA') {
    header('Location: /dashboard');
    exit;
}

$targetEmail = $_GET['email'] ?? '';
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

$page_title = 'Reset Password - Admin - SlapIA';
include '../includes/header.php';
?>

<main class="main-content pt-5 mt-5">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <!-- Back to dashboard -->
                <a href="/dashboard" class="btn btn-link text-secondary text-decoration-none mb-4 p-0 d-inline-flex align-items-center">
                    <i class="fas fa-arrow-left me-2"></i> Dashboard Admin
                </a>

                <div class="bento-card p-4 p-md-5" style="background: rgba(15, 15, 15, 0.4); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 24px; backdrop-filter: blur(20px);">
                    <div class="text-center mb-5">
                        <div class="rounded-circle bg-danger bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px; border: 1px solid rgba(239, 68, 68, 0.2);">
                            <i class="fas fa-key text-danger fa-2x"></i>
                        </div>
                        <h1 class="h3 text-white fw-bold">Réinitialisation Admin</h1>
                        <p class="text-secondary small">Modifier le mot de passe d'un utilisateur SlapIA</p>
                    </div>

                    <?php if ($success): ?>
                        <div class="alert alert-success bg-success bg-opacity-10 border-success border-opacity-20 text-success rounded-4 p-3 mb-4 d-flex align-items-center">
                            <i class="fas fa-check-circle me-3"></i>
                            <div><?php echo htmlspecialchars($success); ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger bg-danger bg-opacity-10 border-danger border-opacity-20 text-danger rounded-4 p-3 mb-4 d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle me-3"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="/api/admin-reset-pwd-exec.php" method="POST" id="reset-form">
                        <div class="mb-4">
                            <label class="form-label text-white small fw-bold text-uppercase opacity-50 mb-2">Email de l'utilisateur</label>
                            <input type="email" name="email" class="form-control bg-white bg-opacity-5 border-white border-opacity-10 text-white rounded-3 py-3 px-4" 
                                   placeholder="exemple@slapia.com" value="<?php echo htmlspecialchars($targetEmail); ?>" required>
                        </div>

                        <div class="mb-5">
                            <label class="form-label text-white small fw-bold text-uppercase opacity-50 mb-2">Nouveau Mot de Passe</label>
                            <div class="input-group">
                                <input type="text" name="new_password" id="new-password" class="form-control bg-white bg-opacity-5 border-white border-opacity-10 text-white rounded-start-3 py-3 px-4" 
                                       placeholder="••••••••" required>
                                <button type="button" class="btn btn-outline-glass border-white border-opacity-10 px-3" onclick="generatePass()">
                                    <i class="fas fa-random"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-danger w-100 py-3 rounded-pill fw-bold text-uppercase tracking-wider shadow-lg">
                            Mettre à jour le mot de passe
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function generatePass() {
    const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
    let pass = "";
    for (let i = 0; i < 12; i++) {
        pass += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('new-password').value = pass;
}

// Sparkle effect or simple aesthetic enhancement
if (window.Swup) {
    // Handling Swup re-init if needed
}
</script>

<?php include '../includes/footer.php'; ?>
