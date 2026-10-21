sistema_escolar/
├── index.php                  Dashboard (indicadores y gráficas)
├── login.php / logout.php     Inicio y cierre de sesión
├── includes/
│   ├── conexion.php           Conexión PDO y constantes de configuración
│   ├── funciones.php          Escape HTML, CSRF, respuestas JSON, autenticación
│   ├── plan.php               Consultas compartidas del plan de estudios
│   ├── init.php               Carga lo anterior y exige sesión
│   └── layout_top.php / layout_bottom.php   Menú lateral, barra superior y scripts
├── alumnos/  materias/  inscripciones/  kardex/
│   ├── index.php              Vista del módulo (tabla y ventanas modales)
│   └── controlador.php        API del servidor (responde JSON)
├── assets/
│   ├── css/app.css            Tema rojo vino sobre Bootstrap
│   ├── js/                    app.js, alumnos.js, materias.js, inscripciones.js,
│   │                          kardex.js, dashboard.js, login.js
│   └── vendor/                Bootstrap, Icons, jQuery, DataTables, SweetAlert2, Chart.js
└── database/
    ├── escuela_sql.sql        Creación completa de la base y datos de prueba
    └── agregar_usuario.sql    Agrega solo la tabla usuario
