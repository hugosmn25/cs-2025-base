<?php
namespace App\Vue;
use App\Utilitaire\Vue_Composant;

class Vue_Entreprise_Gerer_Compte  extends Vue_Composant
{

    private string $msg ="";

    function __construct (string $msg="")
    {
        $this->msg=$msg;
    }

    function donneTexte () : string
    {
        return " 
    <H1>Gestion du compte</H1>
    <table style='display: inline-block'>
        <tr>
            <td>
                <form action='/Gerer_Entreprise/infoEntreprise' method='get' style='display: contents'>
                     
                    
                    <button type='submit'  >
                        Les informations de l&apos;entreprise
                    </button>
                    
                </form>
            </td>
        </tr>
        <tr>
            <td>
                <form action='/Gerer_Entreprise/salariesHabitites' method='get' style='display: contents'>
                     
                         
                
                    <button type='submit'  '>
                        Personnes habilitées
                    </button>
                    
                </form>
            </td>
        </tr>
        <tr>
            <td>
                <form action='/Gerer_Entreprise/ChangerMDPEntreprise' method='get' style='display: contents'>
                    
                    
                    
                    <button type='submit' >
                        Changer mot de passe
                    </button>
                   
                </form>
            </td>
        </tr>
        <tr>
            <td>
                <form action='/Gerer_monCompte/deconnexionEntreprise' method='get'  style='display: contents'>
                      
                    
                    <button type='submit' >
                        Se déconnecter
                    </button>
                    
                </form>
            </td>
        </tr>
    </table>
   <br> $this->msg   ";
    }
}