<?php
/**
 * Minimal web page: upload a photo, get the cut-out back in the browser.
 *   php -S localhost:8080 examples/06-web-upload-form.php
 * Never expose your API key in client-side code – keep calls server-side like this.
 */
require_once __DIR__ . '/../src/autoload.php';
$apiKey = getenv('BGCUT_API_KEY') ?: 'bgc_your_api_key_here';

$preview = $error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['photo']['tmp_name'])) {
    try {
        $bg = new BgCut\Client($apiKey);
        $result = $bg->removeFile($_FILES['photo']['tmp_name'], ['size' => 'preview', 'format' => 'png']);
        $preview = $result->dataUri();
    } catch (BgCut\ApiException $e) {
        $error = $e->getMessage();
    }
}
?>
<!doctype html>
<meta charset="utf-8"><title>Background remover demo</title>
<style>body{font-family:system-ui;max-width:720px;margin:40px auto;padding:0 16px}img{max-width:100%;background:
repeating-conic-gradient(#eee 0 25%,#fff 0 50%) 0 0/24px 24px;border-radius:8px}.err{color:#D62828}</style>
<h1>Remove background</h1>
<form method="post" enctype="multipart/form-data">
  <input type="file" name="photo" accept="image/*" required> <button>Remove background</button>
</form>
<?php if ($error): ?><p class="err"><?= htmlspecialchars($error) ?></p><?php endif; ?>
<?php if ($preview): ?><p><img src="<?= $preview ?>" alt="Result with transparent background"></p><?php endif; ?>
<p><small>Powered by <a href="https://bgcut.net">bgCut.net</a></small></p>
