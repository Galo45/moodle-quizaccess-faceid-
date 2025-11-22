/**
 * Face ID verification module for Moodle quiz access rule
 *
 * @module quizaccess_faceid/faceid
 */
define(['jquery'], function($) {

    var config = {};

    /**
     * Inicializa el módulo de Face ID
     * @param {Object} params Parámetros de configuración
     */
    function init(params) {
        config = params;

        $(document).ready(function() {
            setupFaceIdVerification();
        });
    }

    /**
     * Configura el botón y evento para iniciar verificación
     */
    function setupFaceIdVerification() {
        var startButton = $('#start-faceid');
        if (startButton.length === 0) {
            return;
        }
        startButton.on('click', function() {
            startFaceVerification();
        });
    }

    /**
     * Inicia la verificación: solicita acceso a cámara
     */
    function startFaceVerification() {
        var video = document.getElementById('faceid-video');
        var message = $('#faceid-message');
        var startButton = $('#start-faceid');

        message.html('<span class="text-info">' + config.strings.starting_camera + '</span>');
        startButton.prop('disabled', true);

        navigator.mediaDevices.getUserMedia({
            video: {
                width: 320,
                height: 240,
                facingMode: 'user'
            }
        })
        .then(function(stream) {
            video.srcObject = stream;
            video.style.display = 'block';
            message.html(
                '<span class="text-success">' + config.strings.camera_activated + '</span>'
            );
            setTimeout(function() {
                showCaptureButton(stream);
            }, 2000);
        })
        .catch(function(error) {
            message.html(
                '<span class="text-danger">' + config.strings.error_camera_access +
                error.message + '</span>'
            );
            startButton.prop('disabled', false);
        });
    }

    /**
     * Muestra el botón para capturar la imagen del rostro
     * @param {MediaStream} stream Flujo de video activo
     */
    function showCaptureButton(stream) {
        var message = $('#faceid-message');
        var captureBtn = $(
            '<button type="button" class="btn btn-success ml-2" id="capture-face">' +
            config.strings.capture_face + '</button>'
        );
        message.after(captureBtn);
        captureBtn.on('click', function() {
            captureAndVerifyFace(stream);
        });
    }

    /**
     * Captura la imagen del video y la envía a verificación
     * @param {MediaStream} stream Flujo de video activo
     */
    function captureAndVerifyFace(stream) {
        var video = document.getElementById('faceid-video');
        var canvas = document.createElement('canvas');
        var context = canvas.getContext('2d');
        var message = $('#faceid-message');

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        context.drawImage(video, 0, 0);

        canvas.toBlob(function(blob) {
            stream.getTracks().forEach(function(track) {
                track.stop();
            });
            video.style.display = 'none';

            message.html('<span class="text-info">' + config.strings.processing_image + '</span>');
            $('#capture-face').prop('disabled', true);

            sendImageForVerification(blob);
        }, 'image/jpeg', 0.8);
    }

    /**
     * Envía imagen al servidor para verificación facial
     * @param {Blob} imageBlob Imagen capturada
     */
    function sendImageForVerification(imageBlob) {
        var message = $('#faceid-message');

        if (!config || !config.endpoint || !config.userid || !config.quizid || !config.wwwroot) {
            message.html(
                '<span class="text-danger">' + config.strings.incomplete_configuration + '</span>'
            );
            return;
        }

        var formData = new FormData();
        formData.append('image', imageBlob, 'face.jpg');
        formData.append('userid', config.userid);
        formData.append('quizid', config.quizid);
        formData.append('wwwroot', config.wwwroot);

        // Enviar el número de ID del usuario si está disponible
        if (config.idnumber) {
            formData.append('idnumber', config.idnumber);
        }

        $.ajax({
            url: config.endpoint,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: (config.timeout || 10) * 1000,
            success: function(response) {
                handleVerificationResponse(response);
            },
            error: function(xhr, status, error) {
                message.html(
                    '<span class="text-danger">' + config.strings.error_verification +
                    error + '</span>'
                );
                resetVerification();
            }
        });
    }

    /**
     * Procesa respuesta del servidor Flask
     * @param {Object|string} response Respuesta JSON
     */
    function handleVerificationResponse(response) {
    var message = $('#faceid-message');
    var hiddenField = $('input[name="faceidverified"]');

    try {
        var result = typeof response === 'string' ? JSON.parse(response) : response;

        if (result.success && result.verified) {
            // Marcar como verificado
            hiddenField.val('1');
            hiddenField.prop('value', '1');
            hiddenField.attr('value', '1');
            hiddenField.trigger('change');

            message.html(
                '<span class="text-success"><i class="fa fa-check-circle"></i> ' +
                config.strings.verification_successful_continue + '</span>'
            );

            // Mantener la interfaz visible

        } else {
            var errorMsg = result.message || config.strings.could_not_verify_identity;
            message.html(
                '<span class="text-danger"><i class="fa fa-times-circle"></i> ' +
                errorMsg + '</span>'
            );
            resetVerification();
        }
    } catch (e) {
        message.html('<span class="text-danger">' + config.strings.error_processing_response + '</span>');
        resetVerification();
    }
}


    /**
     * Reinicia la interfaz de verificación
     */
    function resetVerification() {
        $('#start-faceid').prop('disabled', false);
        $('#capture-face').remove();
        var hiddenField = $('input[name="faceidverified"]');
        hiddenField.val('0');
        hiddenField.prop('value', '0');
        hiddenField.attr('value', '0');
    }

    return {
        init: init
    };
});