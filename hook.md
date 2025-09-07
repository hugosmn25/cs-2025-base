# HOOK Git
## Objectifs
- Avoir un hook qui sauvegarde la base de données avant un commit
- Avoir un hook qui restaure la base de données après un pull

## Prérequis : Afficher le dossier git
- => file -> Settings --> Editor --> File Types --> Ignore files and folders --> retirer git
- Le dossier.git n'étant pas sauvegarder, vous pourrez adapter ce md en le sauvegardant dans votre projet

## Créer un hook de sauvegarde de la base de données
- Créer un fichier nommé `pre-commit` dans le dossier `.git/hooks`
- Ajouter le code suivant dans le fichier :
```bash
#!/bin/sh
# Spécifiez l'emplacement de votre exécutable `mysqldump`
# Si vous avez installé XAMPP à l'emplacement par défaut, cela pourrait ressembler à ceci:
MYSQLDUMP="C:/xampp/mysql/bin/mysqldump.exe"
 
# Remplacez par les détails de votre base de données
DB_NAME="nom_de_votre_base"
DB_USER="nom_utilisateur"
DB_PASSWORD="mot_de_passe"
DUMP_FILE="path/vers/votre/dossier/sql/$DB_NAME.sql"

"$MYSQLDUMP" -u $DB_USER -p $DB_PASSWORD $DB_NAME > $DUMP_FILE

# Ajoutez le dump à la liste des fichiers à commettre
git add $DUMP_FILE
 
exit 0
```
## Créer un hook de restauration de la base de données
- Créer un fichier nommé `post-merge` dans le dossier `.git/hooks`
- Ajouter le code suivant dans le fichier :
```bash
#!/bin/sh

# Spécifiez l'emplacement de votre exécutable `mysql` et de `mysqldump`
# Si vous avez installé XAMPP à l'emplacement par défaut, cela pourrait ressembler à ceci:
MYSQL="C:/xampp/mysql/bin/mysql.exe"
MYSQLDUMP="C:/xampp/mysql/bin/mysqldump.exe"

# Remplacez par les détails de votre base de données
DB_NAME="nom_de_votre_base"
DB_USER="nom_utilisateur"
DB_PASSWORD="mot_de_passe"
DUMP_FILE="path/vers/votre/dossier/sql/$DB_NAME.sql"

# (Optionnel) Sauvegardez la base de données actuelle avant de restaurer
"$MYSQLDUMP" -u $DB_USER -p $DB_PASSWORD $DB_NAME > "path/vers/votre/dossier/backup_$(date +%Y%m%d%H%M%S).sql"

# Restaurez la base de données à partir du dump
"$MYSQL" -u $DB_USER -p $DB_PASSWORD $DB_NAME < $DUMP_FILE

exit 0
```
