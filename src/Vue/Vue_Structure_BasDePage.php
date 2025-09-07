<?php
/**
 * Fonction qui affiche l'entête HTML de chaque page
 * @param bool $enHTML
 */
namespace App\Vue;
use App\Utilitaire\Vue_Composant;

class Vue_Structure_BasDePage extends Vue_Composant
{
    public function __construct()
    {
    }

    /**
     * Fonction qui affiche le bas HTML de chaque page
     */
    function donneTexte(): string
    {
        return  " 
            

        </div>
    </body>
</html>";
    }
}