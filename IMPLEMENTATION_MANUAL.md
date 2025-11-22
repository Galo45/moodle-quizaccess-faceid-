# Manual de Implementación - Sistema Face ID para Moodle

**Versión:** 1.0
**Fecha:** Enero 2025
**Autor:** Face ID Development Team

---

## 📚 Tabla de Contenidos

1. [Introducción](#introducción)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Requisitos Previos](#requisitos-previos)
4. [Instalación del Servidor Flask](#instalación-del-servidor-flask)
5. [Instalación del Plugin Moodle](#instalación-del-plugin-moodle)
6. [Configuración Inicial](#configuración-inicial)
7. [Pruebas del Sistema](#pruebas-del-sistema)
8. [Configuración de Producción](#configuración-de-producción)
9. [Mantenimiento y Monitoreo](#mantenimiento-y-monitoreo)
10. [Resolución de Problemas](#resolución-de-problemas)
11. [Anexos](#anexos)

---

## Introducción

### ¿Qué es el Sistema Face ID para Moodle?

El Sistema Face ID para Moodle es una solución biométrica completa para autenticación de estudiantes durante exámenes en línea. Combina:

- **Reconocimiento facial de alta precisión** con múltiples modelos de IA
- **Detección anti-spoofing** para prevenir fraudes
- **Verificación de documentos de identidad** con OCR
- **Integración nativa con Moodle** como regla de acceso a cuestionarios

### Componentes del Sistema

```
┌─────────────────────────────────────────────────────────┐
│                   SISTEMA COMPLETO                      │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ┌───────────────────┐       ┌──────────────────────┐  │
│  │                   │       │                      │  │
│  │  Plugin Moodle    │◄─────►│  Servidor Flask      │  │
│  │  (Frontend + DB)  │ HTTP  │  (AI Backend)        │  │
│  │                   │       │                      │  │
│  └───────────────────┘       └──────────────────────┘  │
│          ▲                           ▲                  │
│          │                           │                  │
│          │                           │                  │
│  ┌───────┴────────┐         ┌────────┴──────────────┐  │
│  │   Estudiante   │         │   Modelos de IA       │  │
│  │   (Webcam)     │         │   - InsightFace       │  │
│  │                │         │   - FaceNet           │  │
│  └────────────────┘         │   - Anti-Spoofing     │  │
│                             │   - OCR (EasyOCR)     │  │
│                             └───────────────────────┘  │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

### Casos de Uso

1. **Exámenes de alto impacto:** Certificaciones, exámenes finales
2. **Educación a distancia:** Verificar identidad en cursos online
3. **Evaluaciones supervisadas:** Complemento a proctoring manual
4. **Instituciones reguladas:** Cumplimiento de normativas de identidad

---

## Arquitectura del Sistema

### Diagrama de Flujo General

```
┌──────────────────────────────────────────────────────────────┐
│ PASO 1: VERIFICACIÓN DE PERFIL (Una sola vez)               │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│  Estudiante                                                  │
│     │                                                        │
│     ├─► Sube documento ID ──► Moodle Plugin                 │
│     │                              │                         │
│     │                              ▼                         │
│     │                         Flask Server                   │
│     │                              │                         │
│     │                              ├─► Valida documento      │
│     │                              ├─► Extrae rostro doc     │
│     │                              ├─► Extrae rostro perfil  │
│     │                              ├─► Compara rostros       │
│     │                              └─► Extrae # OCR          │
│     │                              │                         │
│     │                              ▼                         │
│     │◄───── Perfil Verificado ◄──── Guarda en DB            │
│     │           ✅                                           │
│                                                              │
└──────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────┐
│ PASO 2: ACCESO AL QUIZ (Cada intento)                       │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│  Estudiante                                                  │
│     │                                                        │
│     ├─► Inicia Quiz ──► Moodle verifica perfil              │
│     │                         │                              │
│     │                         ├─► SI: Perfil NO verificado   │
│     │                         │   └─► BLOQUEA acceso         │
│     │                         │                              │
│     │                         └─► SI: Perfil verificado      │
│     │                             └─► Solicita imagen live   │
│     │                                      │                 │
│     ├─► Captura webcam ──────────────────┘                  │
│     │         │                                              │
│     │         ▼                                              │
│     │    Flask Server                                        │
│     │         │                                              │
│     │         ├─► Anti-Spoofing (¿es real?)                 │
│     │         ├─► Extrae rostro (¿solo 1?)                  │
│     │         ├─► Compara vs perfil                         │
│     │         └─► Calcula similitud                         │
│     │         │                                              │
│     │         ▼                                              │
│     │◄─── Verificado ◄─── Marca sesión (30 min)             │
│     │       ✅                                               │
│     │                                                        │
│     └─► ACCEDE AL QUIZ                                      │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

### Stack Tecnológico

#### Backend (Servidor Flask)

| Componente | Tecnología | Propósito |
|------------|------------|-----------|
| **Framework** | Flask 2.3+ | Servidor web HTTP |
| **IA - Face Recognition** | InsightFace, FaceNet, DeepFace | Reconocimiento facial |
| **IA - Anti-Spoofing** | Silent-Face-Anti-Spoofing (MiniFASNet) | Detección de fraudes |
| **IA - OCR** | EasyOCR | Extracción de texto |
| **Deep Learning** | PyTorch 2.0+ | Framework de IA |
| **Visión Computacional** | OpenCV 4.8+ | Procesamiento de imágenes |
| **Servidor** | Python 3.8+ | Lenguaje base |

#### Frontend (Plugin Moodle)

| Componente | Tecnología | Propósito |
|------------|------------|-----------|
| **CMS** | Moodle 3.9+ | Plataforma educativa |
| **Backend** | PHP 7.4+ | Lógica del servidor |
| **Frontend** | JavaScript AMD | Interfaz de usuario |
| **Base de Datos** | MySQL/PostgreSQL | Almacenamiento |
| **API** | cURL, JSON | Comunicación con Flask |

---

## Requisitos Previos

### Servidor de Aplicaciones (donde corre Flask)

#### Hardware Mínimo

- **CPU:** Dual-core 2.0 GHz (i3 o equivalente)
- **RAM:** 4 GB
- **Disco:** 10 GB libres
- **Red:** 10 Mbps

#### Hardware Recomendado

- **CPU:** Quad-core 3.0 GHz+ (i5/i7 o equivalente)
- **RAM:** 8 GB o más
- **Disco:** 20 GB libres (SSD preferido)
- **Red:** 100 Mbps+
- **GPU:** NVIDIA con CUDA (opcional, acelera 3-5x)

#### Software

- **Sistema Operativo:**
  - Ubuntu 20.04+ LTS (recomendado)
  - Windows 10/11 Pro
  - CentOS 8+
  - macOS 11+

- **Python:** 3.8, 3.9, 3.10 o 3.11
- **pip:** Versión actualizada
- **Git:** Para clonar repositorios

### Servidor Moodle

#### Requisitos Moodle

- **Moodle:** 3.9 o superior (probado hasta 4.x)
- **PHP:** 7.4, 8.0, 8.1 (según versión de Moodle)
- **Base de Datos:** MySQL 5.7+, PostgreSQL 10+, MariaDB 10.2+
- **Extensiones PHP requeridas:**
  - curl
  - json
  - gd
  - mbstring
  - xml
  - zip

#### Permisos

- Acceso SSH al servidor (para instalación)
- Permisos de administrador en Moodle
- Capacidad de instalar plugins

### Conectividad

- **Red interna:** Moodle debe poder conectarse al servidor Flask
- **Puertos:** Puerto 5001 (o el configurado) accesible desde Moodle
- **Firewall:** Reglas que permitan comunicación HTTP/HTTPS

### Cliente (Estudiante)

- **Navegador moderno:**
  - Chrome 53+
  - Firefox 36+
  - Edge 79+
  - Safari 11+
- **Webcam** funcional
- **Conexión:** 2 Mbps mínimo

---

## Instalación del Servidor Flask

### Paso 1: Preparar el Entorno

#### En Linux (Ubuntu/Debian)

```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar Python 3 y dependencias
sudo apt install -y python3 python3-pip python3-venv git

# Instalar dependencias del sistema para OpenCV
sudo apt install -y libgl1-mesa-glx libglib2.0-0

# Verificar instalación
python3 --version  # Debe ser 3.8+
pip3 --version
```

#### En Windows

1. **Instalar Python:**
   - Descargar desde [python.org](https://www.python.org/downloads/)
   - Durante instalación, marcar "Add Python to PATH"
   - Versión recomendada: 3.10.x

2. **Instalar Git:**
   - Descargar desde [git-scm.com](https://git-scm.com/download/win)

3. **Verificar instalación:**
   ```cmd
   python --version
   pip --version
   git --version
   ```

#### En macOS

```bash
# Instalar Homebrew (si no está instalado)
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# Instalar Python 3
brew install python@3.10

# Verificar
python3 --version
pip3 --version
```

### Paso 2: Clonar el Repositorio

```bash
# Crear directorio para el proyecto
mkdir -p /opt/faceid
cd /opt/faceid

# Clonar repositorio del servidor Flask
git clone https://github.com/Galo45/faceid-flask-server-.git
cd faceid-flask-server-

# Verificar contenido
ls -la
# Debe mostrar: face3_corrected.py, requirements.txt, src/, resources/, etc.
```

### Paso 3: Crear Entorno Virtual

```bash
# Crear entorno virtual
python3 -m venv venv

# Activar entorno virtual
# En Linux/macOS:
source venv/bin/activate

# En Windows:
venv\Scripts\activate

# Verificar activación (debe mostrar (venv) al inicio del prompt)
which python  # Linux/macOS
where python  # Windows
```

### Paso 4: Instalar Dependencias

```bash
# Actualizar pip
pip install --upgrade pip

# Instalar requirements
pip install -r requirements.txt

# Esto tomará 5-10 minutos dependiendo de la conexión
```

**Contenido de requirements.txt:**
```txt
flask==2.3.0
flask-cors==4.0.0
numpy==1.24.3
opencv-python==4.8.0.74
torch==2.0.1
torchvision==0.15.2
facenet-pytorch==2.5.3
insightface==0.7.3
onnxruntime==1.15.1
deepface==0.0.79
easyocr==1.7.0
Pillow==10.0.0
requests==2.31.0
```

### Paso 5: Verificar Estructura de Archivos

```bash
# Verificar que existan los modelos anti-spoofing
ls -la resources/anti_spoof_models/
# Debe mostrar:
# 4_0_0_80x80_MiniFASNetV1SE.pth
# 2.7_80x80_MiniFASNetV2.pth

ls -la resources/detection_model/
# Debe mostrar:
# Widerface-RetinaFace.caffemodel
# deploy.prototxt

ls -la src/
# Debe mostrar:
# anti_spoof_predict.py
# id_card_detector.py
# generate_patches.py
# utility.py
# data_io/, model_lib/
```

### Paso 6: Primera Ejecución (Test)

```bash
# Ejecutar servidor en modo desarrollo
python face3_corrected.py --host 127.0.0.1 --port 5001
```

**Salida esperada:**
```
✅ FaceNet cargado correctamente
✅ InsightFace cargado correctamente
✅ DeepFace disponible
✅ EasyOCR inicializado
[INFO] [CORRECTED] Sistema inicializado
 * Serving Flask app 'face3_corrected'
 * Debug mode: off
WARNING: This is a development server. Do not use it in a production deployment.
 * Running on http://127.0.0.1:5001
Press CTRL+C to quit
```

### Paso 7: Test de Conectividad

**En otra terminal:**

```bash
# Test 1: Health check
curl http://127.0.0.1:5001/health

# Respuesta esperada:
# {"status": "ok", "uptime": 10, "models_loaded": {...}}

# Test 2: Model info
curl http://127.0.0.1:5001/model-info

# Respuesta esperada:
# {"models": {...}, "thresholds": {...}, "version": "2.1"}
```

Si ambos tests funcionan, el servidor está correctamente instalado. ✅

---

## Instalación del Plugin Moodle

### Paso 1: Preparar Moodle

```bash
# Conectarse al servidor Moodle via SSH
ssh usuario@servidor-moodle

# Navegar al directorio de Moodle
cd /var/www/html/moodle  # Ajustar según tu instalación

# Verificar que es un directorio Moodle válido
ls config.php  # Debe existir
```

### Paso 2: Clonar el Plugin

```bash
# Navegar al directorio de quiz access rules
cd mod/quiz/accessrule/

# Clonar repositorio del plugin
sudo git clone https://github.com/Galo45/moodle-quizaccess-faceid-.git faceid

# Cambiar propietario al usuario web
sudo chown -R www-data:www-data faceid/

# Verificar permisos
ls -la faceid/
```

### Paso 3: Verificar Estructura

```bash
cd faceid/
ls -la

# Debe mostrar:
# amd/
# classes/
# db/
# lang/
# lib.php
# rule.php
# settings.php
# version.php
# profile_verification.php
# etc.
```

### Paso 4: Instalar el Plugin

#### Opción A: Desde Línea de Comandos (Recomendado)

```bash
# Regresar al directorio raíz de Moodle
cd /var/www/html/moodle

# Ejecutar upgrade
sudo -u www-data php admin/cli/upgrade.php

# Seguir las instrucciones en pantalla
# Responder 'y' cuando pregunte si continuar
```

**Salida esperada:**
```
Moodle 3.11+ (Build: 20210517) command line installation program
...
Installing quizaccess_faceid...
Upgrading from v2025091200 to v2025091201
...
Main upgrade completed successfully
```

#### Opción B: Desde Interfaz Web

1. Accede como administrador a Moodle
2. Moodle detectará automáticamente el nuevo plugin
3. Serás redirigido a: **Administración del sitio → Notificaciones**
4. Verás: "quizaccess_faceid - Face ID verification" en la lista
5. Haz clic en **"Actualizar base de datos de Moodle"**
6. Espera a que complete la instalación
7. Haz clic en **"Continuar"**

### Paso 5: Verificar Instalación

```bash
# Verificar que las tablas se crearon
mysql -u root -p moodle_db  # Ajustar credenciales

# En MySQL:
SHOW TABLES LIKE 'mdl_quizaccess_faceid%';

# Debe mostrar:
# mdl_quizaccess_faceid
# mdl_quizaccess_faceid_profile
```

O desde la interfaz web:

1. Ve a: **Administración del sitio → Plugins → Resumen de plugins**
2. Busca "Face ID" en la lista
3. Debe aparecer con estado "Instalado" ✅

---

## Configuración Inicial

### Configurar el Plugin en Moodle

#### 1. Acceder a Configuración

1. Inicia sesión como administrador
2. Ve a: **Administración del sitio → Plugins → Cuestionarios → Reglas de acceso a cuestionarios → Face ID**

#### 2. Configurar Servidor Flask

**URL del servidor:**
```
http://IP_SERVIDOR_FLASK:5001
```

Ejemplos:
- Mismo servidor: `http://127.0.0.1:5001`
- Servidor local: `http://192.168.1.100:5001`
- Servidor remoto: `http://faceid.ejemplo.com:5001`

**Tiempo de espera (segundos):**
```
15
```
Recomendado: 10-20 segundos

**Verificación SSL:**
```
☐ Desactivado (desarrollo)
☑ Activado (producción con HTTPS)
```

#### 3. Configurar Protección de ID Number

**Contraseña del campo ID:**
```
tu_password_segura_123
```

⚠️ **Importante:** Esta contraseña protege el campo "ID number" en el perfil del usuario. Solo usuarios que conozcan esta contraseña podrán editar su número de identificación.

#### 4. Guardar Configuración

Haz clic en **"Guardar cambios"**

### Probar Conexión

#### Desde Moodle (Test Manual)

1. Ve a: **Administración del sitio → Desarrollo → Depuración**
2. Cambia temporalmente a: "DEVELOPER: mensajes de depuración adicionales"
3. Crea un quiz de prueba y habilita Face ID
4. Intenta acceder al quiz
5. Revisa logs en: **Administración del sitio → Informes → Registros**

#### Desde Servidor Flask

```bash
# Verificar que el servidor Flask está escuchando
netstat -tuln | grep 5001

# Debe mostrar:
# tcp   0   0 127.0.0.1:5001   0.0.0.0:*   LISTEN
```

---

## Configuración de un Cuestionario

### Paso 1: Crear o Editar Quiz

1. Ve al curso donde quieres el quiz
2. Activa edición
3. Agrega actividad → **Cuestionario**
4. Configura nombre, descripción, etc.

### Paso 2: Habilitar Face ID

Desplázate a la sección: **"Restricciones adicionales sobre los intentos"**

Verás opciones:
- ⚪ **Face ID verification required - Basic verification**
  - Descripción: Compara imagen en vivo con foto de perfil de Moodle
  - Uso: Exámenes estándar

- ⚪ **Face ID verification required - Verification with profile**
  - Descripción: Requiere perfil verificado con documento de identidad
  - Uso: Exámenes de alto impacto

- ⚪ **Disabled**
  - No requiere verificación facial

### Paso 3: Seleccionar Modo

**Para exámenes estándar:**
Selecciona: ⚪ → ⚫ **Basic verification**

**Para exámenes críticos:**
Selecciona: ⚪ → ⚫ **Verification with profile**

### Paso 4: Guardar

Haz clic en **"Guardar cambios y mostrar"**

El quiz ahora requiere verificación facial. ✅

---

## Pruebas del Sistema

### Test 1: Verificación Básica

**Objetivo:** Probar verificación en vivo vs foto de perfil

#### Preparación

1. Crea usuario de prueba: `estudiante_test`
2. Sube foto de perfil clara del estudiante
3. Crea quiz con "Basic verification"

#### Ejecución

1. Inicia sesión como `estudiante_test`
2. Accede al quiz
3. Deberías ver interfaz de verificación facial
4. Haz clic en "Verificar rostro"
5. Permite acceso a la cámara
6. Espera activación (2 segundos)
7. Haz clic en "Capturar imagen"
8. Espera resultado

**Resultado esperado:**
```
✅ Identidad verificada correctamente
Puede proceder con el cuestionario
```

9. Completa el quiz
10. Cierra sesión

#### Validación

```bash
# En servidor Flask, revisa logs:
tail -f /ruta/logs/server.log

# Busca líneas como:
[INFO] [INSIGHTFACE] 1 rostro detectado con confianza: 0.995
[INFO] Face similarity score: 0.872
[INFO] Verification successful
```

### Test 2: Verificación con Perfil

**Objetivo:** Probar verificación completa con documento ID

#### Preparación

1. Usuario: `estudiante_test2`
2. Sube foto de perfil
3. Prepara foto de cédula/pasaporte
4. Quiz con "Verification with profile"

#### Ejecución - Parte A: Verificar Perfil

1. Inicia sesión como `estudiante_test2`
2. Ve a: **Perfil de usuario**
3. Busca enlace: **"Verificar perfil con documento de identidad"**
4. Haz clic en el enlace
5. Sube foto del documento ID
6. Haz clic en **"Verificar perfil"**
7. Espera resultado (5-10 segundos)

**Resultado esperado:**
```
✅ Perfil verificado exitosamente
✓ Número de ID verificado: 001-1234567-8
Score: 0.785
```

#### Ejecución - Parte B: Acceder al Quiz

1. Ve al quiz configurado
2. Haz clic en "Comenzar intento"
3. Captura imagen en vivo (como en Test 1)
4. Deberías obtener acceso

**Resultado esperado:**
```
✅ Identidad verificada correctamente
Puede proceder con el cuestionario
```

### Test 3: Detección Anti-Spoofing

**Objetivo:** Verificar que el sistema rechaza fotos de fotos

#### Ejecución

1. Toma una foto impresa de un rostro
2. Intenta verificación mostrando la foto a la cámara
3. El sistema debe rechazar

**Resultado esperado:**
```
❌ La imagen no parece ser real. Por favor, use una cámara en vivo.
```

### Test 4: Detección de Múltiples Personas

**Objetivo:** Verificar rechazo de múltiples rostros

#### Ejecución

1. Intenta verificación con 2+ personas en el encuadre
2. Captura imagen

**Resultado esperado:**
```
❌ Se detectaron 2 personas en la imagen.
Por favor, asegúrese de estar solo en el encuadre.
```

### Test 5: Sesión de Verificación

**Objetivo:** Verificar que la sesión dura 30 minutos

#### Ejecución

1. Verifica identidad e inicia quiz
2. **Sin cerrar navegador**, sal del quiz
3. Vuelve a entrar al quiz dentro de 30 minutos
4. **No debe solicitar verificación de nuevo**

**Resultado esperado:**
```
✅ Su identidad ya fue verificada recientemente
Puede continuar con el cuestionario
```

5. Espera >30 minutos o cierra navegador
6. Vuelve a entrar
7. **Debe solicitar verificación de nuevo**

---

## Configuración de Producción

### Servidor Flask

#### 1. Usar Gunicorn (WSGI Server)

**Instalar:**
```bash
pip install gunicorn
```

**Crear script de inicio:**
```bash
# /opt/faceid/start_production.sh
#!/bin/bash

cd /opt/faceid/faceid-flask-server
source venv/bin/activate

gunicorn \
    --bind 0.0.0.0:5001 \
    --workers 4 \
    --timeout 120 \
    --access-logfile /var/log/faceid/access.log \
    --error-logfile /var/log/faceid/error.log \
    face3_corrected:app
```

**Dar permisos:**
```bash
chmod +x /opt/faceid/start_production.sh
```

#### 2. Configurar como Servicio Systemd

**Crear archivo de servicio:**
```bash
sudo nano /etc/systemd/system/faceid-server.service
```

**Contenido:**
```ini
[Unit]
Description=Face ID Flask Server
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/opt/faceid/faceid-flask-server
Environment="PATH=/opt/faceid/faceid-flask-server/venv/bin"
ExecStart=/opt/faceid/faceid-flask-server/venv/bin/python face3_corrected.py --host 0.0.0.0 --port 5001
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

**Activar servicio:**
```bash
sudo systemctl daemon-reload
sudo systemctl enable faceid-server
sudo systemctl start faceid-server

# Verificar estado
sudo systemctl status faceid-server
```

#### 3. Configurar Nginx Reverse Proxy (Opcional)

**Instalar Nginx:**
```bash
sudo apt install nginx
```

**Configurar sitio:**
```bash
sudo nano /etc/nginx/sites-available/faceid
```

**Contenido:**
```nginx
server {
    listen 80;
    server_name faceid.ejemplo.com;

    location / {
        proxy_pass http://127.0.0.1:5001;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # Aumentar timeouts para procesamiento de IA
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }
}
```

**Activar:**
```bash
sudo ln -s /etc/nginx/sites-available/faceid /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

#### 4. Configurar HTTPS con Let's Encrypt

```bash
# Instalar Certbot
sudo apt install certbot python3-certbot-nginx

# Obtener certificado
sudo certbot --nginx -d faceid.ejemplo.com

# Renovación automática (ya configurada)
sudo certbot renew --dry-run
```

#### 5. Configurar Firewall

```bash
# UFW (Ubuntu)
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw enable

# Permitir solo Moodle al puerto 5001
sudo ufw allow from 192.168.1.50 to any port 5001 proto tcp
```

### Plugin Moodle

#### 1. Actualizar URL del Servidor

En configuración del plugin, cambia:
```
De: http://127.0.0.1:5001
A:  https://faceid.ejemplo.com
```

#### 2. Habilitar Verificación SSL

```
☑ Verificación SSL: Activado
```

#### 3. Purgar Cachés

```bash
# Desde CLI
php admin/cli/purge_caches.php

# O desde web:
# Administración del sitio → Desarrollo → Purgar todas las cachés
```

#### 4. Configurar Backup

```bash
# Script de backup diario
#!/bin/bash
# /opt/scripts/backup_faceid_data.sh

DATE=$(date +%Y%m%d)
BACKUP_DIR="/backup/faceid"

# Backup de tabla de perfiles verificados
mysqldump -u root -p moodle_db \
    mdl_quizaccess_faceid_profile \
    > $BACKUP_DIR/profiles_$DATE.sql

# Backup de documentos ID (filearea de Moodle)
tar -czf $BACKUP_DIR/iddocuments_$DATE.tar.gz \
    /var/moodledata/filedir/

# Mantener solo últimos 30 días
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
```

**Agregar a cron:**
```bash
crontab -e

# Añadir:
0 2 * * * /opt/scripts/backup_faceid_data.sh
```

---

## Mantenimiento y Monitoreo

### Monitoreo del Servidor Flask

#### Logs

```bash
# Ver logs en tiempo real
journalctl -u faceid-server -f

# Logs de errores
grep ERROR /var/log/faceid/error.log

# Logs de verificaciones
grep "verified" /var/log/faceid/access.log
```

#### Métricas de Performance

```bash
# CPU y RAM del proceso
ps aux | grep face3_corrected

# Conexiones activas
netstat -an | grep :5001 | wc -l

# Uptime del servicio
systemctl status faceid-server
```

#### Script de Monitoreo

```bash
#!/bin/bash
# /opt/scripts/monitor_faceid.sh

# Verificar que el servicio está corriendo
if ! systemctl is-active --quiet faceid-server; then
    echo "❌ Face ID server is down!" | mail -s "Alert: FaceID Down" admin@ejemplo.com
    systemctl restart faceid-server
fi

# Verificar respuesta HTTP
if ! curl -s http://127.0.0.1:5001/health > /dev/null; then
    echo "❌ Face ID server not responding!" | mail -s "Alert: FaceID No Response" admin@ejemplo.com
fi

# Verificar uso de memoria
MEM=$(ps aux | grep face3_corrected | awk '{print $4}' | head -1)
if (( $(echo "$MEM > 80" | bc -l) )); then
    echo "⚠️ Face ID server using ${MEM}% memory" | mail -s "Warning: High Memory" admin@ejemplo.com
fi
```

**Ejecutar cada 5 minutos:**
```bash
crontab -e

# Añadir:
*/5 * * * * /opt/scripts/monitor_faceid.sh
```

### Mantenimiento del Plugin Moodle

#### Limpieza de Datos

```sql
-- Eliminar perfiles no verificados más antiguos de 6 meses
DELETE FROM mdl_quizaccess_faceid_profile
WHERE verified = 0
  AND timecreated < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 6 MONTH));

-- Eliminar verificaciones de quizzes eliminados
DELETE FROM mdl_quizaccess_faceid
WHERE quizid NOT IN (SELECT id FROM mdl_quiz);
```

#### Actualización del Plugin

```bash
cd /var/www/html/moodle/mod/quiz/accessrule/faceid

# Hacer backup
sudo cp -r . /backup/faceid_plugin_$(date +%Y%m%d)/

# Actualizar desde Git
sudo -u www-data git pull origin main

# Ejecutar upgrade de Moodle
cd /var/www/html/moodle
sudo -u www-data php admin/cli/upgrade.php --non-interactive

# Purgar cachés
sudo -u www-data php admin/cli/purge_caches.php
```

### Rotación de Logs

```bash
# /etc/logrotate.d/faceid

/var/log/faceid/*.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 0640 www-data www-data
    sharedscripts
    postrotate
        systemctl reload faceid-server > /dev/null 2>&1 || true
    endscript
}
```

---

## Resolución de Problemas

### Problema 1: Servidor Flask No Inicia

**Síntoma:**
```
Error: Address already in use
```

**Diagnóstico:**
```bash
# Ver qué está usando el puerto
sudo netstat -tulpn | grep :5001

# O con lsof
sudo lsof -i :5001
```

**Solución:**
```bash
# Matar proceso
sudo kill -9 <PID>

# O cambiar puerto en configuración
python face3_corrected.py --host 127.0.0.1 --port 5002
```

### Problema 2: Moodle No Conecta con Flask

**Síntoma:**
```
Error: Could not connect to verification server
```

**Diagnóstico:**
```bash
# Desde servidor Moodle, test conectividad
curl http://IP_FLASK_SERVER:5001/health

# Test DNS
ping faceid.ejemplo.com

# Test firewall
telnet IP_FLASK_SERVER 5001
```

**Solución:**

1. **Verificar firewall:**
```bash
# En servidor Flask
sudo ufw status
sudo ufw allow from IP_MOODLE to any port 5001
```

2. **Verificar URL en Moodle:**
- Ir a configuración del plugin
- Asegurar que la URL sea correcta
- Probar con IP directa si falla con dominio

3. **Verificar logs de Flask:**
```bash
journalctl -u faceid-server -n 50
```

### Problema 3: Verificación Siempre Falla

**Síntoma:**
```
Could not verify identity. Score: 0.35
```

**Diagnóstico:**

1. **Verificar calidad de fotos:**
   - Foto de perfil debe ser clara, frontal, bien iluminada
   - Imagen en vivo debe tener buena calidad

2. **Revisar logs detallados:**
```bash
tail -f /var/log/faceid/error.log | grep INSIGHTFACE
```

3. **Probar threshold más bajo:**
```python
# En face3_corrected.py línea 109
'live_vs_profile': 0.55,  # Reducir de 0.65
```

**Solución:**

1. **Mejorar fotos de perfil:**
   - Informar a estudiantes sobre requisitos de foto
   - Establecer política de fotos de perfil

2. **Ajustar umbrales:**
   - Solo en casos excepcionales
   - Documentar cambios

3. **Verificar modelos:**
```bash
# Borrar cachés de modelos
rm -rf ~/.insightface/
rm -rf ~/.deepface/

# Reiniciar servidor (descargará de nuevo)
sudo systemctl restart faceid-server
```

### Problema 4: Anti-Spoofing Rechaza Rostros Reales

**Síntoma:**
```
La imagen no parece ser real
```

**Diagnóstico:**

1. **Verificar iluminación:**
   - Mala iluminación causa falsos positivos

2. **Probar manualmente:**
```bash
curl -X POST http://127.0.0.1:5001/test-antispoofing \
  -F "image=@test_image.jpg"
```

**Solución:**

1. **Mejorar condiciones:**
   - Instruir a estudiantes sobre buena iluminación
   - Evitar contraluz

2. **Ajustar threshold (último recurso):**
```python
# En face3_corrected.py línea ~875
is_real = label == 1 and confidence > 0.4  # Reducir de 0.5
```

### Problema 5: OCR No Detecta Número

**Síntoma:**
```
No se pudo extraer el número de documento
```

**Diagnóstico:**

```bash
# Test OCR manualmente
curl -X POST http://127.0.0.1:5001/test-ocr \
  -F "image=@cedula_test.jpg"
```

**Solución:**

1. **Mejorar calidad de imagen:**
   - Documento debe estar completamente visible
   - Sin reflejos o sombras
   - Número de documento claramente legible

2. **Verificar patrón de búsqueda:**
   - El OCR busca números de 10+ dígitos
   - Formatos: xxx-xxxxxxx-x, xxxxxxxxxxx

3. **Revisar texto detectado:**
```bash
# En logs, buscar:
[OCR] Texto completo detectado: ...
```

### Problema 6: Rendimiento Lento

**Síntoma:**
Verificación toma >10 segundos

**Diagnóstico:**

```bash
# Ver uso de CPU
top -p $(pgrep -f face3_corrected)

# Ver uso de RAM
free -h

# Ver I/O
iostat -x 1
```

**Solución:**

1. **Optimizar código:**
```python
# Usar solo InsightFace (más rápido)
# Comentar FaceNet y DeepFace en compare_faces()
```

2. **Aumentar recursos:**
   - Agregar RAM
   - Usar CPU más rápido
   - Considerar GPU

3. **Escalar horizontalmente:**
   - Ejecutar múltiples instancias
   - Load balancer (nginx)

---

## Anexos

### Anexo A: Checklist de Implementación

**Pre-Instalación**
- [ ] Servidor Flask con requisitos mínimos
- [ ] Python 3.8+ instalado
- [ ] Moodle 3.9+ funcionando
- [ ] Acceso SSH a ambos servidores
- [ ] Conectividad de red entre servidores

**Instalación Flask**
- [ ] Repositorio clonado
- [ ] Entorno virtual creado
- [ ] Dependencias instaladas
- [ ] Modelos anti-spoofing verificados
- [ ] Primera ejecución exitosa
- [ ] Health check funciona

**Instalación Moodle**
- [ ] Plugin clonado en directorio correcto
- [ ] Upgrade ejecutado
- [ ] Tablas creadas en BD
- [ ] Plugin visible en lista

**Configuración**
- [ ] URL del servidor configurada
- [ ] Timeout configurado
- [ ] SSL configurado
- [ ] Contraseña de ID configurada
- [ ] Cachés purgadas

**Pruebas**
- [ ] Test 1: Verificación básica
- [ ] Test 2: Verificación con perfil
- [ ] Test 3: Anti-spoofing
- [ ] Test 4: Múltiples personas
- [ ] Test 5: Sesiones

**Producción**
- [ ] Servicio systemd configurado
- [ ] Firewall configurado
- [ ] HTTPS configurado
- [ ] Backup configurado
- [ ] Monitoreo configurado
- [ ] Logs rotación configurada

### Anexo B: Comandos Útiles

```bash
# === SERVIDOR FLASK ===

# Iniciar
sudo systemctl start faceid-server

# Detener
sudo systemctl stop faceid-server

# Reiniciar
sudo systemctl restart faceid-server

# Ver estado
sudo systemctl status faceid-server

# Ver logs en tiempo real
journalctl -u faceid-server -f

# Test manual
python face3_corrected.py --host 127.0.0.1 --port 5001

# === MOODLE ===

# Purgar cachés
php admin/cli/purge_caches.php

# Upgrade
php admin/cli/upgrade.php

# Test conexión a Flask
curl http://FLASK_IP:5001/health

# Ver logs PHP
tail -f /var/log/apache2/error.log
# o
tail -f /var/log/nginx/error.log

# === BASE DE DATOS ===

# Conectar a MySQL
mysql -u root -p moodle_db

# Ver perfiles verificados
SELECT userid, verified, verification_score, FROM_UNIXTIME(last_verification)
FROM mdl_quizaccess_faceid_profile
WHERE verified = 1;

# Ver quizzes con Face ID
SELECT q.id, q.name, f.verification_type
FROM mdl_quiz q
JOIN mdl_quizaccess_faceid f ON q.id = f.quizid
WHERE f.enabled = 1;
```

### Anexo C: Estructura de Directorios Recomendada

```
/opt/faceid/
├── faceid-flask-server/          # Servidor Flask
│   ├── face3_corrected.py
│   ├── requirements.txt
│   ├── src/
│   ├── resources/
│   └── venv/                      # Entorno virtual
│
├── logs/                          # Logs centralizados
│   ├── access.log
│   └── error.log
│
├── scripts/                       # Scripts de mantenimiento
│   ├── backup_faceid_data.sh
│   ├── monitor_faceid.sh
│   └── start_production.sh
│
└── backup/                        # Backups
    ├── profiles_20250115.sql
    └── iddocuments_20250115.tar.gz
```

### Anexo D: Contacto y Soporte

**Documentación:**
- Plugin Moodle: https://github.com/Galo45/moodle-quizaccess-faceid-
- Servidor Flask: https://github.com/Galo45/faceid-flask-server-

**Issues:**
- Reportar bugs: https://github.com/Galo45/moodle-quizaccess-faceid-/issues

**Email:**
- Soporte técnico: rualesgalo709@gmail.com

---

**Fin del Manual de Implementación**

*Última actualización: Enero 2025*
*Versión del documento: 1.0*
