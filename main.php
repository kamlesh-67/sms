<?php
session_start();

// Define constants for application configuration
define('BASE_URL', '/SMS/');
define('PUBLIC_PAGES', ['index.html', 'login.php']); 

class MainController {
    /**
     * Check if user is currently logged in
     * Returns true if user has valid session, false otherwise
     */
    private function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    /**
     * Check if requested page is in public pages list
     * Returns true if page is public, false if protected
     */
    private function isPublicPage($page) {
        return in_array($page, PUBLIC_PAGES);
    }

    /**
     * Handle page redirections
     * Redirects user to specified page and exits script
     */
    private function redirectTo($page) {
        header("Location: " . BASE_URL . $page);
        exit();
    }

    /**
     * Get current page from URL
     * Parses URL and returns current page name
     */
    private function getCurrentPage() {
        $uri = $_SERVER['REQUEST_URI'];
        $path = parse_url($uri, PHP_URL_PATH);
        return str_replace(BASE_URL, '', $path);
    }

    /**
     * Main request handler method
     * Implements following functionalities:
     * 1. Root URL redirection
     * 2. Authentication check
     * 3. Session management
     * 4. Security headers
     * 5. Secure cookie handling
     * 6. Session fixation prevention
     * 7. Logout handling
     * 8. Password change protection
     */
    public function handleRequest() {
        $currentPage = $this->getCurrentPage();

        // 1. Root URL redirection
        if ($currentPage == '' || $currentPage == '/' || $currentPage == 'SMS/') {
            if ($this->isLoggedIn()) {
                $this->redirectTo('dashboard.php');
            } else {
                $this->redirectTo('login.php');
            }
        }

        // 2. Authentication check for protected pages
        if (!$this->isPublicPage($currentPage) && !$this->isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $currentPage;
            $this->redirectTo('login.php');
        }

        // 3. Logout handling
        if ($currentPage == 'logout.php') {
            session_destroy();
            $this->redirectTo('login.php');
        }

        // 4. Password change protection
        if ($currentPage == 'change_password.php' && !$this->isLoggedIn()) {
            $this->redirectTo('login.php');
        }

        // 5. Security headers implementation
        header("X-XSS-Protection: 1; mode=block");
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: SAMEORIGIN");
        header("Content-Security-Policy: default-src 'self' https: 'unsafe-inline' 'unsafe-eval'");

        // 6. Secure cookie configuration
        if (session_id()) {
            $params = session_get_cookie_params();
            setcookie(session_name(), session_id(), [
                'expires' => time() + $params['lifetime'],
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }

        // 7. Session fixation prevention
        if (!isset($_SESSION['last_regeneration'])) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        } else if (time() - $_SESSION['last_regeneration'] > 3600) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
}

// Initialize and run the controller
$controller = new MainController();
$controller->handleRequest();

/*
Implemented Functionalities Summary:
1. Session Management
2. Authentication System
3. Page Access Control
4. Security Headers
5. Secure Cookie Handling
6. Session Fixation Prevention
7. URL Routing
8. Automatic Redirections
9. Password Change Protection
10. Logout Handling
*/
?>


