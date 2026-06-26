// api/github/analyze.php
$repo_url = $_POST['repo'] ?? ''; // مثلاً https://github.com/user/repo

// دریافت ساختار مخزن
$ch = curl_init("https://api.github.com/repos/{$owner}/{$repo}/contents");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: GolestanNet']);
$files = json_decode(curl_exec($ch), true);

// تحلیل با AI
$code_summary = '';
foreach (array_slice($files, 0, 10) as $file) {
    if ($file['type'] == 'file') {
        $code = file_get_contents($file['download_url']);
        $code_summary .= "File: {$file['name']}\n" . substr($code, 0, 500) . "\n\n";
    }
}

require_once 'api/chat/DeepSeekAPI.php';
$ai = new DeepSeekAPI();
$response = $ai->sendMessage("Analyze this codebase:\n$code_summary\n\nGive a summary of the project, main technologies used, and suggestions for improvement.");

echo json_encode(['analysis' => $response['content']]);