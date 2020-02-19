<?php
set_time_limit(300);
// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//include the database
include 'functions.php';

$datamail = $testuser->select('usersmail', ['id', 'name', 'email', 'timefrom', 'timeto', 'callfrom', 'callto', 'callduration', 'talkduration', 'status', 'drunk', 'communicationtype', 'pincode', 'sented']);

foreach ($datamail as $mailrow) {

  $timefrom = getTimeFrom($mailrow['timefrom']);
  $timeto = getTimeTo($mailrow['timefrom'], $mailrow['timeto']);

  $timetosec = strtotime($timefrom);
  if($timetosec <= strtotime('now') && strtotime($mailrow['sented']) < strtotime(date('Y-m-d'))) {

    $selector = getWhere($timefrom, 
                        $timeto, 
                        $mailrow['callfrom'], 
                        $mailrow['callto'], 
                        $mailrow['callduration'], 
                        $mailrow['talkduration'], 
                        $mailrow['status'], 
                        $mailrow['drunk'], 
                        $mailrow['communicationtype'], 
                        $mailrow['pincode']);

    $datacdr = $database->select("cdr", ["datetime","clid","extfield2","dst","duration","billable","disposition"], $selector);
    

    $csvFileName = str_replace(':', '', 'Report_'.$mailrow['name'] . '_' . $timeto . '.csv');
    $fp = fopen($csvFileName, 'w');

    $csvdata = ['Time', 'Call From', 'Name', 'Call To', 'Call Duration(s)', 'Talk Duration(s)', 'Status'];
    fputcsv($fp, $csvdata, ',', "\t");

      $indexsent = 0;
      // output data of each row
      foreach ($datacdr as $cdrrow) {
        fputcsv($fp, $cdrrow, ",", "\t");
        $indexsent = 1;
      }
      
    if ($indexsent) {
      // Instantiation and passing `true` enables exceptions
      $mail = new PHPMailer(true);

      try {
        //Server settings
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
        $mail->isSMTP();                                            // Send using SMTP
        $mail->Host       = 'smtp.gmail.com';                     // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        $mail->Username   = 'smtpmailname@gmail.com';              // SMTP username
        $mail->Password   = 'xxxx';                            // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` also accepted
        $mail->Port       = 587;                                    // TCP port to connect to

        //Recipients
        $mail->setFrom('smtpmailname@gmail.com', 'User name');
        $mail->addAddress($mailrow['email']);               // Name is optional

        // Attachments
        $mail->addAttachment('./'.$csvFileName);    // Optional name

        // Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = 'Report "'.$mailrow['name'] . '" at ' . $timeto;
		
        $mail->Body    = 'This report cover the pbx history from ' . $timefrom . ' to ' . $timeto;
        // $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

        $mail->send();
        echo 'Message has been sent';

        
        $testuser->update("usersmail", [
          "sented" => date('Y-m-d')
        ], [
          "id" => $mailrow['id']
        ]);
        
      } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
      }

      
    } else {
      echo "don't exist!";
    }

    @fclose($fp);
    @unlink($csvFileName);
  }

}
// echo json_encode($data);
