// This file is part of Moodle - http://moodle.org/
//
// ID Number Protection Module
// Protects the ID number field with password verification

define(['jquery', 'core/ajax', 'core/notification', 'core/modal_factory', 'core/modal_events'],
    function($, Ajax, Notification, ModalFactory, ModalEvents) {

    var module = {
        /**
         * Initialize the ID number protection
         * @param {string} wwwroot Moodle wwwroot URL
         */
        init: function(wwwroot) {
            var idnumberField = $('#id_idnumber');

            // Only proceed if ID number field exists
            if (idnumberField.length === 0) {
                return;
            }

            // Store original value
            var originalValue = idnumberField.val();
            var fieldUnlocked = false;

            // Make field readonly initially
            idnumberField.prop('readonly', true);
            idnumberField.css({
                'background-color': '#f0f0f0',
                'cursor': 'not-allowed'
            });

            // Create unlock button
            var unlockButton = $('<button>')
                .attr('type', 'button')
                .addClass('btn btn-secondary btn-sm ml-2')
                .text(M.util.get_string('unlock_idnumber', 'quizaccess_faceid'))
                .css('margin-left', '10px');

            // Insert button after the field
            idnumberField.after(unlockButton);

            // Add help text
            var helpText = $('<div>')
                .addClass('form-text text-muted small')
                .text(M.util.get_string('idnumber_locked_help', 'quizaccess_faceid'));
            unlockButton.after(helpText);

            // Handle unlock button click
            unlockButton.on('click', function(e) {
                e.preventDefault();
                module.showPasswordDialog(wwwroot, function(success) {
                    if (success) {
                        // Unlock the field
                        fieldUnlocked = true;
                        idnumberField.prop('readonly', false);
                        idnumberField.css({
                            'background-color': '#fff',
                            'cursor': 'text'
                        });
                        unlockButton.hide();
                        helpText.text(M.util.get_string('idnumber_unlocked_help', 'quizaccess_faceid'));
                        idnumberField.focus();
                    }
                });
            });

            // Monitor form submission
            idnumberField.closest('form').on('submit', function() {
                // If field was not unlocked and value changed, prevent submission
                if (!fieldUnlocked && idnumberField.val() !== originalValue) {
                    Notification.alert(
                        M.util.get_string('error', 'core'),
                        M.util.get_string('idnumber_unauthorized_change', 'quizaccess_faceid'),
                        M.util.get_string('ok', 'core')
                    );
                    return false;
                }
            });
        },

        /**
         * Show password input dialog
         * @param {string} wwwroot Moodle wwwroot URL
         * @param {function} callback Callback function with success boolean
         */
        showPasswordDialog: function(wwwroot, callback) {
            /* eslint-disable no-console */
            console.log('showPasswordDialog called');
            /* eslint-enable no-console */

            ModalFactory.create({
                type: ModalFactory.types.SAVE_CANCEL,
                title: M.util.get_string('idnumber_password_title', 'quizaccess_faceid'),
                body: '<div class="form-group">' +
                      '<div id="idnumber-password-error" class="alert alert-danger" style="display:none;" role="alert"></div>' +
                      '<label for="idnumber-password-input">' +
                      M.util.get_string('idnumber_password_prompt', 'quizaccess_faceid') +
                      '</label>' +
                      '<input type="password" class="form-control" id="idnumber-password-input" ' +
                      'name="idnumber-pwd-' + Date.now() + '" ' +
                      'placeholder="' + M.util.get_string('password', 'core') + '" ' +
                      'autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" ' +
                      'data-form-type="other">' +
                      '</div>'
            }).then(function(modal) {
                /* eslint-disable no-console */
                console.log('Modal created successfully');
                /* eslint-enable no-console */

                modal.setSaveButtonText(M.util.get_string('confirm', 'core'));

                // Handle save button
                modal.getRoot().on(ModalEvents.save, function(e) {
                    /* eslint-disable no-console */
                    console.log('Save button clicked');
                    /* eslint-enable no-console */

                    e.preventDefault();

                    // Use native JavaScript to get the value (works better with autofill)
                    var passwordInput = document.getElementById('idnumber-password-input');
                    var password = passwordInput ? passwordInput.value.trim() : '';
                    var errorDiv = $('#idnumber-password-error');

                    /* eslint-disable no-console */
                    console.log('Password verification attempt:', {
                        passwordLength: password.length,
                        hasValue: !!password,
                        inputFound: !!passwordInput,
                        inputElement: passwordInput
                    });
                    /* eslint-enable no-console */

                    // Clear previous errors
                    errorDiv.hide().text('');

                    if (!password) {
                        // Show error inline instead of creating another modal
                        errorDiv.text(M.util.get_string('required', 'core')).show();
                        if (passwordInput) {
                            passwordInput.focus();
                        }
                        return;
                    }

                    // Show loading state
                    errorDiv.removeClass('alert-danger').addClass('alert-info').text('Verificando...').show();

                    // Verify password via AJAX
                    module.verifyPassword(wwwroot, password, function(success, message) {
                        if (success) {
                            modal.hide();
                            modal.destroy();
                            Notification.addNotification({
                                message: message,
                                type: 'success'
                            });
                            callback(true);
                        } else {
                            // Show error inline instead of notification
                            errorDiv.removeClass('alert-info').addClass('alert-danger').text(message).show();
                            if (passwordInput) {
                                passwordInput.value = '';
                                passwordInput.focus();
                            }
                        }
                    });
                });

                // Handle cancel
                modal.getRoot().on(ModalEvents.cancel, function() {
                    /* eslint-disable no-console */
                    console.log('Modal cancelled, destroying...');
                    /* eslint-enable no-console */
                    modal.destroy();
                    callback(false);
                });

                // Handle modal hidden event
                modal.getRoot().on(ModalEvents.hidden, function() {
                    /* eslint-disable no-console */
                    console.log('Modal hidden, destroying...');
                    /* eslint-enable no-console */
                    modal.destroy();
                });

                modal.show();

                // Focus password input after modal is shown
                setTimeout(function() {
                    var input = document.getElementById('idnumber-password-input');
                    if (input) {
                        input.focus();
                    }
                }, 500);
            });
        },

        /**
         * Verify password via AJAX
         * @param {string} wwwroot Moodle wwwroot URL
         * @param {string} password Password to verify
         * @param {function} callback Callback function with success boolean and message
         */
        verifyPassword: function(wwwroot, password, callback) {
            $.ajax({
                url: wwwroot + '/mod/quiz/accessrule/faceid/verify_idnumber_password.php',
                method: 'POST',
                data: {
                    password: password,
                    sesskey: M.cfg.sesskey
                },
                dataType: 'json',
                success: function(response) {
                    callback(response.success, response.message);
                },
                error: function(xhr, status, error) {
                    /* eslint-disable no-console */
                    // Log detailed error information to console
                    console.error('ID Number Password Verification Error:');
                    console.error('Status:', status);
                    console.error('Error:', error);
                    console.error('Response Status:', xhr.status);
                    console.error('Response Text:', xhr.responseText);
                    /* eslint-enable no-console */

                    // Try to parse error message from response
                    var errorMessage = M.util.get_string('error', 'core');

                    if (xhr.status === 403) {
                        errorMessage = 'Error de sesión. Por favor recargue la página.';
                    } else if (xhr.status === 404) {
                        errorMessage = 'Error: Archivo de verificación no encontrado.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Error del servidor. Por favor contacte al administrador.';
                    } else if (xhr.responseText) {
                        // Try to extract useful error message from response
                        errorMessage = 'Error: ' + status + ' - Ver consola para más detalles';
                    }

                    callback(false, errorMessage);
                }
            });
        }
    };

    return module;
});
