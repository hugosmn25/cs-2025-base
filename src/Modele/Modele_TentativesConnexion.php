<?php

namespace App\Modele;

use App\Utilitaire\Singleton_ConnexionPDO;
use PDO;

class Modele_TentativesConnexion
{
    public static function TentativesConnexion_Enregistrer(string $login, bool $reussite, ?string $ip = null): bool
    {
        // Redirige vers l'historique unifié
        return Modele_HistoriqueConnexion::HistoriqueConnexion_EnregistrerTentative($login, $reussite, null, $ip, null);
    }

    public static function TentativesConnexion_NombreEchecsRecents(string $login, int $seconds): int
    {
        // Redirige vers l'historique unifié
        return Modele_HistoriqueConnexion::HistoriqueConnexion_NombreEchecsRecents($login, $seconds);
    }

    public static function TentativesConnexion_DerniereEchec(string $login): ?string
    {
        // Redirige vers l'historique unifié
        return Modele_HistoriqueConnexion::HistoriqueConnexion_DerniereEchec($login);
    }
}

