# CampusEdu – Plataforma de gestión académica

Proyecto Intermodular (TFG) del ciclo DAW – thePower FP Oficial, curso 2026/2027.
Autor: Jorge Almagro Rodríguez · Tutor: José Manuel Villar Ferradal

Plataforma web educativa con roles de profesor y alumno: cursos → temas → lecciones → recursos, matriculación y seguimiento del progreso. Vídeo alojado externamente (YouTube / Bunny.net).

## Stack
Laravel · Blade · Alpine.js · Tailwind CSS · SQLite (desarrollo) / MySQL (producción) · Vite

## Puesta en marcha (local, Herd)
```bash
git clone https://github.com/jalro93/CampusEdu.git
cd CampusEdu
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install && npm run dev
```
Con Herd el sitio queda disponible en http://campusedu.test

## Organización del trabajo
- Método RFTP (Requisitos–Funciones–Tareas–Pruebas).
- Ramas: `main` (entregas, con tags `entrega-1`, `entrega-2`, `final`), `develop` y `feature/Rxx-nombre`.
- Commits: `feat(R01F01): descripción` (Conventional Commits con código RFTP).
- Pruebas nombradas con su código RFTP.
