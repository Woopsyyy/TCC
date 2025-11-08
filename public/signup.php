<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/signup.css">
    <title>Create an Account</title>
</head>
<body>
    <div class="main-container">
        <div class="user-card-preview" id="userCardPreview">
            <div class="profile-circle">
                <div class="profile-image" id="cardImage"></div>
            </div>
            <h2 id="cardName">Your Name</h2>
        </div>

        <div class="signup-container">
            <h2>Create Your Account</h2>
        <form action="../BackEnd/auth/signup.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <input type="text" id="name" class="form-control" name="name" placeholder="Full Name" required>
            </div>
            
            <div class="form-group">
                <input type="text" id="username" class="form-control" name="username" placeholder="Username" required>
            </div>
            
            <div class="form-group">
                <input type="password" id="password" class="form-control" name="password" placeholder="Password" required>
            </div>
            
            <div class="file-input-wrapper">
                <label for="profileImage" class="file-input-label">Choose Profile Picture</label>
                <input type="file" id="profileImage" name="profileImage" accept="image/*" style="display: none;">
            </div>
            
            <button type="submit" class="submit-btn">Create Account</button>
        </form>
        
        <div class="login-link">
            <p>Already have an account? <a href="index.html">Login here</a></p>
        </div>
    </div>

    <script>
        const nameInput = document.getElementById('name');
        const profileImage = document.getElementById('profileImage');
        const profileCircle = document.querySelector('.profile-circle');
        const cardImage = document.getElementById('cardImage');
        const cardName = document.getElementById('cardName');

        // Update preview when name changes
        nameInput.addEventListener('input', function(e) {
            const value = e.target.value.trim();
            cardName.textContent = value || 'Your Name';
            if (value) {
                cardName.classList.add('visible');
            } else {
                cardName.classList.remove('visible');
            }
        });

        // Preview image before upload
        profileImage.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imageUrl = e.target.result;
                    
                    // Update card preview
                    profileCircle.classList.add('has-image');
                    cardImage.style.backgroundImage = `url(${imageUrl})`;
                    cardImage.classList.add('visible');
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>