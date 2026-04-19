<?php
include("config/db.php");

// 1. Get the ID from the URL
if (!isset($_GET['id'])) {
    die("Invalid Request: No ID provided.");
}

$opp_id = mysqli_real_escape_with_htmlspecialchars($conn, $_GET['id']);

// 2. Fetch details of the opportunity (to show the title to the student)
$query = "SELECT * FROM opportunities WHERE id = '$opp_id' LIMIT 1";
$result = mysqli_query($conn, $query);
$opp = mysqli_fetch_assoc($result);

if (!$opp) {
    die("Opportunity not found.");
}

// 3. Handle Form Submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['student_name']);
    $roll = mysqli_real_escape_string($conn, $_POST['student_roll']);
    $email = mysqli_real_escape_string($conn, $_POST['student_email']);
    $phone = mysqli_real_escape_string($conn, $_POST['student_phone']);

    $sql = "INSERT INTO applications (opportunity_id, student_name, student_roll, student_email, student_phone) 
            VALUES ('$opp_id', '$name', '$roll', '$email', '$phone')";

    if (mysqli_query($conn, $sql)) {
        $message = "<div class='success'>Application Submitted Successfully!</div>";
    } else {
        $message = "<div class='error'>Error: " . mysqli_error($conn) . "</div>";
    }
}

// Helper function to clean inputs
function mysqli_real_escape_with_htmlspecialchars($conn, $str) {
    return mysqli_real_escape_string($conn, htmlspecialchars($str));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Apply | <?php echo $opp['title']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body {
            background: #f1f5f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .apply-card {
            background: white;
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 500px;
        }
        .badge {
            background: #eef2ff;
            color: #4f46e5;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        h1 { font-size: 24px; margin: 15px 0 5px; color: #1e293b; }
        p.org { color: #64748b; margin-bottom: 30px; font-size: 14px; }
        
        label { display: block; margin-bottom: 8px; font-size: 14px; font-weight: 600; color: #334155; }
        input {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            outline: none;
            transition: 0.2s;
        }
        input:focus { border-color: #4f46e5; box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); }
        
        button {
            width: 100%;
            padding: 14px;
            background: #4f46e5;
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
        }
        button:hover { background: #4338ca; }
        
        .success { background: #dcfce7; color: #15803d; padding: 15px; border-radius: 12px; margin-bottom: 20px; text-align: center; font-weight: 600; }
        .error { background: #fee2e2; color: #b91c1c; padding: 15px; border-radius: 12px; margin-bottom: 20px; text-align: center; font-weight: 600; }
    </style>
</head>
<body>

<div class="apply-card">
    <span class="badge"><?php echo $opp['type']; ?></span>
    <h1><?php echo htmlspecialchars($opp['title']); ?></h1>
    <p class="org"><?php echo htmlspecialchars($opp['organization']); ?></p>

    <?php echo $message; ?>

    <form method="POST">
        <label>Full Name</label>
        <input type="text" name="student_name" placeholder="Enter your full name" required>

        <label>Register / Roll Number</label>
        <input type="text" name="student_roll" placeholder="Enter your roll number" required>

        <label>Email Address</label>
        <input type="email" name="student_email" placeholder="example@college.com" required>

        <label>Phone Number</label>
        <input type="text" name="student_phone" placeholder="+91 00000 00000" required>

        <button type="submit">Submit Application</button>
    </form>
</div>

</body>
</html>