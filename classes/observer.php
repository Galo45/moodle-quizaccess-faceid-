<?php
namespace quizaccess_faceid;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer for profile verification
 */
class observer {

    /**
     * Observer for user updated event
     * Resets profile verification when profile picture is updated
     *
     * @param \core\event\user_updated $event
     */
    public static function user_updated(\core\event\user_updated $event) {
        global $DB;
        
        $userid = $event->objectid;
        $eventdata = $event->get_data();
        
        // Check if this is a profile picture update
        // Moodle triggers user_updated when profile picture is changed
        if (self::is_profile_picture_update($eventdata)) {
            $profilehelper = new profile_helper();
            $profilehelper->reset_profile_verification($userid);
            
            // Log the reset for debugging/auditing
            error_log("FaceID: Profile verification reset for user {$userid} due to profile picture update");
        }
    }
    
    /**
     * Determine if the user update event is related to profile picture change
     *
     * @param array $eventdata Event data
     * @return bool True if profile picture was updated
     */
    private static function is_profile_picture_update($eventdata) {
        // In Moodle, when a profile picture is updated, we can check different indicators:
        // 1. The event context is often user context
        // 2. The relateduserid might be set
        // 3. We can also check if picture field was modified
        
        // For a more robust detection, we could also compare timestamps
        // of when the event occurred vs. last profile verification
        
        // A simple approach is to assume any user_updated event in user context
        // could potentially be a profile picture update, so we reset verification
        // This is safe as it only means the user needs to verify again
        
        if (isset($eventdata['contextlevel']) && $eventdata['contextlevel'] == CONTEXT_USER) {
            return true;
        }
        
        return false;
    }
}