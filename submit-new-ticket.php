<?php

try {
    // es muss die Methode POST verwendet werden
    if (!array_key_exists('REQUEST_METHOD', $_SERVER) || $_SERVER['REQUEST_METHOD'] ?? '' !== 'POST') {
        // Setze den passenden HTTP response code für Method Not Allowed
        http_response_code(405);
        // send allow header: https://www.rfc-editor.org/rfc/rfc9110#section-9.1
        header('Allow: POST');
        die('method not allowed');
    }

    if (empty($_POST) || !array_key_exists('title', $_POST)) {
        // Die Anfrage kommt nicht aus einer validen / unmodifizierten Formularabsendung.
        http_response_code(400);
        die('bad request');
    }

    $userProvidedTitle = (string)$_POST['title'];

    if (strlen($userProvidedTitle) > 255) {
        // Wir haben client-side validation für das Formular auf der Webseite.
        // Ein Nutzer, der diese nicht absichtlich umgeht, wird daher diese Nachricht niemals sehen.
        http_response_code(400);
        die('bad request');
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    /**
     * repräsentiert eine Verbindung zur Datenbank, wie wir sie vorher mit dem
     * Kommandozeilenprogramm "mysql" geöffnet haben.
     */
    $connection = new mysqli("192.168.19.12", "web-php", "c3nt0s", "web");

    if ($connection->connect_errno !== 0) {
        throw new RuntimeException(
            "Could not connect to database. Error code $connection->connect_errno: " . $connection->connect_error,
            1681361641
        );
    }

    /**
     * Bereitet des SQL-Insert-Statement ohne die Werte vor.
     */
    $statement = $connection->prepare("insert into `ticket` (`created_at`, `title`) values (from_unixtime(?), ?)");

    /**
     * Der Zeitpunkt, an dem der Server die Anfrage entgegengenommen hat.
     */
    $dateOfRequestStart = (int)($_SERVER['REQUEST_TIME'] ?? 0);

    if ($dateOfRequestStart <= 0) {
        // _SERVER REQUEST_TIME ist nicht verfügbar. Zurückfallen auf den aktuellen Zeitpunkt.
        $dateOfRequestStart = time();
    }

    // Verlinkt die Fragezeichen-Platzhalter aus dem Insert-Statement mit den Variablen
    $statement->bind_param("is", $dateOfRequestStart, $userProvidedTitle);
    $statement->execute();

    // Wir nutzen hier einen 200 "Ok" HTTP response code statt dem empfohlenen 201 "Created",
    // da es für die erstellte Ressource keine URI gibt, die darauf verweist.
    // Es gibt keine URI, weil wir nur die Listenseite aber keine Detailseite implementieren.
    // https://www.rfc-editor.org/rfc/rfc9110#section-9.3.3

    echo <<<HTML
<!doctype html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Ihr Ticket wurde erfolgreich erstellt | Doubtful-Joy19</title>
</head>
<body>
<h1>Ihr Ticket wurde erfolgreich erstellt</h1>

<p>Sie können nun auf die <a href="/">Startseite</a> zurückkehren.</p>
</body>
</html>
HTML;
} catch (Throwable $exception) {
    // Setzt den HTTP Response Code auf den Fehlerzustand (Server Internal Error)
    http_response_code(500);

    $encodedErrorInformation = htmlspecialchars((string)$exception);
    echo <<<HTML
<!doctype html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Fehler bei Ticketerstellung | Doubtful-Joy19</title>
</head>
<body>
<h1>Fehler bei Ticketerstellung</h1>

<p>Das Ticket konnte nicht erstellt werden.</p>
<p>Bitte übermitteln Sie die folgende Fehlernachricht an einen Administrator:</p>
<pre>$encodedErrorInformation</pre>
</body>
</html>
HTML;
}
