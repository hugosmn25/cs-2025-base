#!/usr/bin/env php
<?php
declare(strict_types=1);

function println(string $s=''){ echo $s.PHP_EOL; }
function prompt(string $label, ?string $default=null): string {
    $suffix = $default !== null ? " [$default]" : "";
    $v = readline("$label$suffix: ");
    if ($v === '' && $default !== null) return $default;
    return $v !== '' ? $v : prompt($label, $default);
}
function promptHidden(string $label, ?string $default=null): string {
    if (stripos(PHP_OS,'WIN')===0) {
        $v = readline("$label".($default?" [Entrée pour garder]":"").": ");
        return ($v==='' && $default!==null) ? $default : $v;
    }
    echo "$label".($default?" [Entrée pour garder]":"").": ";
    shell_exec('stty -echo');
    $v = trim(fgets(STDIN));
    shell_exec('stty echo'); echo PHP_EOL;
    return ($v==='' && $default!==null) ? $default : $v;
}
function confirm(string $msg): bool {
    $ans = readline("$msg (O/n): ");
    if ($ans === '') return true; // défaut = Oui
    return in_array(strtolower($ans), ['o','y','oui','yes'], true);
}
function ok($s){ println("✅ $s"); }
function err($s){ fwrite(STDERR,"❌ $s".PHP_EOL); }

// Charger defaults depuis paramBDD.txt si présent
$DEFAULT_IPBDD=$DEFAULT_BDD=$DEFAULT_USERBDD=$DEFAULT_MDPBDD=null;
if (is_file('paramBDD.txt')) {
    foreach (file('paramBDD.txt') as $line) {
        if (preg_match('/^IPBDD\s+(.+)$/',$line,$m)) $DEFAULT_IPBDD=$m[1];
        if (preg_match('/^BDD\s+(.+)$/',$line,$m)) $DEFAULT_BDD=$m[1];
        if (preg_match('/^USERBDD\s+(.+)$/',$line,$m)) $DEFAULT_USERBDD=$m[1];
        if (preg_match('/^MDPBDD\s+(.+)$/',$line,$m)) $DEFAULT_MDPBDD=$m[1];
    }
}

println("=== Installation BDD MySQL ===");
$ipbdd = prompt("Hôte/IP",$DEFAULT_IPBDD ?? "127.0.0.1");
$port  = prompt("Port","3306");
$bdd   = prompt("Nom de la base",$DEFAULT_BDD ?? "BDDCAFE2025");
$user  = prompt("Utilisateur",$DEFAULT_USERBDD ?? "BDDCAFE2025_user");
$pass  = promptHidden("Mot de passe",$DEFAULT_MDPBDD ?? "secret");

// Écrire paramBDD.txt
file_put_contents("paramBDD.txt","IPBDD $ipbdd\nBDD $bdd\nUSERBDD $user\nMDPBDD $pass\n");
ok("paramBDD.txt écrit.");

// Connexion PDO
try {
    $dsn="mysql:host=$ipbdd;port=$port;dbname=$bdd;charset=utf8mb4";
    $pdo=new PDO($dsn,$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $pdo->query("SELECT 1");
    ok("Connexion OK.");
} catch(PDOException $e){
    err("Connexion échouée: ".$e->getMessage()); exit(1);
}

// Vidage base
println("⚠️ Cette étape supprime toutes les tables de '$bdd'.");
if(confirm("Confirmer vidage ?")){
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    $stmt=$pdo->prepare("SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema=?");
    $stmt->execute([$bdd]);
    foreach($stmt->fetchAll(PDO::FETCH_COLUMN) as $t){
        $pdo->exec("DROP TABLE IF EXISTS `$t`");
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    ok("Base vidée.");
} else {
    println("Vidage ignoré.");
}

// Trouver dernier .sql
$files=glob(__DIR__."/sql/*.sql");
if(!$files){ err("Aucun .sql dans ./sql"); exit(1);}
usort($files,fn($a,$b)=>filemtime($b)<=>filemtime($a));
$latest=$files[0];
println("Dernier script: $latest");

if(confirm("Exécuter ce script ?")){
    $sql=file_get_contents($latest);

    // Split SQL safely: ignore semicolons inside strings and comments
    $stmts = (function(string $sql){
        $out = [];
        $buf = '';
        $len = strlen($sql);
        $inSingle = $inDouble = $inBacktick = false;
        $inLineComment = $inBlockComment = false;
        for ($i=0; $i<$len; $i++) {
            $ch = $sql[$i];
            $next = $i+1 < $len ? $sql[$i+1] : "\0";

            // Handle exiting line comment
            if ($inLineComment) {
                if ($ch === "\n") { $inLineComment = false; $buf .= $ch; }
                continue;
            }
            // Handle exiting block comment
            if ($inBlockComment) {
                if ($ch === '*' && $next === '/') { $inBlockComment = false; $i++; }
                continue;
            }

            // Enter comments when not in quotes
            if (!$inSingle && !$inDouble && !$inBacktick) {
                if ($ch === '-' && $next === '-' && ($i+2 >= $len || ctype_space($sql[$i+2]))) { $inLineComment = true; $i++; continue; }
                if ($ch === '#') { $inLineComment = true; continue; }
                if ($ch === '/' && $next === '*') { $inBlockComment = true; $i++; continue; }
            }

            // Toggle quote states, considering escapes
            if (!$inDouble && !$inBacktick && $ch === "'" ) {
                if (!$inSingle) { $inSingle = true; }
                else {
                    // Close only if not escaped (\' or '')
                    $prev = $i>0 ? $sql[$i-1] : "\0";
                    $prev2 = $i>1 ? $sql[$i-2] : "\0";
                    if ($prev !== '\\' && !($prev === "'" && $prev2 === "'")) { $inSingle = false; }
                }
                $buf .= $ch; continue;
            }
            if (!$inSingle && !$inBacktick && $ch === '"') { $inDouble = !$inDouble; $buf .= $ch; continue; }
            if (!$inSingle && !$inDouble && $ch === '`') { $inBacktick = !$inBacktick; $buf .= $ch; continue; }

            // Statement boundary
            if (!$inSingle && !$inDouble && !$inBacktick && $ch === ';') {
                $trimmed = trim($buf);
                if ($trimmed !== '') $out[] = $trimmed;
                $buf = '';
                continue;
            }

            // Regular char
            $buf .= $ch;
        }
        $trimmed = trim($buf);
        if ($trimmed !== '') $out[] = $trimmed;
        return $out;
    })($sql);

    foreach($stmts as $stmt){
        $pdo->exec($stmt);
    }
    ok("Script exécuté.");
} else println("Exécution ignorée.");

ok("Terminé.");
