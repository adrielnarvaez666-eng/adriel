# Configuración del buscador de registros

## Requisitos

- PHP 7.4 o posterior con las extensiones PDO y `pdo_mysql`.
- MySQL y una base de datos configurada.

## Preparar MySQL

En MySQL, ejecuta el archivo `database.sql` para crear la base de datos `number_lookup` y la tabla `registros`. La tabla guarda el número y los dos nombres y dos apellidos; el número debe ser único.

Configura un usuario de MySQL para la aplicación con permisos únicamente de lectura e inserción sobre `number_lookup.registros`. No uses la cuenta administrativa de MySQL en la aplicación.

Configura estas variables de entorno para el proceso de PHP:

| Variable | Valor predeterminado / ejemplo |
|---|---|
| `DB_HOST` | `127.0.0.1` |
| `DB_PORT` | `3306` |
| `DB_NAME` | `number_lookup` |
| `DB_USER` | Usuario de MySQL de la aplicación |
| `DB_PASSWORD` | Contraseña de ese usuario |

`DB_USER` y `DB_PASSWORD` son obligatorias. No guardes la contraseña en archivos del sitio ni en el repositorio. En Windows, puedes definirla en las variables de entorno del usuario y abrir una nueva consola para que PHP la herede.

## Iniciar la aplicación

Abre PowerShell en esta carpeta. Si configuraste el host, puerto, base de datos y usuario solo para esta consola, establece esos valores primero; luego ejecuta:

```powershell
php -S 127.0.0.1:8000
```

Abre `http://127.0.0.1:8000/login.html` en el navegador. No abras el HTML directamente como archivo: las llamadas a PHP requieren el servidor. Inicia sesión con cualquier usuario y contraseña no vacíos; el login actual es una demostración y no verifica credenciales en el servidor.

En `bienvenida.html` puedes buscar un número exacto de 10 dígitos que comience por `006` o `002`, o agregar un registro con los dos nombres y los dos apellidos. La API valida los datos y usa consultas preparadas. El número no se puede repetir.

Al guardar, la API responde HTTP 201 con `{"saved":true,"database":"saved","record":{...}}` solo después de que MySQL ejecuta correctamente el `INSERT`. La página muestra la confirmación de guardado únicamente cuando recibe y valida esa respuesta; si falla la API o MySQL, presenta el error y no indica que se guardó.

Al consultar un número, la página muestra la respuesta de la API con formato JSON y permite descargarla como `consulta-{numero}.json`. El formato es `{"found":true,"record":{...}}` si existe el registro, o `{"found":false,"record":null}` si no se encuentra.

Después de guardar correctamente un registro, la página ofrece un botón para abrir un chat de WhatsApp con el número `+593969758015` y un mensaje preparado con el nombre completo y el número de identidad. Debes revisar el mensaje y pulsar **Enviar** en WhatsApp; la página no lo envía automáticamente ni de forma anónima. El número de identidad y los nombres se comparten con WhatsApp solo al abrir el enlace. Usa esta función únicamente con autorización y consentimiento del destinatario.

## Probar la API

Una petición `GET` a `http://127.0.0.1:8000/api.php` verifica que PHP puede conectarse a MySQL y consultar la tabla `registros`; responde `{"status":"ok","database":"connected"}` si está disponible. Si la conexión o la tabla falla, devuelve HTTP 503 con un error JSON. Para buscar, agrega `?numero=0061234567`. Para insertar, envía una petición `POST` con `Content-Type: application/json` y un objeto JSON con las propiedades `numero`, `primer_nombre`, `segundo_nombre`, `primer_apellido` y `segundo_apellido`.

**Importante:** el login que acompaña este proyecto es solo una demostración; no protege la API ni los datos. Mantén el servidor en tu equipo o en una red privada de confianza. No publiques registros personales en Internet hasta añadir autenticación real y controles de acceso en el servidor. Para detener el servidor PHP, pulsa `Ctrl+C` en PowerShell.
