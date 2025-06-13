<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Generate a random verification code
        $verification_code = rand(100000, 999999);
        
        // Store the verification code in the session
        $_SESSION['verification_code'] = $verification_code;
        $_SESSION['email'] = $email;

        // Send the verification code to the email
        require 'phpmailer/src/PHPMailer.php';
        require 'phpmailer/src/Exception.php';
        require 'phpmailer/src/SMTP.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'relapsethefuture@gmail.com';
            $mail->Password = 'qrztvndecsawnmqn';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('relapsethefuture@gmail.com', 'Time Capsule');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = "Your Verification Code for Time Capsule";
            $mail->Body = "Your verification code is: <b>$verification_code</b>";

            if ($mail->send()) {
                echo "Verification code has been sent to your email.";
            } else {
                echo "Failed to send verification code.";
            }
        } catch (Exception $e) {
            echo "Mailer Error: " . $mail->ErrorInfo;
        }
    } else {
        echo "Invalid email address.";
    }
}
?>
