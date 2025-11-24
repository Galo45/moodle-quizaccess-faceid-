# Face ID Verification - Moodle Quiz Access Rule Plugin

[![Moodle](https://img.shields.io/badge/Moodle-3.9%2B-orange)](https://moodle.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPLv3-green)](https://www.gnu.org/licenses/gpl-3.0.html)

Plugin de Moodle para control de acceso a cuestionarios mediante verificación biométrica facial en tiempo real. Utiliza reconocimiento facial y detección anti-spoofing para autenticar la identidad de estudiantes antes de iniciar exámenes.

---

## 🎯 Características Principales

- ✅ **Verificación facial en tiempo real** mediante captura de webcam
- ✅ **Dos modos de verificación:**
  - **Básica:** Compara imagen en vivo con foto de perfil de Moodle
  - **Con perfil verificado:** Requiere verificación previa con documento de identidad
- ✅ **Sistema anti-suplantación (anti-spoofing)** para detectar intentos de fraude
- ✅ **Verificación de documento de identidad** con extracción OCR del número
- ✅ **Campo de número de identificación protegido con contraseña**
- ✅ **Sesiones de verificación** válidas por 30 minutos
- ✅ **Interfaz bilingüe:** Español e Inglés
- ✅ **Integración con servidor Flask externo** para procesamiento de IA

---

## 📋 Requisitos

### Moodle y PHP
- Moodle **3.9 o superior**
- PHP **7.4 o superior**
- Extensiones PHP: `curl`, `json`, `gd`

### Servidor Backend
- **Servidor Flask de Face Recognition** funcionando y accesible
- Ver [faceid-flask-server](https://github.com/Galo45/faceid-flask-server-) para instalación del servidor

### Cliente (Estudiante)
- Navegador moderno con soporte para **getUserMedia API**:
  - Chrome 53+
  - Firefox 36+
  - Edge 79+
  - Safari 11+
- **Cámara web** funcional
- Conexión a internet estable

---

## 🚀 Instalación

### 1️⃣ Descargar el Plugin

**Opción A: Clonar con Git**
```bash
cd /ruta/a/moodle/mod/quiz/accessrule/
git clone https://github.com/Galo45/moodle-quizaccess-faceid-.git faceid
```

**Opción B: Descargar ZIP**
1. Descarga el [último release](https://github.com/Galo45/moodle-quizaccess-faceid-/releases)
2. Extrae el contenido en `moodleroot/mod/quiz/accessrule/faceid/`

### 2️⃣ Instalar el Plugin

**Desde línea de comandos:**
```bash
cd /ruta/a/moodle
php admin/cli/upgrade.php
```

**Desde interfaz web:**
1. Accede como administrador
2. Ve a: **Administración del sitio → Notificaciones**
3. Sigue el proceso de instalación guiado

### 3️⃣ Configurar el Plugin

Ve a: **Administración del sitio → Plugins → Cuestionarios → Reglas de acceso a cuestionarios → Face ID**

**Configuración del servidor:**

| Parámetro | Descripción | Valor por defecto |
|-----------|-------------|-------------------|
| **URL del servidor** | URL del servidor Flask | `http://127.0.0.1:5001` |
| **Tiempo de espera** | Timeout de conexión (segundos) | `10` |
| **Verificación SSL** | Validar certificados SSL | Deshabilitado |

**Configuración de seguridad:**

| Parámetro | Descripción |
|-----------|-------------|
| **Contraseña del campo ID** | Contraseña para proteger edición del número de identificación |

**Ejemplo de configuración:**
```
URL del servidor: http://192.168.1.100:5001
Tiempo de espera: 15
Verificación SSL: ☐ (desarrollo) / ☑ (producción)
Contraseña del campo ID: mi_password_segura_123
```

---

## 📖 Uso

### Para Administradores

#### Habilitar Face ID en un Cuestionario

1. Crea o edita un cuestionario
2. Ve a la sección **"Restricciones adicionales sobre los intentos"**
3. Selecciona el modo de verificación:
   - **Basic verification (Verificación básica):** Compara imagen en vivo vs foto de perfil
   - **Verification with profile (Verificación con perfil):** Requiere perfil verificado con documento
   - **Disabled (Deshabilitado):** No requiere verificación facial
4. Guarda los cambios

#### Gestión de Perfiles Verificados

- Los perfiles verificados se almacenan en la tabla `quizaccess_faceid_profile`
- Puedes ver el estado de verificación en el perfil de cada usuario
- Los perfiles se invalidan automáticamente si el usuario cambia su foto de perfil

### Para Estudiantes

#### Modo 1: Verificación Básica

**Requisitos previos:**
1. Tener una **foto de perfil actualizada y clara** en tu cuenta de Moodle
2. Acceso a cámara web

**Proceso:**
1. Haz clic en "Comenzar intento" en el cuestionario
2. Se abrirá la interfaz de verificación facial
3. Permite el acceso a la cámara cuando el navegador lo solicite
4. Haz clic en **"Verificar rostro"**
5. Espera 2 segundos para la activación de la cámara
6. Haz clic en **"Capturar imagen"**
7. El sistema verificará tu identidad (2-5 segundos)
8. Si la verificación es exitosa, podrás acceder al cuestionario

#### Modo 2: Verificación con Perfil

**Paso A: Verificar tu perfil (solo una vez)**

1. Accede a tu **perfil de usuario** en Moodle
2. Busca el enlace **"Verificar perfil con documento de identidad"** en el menú de navegación
3. Sube una **foto clara de tu documento de identidad** (cédula, pasaporte, DNI)
   - Formatos aceptados: JPG, PNG
   - Tamaño máximo: 5 MB
   - Requisitos de la foto:
     - El documento debe estar completamente visible
     - Tu rostro debe ser claramente visible en la foto del documento
     - El número de documento debe ser legible
     - Buena iluminación, sin reflejos
4. Haz clic en **"Verificar perfil"**
5. El sistema validará:
   - ✓ Que la imagen sea un documento de identidad válido
   - ✓ Que tu rostro en el documento coincida con tu foto de perfil
   - ✓ Que el número extraído del documento coincida con tu ID en Moodle
6. Una vez verificado, tu perfil quedará marcado como ✅ **Verificado**

**Paso B: Realizar el cuestionario**

1. Al iniciar un cuestionario que requiere perfil verificado:
   - Si tu perfil **NO está verificado**, verás un mensaje de advertencia y un botón para verificar
   - Si tu perfil **YA está verificado**, procede normalmente
2. Captura tu imagen en tiempo real (igual que verificación básica)
3. El sistema comparará tu imagen con tu perfil verificado
4. Si la verificación es exitosa, podrás acceder al cuestionario

**Sesión de verificación:**
- Una vez verificado, el acceso es válido por **30 minutos**
- Si vuelves a entrar al quiz dentro de ese tiempo, no necesitas verificar de nuevo

---

## 🏗️ Arquitectura Técnica

### Estructura del Plugin

```
faceid/
├── amd/                           # JavaScript AMD modules
│   ├── src/                       # Código fuente
│   │   ├── faceid.js             # Módulo principal de verificación
│   │   └── idnumber_protection.js # Protección del campo ID
│   └── build/                     # JS minificado (no editar)
├── classes/                       # Clases PHP
│   ├── observer.php              # Event observers
│   └── profile_helper.php        # Helper para perfiles
├── db/                            # Base de datos
│   ├── install.xml               # Esquema de tablas
│   └── upgrade.php               # Scripts de actualización
├── lang/                          # Traducciones
│   ├── en/quizaccess_faceid.php  # Inglés
│   └── es/quizaccess_faceid.php  # Español
├── lib.php                        # Funciones de biblioteca
├── locallib.php                   # Funciones locales
├── profile_verification.php       # Página de verificación de perfil
├── rule.php                       # Implementación de access rule
├── settings.php                   # Configuración del admin
├── verify_idnumber_password.php  # Endpoint AJAX
└── version.php                    # Información de versión
```

### Tablas de Base de Datos

#### `quizaccess_faceid`
Almacena la configuración de Face ID por cuestionario.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT | Clave primaria |
| `quizid` | INT | ID del cuestionario (FK) |
| `enabled` | TINYINT | Verificación habilitada (0/1) |
| `verification_type` | VARCHAR(20) | Tipo: 'basic', 'with_profile', 'disabled' |

#### `quizaccess_faceid_profile`
Almacena perfiles verificados de usuarios.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT | Clave primaria |
| `userid` | INT | ID del usuario (FK unique) |
| `iddocument_filename` | VARCHAR(255) | Nombre del archivo de documento |
| `iddocument_filepath` | VARCHAR(255) | Ruta del archivo |
| `iddocument_filesize` | INT | Tamaño del archivo |
| `verified` | TINYINT | Estado de verificación (0/1) |
| `verification_score` | DECIMAL(5,4) | Score de similitud facial |
| `last_verification` | INT | Timestamp de última verificación |
| `timecreated` | INT | Timestamp de creación |
| `timemodified` | INT | Timestamp de modificación |

### Flujo de Verificación

#### Verificación Básica (`/verify`)
```
1. Usuario hace clic en "Comenzar intento"
2. JavaScript captura imagen de webcam → faceid.js
3. AJAX envía imagen a Moodle → rule.php
4. Moodle reenvía:
   - Imagen en vivo
   - URL de foto de perfil
   - User ID
   → Servidor Flask endpoint /verify
5. Servidor Flask:
   ├─ Detección anti-spoofing (imagen real vs foto/video)
   ├─ Extracción de embedding facial (1 rostro requerido)
   ├─ Descarga foto de perfil desde Moodle
   ├─ Extracción de embedding de perfil
   └─ Comparación de similitud (threshold 0.65)
6. Respuesta JSON: {success, verified, score, message}
7. Si verified=true:
   ├─ JavaScript marca campo hidden faceidverified=1
   ├─ PHP valida en validate_preflight_check()
   ├─ Sesión marcada: $SESSION->faceid_verified_{quizid}_{userid}
   └─ Acceso permitido por 30 minutos
```

#### Verificación de Perfil (`/verify-profile`)
```
1. Usuario sube documento ID → profile_verification.php
2. PHP almacena archivo en Moodle filearea 'iddocument'
3. Usuario hace clic en "Verificar perfil"
4. PHP envía a Flask:
   - Archivo de documento ID
   - URL de foto de perfil
   - ID number del usuario
5. Servidor Flask:
   ├─ Validación de documento ID (IDCardDetector)
   ├─ Extracción de rostro de documento (permite múltiples, selecciona mayor)
   ├─ Extracción de rostro de perfil (requiere exactamente 1)
   ├─ Comparación de similitud (threshold 0.7)
   ├─ OCR: Extracción de número de documento
   └─ Comparación OCR vs idnumber de Moodle
6. Respuesta JSON: {success, verified, score, id_number_verification}
7. Si verified=true:
   └─ Actualiza quizaccess_faceid_profile:
      ├─ verified = 1
      ├─ verification_score = score
      └─ last_verification = timestamp
```

#### Verificación con Perfil (`/verify-with-profile`)
```
1. Al iniciar quiz, rule.php verifica perfil:
   - Si perfil NO verificado → muestra alerta + bloquea acceso
   - Si perfil verificado → continúa verificación en vivo
2. JavaScript captura imagen en vivo
3. Moodle envía a Flask:
   - Imagen en vivo
   - URL de foto de perfil
   - User ID
4. Servidor Flask:
   ├─ Detección anti-spoofing
   ├─ Extracción de embedding (1 rostro requerido)
   ├─ Comparación vs perfil verificado (threshold 0.65)
   └─ Retorna verified=true/false
5. Acceso permitido si verified=true
```

### Sistema de Sesiones

El plugin utiliza sesiones PHP para evitar re-verificación:

```php
// Marcar como verificado
$SESSION->faceid_verified_{quizid}_{userid} = time();

// Verificar si está verificado (válido por 30 minutos)
$session_key = "faceid_verified_{quizid}_{userid}";
$verified = !empty($SESSION->$session_key) &&
            (time() - $SESSION->$session_key) < 1800;
```

**Ventajas:**
- No requiere re-verificación al navegar por el quiz
- Válido solo para la sesión actual
- Expira automáticamente

**Limitaciones:**
- Si el usuario cierra el navegador, debe verificar de nuevo
- No persiste entre dispositivos

---

## 🔧 Desarrollo

### Modificar JavaScript

Los archivos fuente están en `amd/src/`. Después de modificarlos:

```bash
# NO requiere compilación, Moodle sirve AMD directamente
# Solo purga las cachés de Moodle:
```

**Desde interfaz web:**
Administración del sitio → Desarrollo → Purgar todas las cachés

### Modificar Base de Datos

Si modificas `db/install.xml` o `db/upgrade.php`:

1. Incrementa la versión en `version.php`:
```php
$plugin->version = 2025091202; // Incrementar
```

2. Ejecuta upgrade:
```bash
php admin/cli/upgrade.php
```

### Añadir Traducciones

Edita los archivos de idioma:
- `lang/en/quizaccess_faceid.php` (Inglés)
- `lang/es/quizaccess_faceid.php` (Español)

Formato:
```php
$string['clave'] = 'Traducción';
```

Purga cachés después de modificar.

### Agregar Nuevo Endpoint al Servidor

Si agregas un nuevo endpoint al servidor Flask:

1. Actualiza `rule.php` método `get_server_config()`:
```php
$config->nuevo_endpoint = $server_url . '/nuevo-endpoint';
```

2. Usa desde JavaScript:
```javascript
$.ajax({
    url: config.nuevo_endpoint,
    // ...
});
```

---

## 🔒 Seguridad

### Medidas Implementadas

1. **Campo de ID protegido:** Número de identificación requiere contraseña para edición
2. **Validación de archivos:** Solo JPEG/PNG, máximo 5MB
3. **Almacenamiento seguro:** Documentos en filearea de Moodle con permisos apropiados
4. **Verificación basada en sesión:** No se confía en datos del cliente
5. **Verificación de capacidades:** Solo usuarios autorizados acceden a funciones
6. **CSRF protection:** Uso de `sesskey()` en todos los formularios
7. **Sanitización de datos:** Uso de `PARAM_*` en todos los inputs

### Recomendaciones de Producción

1. **HTTPS obligatorio:**
   ```apache
   # En Apache
   RewriteEngine On
   RewriteCond %{HTTPS} off
   RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   ```

2. **Habilitar verificación SSL:**
   - En producción, activa "Verificación SSL" en la configuración del plugin

3. **Firewall del servidor Flask:**
   ```bash
   # Solo permitir conexiones desde servidor Moodle
   ufw allow from 192.168.1.50 to any port 5001
   ```

4. **Logs y monitoreo:**
   ```php
   // Habilitar debugging en desarrollo
   $CFG->debug = DEBUG_DEVELOPER;
   $CFG->debugdisplay = 1;
   ```

---

## 🐛 Solución de Problemas

### El cuestionario no solicita verificación facial

**Causa:** Plugin no habilitado correctamente

**Solución:**
1. Verifica instalación: `Administración del sitio → Plugins`
2. Busca "Face ID" en la lista
3. Asegúrate que el quiz tenga Face ID habilitado en configuración
4. Purga cachés: `Administración del sitio → Desarrollo → Purgar todas las cachés`

### La cámara no funciona

**Causas posibles:**

| Error | Solución |
|-------|----------|
| Navegador no soporta getUserMedia | Usa Chrome 53+, Firefox 36+, Edge 79+, Safari 11+ |
| Permisos de cámara denegados | Revisa permisos del navegador en configuración |
| HTTPS requerido | getUserMedia solo funciona en HTTPS (excepto localhost) |
| Cámara en uso por otra app | Cierra otras aplicaciones que usen la cámara |

**Debug:**
1. Abre consola del navegador (F12)
2. Busca errores relacionados con `getUserMedia`
3. Verifica permisos en: `chrome://settings/content/camera`

### Errores de conexión con el servidor Flask

**Error: "Could not connect to verification server"**

**Diagnóstico:**
```bash
# Verifica que el servidor Flask esté corriendo
curl http://127.0.0.1:5001/health

# Debe responder: {"status": "ok", ...}
```

**Soluciones:**

1. **Servidor no está corriendo:**
   ```bash
   cd RFSERVER
   python face3_corrected.py --host 127.0.0.1 --port 5001
   ```

2. **URL incorrecta:**
   - Verifica configuración en Moodle
   - Asegúrate de usar la IP/dominio correcto

3. **Firewall bloqueando:**
   ```bash
   # Linux
   sudo ufw allow 5001

   # Windows
   netsh advfirewall firewall add rule name="Flask Server" dir=in action=allow protocol=TCP localport=5001
   ```

4. **Timeout muy corto:**
   - Aumenta timeout a 20-30 segundos en configuración

### La verificación del perfil falla

**Error: "Face verification failed"**

**Causas y soluciones:**

| Causa | Solución |
|-------|----------|
| Foto de perfil de mala calidad | Actualiza foto de perfil con imagen clara, bien iluminada, frontal |
| Documento ID poco legible | Sube foto del documento con buena resolución, sin reflejos |
| Número de ID no coincide | Verifica que tu campo "ID number" en Moodle sea correcto |
| Múltiples rostros en foto de perfil | La foto de perfil debe tener solo tu rostro |
| Documento no válido | Asegúrate de subir un documento de identidad oficial |

**Error: "No se detectó ningún rostro"**

1. Verifica que la imagen tenga tu rostro claramente visible
2. Asegúrate de tener buena iluminación
3. La foto debe ser frontal, no de perfil

**Error: "Se detectaron X personas en la imagen"**

1. Toma la foto asegurándote de estar solo en el encuadre
2. No uses fotos grupales
3. Asegúrate de que no haya rostros en el fondo

### Logs del servidor Flask

Para ver logs detallados del servidor:

```bash
# El servidor imprime logs en la consola
# Busca líneas como:
[INFO] [INSIGHTFACE] 1 rostro detectado con confianza: 0.995
[ERROR] [SECURITY] InsightFace detectó 2 rostros en la imagen
[OCR] ✓ Número de cédula encontrado: '001-1234567-8'
```

---

## 📊 Limitaciones Conocidas

| Limitación | Descripción | Workaround |
|------------|-------------|------------|
| **Verificación solo al inicio** | No hay verificación continua durante el quiz | Usar proctoring adicional si se requiere |
| **Requiere servidor externo** | Depende completamente del servidor Flask | Mantener servidor en alta disponibilidad |
| **Sin logs de verificación** | No se registran intentos de verificación | Implementar logging personalizado |
| **Sesión de 30 minutos** | Verificación expira cada 30 min | Ajustable en `rule.php` línea 129 |
| **Requiere foto de perfil** | Usuario debe tener foto actualizada | Enviar recordatorios a estudiantes |
| **Sin validación de calidad** | No se valida calidad de foto de perfil | Revisar fotos manualmente |
| **Anti-spoofing con limitaciones** | Puede dar falsos positivos con mala luz | Usar en entornos bien iluminados |

---

## 🔄 Registro de Cambios

### v0.14 (2025-09-12)

**Nuevas características:**
- Implementación inicial del plugin
- Soporte para verificación básica y con perfil
- Sistema anti-spoofing integrado
- Verificación OCR de documentos de identidad
- Protección del campo de número de identificación
- Interfaz bilingüe (Español/Inglés)
- Sesiones de verificación de 30 minutos

**Mejoras de seguridad:**
- Validación estricta de rostro único en imágenes en vivo
- Almacenamiento seguro de documentos en filearea de Moodle
- CSRF protection en todos los formularios

---

## 🤝 Contribuir

¡Las contribuciones son bienvenidas!

### Proceso de Contribución

1. **Fork el repositorio**
   ```bash
   git clone https://github.com/Galo45/moodle-quizaccess-faceid-.git
   cd moodle-quizaccess-faceid-
   ```

2. **Crea una rama para tu funcionalidad**
   ```bash
   git checkout -b feature/nueva-funcionalidad
   ```

3. **Realiza tus cambios**
   - Sigue el coding style de Moodle
   - Añade comentarios PHPDoc
   - Actualiza traducciones si es necesario

4. **Haz commit**
   ```bash
   git commit -am 'Añade nueva funcionalidad: descripción'
   ```

5. **Push y Pull Request**
   ```bash
   git push origin feature/nueva-funcionalidad
   ```
   - Abre un Pull Request en GitHub
   - Describe los cambios detalladamente

### Coding Standards

- Sigue [Moodle Coding Style](https://moodledev.io/general/development/policies/codingstyle)
- Usa `moodle-plugin-ci` para validación
- Añade PHPDoc a todas las funciones
- Mantén compatibilidad con Moodle 3.9+

---

## 📄 Licencia

Este proyecto está licenciado bajo **GNU General Public License v3.0**

Ver [LICENSE](LICENSE) para más detalles.

### Permisos

✅ Uso comercial
✅ Modificación
✅ Distribución
✅ Uso privado

### Condiciones

⚠️ Divulgar código fuente
⚠️ Misma licencia
⚠️ Incluir copyright
⚠️ Documentar cambios

---

## 👥 Autores

- **Desarrollador Principal:** [Galo Ruales](https://github.com/Galo45)
- **Contacto:** rualesgalo709@gmail.com

---

## 🙏 Agradecimientos

- **Silent-Face-Anti-Spoofing** por los modelos de detección de anti-spoofing
- **InsightFace** por los modelos de reconocimiento facial de alta precisión
- **FaceNet PyTorch** por la implementación de FaceNet
- **DeepFace** por los modelos adicionales de reconocimiento
- **EasyOCR** por la extracción de texto de documentos
- **Comunidad de Moodle** por la documentación y soporte

---

## 📚 Recursos Adicionales

- [Documentación de Moodle](https://docs.moodle.org/)
- [Desarrollo de Plugins para Moodle](https://moodledev.io/)
- [Manual de Implementación](IMPLEMENTATION_MANUAL.md)
- [Servidor Flask - Face Recognition](https://github.com/Galo45/faceid-flask-server-)

---

## 📞 Soporte

Si tienes problemas o preguntas:

1. Revisa la sección [Solución de Problemas](#-solución-de-problemas)
2. Busca en [Issues existentes](https://github.com/Galo45/moodle-quizaccess-faceid-/issues)
3. Abre un [nuevo Issue](https://github.com/Galo45/moodle-quizaccess-faceid-/issues/new) con:
   - Versión de Moodle
   - Versión del plugin
   - Descripción del problema
   - Logs relevantes
   - Pasos para reproducir

---

**⭐ Si este plugin te resulta útil, considera darle una estrella en GitHub!**
