<?php

require "autoload.php";

add_header();

# https://stackoverflow.com/a/31107425/10503297
function random_str(int $length, string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'): string {
    if ($length < 1) {
        throw new \RangeException("Length must be a positive integer");
    }
    $pieces = [];
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i) {
        $pieces []= $keyspace[random_int(0, $max)];
    }
    return implode('', $pieces);
}

if (isset($_POST["link"])) {
    $link = $_POST["link"];

    if (preg_match("/^(http(s?):\/\/)?(\[(([0-9a-f]{1,4}:){7}[0-9a-f]{1,4}|([0-9a-f]{1,4}:){1,7}:|([0-9a-f]{1,4}:){1,6}:[0-9a-f]{1,4}|([0-9a-f]{1,4}:){1,5}(:[0-9a-f]{1,4}){1,2}|([0-9a-f]{1,4}:){1,4}(:[0-9a-f]{1,4}){1,3}|([0-9a-f]{1,4}:){1,3}(:[0-9a-f]{1,4}){1,4}|([0-9a-f]{1,4}:){1,2}(:[0-9a-f]{1,4}){1,5}|[0-9a-f]{1,4}:((:[0-9a-f]{1,4}){1,6})|:((:[0-9a-f]{1,4}){1,7}|:)|fe80:(:[0-9a-f]{0,4}){0,4}%[0-9a-z]+|::(ffff(:0{1,4})?:)?((25[0-5]|(2[0-4]|1?[0-9])?[0-9])\.){3}(25[0-5]|(2[0-4]|1?[0-9])?[0-9])|([0-9a-f]{1,4}:){1,4}:((25[0-5]|(2[0-4]|1?[0-9])?[0-9])\.){3}(25[0-5]|(2[0-4]|1?[0-9])?[0-9]))\])|(http(s?):\/\/)?(((([a-zA-Z]+)|([0-9]{1,3}))\.)+(([a-zA-Z]+)|([0-9]{1,3})))/i", $link)) {
        # this fucking regexp magic took me so long to do, it detects perfectly IPv6 and less perfectly IPv4 and (sub-)domains
        $valid_url = true;
    } else {
        $valid_url = false;
    }

    if (! stristr($link, 'http')) {
        $link = "https://" . $link;
    }

    if ($valid_url) {
        $dsn = "mysql:host=" . env("mysql_address") . ";dbname=" . env("mysql_database") . ";port=".env("mysql_port").";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $pdo = new PDO($dsn, env("mysql_username"), env("mysql_password"), $options);
            $pdo->exec("use hiberlink");
        } catch (PDOException $e) {
            die($e->getMessage()." ".(int)$e->getCode());
        }

        while (true) {
            $id = random_str(env("char_per_id"));
            $req = $pdo->prepare("select * from LINKS where id = ?");
            $req->execute([$id]);

            $row = $req->fetch();
            if (! isset($row['original'])) {
                break;
            }
        }

        # tiny hack to do a unique ID
        $req = $pdo->prepare("insert into LINKS (id, original, time) values (?, ?, ?)");
        $req->execute([$id, $link, time()]);

        $req = $pdo->prepare("select * from LINKS where id = ?");
        $req->execute([$id]);

        $row = $req->fetch();
        if (! isset($row['original']) && ! is_curl()) {
            ?>
            <div class="center"><h4>Une erreur inconnue est survenue.</h4></div>
            <a class="btn rounded-lg flex items-center mt-2" href="<?= env("ext_url") ?>">Revenir à l'accueil</a>
            <?php
        } elseif (! isset($row['original']) && is_curl()) {
            echo "erreur";
        } elseif (isset($row['original']) && ! is_curl()) {
            ?>
            <img src="<?= env("ext_url") ?>/src/img/ok.png" width="48" alt="ok">
            <p class="center"><h4>Votre lien est prêt. Partagez le dès maintenant.</h4></p>
            <center>
                <input type="text" id="lien" class=" border rounded-lg w-full px-2 py-1 h-14 mb-3 text-lg text-grey-darker leading-loose" value="<?= env("ext_url")."/?".$id ?>">
            </center>
            <button class="btn rounded-lg flex items-center mt-2" onclick="copytoclipboard();" >Copier dans le presse-papier</button>
            <a class="btn rounded-lg flex items-center mt-2" href="<?= env("ext_url") ?>">Revenir à l'accueil</a>
            <?php
        } elseif (isset($row['original']) && is_curl()) {
            echo env("ext_url")."/?".$id;
        }

    } elseif (! $valid_url && ! is_curl()) {
        ?>
        <div class="center"><h4>Lien invalide.</h4></div>
        <a class="btn rounded-lg flex items-center mt-2" href="<?= env("ext_url") ?>">Revenir à l'accueil</a>
        <?php
    } elseif (! $valid_url && is_curl()) {
        echo "erreur";
    }

} else {
    header("Status: 301 Moved Permanently", false, 301);
    header("Location: https://www.youtube.com/watch?v=dQw4w9WgXcQ");
}

add_footer();