<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        //adatbazis connectorok
// $datumot itt szeddle es parameterkent add at;
        $minutesofday = getMinutesOfDay();
//--------------------------------------------------------------
        //feederes
        $email_list_name = 'rftag_feeder_count';
        $frequency = 30;
        $minutesofday = getMinutesOfDay();
        $connstring = 'pgsql:dbname=appu05;host=143.116.140.161;application_name=TraxMon';
        $user = 'seappadmin';
        $pass = 's3n3mm0nd0mm3G!kuk@c';
        $dataquery = 'select count(id) FROM "RFID"."RF_Tag" where type = 2 AND comment = \'RFTOOLSV2\'';
        $subject = 'RFID feederek';
        //címlista, futási intervallum, hanyadikperceanapnak, adattablaelerese, felhasznalo, jelszo, queryazadatbazisban,level targya
        $a = new alibimail();
        $a->go($email_list_name, $frequency, $minutesofday, $connstring, $user, $pass, $dataquery, $subject);
//-------------------------------------------------------------
        //rfides
        $email_list_name = 'rftag_feeder_count';
        $frequency = 30;
        $minutesofday = getMinutesOfDay();
        $connstring = 'pgsql:dbname=appu05;host=143.116.140.161;application_name=TraxMon';
        $user = 'seappadmin';
        $pass = 's3n3mm0nd0mm3G!kuk@c';
        $dataquery = 'WITH optrace as (
                    select * from "Fuji_Material_Replica"."OPERATORTRACE"
                    WHERE "ACTIONID" = 1012 AND "TIMESTAMP" between $$2020-12-15 06:00$$ AND $$2020-12-16 18:00$$ AND "RESULT" = $$OK$$
                    ), ii as (
                    select * from optrace
                    left join "RFID"."RFCLIENT_OperatorTrace" ON "RFID"."RFCLIENT_OperatorTrace".targetmc = optrace."TARGETMC"
                    AND "RFID"."RFCLIENT_OperatorTrace".target = optrace."OLDDID"
                    AND "RFID"."RFCLIENT_OperatorTrace".source = optrace."DID"
                    ), coll as (
                    select "TARGETMC", "TIMESTAMP", (t1.id is not null) as tisrf, (t2.id is not null) as sisrf, (reader is not null) as isrf from ii
                    left join "RFID"."RF_Tag" t1 ON t1.did_fkey = ii."DID"
                    left join "RFID"."RF_Tag" t2 ON t2.did_fkey = ii."OLDDID"
                    ), rfwasused as (
                    select "TARGETMC", count("TARGETMC") as rfwasused
                            FROM coll WHERE isrf IS TRUE
                    GROUP BY "TARGETMC"
                    ), rfmissed as (
                    select "TARGETMC", count("TARGETMC") as rfmissed
                            FROM coll WHERE isrf IS FALSE AND tisrf IS TRUE AND sisrf IS TRUE
                    GROUP BY "TARGETMC"
                    ), total as (
                    select "TARGETMC", count("TARGETMC") as total
                            FROM optrace
                    GROUP BY "TARGETMC"
                    )
                    select "MACHINENAME", total, rfwasused, rfmissed  from "Fuji_Material_Replica"."MACHINENAMES"
                    LEFT JOIN total ON total."TARGETMC" = "Fuji_Material_Replica"."MACHINENAMES"."MACHINENAME"
                    LEFT JOIN rfwasused ON rfwasused."TARGETMC" = "Fuji_Material_Replica"."MACHINENAMES"."MACHINENAME"
                    LEFT JOIN rfmissed ON rfmissed."TARGETMC" = "Fuji_Material_Replica"."MACHINENAMES"."MACHINENAME"
                    ORDER BY "MACHINENAME"';
        $subject = 'RFID usages';
        //címlista, futási intervallum, hanyadikperceanapnak, adattablaelerese, felhasznalo, jelszo, queryazadatbazisban
        $a = new alibimail();
        $a->go($email_list_name, $frequency, $minutesofday, $connstring, $user, $pass, $dataquery, $subject);

        class alibimail {

            protected $connstring;
            protected $user;
            protected $pass;
            protected $dataquery;
            protected $subject;
            protected $colnames;

            public function __construct() {
                //ne fussunk csak ha idő van
                //$this->go($email_list_name, $frequency, $minutesofday, $connstring, $user, $pass, $dataquery);
            }

            function go($email_list_name, $frequency, $minutesofday, $connstring, $user, $pass, $dataquery, $subject) {
                //ne fussunk csak ha idő van
                if (($minutesofday % $frequency) != 0) {

                    return;
                }

                $this->connstring = $connstring;
                $this->user = $user;
                $this->pass = $pass;
                $this->dataquery = $dataquery;
                $this->subject = $subject;
                $this->sendMail($this->formatMail(), $this->getAddresses($email_list_name));
            }

            function getAddresses($email_list_name) {
                // appu05/TriggerTracker/EmailList   EmailListMembers 
                $Query = 'select email from "StopTrigger"."EmailList" left join "StopTrigger"."EmailListMembers" on "StopTrigger"."EmailListMembers".email_list_id = "StopTrigger"."EmailList".id where "StopTrigger"."EmailList".list_name = \'' . $email_list_name . '\'';
                $appu05_pgsql = new PDO("pgsql:dbname=appu05;host=143.116.140.161;application_name=TraxMon", 'seappadmin', 's3n3mm0nd0mm3G!kuk@c');
                $appu05_pgsql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $result = $appu05_pgsql->query($Query)->fetchALL(PDO::FETCH_ASSOC);
                return $result;
            }

            function getData() {
                $appu05_pgsql = new PDO($this->connstring, $this->user, $this->pass);
                $appu05_pgsql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $result = $appu05_pgsql->query($this->dataquery);
                //column nevek kitalalsa
                $this->colnames = array_keys($appu05_pgsql->query($this->dataquery)->fetch(PDO::FETCH_ASSOC));
                $result = $appu05_pgsql->query($this->dataquery)->fetchALL(PDO::FETCH_OBJ);
                return $result;
            }

            function formatMail() {
                //nyers adat az adatbázisból
                $result = $this->getData($this->connstring, $this->user, $this->pass, $this->dataquery);
                //az adatok html formázása ha van eredményünk
                if (sizeof($result) > 0) {

                    //nyitunk egy táblát 
                    $string = "<html><body><table border='1'>";
                    //tabla head
                    $string .= "<thead><tr>";
                    foreach ($this->colnames as $colname) {

                        $string .= "<th>" . $colname . "</th>";
                    }
                    $string .= "</tr></thead>";

                    foreach ($result as $row) {
                        //html tabla nyitasa
                        $string .= "<tr>";

                        foreach ($row as $data) {
                            //cella

                            $string .= "<td>" . $data . "</td>";
                        }
                        //sorzár
                        $string .= "</tr>";
                    }
                    //minden zár
                    $string .= "</table></body></html>";
                }

                return $string;
            }

            function sendMail($body, $addresses) {
                require_once 'class.SMTP.php';
                require_once 'class.phpmailer.php';

                $mail = new phpmailer();
                $mail->isSMTP();
                $mail->CharSet = 'UTF-8';
                $mail->SMTPAuth = false;
                $mail->Port = 25;
                $mail->Host = "mailhub.sanmina.com";
                $mail->From = 'reporter@sanmina.com';
                $mail->FromName = 'reporter';
                foreach ($addresses as $email) {
                    $cim = $email['email'];
                    $mail->addAddress($cim, $cim);
                }

                $mail->isHTML(true);
                $mail->Subject = $this->subject . " " . date("D M d, Y G:i");
                $mail->Body = $body;

                $mail->smtpConnect([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ]);

                $mail->send();
            }

        }

        function getMinutesOfDay() {

            $hour = date("H");
            $minutes = date("i");
            //+360 , hogy a reggel 6 hoz igazodjunk
            $minutesofday = $hour * 60 + $minutes + 360;
            return $minutesofday;
        }
        ?>
    </body>
</html>
