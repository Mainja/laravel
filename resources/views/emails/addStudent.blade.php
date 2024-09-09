<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Your Login Details</title>
</head>
<body>
    <h2>Plains of Mamre International College</h2>
    <p>Hello {{ $name }}</p>
    <p>
        Your student portal has been created and below are your login credentials. Please login and change your password!
    </p>
    <div>
        <p>Username: {{ $computer_number }}</p>
        <p>Password: {{ $computer_number }}</p>
    </div>

    <p>Click <a href="{{ $url }}" target="_blank" rel="noopener noreferrer">here</a> to login</p>
</body>
</html>