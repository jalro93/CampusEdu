# CampusEdu – Plataforma de gestión académica

Proyecto Intermodular (TFG) del ciclo DAW – thePower FP Oficial, curso 2026/2027.
Autor: Jorge Almagro Rodríguez · Tutor: José Manuel Villar Ferradal

CampusEdu es una aplicación web educativa donde el profesorado organiza sus cursos en **temas, lecciones y recursos**, el alumnado **solicita su matrícula** (que el profesor aprueba) y el sistema registra su **progreso**. El vídeo se aloja en servicios externos (YouTube / Bunny.net) y solo se incrusta.

## Funcionalidades

- **Autenticación** (Laravel Breeze) con elección del tipo de cuenta al registrarse: alumno o profesor.
- **Roles por curso**: una misma persona puede ser profesora en un curso y alumna en otro. Solo las cuentas de profesor pueden crear cursos.
- **Cursos, temas y lecciones** con contenido en Markdown (saneado al mostrarlo) y vídeo incrustado.
- **Recursos** por lección: enlaces externos y archivos privados con descarga autorizada.
- **Matriculación**: el alumno la solicita desde el catálogo y el profesor la aprueba o rechaza.
- **Progreso**: el alumno marca lecciones como completadas y ve su porcentaje de avance.
- **Interfaz adaptable** a móvil, tableta y escritorio, en español.

## Stack

PHP 8.3 · Laravel 13 · Blade · Alpine.js · Tailwind CSS · Vite · SQLite (desarrollo) / MySQL (producción) · PHPUnit

## Requisitos

PHP ≥ 8.3, Composer y Node.js con npm. En local se ha usado [Laravel Herd](https://herd.laravel.com), pero sirve cualquier entorno PHP.

## Puesta en marcha (local)

```bash
git clone https://github.com/jalro93/CampusEdu.git
cd CampusEdu
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install && npm run build
```

Con Herd el sitio queda disponible en http://campusedu.test. Sin Herd, `php artisan serve` lo sirve en http://localhost:8000. Para desarrollo con recarga de estilos, `npm run dev`.

### Usuarios de demostración

`php artisan migrate --seed` crea un curso de ejemplo («Introducción a Laravel») y dos usuarios. La contraseña de ambos es `password` y solo debe usarse en desarrollo:

| Usuario | Tipo de cuenta | Rol en el curso de ejemplo |
| --- | --- | --- |
| `profesor@campusedu.test` | profesor | profesor |
| `alumno@campusedu.test` | alumno | alumno |

### Usar MySQL

SQLite es la opción por defecto. Para MySQL, crea una base de datos vacía y cambia en `.env` `DB_CONNECTION=mysql` y las variables `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD`; después ejecuta `php artisan migrate --seed`. Las migraciones y las pruebas se han comprobado sobre MySQL 9.6 con `ONLY_FULL_GROUP_BY` activo.

## Pruebas

```bash
php artisan test
```

La batería (114 pruebas) usa SQLite en memoria. Cada prueba lleva en su nombre el código RFTP que verifica, por ejemplo `test_R02F02T02P01_alumno_no_puede_editar`.

## Organización del trabajo

- Método RFTP (Requisitos–Funciones–Tareas–Pruebas).
- Ramas: `main` (solo entregas, con las etiquetas `entrega-1`, `entrega-2` y `final`), `develop` (integración) y `feature/Rxx-nombre` (una por requisito).
- Commits en formato `feat(R01F01): descripción`, con el código RFTP de lo que se implementa.
