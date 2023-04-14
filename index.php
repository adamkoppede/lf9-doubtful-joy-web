<!doctype html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Ticketverwaltung | Doubtful-Joy19</title>
</head>
<body>
<h1>Ticketverwaltung von Doubtful-Joy19</h1>

<h2>Neues Ticket erstellen</h2>

<form action="submit-new-ticket.php" method="POST">
    <label>
        Titel des neuen Tickets
        <input type="text" name="title" required maxlength="255"/>
    </label>
    <input type="submit"/>
</form>

<hr />

<h2>Bestehende Tickets</h2>

<?php
try {
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
     * das Ergebnis der Datenbankfrage mit folgendem SQL-Befehl:
     *
     * ```
     * select `title` as `Titel`, `uid` as `ID`, `created_at` as `Erstellt am` from `ticket`
     * ```
     */
    $queryResult = $connection->query(
        "select `title` as `Titel`, `uid` as `ID`, `created_at` as `Erstellt am` from `ticket`"
    );

    // Schreiben des Tabellen-Kopfs
    echo "<table><tr>";
    foreach ($queryResult->fetch_fields() as $field) {
        // normalerweise sollte hier die Datenbank immer den von uns gegebenen Wert zurückgeben (reflection)
        // daher ist der Escape hier nur für den Fall, wenn diese das aus irgendwelchen Gründen nicht tut.
        echo "<th>" . htmlspecialchars($field->name) . "</th>";
    }
    echo "</tr>";

    // Schreiben der Inhalts-Zeilen der Tabelle
    while (($row = $queryResult->fetch_row()) !== null) {
        echo "<tr>";
        foreach ($row as $column) {
            // Der escape ist hier zwingen notwendig, da die Datenbankspalte für den Titel nicht vertrauenswürdige,
            // vom Nutzer gesteuerte, Zeichenketten enthält.
            // Für die restlichen Felder ist es egal, da diese niemals HTML-Code enthalten sollten.
            echo "<td>" . htmlspecialchars($column) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} catch (Throwable $exception) {
    // Setzt den HTTP Response Code auf den Fehlerzustand (Server Internal Error)
    http_response_code(500);

    // Informieren des Nutzers, dass ein Fehler aufgetreten ist
    echo '<p>Die Liste der bestehenden Tickets konnte nicht abgerufen werden.</p>';

    // In einer wirklichen Anwendung würde man diese Fehlerinformationen nicht dem Nutzer übergeben,
    // sondern Monitoring / Logging umsetzen. Für dieses Projekt ist das allerdings out-of-scope.
    echo '<p>Bitte übermitteln Sie die folgende Fehlernachricht an einen Administrator:</p>';
    echo '<pre>' . htmlspecialchars((string)$exception) . '</pre>';
}
?>
</body>
</html>
