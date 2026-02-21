<?php
require_once 'config.php';
require_once 'qwen_api.php';

$generated_story = "";
$error = "";
$history = [];
$tokens_used = 0;

// Fetch History from Database
$stmt = $pdo->query("SELECT * FROM stories ORDER BY created_at DESC LIMIT 10");
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['prompt'])) {
    $prompt = trim($_POST['prompt']);
    
    // Initialize Qwen API
    $qwen = new QwenAPI(DASHSCOPE_API_KEY, QWEN_MODEL);
    
    // Generate Story
    $result = $qwen->generateStory($prompt);
    
    if ($result['success']) {
        $generated_story = $result['story'];
        $tokens_used = $result['tokens'];
        
        // Save to Database
        $insertStmt = $pdo->prepare("
            INSERT INTO stories (prompt, story, model_used, tokens_used) 
            VALUES (:prompt, :story, :model, :tokens)
        ");
        $insertStmt->execute([
            ':prompt' => $prompt,
            ':story' => $generated_story,
            ':model' => $result['model'],
            ':tokens' => $tokens_used
        ]);
        
        // Refresh history
        $stmt = $pdo->query("SELECT * FROM stories ORDER BY created_at DESC LIMIT 10");
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $error = $result['error'];
    }
}

// Calculate total tokens used
$totalTokens = array_sum(array_column($history, 'tokens_used'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Qwen AI Story Teller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .story-box { 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .history-item { 
            border-left: 4px solid #6f42c1; 
            background: white; 
            margin-bottom: 10px; 
            padding: 15px;
        }
        .token-badge {
            background: #6f42c1;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
        }
    </style>
</head>
<body>

<div class="container py-5">
    <h1 class="text-center mb-4">🐉 Qwen AI Story Teller</h1>
    
    <!-- Token Usage Stats -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="alert alert-info d-flex justify-content-between align-items-center">
                <span><strong>Model:</strong> <?php echo QWEN_MODEL; ?></span>
                <span><strong>Total Tokens (Session):</strong> <span class="token-badge"><?php echo number_format($totalTokens); ?></span></span>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Input Form -->
        <div class="col-md-5">
            <div class="story-box">
                <h4>Generate New Story</h4>
                <form method="POST">
                    <div class="mb-3">
                        <label for="prompt" class="form-label">Story Prompt</label>
                        <textarea class="form-control" id="prompt" name="prompt" rows="4" 
                            placeholder="e.g., A dragon who loves to cook..." 
                            required><?php echo htmlspecialchars($_POST['prompt'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <span>✨ Generate with Qwen</span>
                    </button>
                </form>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger mt-3">
                        <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <div class="mt-3">
                    <small class="text-muted">
                        💡 Tip: Qwen-Turbo offers free tier for new users (1M tokens)
                    </small>
                </div>
            </div>
        </div>

        <!-- Generated Story Display -->
        <div class="col-md-7">
            <?php if ($generated_story): ?>
                <div class="story-box border-success">
                    <h4 class="text-success">📖 Latest Story</h4>
                    <p class="text-muted">
                        <strong>Prompt:</strong> <?php echo htmlspecialchars($prompt); ?><br>
                        <strong>Tokens Used:</strong> <span class="token-badge"><?php echo number_format($tokens_used); ?></span>
                    </p>
                    <hr>
                    <div style="white-space: pre-wrap; line-height: 1.6;">
                        <?php echo nl2br(htmlspecialchars($generated_story)); ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="story-box">
                    <div class="alert alert-info">
                        Enter a prompt above to generate a story with Qwen AI.
                    </div>
                </div>
            <?php endif; ?>

            <!-- History Section -->
            <h4 class="mt-4">📚 Recent Stories</h4>
            <div class="mt-3">
                <?php if (count($history) > 0): ?>
                    <?php foreach ($history as $item): ?>
                        <div class="history-item">
                            <div class="d-flex justify-content-between">
                                <small class="text-muted"><?php echo $item['created_at']; ?></small>
                                <span class="token-badge"><?php echo number_format($item['tokens_used']); ?> tokens</span>
                            </div>
                            <p class="mb-1"><strong>Prompt:</strong> <?php echo htmlspecialchars(substr($item['prompt'], 0, 60)) . '...'; ?></p>
                            <p class="mb-0 text-muted text-truncate">
                                <?php echo htmlspecialchars(substr($item['story'], 0, 120)) . '...'; ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No stories generated yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>