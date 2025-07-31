s<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8d7da;
            color: #721c24;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .error-container {
            text-align: center;
            background-color: #f5c6cb;
            padding: 20px;
            border: 1px solid #f5c2c7;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .error-container h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .error-container p {
            font-size: 1.2rem;
            margin-bottom: 20px;
        }
        .error-container a {
            text-decoration: none;
            color: #721c24;
            font-weight: bold;
            border: 1px solid #721c24;
            padding: 10px 15px;
            border-radius: 5px;
            background-color: #f8d7da;
            transition: background-color 0.3s, color 0.3s;
        }
        .error-container a:hover {
            background-color: #721c24;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>Error</h1>
        <p>
            <?php
            // Display the error message passed via the query string
            if (isset($_GET['error'])) {
                echo htmlspecialchars($_GET['error']);
            } else {
                echo "An unknown error occurred.";
            }
            ?>
        </p>
        <a href="studentLogin.php">Go Back to Login</a>
    </div>
</body>
</html>
