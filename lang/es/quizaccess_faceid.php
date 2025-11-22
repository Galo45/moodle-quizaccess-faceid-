<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Verificación Face ID';
$string['privacy:metadata'] = 'El plugin de verificación Face ID no almacena datos personales.';

// Settings form strings
$string['faceidenabled'] = 'Habilitar verificación Face ID';
$string['faceidenabled_help'] = 'Si está habilitado, los estudiantes deben verificar su identidad usando reconocimiento facial antes de iniciar el cuestionario.';
$string['faceid_with_profile'] = 'Habilitar verificación Face ID con verificación';
$string['faceid_with_profile_help'] = 'Si está habilitado, los estudiantes deben verificar su identidad facial comparando con su perfil verificado previamente.';
$string['verification_mode'] = 'Modo de verificación';
$string['verification_mode_help'] = 'Seleccione el tipo de verificación facial: básica (solo anti-spoofing) o con perfil (comparación con perfil verificado).';
$string['disabled'] = 'Deshabilitado';

// Pre-flight check strings
$string['faceidrequired'] = 'Verificación Face ID requerida';
$string['requireface'] = 'Verificación facial requerida';
$string['faceinstructions'] = 'Por favor, haga clic en el botón de abajo para verificar su identidad usando su cámara. Asegúrese de que su rostro sea claramente visible y esté bien iluminado.';
$string['verifyface'] = 'Verificar Rostro';

// Validation strings
$string['facemismatch'] = 'La verificación facial no se completó exitosamente. Por favor verifique su identidad antes de continuar.';
$string['faceverificationfailed'] = 'La verificación facial falló. Por favor intente de nuevo.';
$string['faceverificationsuccess'] = '¡Verificación facial exitosa!';

// Error strings
$string['cameraerror'] = 'No se puede acceder a la cámara. Por favor verifique los permisos de su cámara.';
$string['networkerror'] = 'Error de red durante la verificación. Por favor intente de nuevo.';
$string['verificationerror'] = 'Error de verificación. Por favor contacte a su instructor si el problema persiste.';

// Strings de configuración del servidor
$string['serversettings'] = 'Configuración del servidor de verificación facial';
$string['serversettingsdesc'] = 'Configurar la conexión al servidor de verificación facial';

$string['serverurl'] = 'URL del servidor';
$string['serverurldesc'] = 'URL completa del servidor de verificación facial (ej: http://192.168.1.100:5001 o https://faceid.universidad.edu:5001)';

$string['timeout'] = 'Tiempo de espera de conexión';
$string['timeoutdesc'] = 'Tiempo máximo en segundos para esperar respuesta del servidor (por defecto: 10)';

$string['verifyssl'] = 'Verificar certificados SSL';
$string['verifyssldesc'] = 'Habilitar verificación de certificados SSL (deshabilitar solo para desarrollo/pruebas con certificados auto-firmados)';

// Mensajes de error
$string['servernotconfigured'] = 'El servidor de verificación facial no está configurado';
$string['servernotreachable'] = 'No se puede conectar al servidor de verificación facial';
$string['configurationerror'] = 'Error de configuración del plugin';

// Profile verification strings
$string['iddocument'] = 'Imagen cédula';
$string['iddocument_help'] = 'Subir imagen de su documento de identidad (cédula) para verificación del perfil.';
$string['profileverification'] = 'Perfil verificado';
$string['verified'] = 'Sí';
$string['not_verified'] = 'No';
$string['uploadiddocument'] = 'Subir imagen de cédula';
$string['verifyprofile'] = 'Verificar perfil';
$string['profileverified_success'] = 'Perfil verificado exitosamente';
$string['profileverified_failed'] = 'La verificación del perfil falló. Las caras no coinciden.';
$string['no_iddocument'] = 'No se ha subido imagen de cédula';
$string['verification_score'] = 'Puntaje de similitud: {$a}';
$string['last_verification'] = 'Última verificación: {$a}';
$string['reverify'] = 'Volver a verificar';
$string['iddocument_uploaded'] = 'Imagen de cédula subida correctamente';
$string['current_file'] = 'Archivo actual';

// Strings de protección del número de cédula
$string['idnumberprotection'] = 'Protección del Número de Cédula';
$string['idnumberprotectiondesc'] = 'Configure la protección con contraseña para el campo de número de cédula en los perfiles de usuario';
$string['idnumberpassword'] = 'Contraseña para Editar Número de Cédula';
$string['idnumberpassworddesc'] = 'Contraseña requerida para editar los números de cédula de los usuarios. Dejar vacío para deshabilitar la protección.';
$string['idnumber_mismatch'] = 'Verificación de número de cédula fallida: Se esperaba "{$a->expected}" pero se encontró "{$a->found}" en el documento';
$string['idnumberpassword_notconfigured'] = 'La protección con contraseña del número de cédula no está configurada';
$string['idnumberpassword_correct'] = 'Contraseña correcta. Ahora puede editar el número de cédula.';
$string['idnumberpassword_incorrect'] = 'Contraseña incorrecta. Por favor intente de nuevo.';
$string['unlock_idnumber'] = 'Desbloquear para Editar';
$string['idnumber_locked_help'] = 'Este campo está protegido. Haga clic en "Desbloquear para Editar" para realizar cambios.';
$string['idnumber_unlocked_help'] = 'Campo desbloqueado. Ahora puede editar el número de cédula.';
$string['idnumber_unauthorized_change'] = 'Debe desbloquear el campo de número de cédula antes de realizar cambios.';
$string['idnumber_password_title'] = 'Verificar Contraseña';
$string['idnumber_password_prompt'] = 'Ingrese la contraseña para desbloquear el campo de número de cédula:';

// Cadenas adicionales de verificación
$string['profile_not_verified'] = 'Perfil no verificado';
$string['must_verify_profile_before_quiz'] = 'Debe verificar su perfil antes de poder tomar este cuestionario.';
$string['verify_profile_now'] = 'Verificar Perfil Ahora';
$string['face_verification_valid'] = 'Verificación facial válida';
$string['identity_verified_recently'] = 'Su identidad fue verificada recientemente. Puede ';
$string['must_verify_identity_to'] = 'Debe verificar su identidad para ';
$string['new_attempt'] = 'nuevo intento';
$string['continue_attempt'] = 'continuar con el intento';
$string['start_quiz'] = 'iniciar el cuestionario';
$string['continue_quiz'] = 'continuar con el intento';
$string['face_verification_with_profile'] = 'verificación facial con perfil';
$string['face_verification_basic'] = 'verificación facial';
$string['must_complete_verification'] = 'Debe completar la {$a->type} para {$a->action}.';
$string['id_number_verified'] = 'Número de ID verificado:';
$string['id_number_warning'] = 'Advertencia:';
$string['id_number_document_not_match'] = 'El número de ID del documento ({$a->extracted}) no coincide con el del perfil ({$a->profile})';
$string['id_number_verification_info'] = 'Verificación de número de ID:';
$string['text_detected'] = 'Texto detectado:';
$string['error_verification'] = 'Error en verificación:';
$string['file_type_not_allowed'] = 'Tipo de archivo no permitido. Use JPG o PNG.';
$string['file_too_large'] = 'Archivo muy grande. Máximo 5MB.';
$string['error_uploading_file'] = 'Error subiendo archivo:';
$string['current_id_document_image'] = 'Imagen de cédula actual:';
$string['file_label'] = 'Archivo:';
$string['size_label'] = 'Tamaño:';
$string['uploaded_label'] = 'Subido:';
$string['no_id_document_uploaded'] = 'No has subido ninguna imagen de cédula aún.';
$string['select_file'] = 'Seleccionar archivo:';
$string['upload_id_document_btn'] = 'Subir imagen de cédula';
$string['verify_profile_title'] = 'Verificar perfil';
$string['compare_profile_photo'] = 'Compare su foto de perfil con la imagen de cédula subida.';
$string['back_to_profile'] = '← Volver al perfil';
$string['user_not_found'] = 'Usuario no encontrado';
$string['id_document_not_found'] = 'No se encontró imagen de cédula';
$string['error_in_verification'] = 'Error en la verificación: ';
$string['error_connecting_server'] = 'Error conectando con el servidor de verificación';
$string['invalid_server_response'] = 'Respuesta inválida del servidor';
$string['verification_completed'] = 'Verificación completada';
$string['error_in_comparison'] = 'Error en la comparación: ';

// Cadenas JavaScript
$string['starting_camera'] = 'Iniciando cámara...';
$string['camera_activated'] = 'Cámara activada. Posicione su rostro en el centro.';
$string['error_camera_access'] = 'Error: No se pudo acceder a la cámara. ';
$string['capture_face'] = 'Capturar Rostro';
$string['processing_image'] = 'Procesando imagen...';
$string['incomplete_configuration'] = 'Configuración incompleta. Verifica endpoint, userid, quizid y wwwroot.';
$string['verification_successful_continue'] = '✅ Verificación exitosa. Puede continuar con el cuestionario.';
$string['could_not_verify_identity'] = '❌ No se pudo verificar la identidad';
$string['error_processing_response'] = '❌ Error procesando respuesta del servidor';