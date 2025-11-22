<?php
defined('MOODLE_INTERNAL') || die();

use mod_quiz\local\access_rule_base;

class quizaccess_faceid extends access_rule_base {

    public static function make(\mod_quiz\quiz_settings $quizobj, $timenow, $canignoretimelimits) {
        global $DB;
        $rec = $DB->get_record('quizaccess_faceid', ['quizid' => $quizobj->get_quiz()->id]);
        return ($rec && $rec->enabled) ? new self($quizobj, $timenow) : null;
    }

    public static function add_settings_form_fields(mod_quiz_mod_form $quizform, \MoodleQuickForm $mform) {
        // Create radio buttons for mutually exclusive options
        $radioarray = array();
        $radioarray[] = $mform->createElement('radio', 'faceid_verification_type', '', 
            get_string('faceidenabled', 'quizaccess_faceid'), 'basic');
        $radioarray[] = $mform->createElement('radio', 'faceid_verification_type', '', 
            get_string('faceid_with_profile', 'quizaccess_faceid'), 'with_profile');
        $radioarray[] = $mform->createElement('radio', 'faceid_verification_type', '', 
            get_string('disabled', 'quizaccess_faceid'), 'disabled');
        
        $mform->addGroup($radioarray, 'faceid_verification_array', 
            get_string('verification_mode', 'quizaccess_faceid'), array('<br/>'), false);
        $mform->addHelpButton('faceid_verification_array', 'verification_mode', 'quizaccess_faceid');
        $mform->setDefault('faceid_verification_type', 'disabled');
        
        // Add JavaScript to handle mutual exclusion
        global $PAGE;
        $PAGE->requires->js_amd_inline("
            require(['jquery'], function($) {
                $(document).ready(function() {
                    // Handle radio button changes
                    $('input[name=\"faceid_verification_type\"]').change(function() {
                        var selectedValue = $(this).val();
                        console.log('FaceID verification type changed to:', selectedValue);
                    });
                });
            });
        ");
    }

    public static function save_settings($quiz) {
        global $DB;
        
        // Get verification type from radio buttons
        $verification_type = isset($quiz->faceid_verification_type) ? $quiz->faceid_verification_type : 'disabled';
        $enabled = ($verification_type !== 'disabled') ? 1 : 0;

        $record = (object)[
            'quizid' => $quiz->id,
            'enabled' => $enabled,
            'verification_type' => $verification_type
        ];

        if ($DB->record_exists('quizaccess_faceid', ['quizid' => $quiz->id])) {
            $record->id = $DB->get_field('quizaccess_faceid', 'id', ['quizid' => $quiz->id]);
            $DB->update_record('quizaccess_faceid', $record);
        } else {
            $DB->insert_record('quizaccess_faceid', $record);
        }
    }

    public static function get_extra_settings($quizid) {
        global $DB;
        $rec = $DB->get_record('quizaccess_faceid', ['quizid' => $quizid]);
        if ($rec) {
            return [
                'faceid_enabled' => (int)$rec->enabled,
                'faceid_verification_type' => $rec->verification_type ?? 'disabled'
            ];
        }
        return ['faceid_verification_type' => 'disabled'];
    }

    public static function is_enabled($quiz) {
        global $DB;
        
        // Check if faceid is enabled based on verification type
        if (isset($quiz->faceid_verification_type)) {
            return $quiz->faceid_verification_type !== 'disabled';
        }
        
        // Fallback: check database record
        $rec = $DB->get_record('quizaccess_faceid', ['quizid' => $quiz->id]);
        return $rec && $rec->enabled && ($rec->verification_type ?? 'disabled') !== 'disabled';
    }
    
    public static function get_verification_type($quiz) {
        global $DB;
        
        if (isset($quiz->faceid_verification_type)) {
            return $quiz->faceid_verification_type;
        }
        
        $rec = $DB->get_record('quizaccess_faceid', ['quizid' => $quiz->id]);
        return $rec ? ($rec->verification_type ?? 'basic') : 'disabled';
    }

    // CLAVE: Requerir verificación para nuevos intentos Y para continuar intentos existentes
    public function is_preflight_check_required($attemptid) {
        global $USER;
        
        // Siempre requerir verificación facial, pero verificar si ya se hizo en esta sesión
        if ($attemptid === null) {
            // Nuevo intento - siempre requerir verificación
            return true;
        } else {
            // Continuar intento existente - verificar si ya se verificó en esta sesión
            return !$this->is_face_verified_for_session($this->quiz->id, $USER->id);
        }
    }

    public function prevent_new_attempt($numprevattempts, $lastattempt) {
        return false;
    }

    // NUEVO: Verificar si ya se hizo la verificación facial para este usuario, quiz y sesión
    private function is_face_verified_for_session($quizid, $userid) {
        global $DB, $SESSION;
        
        // Verificar en la sesión si ya se verificó para este quiz
        $session_key = "faceid_verified_{$quizid}_{$userid}";
        
        // Verificar si existe y si fue en los últimos 30 minutos (opcional)
        if (!empty($SESSION->$session_key)) {
            $verification_time = $SESSION->$session_key;
            $time_limit = 30 * 60; // 30 minutos
            
            // Si la verificación fue hace menos de 30 minutos, considerarla válida
            return (time() - $verification_time) < $time_limit;
        }
        
        return false;
    }

    // NUEVO: Marcar como verificado en la sesión
    private function mark_face_verified_in_session($quizid, $userid) {
        global $SESSION;
        $session_key = "faceid_verified_{$quizid}_{$userid}";
        $SESSION->$session_key = time(); // Guardar timestamp de verificación
    }

    public function description() {
        return get_string('faceidrequired', 'quizaccess_faceid');
    }

    public function add_preflight_check_form_fields(\mod_quiz\form\preflight_check_form $quizform, \MoodleQuickForm $mform, $attemptid) {
        global $PAGE, $DB, $USER, $CFG;

        // Solo agregar campos si es requerido
        if (!$this->is_preflight_check_required($attemptid)) {
            return;
        }

        $rec = $DB->get_record('quizaccess_faceid', ['quizid' => $this->quiz->id]);
        if (!$rec || !$rec->enabled) {
            return;
        }

        $verification_type = $rec->verification_type ?? 'basic';
        
        // If verification type is 'with_profile', check if user has verified profile
        if ($verification_type === 'with_profile') {
            $profile_helper = new \quizaccess_faceid\profile_helper();
            $profile = $profile_helper->get_profile_verification($USER->id);
            
            if (!$profile || !$profile->verified) {
                // User must verify profile first
                $html = '
                    <div class="alert alert-warning" role="alert">
                        <i class="fa fa-exclamation-triangle"></i>
                        <strong>' . get_string('profile_not_verified', 'quizaccess_faceid') . '</strong><br>
                        ' . get_string('must_verify_profile_before_quiz', 'quizaccess_faceid') . '
                        <br><br>
                        <a href="' . $CFG->wwwroot . '/mod/quiz/accessrule/faceid/profile_verification.php" class="btn btn-warning">
                            <i class="fa fa-user-check"></i> ' . get_string('verify_profile_now', 'quizaccess_faceid') . '
                        </a>
                    </div>';
                $mform->addElement('html', $html);
                
                // Campo oculto que bloquea el acceso
                $mform->addElement('hidden', 'faceidverified', '0');
                $mform->setType('faceidverified', PARAM_TEXT);
                $mform->addRule('faceidverified', get_string('must_verify_profile_before_quiz', 'quizaccess_faceid'), 'required', null, 'client');
                return;
            }
        }

        // Determinar el tipo de mensaje según si es nuevo intento o continuación
        $is_new_attempt = ($attemptid === null);
        $message_type = $is_new_attempt ? get_string('new_attempt', 'quizaccess_faceid') : get_string('continue_attempt', 'quizaccess_faceid');

        // Verificar si ya se verificó en esta sesión
        if ($this->is_face_verified_for_session($this->quiz->id, $USER->id)) {
            // Ya verificado recientemente, solo mostrar mensaje de confirmación
            $html = '
                <div class="alert alert-success" role="alert">
                    <i class="fa fa-check-circle"></i>
                    <strong>' . get_string('face_verification_valid', 'quizaccess_faceid') . '</strong><br>
                    ' . get_string('identity_verified_recently', 'quizaccess_faceid') . $message_type . '.
                </div>';
            $mform->addElement('html', $html);
            
            // Campo oculto ya verificado
            $mform->addElement('hidden', 'faceidverified', '1');
            $mform->setType('faceidverified', PARAM_TEXT);
            return;
        }

        // Mostrar interfaz de verificación facial
        $action_text = $is_new_attempt ? get_string('start_quiz', 'quizaccess_faceid') : get_string('continue_quiz', 'quizaccess_faceid');

        $html = '
            <div id="faceid-container" style="margin-bottom:1rem">
                <div class="alert alert-info" role="alert">
                    <strong>' . get_string('requireface', 'quizaccess_faceid') . '</strong><br>
                    ' . get_string('must_verify_identity_to', 'quizaccess_faceid') . $action_text . '.<br>
                    ' . get_string('faceinstructions', 'quizaccess_faceid') . '
                </div>
                <button type="button" id="start-faceid" class="btn btn-primary">
                    <i class="fa fa-camera"></i> ' . get_string('verifyface', 'quizaccess_faceid') . '
                </button>
                <p id="faceid-message" style="margin-top:10px;"></p>
                <video id="faceid-video"
                       style="display:none;max-width:320px;border:2px solid #007bff;border-radius:6px"
                       autoplay muted playsinline></video>
            </div>';
        $mform->addElement('html', $html);

        // Campo oculto para verificación
        $mform->addElement('hidden', 'faceidverified', '0');
        $mform->setType('faceidverified', PARAM_TEXT);
        $mform->addRule('faceidverified', null, 'required', null, 'client');

        // Determine endpoint based on verification type
        $server_config = self::get_server_config();
        $endpoint = ($verification_type === 'with_profile') ? 
                   $server_config->base_url . '/verify-with-profile' : 
                   $server_config->verify_url;

        // Obtener el número de ID del usuario (campo opcional en Moodle)
        $user_idnumber = !empty($USER->idnumber) ? $USER->idnumber : '';

        $config = [
            'quizid'   => (int)$this->quiz->id,
            'userid'   => (int)$USER->id,
            'idnumber' => $user_idnumber, // Número de ID del perfil de Moodle
            'endpoint' => $endpoint,
            'wwwroot'  => $CFG->wwwroot,
            'attemptid' => $attemptid, // Agregar attemptid para debugging
            'is_new_attempt' => $is_new_attempt,
            'verification_type' => $verification_type,
            'strings' => [
                'starting_camera' => get_string('starting_camera', 'quizaccess_faceid'),
                'camera_activated' => get_string('camera_activated', 'quizaccess_faceid'),
                'error_camera_access' => get_string('error_camera_access', 'quizaccess_faceid'),
                'capture_face' => get_string('capture_face', 'quizaccess_faceid'),
                'processing_image' => get_string('processing_image', 'quizaccess_faceid'),
                'incomplete_configuration' => get_string('incomplete_configuration', 'quizaccess_faceid'),
                'verification_successful_continue' => get_string('verification_successful_continue', 'quizaccess_faceid'),
                'could_not_verify_identity' => get_string('could_not_verify_identity', 'quizaccess_faceid'),
                'error_processing_response' => get_string('error_processing_response', 'quizaccess_faceid'),
                'error_verification' => get_string('error_verification', 'quizaccess_faceid')
            ]
        ];

        $PAGE->requires->js_call_amd('quizaccess_faceid/faceid', 'init', [$config]);
    }

    public function validate_preflight_check($data, $files, $errors, $attemptid) {
        global $USER, $DB;
        
        // Solo validar si es requerido
        if (!$this->is_preflight_check_required($attemptid)) {
            return $errors;
        }

        $rec = $DB->get_record('quizaccess_faceid', ['quizid' => $this->quiz->id]);
        $verification_type = $rec ? ($rec->verification_type ?? 'basic') : 'basic';

        // If verification type is 'with_profile', check profile verification first
        if ($verification_type === 'with_profile') {
            $profile_helper = new \quizaccess_faceid\profile_helper();
            $profile = $profile_helper->get_profile_verification($USER->id);

            if (!$profile || !$profile->verified) {
                $errors['faceidverified'] = get_string('must_verify_profile_before_quiz', 'quizaccess_faceid');
                return $errors;
            }
        }

        $verified = false;
        if (isset($data['faceidverified'])) {
            $value = $data['faceidverified'];
            $verified = in_array($value, ['1', 1, true, 'true'], true) || 
                       (is_string($value) && trim($value) === '1');
        }

        if (!$verified) {
            $is_new_attempt = ($attemptid === null);
            $action = $is_new_attempt ? get_string('start_quiz', 'quizaccess_faceid') : get_string('continue_quiz', 'quizaccess_faceid');
            $verification_text = ($verification_type === 'with_profile') ?
                                get_string('face_verification_with_profile', 'quizaccess_faceid') : get_string('face_verification_basic', 'quizaccess_faceid');
            $errors['faceidverified'] = get_string('must_complete_verification', 'quizaccess_faceid',
                (object)['type' => $verification_text, 'action' => $action]);
        } else {
            // Marcar como verificado en la sesión para este quiz
            $this->mark_face_verified_in_session($this->quiz->id, $USER->id);
            
            // Log para debugging
            error_log("FaceID: Usuario {$USER->id} verificado para quiz {$this->quiz->id}, tipo: {$verification_type}, attemptid: " . var_export($attemptid, true));
        }

        return $errors;
    }

    // NUEVO: Método para limpiar la verificación cuando sea necesario
    public static function clear_verification_session($quizid, $userid) {
        global $SESSION;
        $session_key = "faceid_verified_{$quizid}_{$userid}";
        unset($SESSION->$session_key);
    }

    // IMPORTANTE: No interferir durante el intento
    public function prevent_access() {
        // No prevenir acceso durante el intento del quiz
        return false;
    }

    // IMPORTANTE: No bloquear la navegación durante el intento
    public function current_attempt_finished() {
        // Método vacío - no hacer nada especial cuando termine el intento
    }
    /**
     * Get server configuration from admin settings
     * @return object Configuration object with URLs and settings
     */
    private static function get_server_config() {
        $config = new stdClass();
        
        // Get server URL from admin settings
        $server_url = get_config('quizaccess_faceid', 'server_url');
        if (empty($server_url)) {
            $server_url = 'http://127.0.0.1:5001'; // Default fallback
        }
        
        // Ensure URL has proper format
        $server_url = rtrim($server_url, '/');
        
        // Build complete URLs
        $config->base_url = $server_url;
        $config->verify_url = $server_url . '/verify';
        $config->model_info_url = $server_url . '/model-info';
        
        // Get other settings
        $config->timeout = (int)get_config('quizaccess_faceid', 'timeout') ?: 10;
        $config->verify_ssl = (bool)get_config('quizaccess_faceid', 'verify_ssl');
        
        return $config;
    }

    /**
     * Test connection to the face verification server
     * @return array Result of connection test
     */
    public static function test_server_connection() {
        $config = self::get_server_config();
        
        try {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $config->base_url . '/',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $config->timeout,
                CURLOPT_SSL_VERIFYPEER => $config->verify_ssl,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json']
            ]);
            
            $response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);
            curl_close($curl);
            
            if ($error) {
                return ['success' => false, 'message' => 'Connection error: ' . $error];
            }
            
            if ($http_code == 200) {
                return ['success' => true, 'message' => 'Connection successful', 'response' => $response];
            } else {
                return ['success' => false, 'message' => 'HTTP Error: ' . $http_code];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }
}