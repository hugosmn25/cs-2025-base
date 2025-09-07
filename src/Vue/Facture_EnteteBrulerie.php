<?php
namespace App\Vue;
use App\Utilitaire\Vue_Composant;
class Facture_EnteteBrulerie extends Vue_Composant
{

    private $infoCommande;
    private $infoentreprise;

    public function __construct($infoCommande, $infoentreprise)
    {
        $this->infoCommande=$infoCommande;

        $this->infoentreprise=$infoentreprise;
    }

    function donneTexte(): string
    {
        return "

<table style='width: 100%' >
<tr >
<td  style='text-align: left; width: 50%' > 
   <H1 > La brulerie comtoise </H1 >
        15 grande rue <br>
    39100 DOLE <br>
    </td >
<td  style='text-align: right; width: 50%' > ".$this->infoCommande["dateCreation"]."    </td >
</tr >
<tr >
<td  style='text-align: left; width: 50%' > </td >
<td  style='text-align: right; width: 50%' >   
<h1 >". $this->infoentreprise["denomination"]."</h1 >
        ".$this->infoentreprise["rueAdresse"]."<br >
        ".$this->infoentreprise["complementAdresse"]."<br >

        ".$this->infoentreprise["codePostal"]." ".$this->infoentreprise["ville"]."<br>
  ".$this->infoentreprise["pays"]." <br>
  </td >
</tr >
</table >
    
    
    <h3 align='center' > Commande n°".$this->infoCommande["id"]." </h3 >

        ";
    }

}