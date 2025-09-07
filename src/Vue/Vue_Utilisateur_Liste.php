<?php
namespace App\Vue;
use App\Utilitaire\Vue_Composant;

class Vue_Utilisateur_Liste extends Vue_Composant
{
    private array $listeUtilisateur;
    private string $msg="";

    public function __construct(array $listeUtilisateur, string $msg="")
    {
        $this->listeUtilisateur=$listeUtilisateur;
        $this->msg=$msg;
    }

    function donneTexte(): string
    {
        $str= '
<H1>Liste des utilisateurs</H1>

    <table style="    display: inline-block;">
         <tr>
            <td colspan="6" style="text-align: center">
                <form action="/Gerer_utilisateur/nouveau" method="get" style=\'display: contents\'>
                    
                   
 
                        <button type=\'submit\' 
                            onmouseover=\"this.style.background=\'#FFFF99\';this.style.color=\'#FF0000\';\"
                            onmouseout=\"this.style.background=\'\';this.style.color=\'\';\"  > 
                                Nouvel utilisateur ? 
                        </button>
                </form>
            </td>
 
        </tr>
        <tr>
            <th>Num utilisateur</th>
            <th>Login</th>
            <th>Niveau d\'autorisation </th>
        </tr>';

        $i=0;
        while ($i < count($this->listeUtilisateur)) {
            $iemeUtilisateur=$this->listeUtilisateur[$i];

            $str .=  "
           
            
        <tr >
            <td>$iemeUtilisateur[idUtilisateur]</td>
            <td>$iemeUtilisateur[login]</td>
            <td>$iemeUtilisateur[libelle]</td> 
            <!-- Création du bouton Modifier -->
            <td>
            ";


                $str .=  "
                <form action='/Gerer_utilisateur/ModifierUtilisateur/$iemeUtilisateur[idUtilisateur]' method='get' style='display: contents'>
                 
                         
                        <button type='submit' 
                            onmouseover=\"this.style.background='#FFFF99';this.style.color='#FF0000';\"
                            onmouseout=\"this.style.background='';this.style.color='';\" > 
                                Modifier 
                        </button>
                </form>
            </td>
            <!-- Création du bouton Désactiver -->
            ";
                switch ($iemeUtilisateur["desactiver"]) {
                    case 0:
                        $str .=  "
            <td>
                <form action='/Gerer_utilisateur/DesactiverUtilisateur/$iemeUtilisateur[idUtilisateur]' method='get' style='display: contents'>
                    
                    <input type='hidden' name='case' value='Gerer_utilisateur'>
                    
                          <button type='submit' 
                                onmouseover=\"this.style.background='#FFFF99';this.style.color='#FF0000';\"
                                onmouseout=\"this.style.background='';this.style.color='';\" > 
                                    Désactiver 
                        </button>
                </form>
            </td>
        </tr>
        
         ";
                        break;
                    case 1:
                        $str .=  "
            <td>
                <form action='/Gerer_utilisateur/ActiverUtilisateur/$iemeUtilisateur[idUtilisateur]' method='get' style='display: contents'>
                           
                        
                          <button type='submit' 
                            onmouseover=\"this.style.background='#FFFF99';this.style.color='#FF0000';\"
                            onmouseout=\"this.style.background='';this.style.color='';\"> 
                                Activer 
                        </button>
                </form>
            </td>
        </tr>
        
         ";
                        break;
                }
                $i++;
            }


        $str .=  "
</table>";

        $str .=  "<br><br>$this->msg";
        return $str;
    }


}