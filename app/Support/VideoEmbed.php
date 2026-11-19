<?php

namespace App\Support;

/**
 * Vídeo externo de una lección (R04F03): solo se admiten YouTube y Bunny.net.
 *
 * A partir del enlace que pega el profesor se construye en el servidor la URL de
 * incrustación (embed). Nunca se muestra el enlace tal cual: si no coincide con un
 * formato conocido, no hay embed y la validación lo rechaza.
 */
final class VideoEmbed
{
    private const YOUTUBE_HOSTS = ['youtube.com', 'www.youtube.com', 'm.youtube.com'];

    private const BUNNY_HOSTS = ['player.mediadelivery.net', 'iframe.mediadelivery.net'];

    private const YOUTUBE_ID = '[A-Za-z0-9_-]{11}';

    public static function embedUrl(?string $url): ?string
    {
        if ($url === null || trim($url) === '') {
            return null;
        }

        $parts = parse_url(trim($url));

        // Solo https y sin usuario, contraseña ni puerto (evita https://youtube.com@otro.sitio/...).
        if ($parts === false
            || ($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
            return null;
        }

        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        parse_str($parts['query'] ?? '', $query);

        if (in_array($host, self::YOUTUBE_HOSTS, true)) {
            if ($path === '/watch' && isset($query['v']) && is_string($query['v'])
                && preg_match('/^'.self::YOUTUBE_ID.'$/', $query['v'])) {
                return self::youtube($query['v']);
            }
            if (preg_match('#^/embed/('.self::YOUTUBE_ID.')$#', $path, $m)) {
                return self::youtube($m[1]);
            }

            return null;
        }

        if ($host === 'youtu.be' && preg_match('#^/('.self::YOUTUBE_ID.')$#', $path, $m)) {
            return self::youtube($m[1]);
        }

        if (in_array($host, self::BUNNY_HOSTS, true)
            && preg_match('#^/(?:embed|play)/(\d+)/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})$#i', $path, $m)) {
            return 'https://player.mediadelivery.net/embed/'.$m[1].'/'.strtolower($m[2]);
        }

        return null;
    }

    private static function youtube(string $id): string
    {
        return 'https://www.youtube-nocookie.com/embed/'.$id;
    }
}
