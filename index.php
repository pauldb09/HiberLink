<?php

require "autoload.php";

$argument = preg_split('/[\/].*[?]/', $_SERVER["REQUEST_URI"]);
if (sizeof($argument) === 2) {
    $argument = $argument[1];

    $dsn = "mysql:host=" . env("mysql_address") . ";dbname=" . env("mysql_databse") . ";port=".env("mysql_port").";charset=utf8mb4";
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

    $req = $pdo->prepare("select * from LINKS where id = ?");
    $req->execute([$argument]);

    $row = $req->fetch();
    if (isset($row['original'])) {
        header("Status: 301 Moved Permanently", true, 301);
        header("Location: ".$row['original']);
        add_header();
        echo $row['original'];
    } else {
        add_header();
        ?>
        <div class="center"><h4>Ce lien n'existe pas.</h4></div>
        <a class="btn rounded-lg flex items-center mt-2" href="<?= env("ext_url") ?>">Revenir à l'accueil</a>
        <?php
    }
} else {
    add_header();
    ?>
                    <img src="<?= env('ext_url') ?>/src/img/add.png" width="48" alt="+">
                    <div class="center"><h4>Transformez votre lien dès maintenant.</h4></div>
                    <form method="post" action="<?= env('ext_url') ?>/link.php">
                        <center>
                            <input placeholder="Lien original" type="text" name="link" class="border rounded-lg w-full px-2 py-1 h-14 mb-3 text-lg text-grey-darker leading-loose" required>
                            <input id="buttonsend" type="submit" value="Transformation" name="submit" class="btn rounded-lg flex items-center mt-2">
                        </center>
                    </form>
    <?php
}

add_footer();