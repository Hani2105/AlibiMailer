<?php

function sendMail($body, $addresses, $subject) {
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
    $mail->Subject = $subject;
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

function getMinutesOfDay() {

    $hour = date("H");
    $minutes = date("i");
    //+360 , hogy a reggel 6 hoz igazodjunk
    $minutesofday = $hour * 60 + $minutes + 360;
    return $minutesofday;
}

function getPrevShift($szakhossz) {
    //ki kell szedni az elozo muszak kezdo es veg datumat majd visszaadni egy tombben annak megfeleloen, hogy 12 vagy 8 oras a szak
    if ($szakhossz == 12) {
        //ha reggelben vagyunk
        if (date("H") > 5 && date("H") < 18) {
            //akkor az előző szak az előző nap 18-06 lesz
            $datefrom = date('Y-m-d', strtotime("-1 days")) . " 18:00";
            $dateto = date('Y-m-d') . " 05:59";
            $dates = [
                1 => $datefrom,
                2 => $dateto,
            ];
            return $dates;
            //delutanosban vagyunk még aznap
        } else if (date("H") > 17) {
            $datefrom = date('Y-m-d') . " 06:00";
            $dateto = date('Y-m-d') . " 17:59";
            $dates = [
                1 => $datefrom,
                2 => $dateto,
            ];
            return $dates;
        }
        //a már másnap vagyunk éjfél után azaz minden más eset
        else {
            $datefrom = date('Y-m-d', strtotime("-1 days")) . " 06:00";
            $dateto = date('Y-m-d', strtotime("-1 days")) . " 17:50";
            $dates = [
                1 => $datefrom,
                2 => $dateto,
            ];
            return $dates;
        }
    }
}
?>

