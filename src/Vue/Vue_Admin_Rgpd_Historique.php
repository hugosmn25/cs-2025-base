<?php
namespace App\Vue;

use App\Utilitaire\Vue_Composant;

class Vue_Admin_Rgpd_Historique extends Vue_Composant
{
    private array $evenements;

    public function __construct(array $evenements)
    {
        $this->evenements = $evenements;
    }

    private static function ipToString($bin): string
    {
        if ($bin === null || $bin === '') return '';
        // Some PDO drivers may return resource/stream for BLOB
        if (is_resource($bin)) {
            $bin = stream_get_contents($bin);
        }
        $ip = @inet_ntop($bin);
        return $ip !== false ? $ip : '';
    }

    function donneTexte(): string
    {
        $rows = '';
        foreach ($this->evenements as $e) {
            $id = (int)$e['id'];
            $survenu = htmlspecialchars($e['survenu_le'] ?? '');
            $idUtil = (int)($e['idUtilisateur'] ?? 0);
            $consentId = (int)($e['consentement_id'] ?? 0);
            $type = htmlspecialchars($e['type_evenement'] ?? '');
            $verPol = (int)($e['version_politique_id'] ?? 0);
            $hash = htmlspecialchars(substr($e['hash_snapshot'] ?? '', 0, 16)).'…';
            $ip = self::ipToString($e['ip_adresse'] ?? null);
            $ip = htmlspecialchars($ip);
            $rows .= "<tr>"
                ."<td>$id</td>"
                ."<td>$survenu</td>"
                ."<td>$idUtil</td>"
                ."<td>$consentId</td>"
                ."<td>$type</td>"
                ."<td>$verPol</td>"
                ."<td><code>$hash</code></td>"
                ."<td>$ip</td>"
                ."</tr>";
        }

        if ($rows === '') {
            $rows = "<tr><td colspan='8' style='text-align:center; color:#777;'>Aucun évènement</td></tr>";
        }

        return "
        <section style='padding: 10px;'>
            <div style='max-width: 1000px; margin: 0 auto;'>
                <h2>Historique des évènements de consentement</h2>
                <table border='1' cellpadding='6' cellspacing='0' style='width:100%; border-collapse:collapse;'>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Utilisateur</th>
                            <th>Consentement</th>
                            <th>Type</th>
                            <th>Version politique</th>
                            <th>Hash</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        $rows
                    </tbody>
                </table>
            </div>
        </section>
        ";
    }
}

