/**
 * Login Page JavaScript
 */

$(document).ready(function() {
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        
        const username = $('#username').val().trim();
        const password = $('#password').val();
        
        if (!username || !password) {
            showError('Please enter both username and password');
            return;
        }
        
        // Disable submit button
        const $submitBtn = $(this).find('button[type="submit"]');
        $submitBtn.prop('disabled', true).text('Logging in...');
        
        $.ajax({
            url: 'backend/api/auth.php',
            method: 'POST',
            data: {
                action: 'login',
                username: username,
                password: password
            },
            success: function(response) {
                if (response.success) {
                    // Redirect based on role
                    const role = response.data.role;
                    if (role === 'professor') {
                        window.location.href = 'professor/home.php';
                    } else if (role === 'student') {
                        window.location.href = 'student/home.php';
                    } else if (role === 'administrator') {
                        window.location.href = 'admin/home.php';
                    } else {
                        window.location.href = 'index.php';
                    }
                } else {
                    showError(response.message || 'Login failed');
                    $submitBtn.prop('disabled', false).text('Login');
                }
            },
            error: function(xhr) {
                let errorMsg = 'An error occurred. Please try again.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMsg = response.message || errorMsg;
                } catch (e) {
                    // Use default error message
                }
                showError(errorMsg);
                $submitBtn.prop('disabled', false).text('Login');
            }
        });
    });
    
    function showError(message) {
        const $error = $('#loginError');
        $error.text(message).addClass('active');
        setTimeout(() => {
            $error.removeClass('active');
        }, 5000);
    }
});

