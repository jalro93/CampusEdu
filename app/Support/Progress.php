<?php

namespace App\Support;

/** Cálculo del porcentaje de avance de un alumno en un curso (R06F02). */
final class Progress
{
    /**
     * Porcentaje entero (0-100) de lecciones completadas sobre el total.
     * Un curso sin lecciones tiene 0 % (nunca se divide entre cero).
     */
    public static function percent(int $completed, int $total): int
    {
        if ($total <= 0) {
            return 0;
        }

        return (int) min(100, max(0, round($completed / $total * 100)));
    }
}
