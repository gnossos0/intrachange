<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

try {
    require_once __DIR__ . '/backend/db_connect.php';
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $display_name = trim($_POST['display_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $signature = trim($_POST['signature'] ?? '');
    
    // Validate input
    if (empty($display_name)) {
        $error = 'Display name is required.';
    } elseif (strlen($display_name) > 50) {
        $error = 'Display name must be 50 characters or less.';
    } elseif (strlen($signature) > 100) {
        $error = 'Quip must be 100 characters or less.';
    } else {
        // Handle file upload
        $avatar_path = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/avatars/';
            
            // Create directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_info = getimagesize($_FILES['avatar']['tmp_name']);
            if ($file_info === false) {
                $error = 'Please upload a valid image file.';
            } else {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($file_info['mime'], $allowed_types)) {
                    $error = 'Please upload a JPEG, PNG, or GIF image.';
                } elseif ($_FILES['avatar']['size'] > 5 * 1024 * 1024) { // 5MB limit
                    $error = 'Image file must be smaller than 5MB.';
                } else {
                    // Generate unique filename
                    $extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                    $filename = 'avatar_' . $user_id . '_' . time() . '.' . $extension;
                    $avatar_path = $upload_dir . $filename;
                    
                    if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $avatar_path)) {
                        $error = 'Failed to upload image. Please try again.';
                        $avatar_path = null;
                    }
                }
            }
        }
        
        // If no errors, update the user
        if (empty($error)) {
            if ($avatar_path) {
                // Update with new avatar
                $stmt = $conn->prepare("UPDATE users SET display_name = ?, bio = ?, signature = ?, avatar = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->bind_param("ssssi", $display_name, $bio, $signature, $avatar_path, $user_id);
            } else {
                // Update without changing avatar
                $stmt = $conn->prepare("UPDATE users SET display_name = ?, bio = ?, signature = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->bind_param("sssi", $display_name, $bio, $signature, $user_id);
            }
            
            if ($stmt->execute()) {
                $message = 'Profile updated successfully!';
            } else {
                $error = 'Failed to update profile. Please try again.';
                // Clean up uploaded file if database update failed
                if ($avatar_path && file_exists($avatar_path)) {
                    unlink($avatar_path);
                }
            }
            $stmt->close();
        }
    }
}

// Get current user data
$stmt = $conn->prepare("SELECT username, email, display_name, bio, signature, avatar, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Intrachange</title>
    <link rel="icon" type="image/jpeg" href="img/icon.jpg">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Georgia, serif;
            background: linear-gradient(135deg, #f4f1e8 0%, #e8e0d0 100%);
            min-height: 100vh;
            color: #4a4a4a;
            line-height: 1.6;
        }

        h1 {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 2rem;
            text-align: center;
            font-size: 2.5rem;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #2c3e50;
        }

        input[type="text"],
        textarea,
        input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            font-family: Georgia, serif;
        }

        input[type="text"]:focus,
        textarea:focus {
            outline: none;
            border-color: #3498db;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .readonly {
            background-color: #f8f9fa;
            color: #6c757d;
            cursor: not-allowed;
        }

        .btn {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: linear-gradient(135deg, #2980b9, #1f6391);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #7f8c8d, #6c7b7d);
        }

        .btn-outline {
            background: transparent;
            color: #3498db;
            border: 2px solid #3498db;
        }

        .btn-outline:hover {
            background: #3498db;
            color: white;
        }

        .message {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .help-text {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }

        .avatar-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 10px;
            margin-top: 10px;
            border: 2px solid #ddd;
        }

        .current-avatar {
            margin-bottom: 1rem;
        }

        .form-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .back-link {
            text-align: center;
            margin-top: 2rem;
        }

        .back-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: bold;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .character-count {
            font-size: 0.8rem;
            color: #6c757d;
            text-align: right;
            margin-top: 0.25rem;
        }

        @media (max-width: 600px) {
            .form-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 200px;
                margin-right: 0;
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit Profile</h1>
        
        <?php if (!empty($message)): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly class="readonly">
                    <div class="help-text">Username cannot be changed</div>
                </div>

                <div class="form-group">
                    <label for="display_name">Display Name</label>
                    <input type="text" id="display_name" name="display_name" value="<?php echo htmlspecialchars($user['display_name'] ?? $user['username']); ?>" required maxlength="50">
                    <div class="help-text">This is how your name appears to other players (max 50 characters)</div>
                </div>

                <div class="form-group">
                    <label for="bio">Something About Me</label>
                    <textarea id="bio" name="bio" placeholder="Tell other players about yourself, your chess experience, interests, or anything you'd like to share..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    <div class="help-text">Share your chess background, interests, or anything you'd like other players to know</div>
                </div>

                <div class="form-group">
                    <label for="signature">Quip Space</label>
                    <input type="text" id="signature" name="signature" value="<?php echo htmlspecialchars($user['signature'] ?? ''); ?>" maxlength="100" placeholder="A witty saying, favorite quote, or personal motto...">
                    <div class="character-count">
                        <span id="sig-count"><?php echo strlen($user['signature'] ?? ''); ?></span>/100 characters
                    </div>
                    <div class="help-text">A short phrase that appears with your posts and profile</div>
                </div>

                <div class="form-group">
                    <label for="avatar">Profile Picture</label>
                    <?php if (!empty($user['avatar']) && file_exists($user['avatar'])): ?>
                        <div class="current-avatar">
                            <p><strong>Current Avatar:</strong></p>
                            <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Current avatar" class="avatar-preview">
                        </div>
                    <?php endif; ?>
                    <input type="file" id="avatar" name="avatar" accept="image/*">
                    <div class="help-text">Upload a new profile picture. Recommended size: 200x200 pixels. Max file size: 5MB. Supports JPEG, PNG, and GIF formats.</div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">Update Profile</button>
                    <a href="change_password.php" class="btn btn-outline">Change Password</a>
                    <a href="player_profile.php?id=<?php echo $user_id; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>

        <div class="back-link">
            <a href="player_profile.php?id=<?php echo $user_id; ?>">← Back to Profile</a>
        </div>
    </div>

    <script>
        // Character counter for signature field
        document.getElementById('signature').addEventListener('input', function() {
            const count = this.value.length;
            document.getElementById('sig-count').textContent = count;
            
            // Change color when approaching limit
            const counter = document.getElementById('sig-count');
            if (count > 90) {
                counter.style.color = '#e74c3c';
            } else if (count > 75) {
                counter.style.color = '#f39c12';
            } else {
                counter.style.color = '#6c757d';
            }
        });

        // Preview uploaded image
        document.getElementById('avatar').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Remove existing preview if any
                    const existingPreview = document.querySelector('.upload-preview');
                    if (existingPreview) {
                        existingPreview.remove();
                    }
                    
                    // Create new preview
                    const preview = document.createElement('div');
                    preview.className = 'upload-preview';
                    preview.style.marginTop = '10px';
                    preview.innerHTML = '<p><strong>New Upload Preview:</strong></p><img src="' + e.target.result + '" alt="Upload preview" class="avatar-preview">';
                    
                    // Insert after the file input
                    document.getElementById('avatar').parentNode.appendChild(preview);
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>