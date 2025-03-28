<?php
class SettingServices {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function updateNotificationSettings($userId, $settings) {
        $emailNotif = $settings['email_notifications'] ? 1 : 0;
        $smsNotif = $settings['sms_notifications'] ? 1 : 0;
        $query = "UPDATE user_settings 
                SET email_notifications = $emailNotif,
                    sms_notifications = $smsNotif
                WHERE user_id = $userId";
        return $this->db->execute($query);
    }

    public function updateAppearanceSettings($userId, $settings) {
        $theme = $this->db->escapeString($settings['theme']);
        $fontSize = $this->db->escapeString($settings['font_size']);
        $query = "UPDATE user_settings 
                SET theme = '$theme',
                    font_size = '$fontSize'
                WHERE user_id = $userId";
        return $this->db->execute($query);
    }

    public function updatePrivacySettings($userId, $settings) {
        $profileVisibility = $this->db->escapeString($settings['profile_visibility']);
        $showEmail = $settings['show_email'] ? 1 : 0;
        $showPhone = $settings['show_phone'] ? 1 : 0;
        $query = "UPDATE user_settings 
                SET profile_visibility = '$profileVisibility',
                    show_email = $showEmail,
                    show_phone = $showPhone
                WHERE user_id = $userId";
        return $this->db->execute($query);
    }

    public function getSettings($userId) {
        $query = "SELECT * FROM user_settings WHERE user_id = $userId";
        return $this->db->select($query)->fetch_assoc();
    }

    public function initializeSettings($userId) {
        $query = "INSERT INTO user_settings (
                    user_id, 
                    email_notifications,
                    sms_notifications,
                    theme,
                    font_size,
                    profile_visibility,
                    show_email,
                    show_phone
                ) VALUES (
                    $userId,
                    1, 
                    1,
                    'light',
                    'medium',
                    'public',
                    1,
                    1
                )";
        return $this->db->execute($query);
    }
}

// Example usage:
/*
$settingServices = new SettingServices();

// Get user settings
$settings = $settingServices->getSettings(1);

// Update notification settings
$notifSettings = [
    'email_notifications' => true,
    'sms_notifications' => false
];
$settingServices->updateNotificationSettings(1, $notifSettings);

// Update appearance settings
$appearanceSettings = [
    'theme' => 'dark',
    'font_size' => 'large'
];
$settingServices->updateAppearanceSettings(1, $appearanceSettings);

// Update privacy settings
$privacySettings = [
    'profile_visibility' => 'private',
    'show_email' => false,
    'show_phone' => true
];
$settingServices->updatePrivacySettings(1, $privacySettings);

// Initialize settings for new user
$settingServices->initializeSettings(1);
*/
?>
