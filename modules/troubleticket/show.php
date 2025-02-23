<?php

$stepParameter = $_GET["step"] ?? 0;
switch ($stepParameter) {
    default:
        include_once('modules/troubleticket/search.inc.php');
        break;

    case 2:
        $tt_id = $_GET['ttid'];

        $rowtest = $db->qry_first("SELECT COUNT(*) AS n FROM %prefix%troubleticket WHERE ttid = %int%", $tt_id);
        $numrows = $rowtest["n"];

        // Check if ticketid is empty
        if ($tt_id == "") {
            $func->information(t('Es wurde keine Troubleticket-ID übergeben. Aufruf inkorrekt.'));
        // Check if ticketid is valid
        } elseif ($numrows == "") {
            $func->information(t('Es wurde keine Troubleticket-ID übergeben. Aufruf inkorrekt.'));
        } else {
            $dsp->NewContent(t('Troubleticket anzeigen'), t('Hier siehst du alle Informationen zu diesem Ticket'));

            // Ticket aus DB laden und ausgeben
            $row = $db->qry_first("SELECT * FROM %prefix%troubleticket WHERE ttid = %int%", $tt_id);

            $origin_user_id = $row["origin_userid"];
            $get_originuser = $db->qry_first("SELECT userid, username FROM %prefix%user WHERE userid = %int% ", $origin_user_id);
            $target_user_id = $row["target_userid"];
            $get_targetuser = $db->qry_first("SELECT userid, username FROM %prefix%user WHERE userid = %int% ", $target_user_id);

            $dsp->AddDoubleRow(t('Überschrift'), $row["caption"]);
            $dsp->AddDoubleRow(t('Problembeschreibung'), $func->text2html($row["text"]));
            $dsp->AddDoubleRow(t('Eingetragen am/um'), $func->unixstamp2date($row["created"], "daydatetime"));
            $dsp->AddDoubleRow(t('Von Benutzer'), $dsp->FetchUserIcon($get_originuser["userid"], $get_originuser["username"]));

            $priority = match ($row["priority"]) {
                20 => t('Normal'),
                30 => t('Hoch'),
                40 => t('Kritisch'),
                default => t('Niedrig'),
            };
            $dsp->AddDoubleRow(t('Priorität'), $priority);

            // entsprechend des ticketstatuses passende zeilen ausgeben
            $status_wahl = array();
            switch ($row["status"]) {
                default:
                    $status    = t('default: Scriptfehler!');
                    break;

                // status: NEU EINGETRAGEN / NICHT GEPRÜFT
                case 1:
                    $status    = t('Neu / Ungeprüft');
                    $time_text = "";
                    $time_val = "";
                    break;

                // status: GEPRÜFT / ggf. VON EINEM ORGA NEU EINGETRAGEN
                case 2:
                    $status    = t('Überprüft / Akzeptiert');
                    $time_text = t('Überprüft am/um');
                    $time_val = $func->unixstamp2date($row["verified"], "daydatetime");
                    break;

                // status: ORGA HAT ARBEIT BEGONNEN
                case 3:
                    $status    = t('In Arbeit');
                    $time_text = t('In Bearbeitung seit');
                    $time_val = $func->unixstamp2date($row["process"], "daydatetime");
                    break;

                // status: BEARBEITUNG ABGESCHLOSSEN
                case 4:
                    $status    = t('Abgeschlossen');
                    $time_text = t('Beendet am/um');
                    $time_val = $func->unixstamp2date($row["finished"], "daydatetime");
                    break;

                // status: BEARBEITUNG ABGELEHNT
                case 5:
                    $status    = t('Abgelehnt');
                    $time_text = t('Bearbeitung abgelehnt am/um');
                    $time_val = $func->unixstamp2date($row["finished"], "daydatetime");
                    break;
            }
            $dsp->AddDoubleRow(t('Ticketstatus'), $status);
            if ($time_text and $time_val) {
                $dsp->AddDoubleRow($time_text, $time_val);
            }

            $targetUserUserId = $get_targetuser["userid"] ?? 0;
            $targetUserUsername = $get_targetuser["username"] ?? '';
            $dsp->AddDoubleRow(t('Bearbeitender Orga'), $dsp->FetchUserIcon($targetUserUserId, $targetUserUsername));

            if (!$row["publiccomment"]) {
                $row["publiccomment"] = t(' Kein Hinweis eingetragen');
            }
            $dsp->AddDoubleRow(t('Kommentar'), $func->text2html($row["publiccomment"]));
            if ($auth['type'] > \LS_AUTH_TYPE_USER) {
                if (!$row["orgacomment"]) {
                    $row["orgacomment"] = t(' Kein Hinweis eingetragen');
                }
                $dsp->AddDoubleRow(t('Kommentar von und für Orgas'), $row["orgacomment"]);
            }

            $dsp->AddBackButton("index.php?mod=troubleticket", "troubleticket/change");
        }
        break;
}
