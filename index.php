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
        require_once 'class.alibimail.php';
        require_once 'class.function.php';
        //adatbazis connectorok
// $datumot itt szeddle es parameterkent add at;
        $minutesofday = getMinutesOfDay();
        $dates = getPrevShift(12);
//--------------------------------------------------------------
        //feederes
        $email_list_name = 'rftag_feeder_count';
        $frequency = 30;
        $minutesofday = getMinutesOfDay();
        $connstring = 'pgsql:dbname=appu05;host=143.116.140.161;application_name=TraxMon';
        $user = 'seappadmin';
        $pass = 's3n3mm0nd0mm3G!kuk@c';
        $dataquery = 'select count(id) FROM "RFID"."RF_Tag" where type = 2 AND comment = \'RFTOOLSV2\'';
        $subject = 'RFID feederek ' . date("Y-m-d H:i");
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
                    WHERE "ACTIONID" = 1012 AND "TIMESTAMP" between \'' . $dates[1] . '\' AND \'' . $dates[2] . '\' AND "RESULT" = $$OK$$
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
        $subject = 'RFID usages (' . $dates[1] . " - " . $dates[2] . ")";
        //címlista, futási intervallum, hanyadikperceanapnak, adattablaelerese, felhasznalo, jelszo, queryazadatbazisban
        $a = new alibimail();
        $a->go($email_list_name, $frequency, $minutesofday, $connstring, $user, $pass, $dataquery, $subject);


        ?>
    </body>
</html>
