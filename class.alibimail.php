<?php

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
        sendMail($this->formatMail(), $this->getAddresses($email_list_name), $subject);
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

}
?>

