<?php
session_start();
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/SMTP.php';

// Database connection
$host = 'localhost';
$db = 'time_capsule';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $message = htmlspecialchars($_POST['message']);
    $future_datetime = $_POST['future-datetime'];
    $verify_code = $_POST['verify'];

    // Check if the verification code matches the one stored in the session
    if ($verify_code != $_SESSION['verification_code']) {
        echo "Invalid verification code.";
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format";
        exit;
    }

    // Handle file upload
    $attachment_path = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/';
    $original_file_name = basename($_FILES['attachment']['name']);
    $file_name = uniqid() . '_' . $original_file_name; // Generate a unique file name
    $target_file = $upload_dir . $file_name;

    // Ensure the upload directory exists
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Move the uploaded file to the target directory
    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_file)) {
        $attachment_path = $target_file;
    } else {
        echo "Failed to upload the file.";
        exit;
    }
}


    // Insert the message and attachment path into the database
    $stmt = $conn->prepare("INSERT INTO time_capsules (email, message, send_datetime, attachment_path) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $email, $message, $future_datetime, $attachment_path);
    if ($stmt->execute()) {
        echo "Your message has been stored and will be sent on: " . $future_datetime;
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Function to send messages when due
function sendMessages($conn) {
    $sql = "SELECT * FROM time_capsules WHERE send_datetime <= NOW()";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $email = $row['email'];
            $subject = "Your Message from the Past";
            $message = $row['message'];
            $attachment_path = $row['attachment_path'];

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
                $mail->Subject = $subject;
                $mail->Body = nl2br($message);

                // Attach file if present
                if (!empty($attachment_path)) {
                    $mail->addAttachment($attachment_path);
                }

                if ($mail->send()) {
                    $delete_sql = "DELETE FROM time_capsules WHERE id = ?";
                    $stmt = $conn->prepare($delete_sql);
                    $stmt->bind_param("i", $row['id']);
                    $stmt->execute();
                    $stmt->close();
                }
            } catch (Exception $e) {
                echo "Mailer Error: " . $mail->ErrorInfo;
            }
        }
    }
}

// Call the sendMessages function to check and send messages
sendMessages($conn);

$conn->close();
?>
